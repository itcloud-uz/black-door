const db = require('../db');

exports.getUsers = async (req, res) => {
  try {
    const result = await db.query(
      "SELECT id, email, full_name, role, telegram_id, is_telegram_verified, is_email_verified, created_at, last_login FROM users ORDER BY created_at DESC"
    );
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error fetching users:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createUser = async (req, res) => {
  const { email, full_name, role, telegram_id } = req.body;

  if (!email || !full_name || !role) {
    return res.status(400).json({ error: 'Email, Foydalanuvchi ismi va roli talab qilinadi' });
  }

  try {
    // Check if email already exists
    const checkEmail = await db.query("SELECT * FROM users WHERE email = $1", [email]);
    if (checkEmail.rows.length > 0) {
      return res.status(400).json({ error: 'Bu email orqali ro\'yxatdan o\'tilgan' });
    }

    const isTelegramVerified = !!telegram_id;
    const result = await db.query(
      "INSERT INTO users (email, full_name, role, telegram_id, is_telegram_verified, is_email_verified) VALUES ($1, $2, $3, $4, $5, true) RETURNING *",
      [email, full_name, role, telegram_id || null, isTelegramVerified]
    );

    const newUser = result.rows[0];

    // Audit log
    await db.query(
      "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1::uuid, 'Created', 'User', $2::varchar, $3, $4)",
      [req.user.id, newUser.id, JSON.stringify({ email: newUser.email, role: newUser.role }), req.ip]
    );

    return res.status(201).json(newUser);
  } catch (err) {
    console.error("Error creating user:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateUser = async (req, res) => {
  const { id } = req.params;
  const { email, full_name, role, telegram_id, is_telegram_verified } = req.body;

  if (!email || !full_name || !role) {
    return res.status(400).json({ error: 'Email, Foydalanuvchi ismi va roli talab qilinadi' });
  }

  try {
    // Check if user exists
    const checkUser = await db.query("SELECT * FROM users WHERE id = $1", [id]);
    if (checkUser.rows.length === 0) {
      return res.status(404).json({ error: 'Foydalanuvchi topilmadi' });
    }

    // Check if email is taken by another user
    const checkEmail = await db.query("SELECT * FROM users WHERE email = $1 AND id != $2", [email, id]);
    if (checkEmail.rows.length > 0) {
      return res.status(400).json({ error: 'Bu email boshqa foydalanuvchiga tegishli' });
    }

    const result = await db.query(
      "UPDATE users SET email = $1, full_name = $2, role = $3, telegram_id = $4, is_telegram_verified = $5, updated_at = NOW() WHERE id = $6 RETURNING *",
      [email, full_name, role, telegram_id || null, is_telegram_verified === true || is_telegram_verified === 'true', id]
    );

    const updatedUser = result.rows[0];

    // Audit log
    await db.query(
      "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1::uuid, 'Updated', 'User', $2::varchar, $3, $4)",
      [req.user.id, updatedUser.id, JSON.stringify({ email: updatedUser.email, role: updatedUser.role }), req.ip]
    );

    return res.status(200).json(updatedUser);
  } catch (err) {
    console.error("Error updating user:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteUser = async (req, res) => {
  const { id } = req.params;

  if (id === req.user.id) {
    return res.status(400).json({ error: 'O\'z akkauntingizni o\'chira olmaysiz' });
  }

  try {
    const checkUser = await db.query("SELECT * FROM users WHERE id = $1", [id]);
    if (checkUser.rows.length === 0) {
      return res.status(404).json({ error: 'Foydalanuvchi topilmadi' });
    }

    await db.query("DELETE FROM users WHERE id = $1", [id]);

    // Audit log
    await db.query(
      "INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address) VALUES ($1::uuid, 'Deleted', 'User', $2::varchar, $3)",
      [req.user.id, id, req.ip]
    );

    return res.status(200).json({ message: 'Foydalanuvchi o\'chirildi' });
  } catch (err) {
    console.error("Error deleting user:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
