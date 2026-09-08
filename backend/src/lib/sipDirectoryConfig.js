const fs = require('fs/promises');
const path = require('path');
const crypto = require('crypto');
const config = require('../config');
const db = require('../db');

const directoryPath = config.freeswitch.directoryConfigPath;

const escapeXml = (value) =>
  value
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

const safeFileName = (username) => username.replace(/[^a-zA-Z0-9_.-]/g, '_');

const buildUserXml = ({ username, password }) => {
  const domain = config.freeswitch.directoryDomain;
  if (!domain) throw new Error('FreeSWITCH directory domain is required for SIP digest provisioning');
  // SIP Digest needs HA1 = MD5(username:realm:password), not a hash of the password alone.
  const digest = crypto.createHash('md5').update(`${username}:${domain}:${password}`).digest('hex');
  return `<include>
  <user id="${escapeXml(username)}">
    <params>
      <param name="a1-hash" value="${digest}"/>
    </params>
    <variables>
      <variable name="user_context" value="default"/>
      <variable name="effective_caller_id_name" value="${escapeXml(username)}"/>
      <variable name="effective_caller_id_number" value="${escapeXml(username)}"/>
    </variables>
  </user>
</include>
`;
};

const writeSipUser = async ({ username, password }) => {
  if (!directoryPath || !username || !password) return false;
  await fs.mkdir(directoryPath, { recursive: true });
  const filePath = path.join(directoryPath, `${safeFileName(username)}.xml`);
  await fs.writeFile(filePath, buildUserXml({ username, password }), 'utf8');
  return true;
};

const triggerReload = async () => {
  try {
    // Create a trigger file that the FreeSWITCH container watches for
    const triggerPath = path.join(directoryPath, '.reload_trigger');
    await fs.writeFile(triggerPath, Date.now().toString(), 'utf8');
  } catch (err) {
    console.warn('[sip-directory] trigger creation failed:', err.message);
  }
};

const syncSipUser = async ({ username, password }) => {
  const written = await writeSipUser({ username, password });
  if (written) await triggerReload();
  return written;
};

const syncAllSipUsers = async () => {
  if (!directoryPath) return;
  const result = await db.query(
    `SELECT sip_username, sip_password
       FROM sip_credentials
      WHERE sip_username IS NOT NULL AND sip_username <> ''
        AND sip_password IS NOT NULL AND sip_password <> ''`
  );
  await fs.mkdir(directoryPath, { recursive: true });
  await Promise.all(
    result.rows.map((row) =>
      writeSipUser({ username: row.sip_username, password: row.sip_password })
    )
  );
  await triggerReload();
  console.log(`[sip-directory] synchronized ${result.rowCount} user(s)`);
};

module.exports = { syncSipUser, syncAllSipUsers };
