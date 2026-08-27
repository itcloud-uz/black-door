const { Telegraf } = require('telegraf');
const db = require('../db');
require('dotenv').config();

let bot = null;

if (process.env.TELEGRAM_BOT_TOKEN && process.env.TELEGRAM_BOT_TOKEN !== 'mock-telegram-bot-token') {
  try {
    bot = new Telegraf(process.env.TELEGRAM_BOT_TOKEN);

    // Start command with deep-linking payload
    bot.start(async (ctx) => {
      const payload = ctx.payload;
      const telegramId = ctx.from.id.toString();
      const telegramUsername = ctx.from.username || '';

      if (!payload) {
        return ctx.reply(
          "Salom! 2FA faollashtirish uchun tizim web-paneli orqali Telegram ulanish havolasini bosing."
        );
      }

      try {
        const res = await db.query(
          "SELECT * FROM verification_tokens WHERE token = $1 AND is_used = false AND expires_at > NOW()",
          [payload]
        );

        if (res.rows.length === 0) {
          return ctx.reply("Kechirasiz, ushbu havola eskirgan yoki noto'g'ri.");
        }

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
          "UPDATE users SET telegram_id = $1, telegram_username = $2 WHERE id = $3",
          [telegramId, telegramUsername, userId]
        );

        ctx.reply(
          `Salom! Akkauntingiz muvaffaqiyatli bog'landi.\n\nTasdiqlash kodi: ${code}\n\nUshbu kodni web-panelga kiriting.`
        );

      } catch (err) {
        console.error("Telegram bot database error:", err);
        ctx.reply("Tizimda xatolik yuz berdi. Iltimos keyinroq qayta urining.");
      }
    });

    bot.launch()
      .then(() => console.log("Telegram Bot successfully launched."))
      .catch((err) => console.error("Error launching Telegram Bot:", err));

  } catch (err) {
    console.error("Failed to initialize Telegram Bot:", err);
  }
} else {
  console.log("Using Mock Telegram Bot (Token is missing or mock)");
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
  generateMockCodeForUser
};
