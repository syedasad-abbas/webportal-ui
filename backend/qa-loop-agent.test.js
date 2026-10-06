'use strict';

const assert = require('assert');
const {
  classifyFailure,
  validateReport,
  shouldRetry,
} = require('./qa-loop-agent');

const productBug = classifyFailure({
  name: 'dash board menu',
  detail: 'status=404 url=http://127.0.0.1:8080/admin/users',
}, {
  apiTrace: [{ status: 404, path: '/admin/users' }],
});
assert.strictEqual(productBug.classification, 'product-bug');

const testBug = classifyFailure({
  name: 'dialog control',
  detail: 'expected visible but target was hidden',
}, { apiTrace: [{ status: 200, path: '/health' }] });
assert.strictEqual(testBug.classification, 'test-bug');

const blocker = classifyFailure({
  name: 'live audio',
  detail: 'Browser audio is not configured for this user',
}, { apiTrace: [{ status: 200, path: '/health' }] });
assert.strictEqual(blocker.classification, 'environment-blocker');

const valid = validateReport({
  passed: [{ name: 'dashboard' }],
  failed: [{ name: 'nav' }],
  blocked: [],
  inventory: [{ name: 'tabs' }],
});
assert.strictEqual(valid.valid, true);
assert.throws(() => validateReport({ passed: 'nope', failed: [] }), /passed/);
assert.strictEqual(shouldRetry({ failed: [{ name: 'nav' }], retries: 1, maxRetries: 3 }), true);
assert.strictEqual(shouldRetry({ failed: [{ name: 'nav' }], retries: 3, maxRetries: 3 }), false);

console.log('qa-loop-agent tests: 10 assertions passed');
