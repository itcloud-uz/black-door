const db = require('../db');

const logAudit = async (userId, action, entityId, changes, ip) => {
  await db.query(
    "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1, $2, 'Product', $3, $4, $5)",
    [userId, action, entityId, JSON.stringify(changes), ip]
  );
};

exports.getProducts = async (req, res) => {
  try {
    const result = await db.query(
      "SELECT p.*, u.full_name as creator_name FROM products p LEFT JOIN users u ON p.created_by = u.id ORDER BY p.name ASC"
    );
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting products:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createProduct = async (req, res) => {
  const { name, description, unit_type, base_price, cost_price, quantity_in_stock, category, manufacturer, is_active } = req.body;

  if (!name || !unit_type || !category) {
    return res.status(400).json({ error: 'Required fields are missing' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const insertQuery = `
      INSERT INTO products (
        name, description, unit_type, base_price, cost_price, 
        quantity_in_stock, category, manufacturer, is_active, created_by
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      RETURNING *
    `;
    const params = [
      name,
      description || null,
      unit_type,
      base_price || 0.00,
      cost_price || 0.00,
      quantity_in_stock || 0.00,
      category,
      manufacturer || null,
      is_active !== undefined ? is_active : true,
      req.user.id
    ];

    const result = await client.query(insertQuery, params);
    const newProduct = result.rows[0];

    // Seed default warehouse inventory spot for this product
    await client.query(
      "INSERT INTO warehouse_inventory (product_id, quantity, location) VALUES ($1, $2, $3) ON CONFLICT (product_id) DO NOTHING",
      [newProduct.id, newProduct.quantity_in_stock, 'A-sektor, Belgilanmagan']
    );

    await client.query('COMMIT');

    await logAudit(req.user.id, 'Created', newProduct.id, newProduct, req.ip);

    return res.status(201).json(newProduct);
  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error creating product:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

exports.updateProduct = async (req, res) => {
  const { id } = req.params;
  const { name, description, unit_type, category, manufacturer, is_active } = req.body;

  try {
    const checkRes = await db.query("SELECT * FROM products WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }

    const currentProd = checkRes.rows[0];

    const updateQuery = `
      UPDATE products 
      SET name = $1, description = $2, unit_type = $3, category = $4, manufacturer = $5, is_active = $6, updated_at = NOW()
      WHERE id = $7
      RETURNING *
    `;
    const params = [
      name || currentProd.name,
      description !== undefined ? description : currentProd.description,
      unit_type || currentProd.unit_type,
      category || currentProd.category,
      manufacturer !== undefined ? manufacturer : currentProd.manufacturer,
      is_active !== undefined ? is_active : currentProd.is_active,
      id
    ];

    const result = await db.query(updateQuery, params);
    const updatedProd = result.rows[0];

    await logAudit(req.user.id, 'Updated', id, { before: currentProd, after: updatedProd }, req.ip);

    return res.status(200).json(updatedProd);
  } catch (err) {
    console.error("Error updating product:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteProduct = async (req, res) => {
  const { id } = req.params;

  try {
    const checkRes = await db.query("SELECT * FROM products WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }

    const prod = checkRes.rows[0];

    await db.query("DELETE FROM products WHERE id = $1", [id]);

    await logAudit(req.user.id, 'Deleted', id, prod, req.ip);

    return res.status(200).json({ message: 'Product successfully deleted' });
  } catch (err) {
    console.error("Error deleting product:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updatePrice = async (req, res) => {
  const { id } = req.params;
  const { base_price, cost_price } = req.body;

  if (base_price === undefined && cost_price === undefined) {
    return res.status(400).json({ error: 'At least one of base_price or cost_price must be provided' });
  }

  try {
    const checkRes = await db.query("SELECT * FROM products WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }

    const currentProd = checkRes.rows[0];
    const newBase = base_price !== undefined ? base_price : currentProd.base_price;
    const newCost = cost_price !== undefined ? cost_price : currentProd.cost_price;

    const result = await db.query(
      "UPDATE products SET base_price = $1, cost_price = $2, updated_at = NOW() WHERE id = $3 RETURNING *",
      [newBase, newCost, id]
    );

    const updatedProd = result.rows[0];

    await logAudit(req.user.id, 'Updated', id, { 
      message: 'Price updated', 
      before: { base: currentProd.base_price, cost: currentProd.cost_price }, 
      after: { base: updatedProd.base_price, cost: updatedProd.cost_price } 
    }, req.ip);

    return res.status(200).json(updatedProd);

  } catch (err) {
    console.error("Error updating product price:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateStock = async (req, res) => {
  const { id } = req.params;
  const { quantity_in_stock } = req.body;

  if (quantity_in_stock === undefined || isNaN(quantity_in_stock)) {
    return res.status(400).json({ error: 'Valid quantity_in_stock is required' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const checkRes = await client.query("SELECT * FROM products WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }

    const currentProd = checkRes.rows[0];

    // Update product stock
    const result = await client.query(
      "UPDATE products SET quantity_in_stock = $1, updated_at = NOW() WHERE id = $2 RETURNING *",
      [quantity_in_stock, id]
    );

    const updatedProd = result.rows[0];

    // Synchronize warehouse inventory quantity
    await client.query(
      "UPDATE warehouse_inventory SET quantity = $1, updated_at = NOW() WHERE product_id = $2",
      [quantity_in_stock, id]
    );

    // Insert an inventory adjustment audit transaction
    const receiptNum = 'BD-ADJ-STK-' + Date.now();
    const diff = parseFloat(quantity_in_stock) - parseFloat(currentProd.quantity_in_stock);
    if (diff !== 0) {
      await client.query(
        `INSERT INTO transactions (
          transaction_type, amount, currency, description, product_id, status, receipt_number, created_by
        ) VALUES ('inventory_adjustment', $1, 'UZS', $2, $3, 'completed', $4, $5)`,
        [
          Math.abs(diff),
          `Sana bo'yicha qoldiq qayta hisoblandi. Farqi: ${diff}`,
          id,
          receiptNum,
          req.user.id
        ]
      );
    }

    await client.query('COMMIT');

    await logAudit(req.user.id, 'Updated', id, { 
      message: 'Stock updated', 
      before: currentProd.quantity_in_stock, 
      after: updatedProd.quantity_in_stock 
    }, req.ip);

    return res.status(200).json(updatedProd);

  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error updating product stock:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

exports.getCategories = async (req, res) => {
  try {
    const result = await db.query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    const categories = result.rows.map(r => r.category);
    return res.status(200).json(categories);
  } catch (err) {
    console.error("Error getting product categories:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
