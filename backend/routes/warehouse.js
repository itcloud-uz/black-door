const express = require('express');
const router = express.Router();
const warehouseController = require('../controllers/warehouseController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);

// Admin endpoints
router.get('/admin/inventory', authorizeRoles('admin'), warehouseController.getInventory);
router.get('/admin/operations', authorizeRoles('admin'), warehouseController.getOperations);
router.get('/admin/alerts', authorizeRoles('admin'), warehouseController.getAlerts);

// Employee / Manager endpoints
router.post('/employee/receive', authorizeRoles('employee', 'admin'), warehouseController.receiveGoods);
router.post('/employee/dispatch', authorizeRoles('employee', 'admin'), warehouseController.dispatchGoods);
router.post('/employee/transfer', authorizeRoles('employee', 'admin'), warehouseController.transferGoods);
router.post('/employee/damage-report', authorizeRoles('employee', 'admin'), warehouseController.damageReport);
router.get('/employee/inventory', authorizeRoles('employee', 'admin'), warehouseController.getInventory);
router.get('/employee/operations', authorizeRoles('employee', 'admin'), warehouseController.getOperations);

module.exports = router;
