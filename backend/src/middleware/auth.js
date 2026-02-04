const jwt = require('jsonwebtoken');
const config = require('../config');

const permissionAliases = {
  'dialer.create_call': ['dial']
};

const hasPermission = (granted, permission) => {
  if (granted.includes(permission)) {
    return true;
  }
  const aliases = permissionAliases[permission] || [];
  return aliases.some((alias) => granted.includes(alias));
};

const authenticate = (roles = []) => {
  const allowedRoles = Array.isArray(roles) ? roles : [roles];
  return (req, res, next) => {
    const header = req.headers.authorization || '';
    const token = header.replace('Bearer ', '');
    if (!token) {
      return res.status(401).json({ message: 'Missing token' });
    }

    try {
      const decoded = jwt.verify(token, config.jwtSecret);
      if (allowedRoles.length && !allowedRoles.includes(decoded.role)) {
        return res.status(403).json({ message: 'Forbidden' });
      }
      req.user = decoded;
      return next();
    } catch (err) {
      return res.status(401).json({ message: 'Invalid token' });
    }
  };
};

const requirePermissions = (required = []) => {
  const requiredList = Array.isArray(required) ? required : [required];
  return (req, res, next) => {
    if (!requiredList.length) {
      return next();
    }
    const role = req.user?.role;
    if (role === 'superadmin' || role === 'admin') {
      return next();
    }
    const granted = Array.isArray(req.user?.permissions) ? req.user.permissions : [];
    const hasAll = requiredList.every((permission) => hasPermission(granted, permission));
    if (!hasAll) {
      return res.status(403).json({ message: 'Forbidden' });
    }
    return next();
  };
};

module.exports = {
  authenticate,
  requirePermissions
};
