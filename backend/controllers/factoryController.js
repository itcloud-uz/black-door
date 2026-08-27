const db = require('../db');

const logAudit = async (userId, action, entityId, changes, ip) => {
  await db.query(
    "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1, $2, 'Factory', $3, $4, $5)",
    [userId, action, entityId, JSON.stringify(changes), ip]
  );
};

exports.getFactories = async (req, res) => {
  try {
    const result = await db.query(
      "SELECT f.*, u.full_name as creator_name FROM factories f LEFT JOIN users u ON f.created_by = u.id ORDER BY f.name ASC"
    );
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting factories:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createFactory = async (req, res) => {
  const { name, address, phone, manager_name, equipment_type, rental_rate_per_day, production_commission_percent, is_active } = req.body;

  if (!name || !address || !phone || !manager_name || !equipment_type) {
    return res.status(400).json({ error: 'Required fields are missing' });
  }

  try {
    const insertQuery = `
      INSERT INTO factories (
        name, address, phone, manager_name, equipment_type, 
        rental_rate_per_day, production_commission_percent, is_active, created_by
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
      RETURNING *
    `;
    const params = [
      name,
      address,
      phone,
      manager_name,
      equipment_type,
      rental_rate_per_day || 0.00,
      production_commission_percent || 0.00,
      is_active !== undefined ? is_active : true,
      req.user.id
    ];

    const result = await db.query(insertQuery, params);
    const newFactory = result.rows[0];

    await logAudit(req.user.id, 'Created', newFactory.id, newFactory, req.ip);

    return res.status(201).json(newFactory);
  } catch (err) {
    console.error("Error creating factory:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateFactory = async (req, res) => {
  const { id } = req.params;
  const { name, address, phone, manager_name, equipment_type, is_active } = req.body;

  try {
    const checkRes = await db.query("SELECT * FROM factories WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Factory not found' });
    }

    const currentFac = checkRes.rows[0];

    const updateQuery = `
      UPDATE factories 
      SET name = $1, address = $2, phone = $3, manager_name = $4, equipment_type = $5, is_active = $6, updated_at = NOW()
      WHERE id = $7
      RETURNING *
    `;
    const params = [
      name || currentFac.name,
      address || currentFac.address,
      phone || currentFac.phone,
      manager_name || currentFac.manager_name,
      equipment_type || currentFac.equipment_type,
      is_active !== undefined ? is_active : currentFac.is_active,
      id
    ];

    const result = await db.query(updateQuery, params);
    const updatedFac = result.rows[0];

    await logAudit(req.user.id, 'Updated', id, { before: currentFac, after: updatedFac }, req.ip);

    return res.status(200).json(updatedFac);
  } catch (err) {
    console.error("Error updating factory:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteFactory = async (req, res) => {
  const { id } = req.params;

  try {
    const checkRes = await db.query("SELECT * FROM factories WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Factory not found' });
    }

    const fac = checkRes.rows[0];

    await db.query("DELETE FROM factories WHERE id = $1", [id]);

    await logAudit(req.user.id, 'Deleted', id, fac, req.ip);

    return res.status(200).json({ message: 'Factory deleted successfully' });
  } catch (err) {
    console.error("Error deleting factory:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.setRates = async (req, res) => {
  const { id } = req.params;
  const { rental_rate_per_day, production_commission_percent } = req.body;

  if (rental_rate_per_day === undefined || production_commission_percent === undefined) {
    return res.status(400).json({ error: 'Both rental_rate_per_day and production_commission_percent must be provided' });
  }

  try {
    const checkRes = await db.query("SELECT * FROM factories WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Factory not found' });
    }

    const currentFac = checkRes.rows[0];

    const result = await db.query(
      "UPDATE factories SET rental_rate_per_day = $1, production_commission_percent = $2, updated_at = NOW() WHERE id = $3 RETURNING *",
      [rental_rate_per_day, production_commission_percent, id]
    );

    const updatedFac = result.rows[0];

    await logAudit(req.user.id, 'Updated', id, { 
      message: 'Rates changed', 
      before: { rental: currentFac.rental_rate_per_day, commission: currentFac.production_commission_percent }, 
      after: { rental: updatedFac.rental_rate_per_day, commission: updatedFac.production_commission_percent } 
    }, req.ip);

    return res.status(200).json(updatedFac);

  } catch (err) {
    console.error("Error setting rates:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getRevenue = async (req, res) => {
  const { id } = req.params;

  try {
    const facRes = await db.query("SELECT * FROM factories WHERE id = $1", [id]);
    if (facRes.rows.length === 0) {
      return res.status(404).json({ error: 'Factory not found' });
    }

    // Retrieve rental/commission transactions associated with this factory
    const result = await db.query(
      "SELECT * FROM transactions WHERE factory_id = $1 AND transaction_type IN ('factory_rental', 'factory_commission') ORDER BY created_at DESC",
      [id]
    );

    // Sum total rental and commission revenues
    let totalRental = 0;
    let totalCommission = 0;

    result.rows.forEach(t => {
      if (t.status === 'completed') {
        const val = parseFloat(t.amount);
        if (t.transaction_type === 'factory_rental') {
          totalRental += val;
        } else if (t.transaction_type === 'factory_commission') {
          totalCommission += val;
        }
      }
    });

    return res.status(200).json({
      factory: facRes.rows[0],
      totalRental,
      totalCommission,
      totalRevenue: totalRental + totalCommission,
      transactions: result.rows
    });
  } catch (err) {
    console.error("Error getting revenue:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

// Factory Products (Link / Unlink)
exports.getFactoryProducts = async (req, res) => {
  const { id } = req.params;

  try {
    const result = await db.query(
      `SELECT fp.*, p.name as product_name, p.unit_type, p.base_price 
       FROM factory_products fp 
       JOIN products p ON fp.product_id = p.id 
       WHERE fp.factory_id = $1`,
      [id]
    );
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting factory products:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.addFactoryProduct = async (req, res) => {
  const { id } = req.params;
  const { product_id, production_capacity_per_day, production_status } = req.body;

  if (!product_id || production_capacity_per_day === undefined) {
    return res.status(400).json({ error: 'product_id and production_capacity_per_day are required' });
  }

  try {
    const insertQuery = `
      INSERT INTO factory_products (factory_id, product_id, production_capacity_per_day, production_status)
      VALUES ($1, $2, $3, $4)
      ON CONFLICT (factory_id, product_id)
      DO UPDATE SET production_capacity_per_day = EXCLUDED.production_capacity_per_day, production_status = EXCLUDED.production_status, updated_at = NOW()
      RETURNING *
    `;
    const result = await db.query(insertQuery, [
      id,
      product_id,
      production_capacity_per_day,
      production_status || 'active'
    ]);

    return res.status(200).json(result.rows[0]);
  } catch (err) {
    console.error("Error adding factory product:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.removeFactoryProduct = async (req, res) => {
  const { id, productId } = req.params;

  try {
    await db.query(
      "DELETE FROM factory_products WHERE factory_id = $1 AND product_id = $2",
      [id, productId]
    );
    return res.status(200).json({ message: 'Product unlinked from factory successfully' });
  } catch (err) {
    console.error("Error removing factory product:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
