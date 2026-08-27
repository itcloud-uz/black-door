const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');
const { authenticateToken } = require('../middleware/authMiddleware');

router.post('/google-login', authController.googleLogin);
router.post('/telegram-request', authenticateToken, authController.telegramRequest);
router.post('/telegram-verify', authenticateToken, authController.telegramVerify);
router.post('/refresh-token', authController.refreshToken);
router.post('/logout', authenticateToken, authController.logout);
router.get('/verify-session', authenticateToken, authController.verifySession);

// Mock Helper to bypass Telegram Bot locally
router.post('/telegram-mock', authenticateToken, async (req, res) => {
  try {
    const telegramBot = require('../services/telegramBot');
    const { code, token } = await telegramBot.generateMockCodeForUser(req.user.id);
    return res.status(200).json({
      message: 'Mock verification token created successfully',
      code,
      token,
      instructions: `Kodni tasdiqlash uchun web panelda ushbu kodni kiriting: ${code}`
    });
  } catch (err) {
    console.error("Mock Telegram creation error:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
});

module.exports = router;
