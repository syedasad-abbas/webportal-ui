const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function load(relative, mocks) {
  const filename = path.resolve(__dirname, '../src', relative);
  const module = { exports: {} };
  vm.runInNewContext(fs.readFileSync(filename, 'utf8'), {
    module, exports: module.exports, console: { warn() {}, log() {} }, process, URL,
    require(name) { return name in mocks ? mocks[name] : require(name); }
  }, { filename });
  return module.exports;
}

function fixture() {
  let granted = [];
  let fail = false;
  const auth = load('middleware/auth.js', {
    jsonwebtoken: {}, '../config': {}, '../db': {},
    '../services/metricsService': {},
    '../services/permissionService': { getAssignedPermissions: async () => {
      if (fail) throw new Error('unavailable');
      return [...granted];
    } }
  });
  return {
    grant(value) { granted = value; },
    fail() { fail = true; },
    async check(required, role, claims = []) {
      const req = { user: { id: 1, role, permissions: claims } };
      const res = { code: 200, status(code) { this.code = code; return this; }, json() {} };
      let allowed = false;
      await auth.requirePermissions(required)(req, res, () => { allowed = true; });
      return { allowed, status: res.code };
    }
  };
}

test('all permission names deny, grant, and revoke equally for every role', async () => {
  const source = fs.readFileSync(path.resolve(__dirname, '../../laravel/app/Services/PermissionService.php'), 'utf8');
  const permissions = [...new Set([...source.matchAll(/'([a-z_]+\.[a-z_]+)'/g)].map((m) => m[1]))];
  assert.ok(permissions.length > 50);
  const f = fixture();
  for (const role of ['admin', 'superadmin', 'Agent', 'support', 'custom']) {
    for (const permission of permissions) {
      f.grant([]);
      assert.equal((await f.check(permission, role, [permission])).status, 403);
      f.grant([permission]);
      assert.equal((await f.check(permission, role)).allowed, true);
      f.grant([]);
      assert.equal((await f.check(permission, role, [permission])).allowed, false);
    }
  }
});

test('required permissions are conjunctive and dial alias remains supported', async () => {
  const f = fixture();
  f.grant(['contacts.view']);
  assert.equal((await f.check(['contacts.view', 'contacts.edit'], 'admin')).status, 403);
  f.grant(['dialer.create_call']);
  assert.equal((await f.check('dial', 'custom')).allowed, true);
  assert.equal((await f.check('campaign.play', 'admin')).status, 403);
});

test('database failures cannot grant access', async () => {
  const f = fixture();
  f.fail();
  assert.equal((await f.check('carrier.view', 'superadmin', ['carrier.view'])).status, 503);
});

test('live events use authenticated identity and recheck revoked permissions', async () => {
  let server;
  let granted = ['dashboard.view', 'dialer.create_call'];
  class Server {
    constructor() { server = this; this.sockets = { sockets: new Map() }; }
    use(fn) { this.guard = fn; }
    on(name, fn) { this.connection = fn; }
  }
  const socketModule = load('socket.js', {
    'socket.io': { Server }, './config': { jwtSecret: 'test' },
    jsonwebtoken: { verify(token) { if (token !== 'valid') throw Error(); return { id: 7 }; } },
    './services/permissionService': { getAssignedPermissions: async () => granted },
    fs: { existsSync: () => false }, os: { networkInterfaces: () => ({}) }
  });
  socketModule.initSocket({});
  const events = [];
  const socket = { handshake: { auth: { token: 'valid', userId: 99 } }, data: {}, join() {}, emit(event) { events.push(event); } };
  server.guard(socket, (error) => assert.equal(error, undefined));
  assert.equal(socket.data.userId, 7);
  server.sockets.sockets.set('one', socket);
  await socketModule.emitSocketEvent('dashboard.metrics', {});
  await socketModule.emitToUser(99, 'incoming.call', {});
  assert.deepEqual(events, ['dashboard.metrics']);
  await socketModule.emitToUser(7, 'incoming.call', {});
  assert.equal(events.length, 2);
  granted = [];
  await socketModule.emitSocketEvent('dashboard.metrics', {});
  await socketModule.emitToUser(7, 'incoming.call', {});
  assert.equal(events.length, 2);
  socket.handshake.auth.token = '';
  server.guard(socket, (error) => assert.ok(error));
});
