const fs = require('fs/promises');
const path = require('path');
const config = require('../config');
const db = require('../db');
const freeswitch = require('./freeswitch');

const directoryPath = config.freeswitch.directoryConfigPath;

const escapeXml = (value) =>
  value
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

const safeFileName = (username) => username.replace(/[^a-zA-Z0-9_.-]/g, '_');

const buildUserXml = ({ username, password }) => `<include>
  <user id="${escapeXml(username)}">
    <params>
      <param name="password" value="${escapeXml(password)}"/>
    </params>
    <variables>
      <variable name="user_context" value="default"/>
      <variable name="effective_caller_id_name" value="${escapeXml(username)}"/>
      <variable name="effective_caller_id_number" value="${escapeXml(username)}"/>
    </variables>
  </user>
</include>
`;

const writeSipUser = async ({ username, password }) => {
  if (!directoryPath || !username || !password) return false;
  await fs.mkdir(directoryPath, { recursive: true });
  const filePath = path.join(directoryPath, `${safeFileName(username)}.xml`);
  await fs.writeFile(filePath, buildUserXml({ username, password }), 'utf8');
  return true;
};

const reloadDirectory = async () => {
  try {
    await freeswitch.reloadXml();
  } catch (err) {
    console.warn('[sip-directory] reload deferred:', err.message);
  }
};

const syncSipUser = async ({ username, password }) => {
  const written = await writeSipUser({ username, password });
  if (written) await reloadDirectory();
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
  console.log(`[sip-directory] synchronized ${result.rowCount} user(s)`);
};

module.exports = { syncSipUser, syncAllSipUsers };
