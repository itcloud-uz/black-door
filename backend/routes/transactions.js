const express = require('express');
const router = express.Router();
const transactionController = require('../controllers/transactionController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);
router.use(authorizeRoles('admin'));

router.get('/', transactionController.getTransactions);
router.post('/', transactionController.createTransaction);
router.put('/:id', transactionController.updateTransaction);
router.delete('/:id', transactionController.deleteTransaction);
router.get('/export', transactionController.exportTransactions);

module.exports = router;
