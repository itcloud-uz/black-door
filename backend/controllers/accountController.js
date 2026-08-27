const db = require('../db');

const logAudit = async (userId, action, entityId, changes, ip) => {
  await db.query(
    "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1, $2, 'Account', $3, $4, $5)",
    [userId, action, entityId, JSON.stringify(changes), ip]
  );
};

exports.getAccounts = async (req, res) => {
  try {
    const result = await db.query(
      "SELECT a.*, u.full_name as creator_name FROM accounts a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.account_holder_name ASC"
    );
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting accounts:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createAccount = async (req, res) => {
  const { account_type, account_holder_name, account_number, phone, email, current_balance, currency, account_status } = req.body;

  if (!account_holder_name || !account_number || !currency) {
    return res.status(400).json({ error: 'Required fields are missing' });
  }

  try {
    const checkRes = await db.query("SELECT * FROM accounts WHERE account_number = $1", [account_number]);
    if (checkRes.rows.length > 0) {
      return res.status(400).json({ error: 'Account number already exists' });
    }

    const insertQuery = `
      INSERT INTO accounts (
        account_type, account_holder_name, account_number, phone, email, 
        current_balance, currency, account_status, created_by
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
      RETURNING *
    `;
    const params = [
      account_type || 'person',
      account_holder_name,
      account_number,
      phone || null,
      email || null,
      current_balance || 0.00,
      currency,
      account_status || 'active',
      req.user.id
    ];

    const result = await db.query(insertQuery, params);
    const newAcc = result.rows[0];

    await logAudit(req.user.id, 'Created', newAcc.id, newAcc, req.ip);

    return res.status(201).json(newAcc);
  } catch (err) {
    console.error("Error creating account:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateAccount = async (req, res) => {
  const { id } = req.params;
  const { account_holder_name, phone, email, account_status } = req.body;

  try {
    const checkRes = await db.query("SELECT * FROM accounts WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Account not found' });
    }

    const currentAcc = checkRes.rows[0];

    const updateQuery = `
      UPDATE accounts 
      SET account_holder_name = $1, phone = $2, email = $3, account_status = $4, updated_at = NOW()
      WHERE id = $5
      RETURNING *
    `;
    const params = [
      account_holder_name || currentAcc.account_holder_name,
      phone !== undefined ? phone : currentAcc.phone,
      email !== undefined ? email : currentAcc.email,
      account_status || currentAcc.account_status,
      id
    ];

    const result = await db.query(updateQuery, params);
    const updatedAcc = result.rows[0];

    await logAudit(req.user.id, 'Updated', id, { before: currentAcc, after: updatedAcc }, req.ip);

    return res.status(200).json(updatedAcc);
  } catch (err) {
    console.error("Error updating account:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteAccount = async (req, res) => {
  const { id } = req.params;

  try {
    const checkRes = await db.query("SELECT * FROM accounts WHERE id = $1", [id]);
    if (checkRes.rows.length === 0) {
      return res.status(404).json({ error: 'Account not found' });
    }

    const acc = checkRes.rows[0];

    // Delete the account
    await db.query("DELETE FROM accounts WHERE id = $1", [id]);

    await logAudit(req.user.id, 'Deleted', id, acc, req.ip);

    return res.status(200).json({ message: 'Account deleted successfully' });
  } catch (err) {
    console.error("Error deleting account:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getBalanceHistory = async (req, res) => {
  const { id } = req.params;

  try {
    const accRes = await db.query("SELECT * FROM accounts WHERE id = $1", [id]);
    if (accRes.rows.length === 0) {
      return res.status(404).json({ error: 'Account not found' });
    }

    // Retrieve all transactions involving this account
    const result = await db.query(
      "SELECT * FROM transactions WHERE person_id = $1 ORDER BY created_at DESC",
      [id]
    );

    return res.status(200).json({
      account: accRes.rows[0],
      transactions: result.rows
    });
  } catch (err) {
    console.error("Error getting balance history:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.adjustBalance = async (req, res) => {
  const { id } = req.params;
  const { adjustmentAmount, description } = req.body; // positive to add, negative to subtract

  if (adjustmentAmount === undefined || isNaN(adjustmentAmount)) {
    return res.status(400).json({ error: 'Valid adjustmentAmount is required' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const accRes = await client.query("SELECT * FROM accounts WHERE id = $1", [id]);
    if (accRes.rows.length === 0) {
      return res.status(404).json({ error: 'Account not found' });
    }

    const acc = accRes.rows[0];
    const newBalance = parseFloat(acc.current_balance) + parseFloat(adjustmentAmount);

    // Update balance
    await client.query(
      "UPDATE accounts SET current_balance = $1, updated_at = NOW() WHERE id = $2",
      [newBalance, id]
    );

    // Log the adjustment as a transaction to preserve history
    const receiptNum = 'BD-ADJ-' + Date.now();
    const txType = parseFloat(adjustmentAmount) >= 0 ? 'cash_deposit' : 'personal_withdrawal';
    await client.query(
      `INSERT INTO transactions (
        transaction_type, amount, currency, description, person_id, status, receipt_number, created_by
      ) VALUES ($1, $2, $3, $4, $5, 'completed', $6, $7)`,
      [
        txType,
        Math.abs(parseFloat(adjustmentAmount)),
        acc.currency,
        description || 'Balansni qo\'lda to\'g\'rilash',
        id,
        receiptNum,
        req.user.id
      ]
    );

    await client.query('COMMIT');

    await logAudit(req.user.id, 'Updated', id, { 
      message: 'Manual balance adjustment', 
      amount: adjustmentAmount, 
      before: acc.current_balance, 
      after: newBalance 
    }, req.ip);

    return res.status(200).json({ 
      message: 'Balance successfully adjusted',
      current_balance: newBalance
    });

  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error adjusting balance:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};
