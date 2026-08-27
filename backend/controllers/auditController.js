const db = require('../db');

exports.getAuditLogs = async (req, res) => {
  const { action, entityType, userId } = req.query;
  let queryText = `
    SELECT al.*, u.full_name as user_name, u.email as user_email
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
  `;
  const params = [];
  let index = 1;

  if (action) {
    queryText += ` AND al.action = $${index}`;
    params.push(action);
    index++;
  }
  if (entityType) {
    queryText += ` AND al.entity_type = $${index}`;
    params.push(entityType);
    index++;
  }
  if (userId) {
    queryText += ` AND al.user_id = $${index}`;
    params.push(userId);
    index++;
  }

  queryText += " ORDER BY al.created_at DESC LIMIT 100";

  try {
    const result = await db.query(queryText, params);
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting audit logs:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getEntityHistory = async (req, res) => {
  const { entity_type, entity_id } = req.params;

  try {
    const result = await db.query(
      `SELECT al.*, u.full_name as user_name, u.email as user_email
       FROM audit_log al
       LEFT JOIN users u ON al.user_id = u.id
       WHERE al.entity_type = $1 AND al.entity_id = $2
       ORDER BY al.created_at DESC`,
      [entity_type, entity_id]
    );

    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting entity history:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
