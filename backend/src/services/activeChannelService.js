const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
async function getActiveChannelIds(snapshot = () => require('../lib/freeswitch').getChannelSnapshot()) {
    const data = JSON.parse(await snapshot());
    // Invalid/error responses must never be treated as a successful empty list.
    if (!Number.isInteger(data?.row_count) || data.row_count < 0 ||
        (data.row_count > 0 && !Array.isArray(data.rows))) throw Error('Invalid channel snapshot');
    const rows = data.rows || [];
    if (rows.length !== data.row_count || rows.some(row => !uuid.test(row?.uuid || ''))) {
        throw Error('Incomplete channel snapshot');
    }
    return [...new Set(rows.map(row => row.uuid))];
}
module.exports = { getActiveChannelIds };
