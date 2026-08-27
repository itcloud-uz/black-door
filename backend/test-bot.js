const { Telegraf } = require('telegraf');
const token = '8304799073:AAGOi1nbw29OkKY_YhrP3kOJnRGRVq-qVPY';

const bot = new Telegraf(token);
bot.telegram.getMe().then((me) => {
  console.log("SUCCESS: Bot is valid and running!");
  console.log("Bot username:", me.username);
  process.exit(0);
}).catch((err) => {
  console.error("ERROR: Failed to connect to bot:");
  console.error(err);
  process.exit(1);
});
