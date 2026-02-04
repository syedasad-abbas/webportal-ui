const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const db = require('../db');
const config = require('../config');

const normalizePermissions = (permissions) => {
  if (!permissions) {
    return [];
  }
  if (Array.isArray(permissions)) {
    return permissions;
  }
  if (typeof permissions === 'string') {
    try {
      const parsed = JSON.parse(permissions);
      return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
      return [];
    }
  }
  return [];
};

const generateToken = (user) =>
  jwt.sign(
    {
      id: user.id,
      email: user.email,
      role: user.role,
      groupId: user.group_id,
      carrierId: user.carrier_id,
      permissions: normalizePermissions(user.permissions ?? user.backend_permissions)
    },
    config.jwtSecret,
    { expiresIn: '12h' }
  );

const authenticate = async (email, password, role) => {
  const roles = role ? (Array.isArray(role) ? role : [role]) : [];
  const baseQuery = 'SELECT * FROM users WHERE email = $1';
  const query = roles.length ? `${baseQuery} AND role = ANY($2::text[])` : baseQuery;
  const params = roles.length ? [email, roles] : [email];
  const result = await db.query(query, params);
  if (result.rowCount === 0) {
    throw new Error('Invalid credentials');
  }
  const user = result.rows[0];
  const match = await bcrypt.compare(password, user.password_hash);
  if (!match) {
    throw new Error('Invalid credentials');
  }

  const token = generateToken(user);
  return { token, user };
};

module.exports = {
  authenticate
};
