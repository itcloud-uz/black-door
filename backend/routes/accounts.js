const express = require('express');
const router = express.Router();
const accountController = require('../controllers/accountController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);
router.use(authorizeRoles('admin'));

router.get('/', accountController.getAccounts);
router.post('/', accountController.createAccount);
router.put('/:id', accountController.updateAccount);
router.delete('/:id', accountController.deleteAccount);
router.get('/:id/balance-history', accountController.getBalanceHistory);
router.post('/:id/adjust-balance', accountController.adjustBalance);

module.exports = router;
