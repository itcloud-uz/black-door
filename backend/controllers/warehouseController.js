const db = require('../db');

exports.getInventory = async (req, res) => {
  try {
    const result = await db.query(
      `SELECT wi.*, p.name as product_name, p.unit_type, p.category, p.quantity_in_stock as system_quantity
       FROM warehouse_inventory wi
       JOIN products p ON wi.product_id = p.id
       ORDER BY p.name ASC`
    );
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting warehouse inventory:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getOperations = async (req, res) => {
  const { operationType, productId } = req.query;
  let queryText = `
    SELECT wo.*, p.name as product_name, p.unit_type, u.full_name as operator_name
    FROM warehouse_operations wo
    JOIN products p ON wo.product_id = p.id
    JOIN users u ON wo.performed_by = u.id
    WHERE 1=1
  `;
  const params = [];
  let index = 1;

  if (operationType) {
    queryText += ` AND wo.operation_type = $${index}`;
    params.push(operationType);
    index++;
  }
  if (productId) {
    queryText += ` AND wo.product_id = $${index}`;
    params.push(productId);
    index++;
  }

  queryText += " ORDER BY wo.created_at DESC";

  try {
    const result = await db.query(queryText, params);
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting warehouse operations:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getAlerts = async (req, res) => {
  try {
    // Understock alert: if quantity is less than 10 (or a custom limit, let's say 20)
    // Or if discrepancy exists: product.quantity_in_stock != warehouse_inventory.quantity
    const discrepancyRes = await db.query(
      `SELECT p.id, p.name, p.quantity_in_stock as system_qty, wi.quantity as warehouse_qty
       FROM products p
       JOIN warehouse_inventory wi ON p.id = wi.product_id
       WHERE p.quantity_in_stock != wi.quantity`
    );

    const lowStockRes = await db.query(
      `SELECT id, name, quantity_in_stock, unit_type 
       FROM products 
       WHERE quantity_in_stock < 50 AND is_active = true`
    );

    return res.status(200).json({
      discrepancies: discrepancyRes.rows,
      lowStock: lowStockRes.rows
    });
  } catch (err) {
    console.error("Error getting warehouse alerts:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

// Employee intake / receive goods
exports.receiveGoods = async (req, res) => {
  const { product_id, quantity, to_location, notes } = req.body;

  if (!product_id || quantity === undefined || isNaN(quantity) || parseFloat(quantity) <= 0) {
    return res.status(400).json({ error: 'Valid product_id and positive quantity are required' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    // 1. Check product
    const prodRes = await client.query("SELECT * FROM products WHERE id = $1", [product_id]);
    if (prodRes.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }

    const qty = parseFloat(quantity);
    const location = to_location || 'A-sektor, Birinchi qabul';

    // 2. Update product stock
    await client.query(
      "UPDATE products SET quantity_in_stock = quantity_in_stock + $1, updated_at = NOW() WHERE id = $2",
      [qty, product_id]
    );

    // 3. Update warehouse inventory
    await client.query(
      `INSERT INTO warehouse_inventory (product_id, quantity, location, updated_at) 
       VALUES ($1, $2, $3, NOW())
       ON CONFLICT (product_id) 
       DO UPDATE SET quantity = warehouse_inventory.quantity + EXCLUDED.quantity, location = EXCLUDED.location, updated_at = NOW()`,
      [product_id, qty, location]
    );

    // 4. Create operation log
    const opQuery = `
      INSERT INTO warehouse_operations (operation_type, product_id, quantity, to_location, notes, performed_by)
      VALUES ('receive', $1, $2, $3, $4, $5)
      RETURNING *
    `;
    const opRes = await client.query(opQuery, [product_id, qty, location, notes || 'Tovarni qabul qilish', req.user.id]);

    await client.query('COMMIT');

    return res.status(201).json(opRes.rows[0]);

  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error receiving goods:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

// Employee dispatch / ship goods
exports.dispatchGoods = async (req, res) => {
  const { product_id, quantity, from_location, notes } = req.body;

  if (!product_id || quantity === undefined || isNaN(quantity) || parseFloat(quantity) <= 0) {
    return res.status(400).json({ error: 'Valid product_id and positive quantity are required' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    // 1. Check current inventory stock
    const invRes = await client.query("SELECT * FROM warehouse_inventory WHERE product_id = $1", [product_id]);
    if (invRes.rows.length === 0 || parseFloat(invRes.rows[0].quantity) < parseFloat(quantity)) {
      return res.status(400).json({ error: 'Insufficient stock in warehouse' });
    }

    const qty = parseFloat(quantity);
    const location = from_location || invRes.rows[0].location;

    // 2. Decrease product stock
    await client.query(
      "UPDATE products SET quantity_in_stock = quantity_in_stock - $1, updated_at = NOW() WHERE id = $2",
      [qty, product_id]
    );

    // 3. Decrease warehouse inventory
    await client.query(
      "UPDATE warehouse_inventory SET quantity = quantity - $1, updated_at = NOW() WHERE product_id = $2",
      [qty, product_id]
    );

    // 4. Create operation log
    const opQuery = `
      INSERT INTO warehouse_operations (operation_type, product_id, quantity, from_location, notes, performed_by)
      VALUES ('dispatch', $1, $2, $3, $4, $5)
      RETURNING *
    `;
    const opRes = await client.query(opQuery, [product_id, qty, location, notes || 'Tovarni jo\'natish', req.user.id]);

    await client.query('COMMIT');

    return res.status(201).json(opRes.rows[0]);

  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error dispatching goods:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

// Employee transfer internally
exports.transferGoods = async (req, res) => {
  const { product_id, quantity, from_location, to_location, notes } = req.body;

  if (!product_id || !to_location || quantity === undefined || isNaN(quantity) || parseFloat(quantity) <= 0) {
    return res.status(400).json({ error: 'Valid product_id, positive quantity, and to_location are required' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    // Verify inventory exist
    const invRes = await client.query("SELECT * FROM warehouse_inventory WHERE product_id = $1", [product_id]);
    if (invRes.rows.length === 0) {
      return res.status(404).json({ error: 'Inventory not found for this product' });
    }

    const qty = parseFloat(quantity);
    const currentLoc = invRes.rows[0].location;

    // Update location of the product
    await client.query(
      "UPDATE warehouse_inventory SET location = $1, updated_at = NOW() WHERE product_id = $2",
      [to_location, product_id]
    );

    // Create operation log
    const opQuery = `
      INSERT INTO warehouse_operations (operation_type, product_id, quantity, from_location, to_location, notes, performed_by)
      VALUES ('transfer', $1, $2, $3, $4, $5, $6)
      RETURNING *
    `;
    const opRes = await client.query(opQuery, [
      product_id,
      qty,
      from_location || currentLoc,
      to_location,
      notes || 'Ichki joylashuvni o\'zgartirish',
      req.user.id
    ]);

    await client.query('COMMIT');

    return res.status(201).json(opRes.rows[0]);

  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error transferring goods:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

// Employee damage report
exports.damageReport = async (req, res) => {
  const { product_id, quantity, notes } = req.body;

  if (!product_id || quantity === undefined || isNaN(quantity) || parseFloat(quantity) <= 0) {
    return res.status(400).json({ error: 'Valid product_id and positive quantity are required' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const invRes = await client.query("SELECT * FROM warehouse_inventory WHERE product_id = $1", [product_id]);
    if (invRes.rows.length === 0 || parseFloat(invRes.rows[0].quantity) < parseFloat(quantity)) {
      return res.status(400).json({ error: 'Insufficient stock in warehouse to report damage' });
    }

    const qty = parseFloat(quantity);
    const location = invRes.rows[0].location;

    // Decrease system stock
    await client.query(
      "UPDATE products SET quantity_in_stock = quantity_in_stock - $1, updated_at = NOW() WHERE id = $2",
      [qty, product_id]
    );

    // Decrease warehouse stock
    await client.query(
      "UPDATE warehouse_inventory SET quantity = quantity - $1, updated_at = NOW() WHERE product_id = $2",
      [qty, product_id]
    );

    // Add operation
    const opQuery = `
      INSERT INTO warehouse_operations (operation_type, product_id, quantity, from_location, notes, performed_by)
      VALUES ('damage_report', $1, $2, $3, $4, $5)
      RETURNING *
    `;
    const opRes = await client.query(opQuery, [
      product_id,
      qty,
      location,
      notes || 'Zararlangan mahsulot dalolatnomasi',
      req.user.id
    ]);

    // Create an audit transaction as inventory adjustment
    const receiptNum = 'BD-DMG-' + Date.now();
    await client.query(
      `INSERT INTO transactions (
        transaction_type, amount, currency, description, product_id, status, receipt_number, created_by
      ) VALUES ('inventory_adjustment', $1, 'UZS', $2, $3, 'completed', $4, $5)`,
      [
        qty,
        `Yaroqsiz/Zararlangan mahsulot hisobdan chiqarildi. Izoh: ${notes || 'Sababsiz'}`,
        product_id,
        receiptNum,
        req.user.id
      ]
    );

    await client.query('COMMIT');

    return res.status(201).json(opRes.rows[0]);

  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error reporting damage:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};
