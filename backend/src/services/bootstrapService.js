const bcrypt = require('bcryptjs');
const db = require('../db');
const config = require('../config');

const ensureSchemaUpgrades = async () => {
  await db.query(
    'ALTER TABLE carriers ADD COLUMN IF NOT EXISTS caller_id_required BOOLEAN NOT NULL DEFAULT TRUE'
  );
  await db.query('ALTER TABLE call_logs ALTER COLUMN caller_id DROP NOT NULL');
};

const seedDefaults = async () => {
  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const groupResult = await client.query('SELECT id FROM groups WHERE name = $1', [
      config.defaults.groupName
    ]);
    let groupId = groupResult.rows[0]?.id;

    if (!groupId) {
      const insertGroup = await client.query(
        'INSERT INTO groups (name, permissions) VALUES ($1, $2::jsonb) RETURNING id',
        [config.defaults.groupName, JSON.stringify(config.defaults.groupPermissions)]
      );
      groupId = insertGroup.rows[0].id;
    }

    const carrierResult = await client.query('SELECT id FROM carriers WHERE name = $1', [
      config.defaults.carrierName
    ]);
    let carrierId = carrierResult.rows[0]?.id;

    if (!carrierId) {
      const insertCarrier = await client.query(
        'INSERT INTO carriers (name, default_caller_id, caller_id_required, sip_domain, sip_port, transport) VALUES ($1, $2, $3, $4, $5, $6) RETURNING id',
        [
          config.defaults.carrierName,
          config.defaults.carrierCallerId,
          true,
          config.defaults.carrierDomain,
          config.defaults.carrierPort,
          config.defaults.carrierTransport
        ]
      );
      carrierId = insertCarrier.rows[0].id;
    } else {
      await client.query(
        `UPDATE carriers
            SET sip_domain = COALESCE(sip_domain, $2),
                sip_port = COALESCE(sip_port, $3),
                transport = COALESCE(transport, $4)
          WHERE id = $1`,
        [carrierId, config.defaults.carrierDomain, config.defaults.carrierPort, config.defaults.carrierTransport]
      );
    }

    const passwordHash = await bcrypt.hash(config.defaults.adminPassword, 10);

    const adminByEmail = await client.query(
      'SELECT id FROM users WHERE email = $1 AND role = ANY($2::text[])',
      [config.defaults.adminEmail, ['admin', 'superadmin']]
    );

    if (adminByEmail.rowCount === 0) {
      const existingAdmin = await client.query(
        'SELECT id FROM users WHERE role = ANY($1::text[]) ORDER BY created_at ASC LIMIT 1',
        [['admin', 'superadmin']]
      );

      if (existingAdmin.rowCount === 0) {
        await client.query(
          `INSERT INTO users (full_name, email, password_hash, role, group_id, carrier_id, recording_enabled)
           VALUES ($1, $2, $3, $4, $5, $6, true)`,
          ['Default Admin', config.defaults.adminEmail, passwordHash, config.defaults.adminRole, groupId, carrierId]
        );
      } else {
        await client.query(
          `UPDATE users
             SET email = $1,
                 password_hash = $2,
                 group_id = $3,
                 carrier_id = $4,
                 role = $5,
                 recording_enabled = true
           WHERE id = $6`,
          [
            config.defaults.adminEmail,
            passwordHash,
            groupId,
            carrierId,
            config.defaults.adminRole,
            existingAdmin.rows[0].id
          ]
        );
      }
    } else {
      await client.query(
        `UPDATE users
           SET password_hash = $1,
               group_id = $2,
               carrier_id = $3,
               role = $4,
               recording_enabled = true
         WHERE id = $5`,
        [passwordHash, groupId, carrierId, config.defaults.adminRole, adminByEmail.rows[0].id]
      );
    }

    await client.query('COMMIT');
  } catch (err) {
    await client.query('ROLLBACK');
    throw err;
  } finally {
    client.release();
  }
};

const ensureDefaults = async () => {
  // Always run schema upgrades
  await ensureSchemaUpgrades();

  // Conditionally run default inserts/updates
  const shouldSeed = process.env.BOOTSTRAP_DEFAULTS !== 'false';
  if (!shouldSeed) {
    console.log('BOOTSTRAP_DEFAULTS=false → skipping default group/carrier/admin seeding');
    return;
  }

  await seedDefaults();
};

module.exports = {
  ensureDefaults,
  ensureSchemaUpgrades,
  seedDefaults
};
