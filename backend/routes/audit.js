const express = require('express');
const router = express.Router();
const auditController = require('../controllers/auditController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);
router.use(authorizeRoles('admin'));

router.get('/', auditController.getAuditLogs);
router.get('/:entity_type/:entity_id', auditController.getEntityHistory);

module.exports = router;
