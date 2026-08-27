const express = require('express');
const router = express.Router();
const productController = require('../controllers/productController');
const { authenticateToken, authorizeRoles } = require('../middleware/authMiddleware');

router.use(authenticateToken);
router.use(authorizeRoles('admin'));

router.get('/', productController.getProducts);
router.post('/', productController.createProduct);
router.put('/:id', productController.updateProduct);
router.delete('/:id', productController.deleteProduct);
router.patch('/:id/price', productController.updatePrice);
router.patch('/:id/stock', productController.updateStock);
router.get('/categories', productController.getCategories);

module.exports = router;
