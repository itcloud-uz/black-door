const jwt = require('jsonwebtoken');
const db = require('../db');
const { OAuth2Client } = require('google-auth-library');
const telegramBot = require('../services/telegramBot');
require('dotenv').config();

// Initialize Google client if credentials exist
let googleClient = null;
if (process.env.GOOGLE_CLIENT_ID && process.env.GOOGLE_CLIENT_ID !== 'mock-google-client-id') {
  googleClient = new OAuth2Client(process.env.GOOGLE_CLIENT_ID);
}

// Generate Access Token (1 hour)
const generateAccessToken = (user) => {
  return jwt.sign(
    { id: user.id, email: user.email, role: user.role, fullName: user.full_name },
    process.env.JWT_SECRET,
    { expiresIn: '1h' }
  );
};

// Generate Refresh Token (7 days)
const generateRefreshToken = (user) => {
  return jwt.sign(
    { id: user.id },
    process.env.JWT_REFRESH_SECRET,
    { expiresIn: '7d' }
  );
};

exports.googleLogin = async (req, res) => {
  const { credential } = req.body;

  if (!credential) {
    return res.status(400).json({ error: 'Google credential token is missing' });
  }

  let email, name;

  // 1. Verify Google Token (Mock bypass for testing/development)
  if (credential === 'mock-google-token-admin') {
    email = 'admin@blackdoor.uz';
    name = 'Sardor Abdullayev';
  } else if (credential === 'mock-google-token-employee') {
    email = 'employee@blackdoor.uz';
    name = 'Oybek Aliyev';
  } else if (googleClient) {
    try {
      const ticket = await googleClient.verifyIdToken({
        idToken: credential,
        audience: process.env.GOOGLE_CLIENT_ID,
      });
      const payload = ticket.getPayload();
      email = payload.email;
      name = payload.name;
    } catch (err) {
      console.error("Google token verification error:", err);
      return res.status(401).json({ error: 'Invalid Google credential token' });
    }
  } else {
    // If no real credentials and not a mock token, reject
    return res.status(400).json({ 
      error: 'Google OAuth not configured on server. Use test tokens: "mock-google-token-admin" or "mock-google-token-employee"' 
    });
  }

  try {
    // 2. Check if user exists in DB, if not create them
    let userRes = await db.query("SELECT * FROM users WHERE email = $1", [email]);
    let user;

    if (userRes.rows.length === 0) {
      // Automatic registration (default role is employee unless it's admin@blackdoor.uz)
      const role = email === 'admin@blackdoor.uz' ? 'admin' : 'employee';
      const insertRes = await db.query(
        "INSERT INTO users (email, full_name, role, is_email_verified) VALUES ($1, $2, $3, true) RETURNING *",
        [email, name, role]
      );
      user = insertRes.rows[0];
      
      // Audit log registration
      await db.query(
        "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1, 'Created', 'User', $2, $3, $4)",
        [user.id, user.id, JSON.stringify({ email, role }), req.ip]
      );
    } else {
      user = userRes.rows[0];
    }

    // Update last login
    await db.query("UPDATE users SET last_login = NOW() WHERE id = $1", [user.id]);

    // 3. Return response with intermediate state requiring 2FA
    // We sign a temporary token that only grants permission to verify Telegram 2FA
    const tempToken = jwt.sign(
      { id: user.id, email: user.email, role: user.role, temp: true },
      process.env.JWT_SECRET,
      { expiresIn: '15m' }
    );

    return res.status(200).json({
      message: 'Google login successful. Telegram 2FA required.',
      require2FA: true,
      tempToken,
      user: {
        id: user.id,
        email: user.email,
        fullName: user.full_name,
        role: user.role,
        isTelegramVerified: user.is_telegram_verified
      }
    });

  } catch (err) {
    console.error("Database error during Google Login:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.telegramRequest = async (req, res) => {
  const userId = req.user.id;

  try {
    const userRes = await db.query("SELECT * FROM users WHERE id = $1", [userId]);
    if (userRes.rows.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const user = userRes.rows[0];

    // Check if Telegram ID is added and verified. If not, generate a start link for linking!
    if (!user.telegram_id || !user.is_telegram_verified) {
      const token = require('crypto').randomUUID();
      const expiresAt = new Date(Date.now() + 10 * 60 * 1000); // 10 minutes
      await db.query(
        "INSERT INTO verification_tokens (user_id, token, type, expires_at) VALUES ($1, $2, 'telegram', $3)",
        [user.id, token, expiresAt]
      );
      const botUsername = process.env.TELEGRAM_BOT_USERNAME || 'blackdoor_2fa_bot';
      const startLink = `https://t.me/${botUsername}?start=${token}`;
      return res.status(200).json({
        message: 'Telegram 2FA faollashtirilmagan. Ulanish uchun quyidagi botni bosing.',
        requireSetup: true,
        botUsername,
        startLink
      });
    }

    const token = require('crypto').randomUUID();
    const code = Math.floor(100000 + Math.random() * 900000).toString();
    const expiresAt = new Date(Date.now() + 5 * 60 * 1000); // 5 minutes

    await db.query(
      "INSERT INTO verification_tokens (user_id, token, type, telegram_code, expires_at) VALUES ($1, $2, 'telegram', $3, $4)",
      [user.id, token, code, expiresAt]
    );

    // Send code directly to user's Telegram Chat ID!
    const sent = await telegramBot.send2FACode(user.telegram_id, code);

    const botUsername = process.env.TELEGRAM_BOT_USERNAME || 'blackdoor_2fa_bot';
    const startLink = `https://t.me/${botUsername}?start=${token}`;

    return res.status(200).json({
      message: sent ? 'Kodi Telegram raqamingizga yuborildi.' : 'Telegram xabarini yuborishda xatolik yuz berdi.',
      botUsername,
      startLink,
      mockCode: code // Returned for testing purposes in case bot is not running
    });

  } catch (err) {
    console.error("Error creating Telegram request:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.telegramVerify = async (req, res) => {
  const userId = req.user.id;
  const { code } = req.body;

  if (!code) {
    return res.status(400).json({ error: 'Verification code is required' });
  }

  try {
    // Check code validity
    const resTokens = await db.query(
      "SELECT * FROM verification_tokens WHERE user_id = $1 AND telegram_code = $2 AND is_used = false AND expires_at > NOW()",
      [userId, code]
    );

    if (resTokens.rows.length === 0) {
      return res.status(400).json({ error: 'Invalid or expired verification code' });
    }

    const tokenRow = resTokens.rows[0];

    // Mark token as used
    await db.query("UPDATE verification_tokens SET is_used = true WHERE id = $1", [tokenRow.id]);

    // Update user verified status
    await db.query("UPDATE users SET is_telegram_verified = true WHERE id = $1", [userId]);

    const userRes = await db.query("SELECT * FROM users WHERE id = $1", [userId]);
    const user = userRes.rows[0];

    // Issue production JWT tokens
    const accessToken = generateAccessToken(user);
    const refreshToken = generateRefreshToken(user);

    // Audit log successful authentication
    await db.query(
      "INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address) VALUES ($1::uuid, 'Login', 'User', $1::varchar, $2)",
      [user.id, req.ip]
    );

    return res.status(200).json({
      message: 'Telegram 2FA successfully verified.',
      accessToken,
      refreshToken,
      user: {
        id: user.id,
        email: user.email,
        fullName: user.full_name,
        role: user.role
      }
    });

  } catch (err) {
    console.error("Error during Telegram verify:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.refreshToken = async (req, res) => {
  const { token } = req.body;

  if (!token) {
    return res.status(401).json({ error: 'Refresh token is required' });
  }

  try {
    jwt.verify(token, process.env.JWT_REFRESH_SECRET, async (err, payload) => {
      if (err) {
        return res.status(403).json({ error: 'Invalid or expired refresh token' });
      }

      const userRes = await db.query("SELECT * FROM users WHERE id = $1", [payload.id]);
      if (userRes.rows.length === 0) {
        return res.status(404).json({ error: 'User not found' });
      }

      const user = userRes.rows[0];
      const newAccessToken = generateAccessToken(user);

      return res.status(200).json({
        accessToken: newAccessToken
      });
    });
  } catch (err) {
    console.error("Error refreshing token:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.logout = async (req, res) => {
  // Handled on client by deleting tokens; on backend we can write an audit log
  if (req.user) {
    try {
      await db.query(
        "INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address) VALUES ($1::uuid, 'Logout', 'User', $1::varchar, $2)",
        [req.user.id, req.ip]
      );
    } catch (err) {
      console.error("Audit log error on logout:", err);
    }
  }
  return res.status(200).json({ message: 'Logged out successfully' });
};

exports.verifySession = async (req, res) => {
  try {
    const userRes = await db.query(
      "SELECT id, email, full_name, role, telegram_id, is_telegram_verified FROM users WHERE id = $1", 
      [req.user.id]
    );
    if (userRes.rows.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }
    return res.status(200).json({ user: userRes.rows[0] });
  } catch (err) {
    console.error("Error verifying session:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateSettings = async (req, res) => {
  const userId = req.user.id;
  const { telegram_id, full_name } = req.body;

  try {
    const userRes = await db.query("SELECT * FROM users WHERE id = $1", [userId]);
    if (userRes.rows.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const user = userRes.rows[0];
    const newName = full_name !== undefined ? full_name : user.full_name;
    const newTgId = telegram_id !== undefined ? telegram_id : user.telegram_id;
    const tgVerified = newTgId ? true : false;

    const result = await db.query(
      "UPDATE users SET full_name = $1, telegram_id = $2, is_telegram_verified = $3, updated_at = NOW() WHERE id = $4 RETURNING *",
      [newName, newTgId || null, tgVerified, userId]
    );

    return res.status(200).json({
      message: 'Sozlamalar muvaffaqiyatli saqlandi',
      user: {
        id: result.rows[0].id,
        email: result.rows[0].email,
        fullName: result.rows[0].full_name,
        role: result.rows[0].role,
        telegramId: result.rows[0].telegram_id,
        isTelegramVerified: result.rows[0].is_telegram_verified
      }
    });

  } catch (err) {
    console.error("Error updating user settings:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
