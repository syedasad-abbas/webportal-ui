const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const box = { module: { exports: {} }, require: () => ({}), Map, Date };
vm.runInNewContext(fs.readFileSync(__dirname + '/../src/lib/callOutcomes.js', 'utf8'), box);
const { recordHangup } = box.module.exports;
const uuid = '12345678-1234-1234-1234-123456789abc';
test('hangup records inbound and outbound end without controlling channels', async () => {
    for (const direction of ['inbound', 'outbound']) {
        let captured;
        await recordHangup({ 'Event-Name': 'CHANNEL_HANGUP_COMPLETE', 'Unique-ID': uuid, 'Call-Direction': direction }, async (...args) => { captured = args; });
        assert.match(captured[0], /ended_at = NOW\(\)/);
        assert.match(captured[0], /ended_at IS NULL/);
        assert.equal(captured[1][0], uuid);
    }
});
test('ignores non-hangup events and invalid channel IDs', async () => {
    let calls = 0;
    const query = async () => calls++;
    await recordHangup({ 'Event-Name': 'CHANNEL_ANSWER', 'Unique-ID': uuid }, query);
    await recordHangup({ 'Event-Name': 'CHANNEL_HANGUP_COMPLETE', 'Unique-ID': 'bad' }, query);
    assert.equal(calls, 0);
});
