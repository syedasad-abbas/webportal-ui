const db = require('../db');

const normalizeDid = (value) => String(value || '').replace(/\D/g, '');

const findActiveDid = async (value) => {
  const did = normalizeDid(value);
  if (!did) {
    return null;
  }

  const result = await db.query(
    `SELECT d.id, d.did, d.label, d.carrier_id, c.name AS carrier_name
       FROM inbound_dids d
       JOIN carriers c ON c.id = d.carrier_id
      WHERE d.did = $1
        AND d.is_active = TRUE
      LIMIT 1`,
    [did]
  );

  return result.rows[0] || null;
};

module.exports = {
  normalizeDid,
  findActiveDid
};
