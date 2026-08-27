const { Telegraf } = require('telegraf');
const db = require('../db');
require('dotenv').config();

const token = process.env.TELEGRAM_BOT_TOKEN || '8304799073:AAGOi1nbw29OkKY_YhrP3kOJnRGRVq-qVPY';
let bot = null;

if (token && token !== 'mock-telegram-bot-token') {
  try {
    bot = new Telegraf(token);

    bot.start(async (ctx) => {
      const payload = ctx.payload;
      const telegramId = ctx.from.id.toString();
      const telegramUsername = ctx.from.username || '';

      // If user has started bot with token payload, do auto link
      if (payload) {
        try {
          const res = await db.query(
            "SELECT * FROM verification_tokens WHERE token = $1 AND is_used = false AND expires_at > NOW()",
            [payload]
          );

          if (res.rows.length > 0) {
            const tokenRow = res.rows[0];
            const userId = tokenRow.user_id;

            let code = tokenRow.telegram_code;
            if (!code) {
              code = Math.floor(100000 + Math.random() * 900000).toString();
              await db.query(
                "UPDATE verification_tokens SET telegram_code = $1 WHERE id = $2",
                [code, tokenRow.id]
              );
            }

            await db.query(
              "UPDATE users SET telegram_id = $1, telegram_username = $2, is_telegram_verified = true WHERE id = $3",
              [telegramId, telegramUsername, userId]
            );

            return ctx.reply(
              `Salom! Akkauntingiz muvaffaqiyatli bog'landi.\n\nSizning Chat ID: ${telegramId}\n\nTasdiqlash kodi: ${code}`
            );
          }
        } catch (err) {
          console.error("Error auto-linking Telegram ID:", err);
        }
      }

      // Default response returning their chat ID so they can paste it manually in Settings!
      ctx.reply(
        `Salom, ${ctx.from.first_name || 'Foydalanuvchi'}!\n\nSizning Telegram Chat ID: \`${telegramId}\`\n\n2FA tasdiqlash kodlarini olish uchun ushbu ID-ni veb-ilovaning Sozlamalar bo'limida akkauntingizga kiriting.`
      );
    });

    bot.launch()
      .then(() => console.log("Telegram Bot successfully launched with token:", token.substring(0, 10) + "..."))
      .catch((err) => console.error("Error launching Telegram Bot:", err));

  } catch (err) {
    console.error("Failed to initialize Telegram Bot:", err);
  }
} else {
  console.log("Using Mock Telegram Bot (Token is missing or mock)");
}

// Function to send 2FA verification code
async function send2FACode(telegramId, code) {
  if (bot) {
    try {
      await bot.telegram.sendMessage(
        telegramId, 
        `🚪 *Black Door ERP*\n\nTizimga kirish uchun tasdiqlash kodi: *${code}*\n\nUshbu kod faqat 5 daqiqa davomida faol.`,
        { parse_mode: 'Markdown' }
      );
      return true;
    } catch (err) {
      console.error(`Error sending message to Telegram ID ${telegramId}:`, err);
      return false;
    }
  }
  return false;
}

async function generateMockCodeForUser(userId) {
  const token = require('crypto').randomUUID();
  const code = Math.floor(100000 + Math.random() * 900000).toString();
  const expiresAt = new Date(Date.now() + 5 * 60 * 1000);

  await db.query(
    "INSERT INTO verification_tokens (user_id, token, type, telegram_code, expires_at) VALUES ($1, $2, 'telegram', $3, $4)",
    [userId, token, code, expiresAt]
  );

  return { token, code };
}

module.exports = {
  bot,
  send2FACode,
  generateMockCodeForUser
};
