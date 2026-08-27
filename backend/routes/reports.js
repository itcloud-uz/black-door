const express = require('express');
const router = express.Router();
const reportController = require('../controllers/reportController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);
router.use(authorizeRoles('admin'));

router.get('/daily', reportController.getDailyReport);
router.get('/monthly', reportController.getMonthlyReport);
router.get('/factory-productivity', reportController.getFactoryProductivity);
router.get('/balance-sheet', reportController.getBalanceSheet);

module.exports = router;
