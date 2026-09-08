const db = require('../db');

const getAssignedPermissions = async (userId) => {
  const result = await db.query(
        `SELECT DISTINCT p.name
           FROM permissions p
          WHERE p.guard_name = 'web'
            AND (EXISTS (
              SELECT 1 FROM model_has_permissions mp
               WHERE mp.permission_id = p.id AND mp.model_id = $1 AND mp.model_type = $2
            ) OR EXISTS (
              SELECT 1 FROM role_has_permissions rp
              JOIN model_has_roles mr ON mr.role_id = rp.role_id
              JOIN roles r ON r.id = mr.role_id AND r.guard_name = 'web'
               WHERE rp.permission_id = p.id AND mr.model_id = $1 AND mr.model_type = $2
            ))`,
        [userId, 'App\\Models\\User']
      );
  return result.rows.map((row) => row.name);
};

module.exports = { getAssignedPermissions };
