const { test } = require('node:test');
const assert = require('node:assert/strict');
const {getActiveChannelIds} = require('../src/services/activeChannelService');
const id = '12345678-1234-1234-1234-123456789abc';
test('empty FreeSWITCH snapshot excludes all stale database calls', async () => {
    assert.deepEqual(await getActiveChannelIds(async () => '{"row_count":0}'), []);
});
test('returns only channels currently present in FreeSWITCH', async () => {
    assert.deepEqual(await getActiveChannelIds(async () => JSON.stringify({row_count:1, rows:[{uuid:id}]})), [id]);
});
test('rejects errors and incomplete snapshots instead of asserting no calls', async () => {
    for (const value of ['-ERR unavailable', '{}', '{"row_count":1,"rows":[]}', '{"row_count":1,"rows":[{"uuid":"bad"}]}']) {
        await assert.rejects(getActiveChannelIds(async () => value));
    }
    await assert.rejects(getActiveChannelIds(async () => { throw Error('timeout'); }));
});
