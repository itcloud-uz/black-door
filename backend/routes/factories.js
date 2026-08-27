const express = require('express');
const router = express.Router();
const factoryController = require('../controllers/factoryController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);
router.use(authorizeRoles('admin'));

router.get('/', factoryController.getFactories);
router.post('/', factoryController.createFactory);
router.put('/:id', factoryController.updateFactory);
router.delete('/:id', factoryController.deleteFactory);
router.post('/:id/set-rates', factoryController.setRates);
router.get('/:id/revenue', factoryController.getRevenue);

// Factory product linkage
router.get('/:id/products', factoryController.getFactoryProducts);
router.post('/:id/products', factoryController.addFactoryProduct);
router.delete('/:id/products/:productId', factoryController.removeFactoryProduct);

module.exports = router;
