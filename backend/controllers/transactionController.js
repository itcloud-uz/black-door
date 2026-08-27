const db = require('../db');
const ExcelJS = require('exceljs');
const PDFDocument = require('pdfkit');

// Helper to log changes to audit_log
const logAudit = async (userId, action, entityId, changes, ip) => {
  await db.query(
    "INSERT INTO audit_log (user_id, action, entity_type, entity_id, changes, ip_address) VALUES ($1, $2, 'Transaction', $3, $4, $5)",
    [userId, action, entityId, JSON.stringify(changes), ip]
  );
};

exports.getTransactions = async (req, res) => {
  const { startDate, endDate, type, personId, search } = req.query;
  let queryText = `
    SELECT t.*, u.full_name as creator_name, a.account_holder_name as person_name, f.name as factory_name, p.name as product_name
    FROM transactions t
    LEFT JOIN users u ON t.created_by = u.id
    LEFT JOIN accounts a ON t.person_id = a.id
    LEFT JOIN factories f ON t.factory_id = f.id
    LEFT JOIN products p ON t.product_id = p.id
    WHERE 1=1
  `;
  const params = [];
  let index = 1;

  if (startDate) {
    queryText += ` AND t.created_at >= $${index}`;
    params.push(startDate);
    index++;
  }
  if (endDate) {
    queryText += ` AND t.created_at <= $${index}`;
    params.push(endDate);
    index++;
  }
  if (type) {
    queryText += ` AND t.transaction_type = $${index}`;
    params.push(type);
    index++;
  }
  if (personId) {
    queryText += ` AND t.person_id = $${index}`;
    params.push(personId);
    index++;
  }
  if (search) {
    queryText += ` AND (t.description ILIKE $${index} OR t.receipt_number ILIKE $${index})`;
    params.push(`%${search}%`);
    index++;
  }

  queryText += " ORDER BY t.created_at DESC";

  try {
    const result = await db.query(queryText, params);
    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error fetching transactions:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createTransaction = async (req, res) => {
  const {
    transaction_type,
    amount,
    currency,
    from_account,
    to_account,
    description,
    factory_id,
    product_id,
    person_id,
    status
  } = req.body;

  if (!transaction_type || !amount || !currency || !description) {
    return res.status(400).json({ error: 'Required fields are missing' });
  }

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    // Auto-generate receipt number
    const receiptNum = 'BD-TX-' + Date.now() + Math.floor(1000 + Math.random() * 9000);

    const insertQuery = `
      INSERT INTO transactions (
        transaction_type, amount, currency, from_account, to_account, description,
        factory_id, product_id, person_id, status, receipt_number, created_by
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
      RETURNING *
    `;
    const insertParams = [
      transaction_type, amount, currency, from_account, to_account, description,
      factory_id || null, product_id || null, person_id || null, status || 'completed',
      receiptNum, req.user.id
    ];

    const txRes = await client.query(insertQuery, insertParams);
    const newTx = txRes.rows[0];

    // If transaction status is completed and person_id is linked, adjust their account balance!
    if (newTx.status === 'completed' && person_id) {
      // Fetch account details to know its currency
      const accRes = await client.query("SELECT currency FROM accounts WHERE id = $1", [person_id]);
      if (accRes.rows.length > 0) {
        const accountCurrency = accRes.rows[0].currency;
        let finalAmount = parseFloat(amount);

        // If currencies mismatch, convert using exchange_rate
        if (currency !== accountCurrency) {
          const rate = parseFloat(req.body.exchange_rate) || 12800;
          if (currency === 'USD' && accountCurrency === 'UZS') {
            finalAmount = finalAmount * rate;
          } else if (currency === 'UZS' && accountCurrency === 'USD') {
            finalAmount = finalAmount / rate;
          }
        }

        // Determine if we should add or subtract based on transaction type
        const isIncome = [
          'cash_deposit', 'product_sale', 'factory_commission', 'factory_rental'
        ].includes(transaction_type);

        const changeAmount = isIncome ? finalAmount : -finalAmount;

        await client.query(
          "UPDATE accounts SET current_balance = current_balance + $1, updated_at = NOW() WHERE id = $2",
          [changeAmount, person_id]
        );
      }
    }

    // Adjust product quantity if it's product_sale or product_purchase
    if (newTx.status === 'completed' && product_id) {
      if (transaction_type === 'product_sale') {
        await client.query(
          "UPDATE products SET quantity_in_stock = quantity_in_stock - $1, updated_at = NOW() WHERE id = $2",
          [amount, product_id]
        );
      } else if (transaction_type === 'product_purchase') {
        await client.query(
          "UPDATE products SET quantity_in_stock = quantity_in_stock + $1, updated_at = NOW() WHERE id = $2",
          [amount, product_id]
        );
      }
    }

    await client.query('COMMIT');

    // Log Audit
    await logAudit(req.user.id, 'Created', newTx.id, newTx, req.ip);

    return res.status(201).json(newTx);
  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error creating transaction:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

exports.updateTransaction = async (req, res) => {
  const { id } = req.params;
  const { description, from_account, to_account, status } = req.body;

  try {
    const currentTxRes = await db.query("SELECT * FROM transactions WHERE id = $1", [id]);
    if (currentTxRes.rows.length === 0) {
      return res.status(404).json({ error: 'Transaction not found' });
    }

    const currentTx = currentTxRes.rows[0];

    const updateQuery = `
      UPDATE transactions 
      SET description = $1, from_account = $2, to_account = $3, status = $4, updated_at = NOW()
      WHERE id = $5
      RETURNING *
    `;
    const result = await db.query(updateQuery, [
      description || currentTx.description,
      from_account || currentTx.from_account,
      to_account || currentTx.to_account,
      status || currentTx.status,
      id
    ]);

    const updatedTx = result.rows[0];

    // Log audit
    await logAudit(req.user.id, 'Updated', id, { before: currentTx, after: updatedTx }, req.ip);

    return res.status(200).json(updatedTx);
  } catch (err) {
    console.error("Error updating transaction:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteTransaction = async (req, res) => {
  const { id } = req.params;

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const txRes = await client.query("SELECT * FROM transactions WHERE id = $1", [id]);
    if (txRes.rows.length === 0) {
      return res.status(404).json({ error: 'Transaction not found' });
    }

    const tx = txRes.rows[0];

    // If it was completed, roll back the balance and product stock adjustments!
    if (tx.status === 'completed') {
      if (tx.person_id) {
        const isIncome = [
          'cash_deposit', 'product_sale', 'factory_commission', 'factory_rental'
        ].includes(tx.transaction_type);

        const rollbackAmount = isIncome ? -tx.amount : tx.amount;

        await client.query(
          "UPDATE accounts SET current_balance = current_balance + $1, updated_at = NOW() WHERE id = $2",
          [rollbackAmount, tx.person_id]
        );
      }

      if (tx.product_id) {
        if (tx.transaction_type === 'product_sale') {
          await client.query(
            "UPDATE products SET quantity_in_stock = quantity_in_stock + $1, updated_at = NOW() WHERE id = $2",
            [tx.amount, tx.product_id]
          );
        } else if (tx.transaction_type === 'product_purchase') {
          await client.query(
            "UPDATE products SET quantity_in_stock = quantity_in_stock - $1, updated_at = NOW() WHERE id = $2",
            [tx.amount, tx.product_id]
          );
        }
      }
    }

    // We do soft delete by changing status to 'cancelled' or actually delete it. Let's delete it.
    await client.query("DELETE FROM transactions WHERE id = $1", [id]);

    await client.query('COMMIT');

    // Log audit
    await logAudit(req.user.id, 'Deleted', id, tx, req.ip);

    return res.status(200).json({ message: 'Transaction successfully deleted and rolled back.' });
  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Error deleting transaction:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  } finally {
    client.release();
  }
};

exports.exportTransactions = async (req, res) => {
  const { format, personId } = req.query; // 'excel' or 'pdf'

  try {
    let queryText = `
      SELECT t.*, a.account_holder_name as person_name, f.name as factory_name 
      FROM transactions t
      LEFT JOIN accounts a ON t.person_id = a.id
      LEFT JOIN factories f ON t.factory_id = f.id
    `;
    const params = [];
    if (personId) {
      queryText += ` WHERE t.person_id = $1`;
      params.push(personId);
    }
    queryText += ` ORDER BY t.created_at DESC`;

    const txRes = await db.query(queryText, params);
    const transactions = txRes.rows;

    if (format === 'excel') {
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet('Tranzaksiyalar');

      worksheet.columns = [
        { header: 'Kvitansiya #', key: 'receipt_number', width: 20 },
        { header: 'Turi', key: 'transaction_type', width: 20 },
        { header: 'Summa', key: 'amount', width: 15 },
        { header: 'Valyuta', key: 'currency', width: 10 },
        { header: 'Shaxs/Hisob', key: 'person_name', width: 25 },
        { header: 'Zavod', key: 'factory_name', width: 25 },
        { header: 'Tavsif', key: 'description', width: 35 },
        { header: 'Sana', key: 'created_at', width: 25 }
      ];

      transactions.forEach(t => {
        worksheet.addRow({
          receipt_number: t.receipt_number,
          transaction_type: t.transaction_type,
          amount: parseFloat(t.amount),
          currency: t.currency,
          person_name: t.person_name || '—',
          factory_name: t.factory_name || '—',
          description: t.description,
          created_at: new Date(t.created_at).toLocaleString()
        });
      });

      res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      res.setHeader('Content-Disposition', 'attachment; filename=transactions.xlsx');
      await workbook.xlsx.write(res);
      return res.end();

    } else if (format === 'pdf') {
      const doc = new PDFDocument({ margin: 30 });
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', 'attachment; filename=transactions.pdf');
      doc.pipe(res);

      doc.fontSize(18).text('Black Door - Tranzaksiyalar Jurnali', { align: 'center' });
      doc.moveDown();

      transactions.forEach(t => {
        doc.fontSize(10).text(
          `Kvitansiya: ${t.receipt_number || '—'} | Turi: ${t.transaction_type} | Summa: ${t.amount} ${t.currency}\n` +
          `Hisobdan/Kassadan: ${t.from_account || '—'} -> Kimgacha: ${t.to_account || '—'}\n` +
          `Sana: ${new Date(t.created_at).toLocaleString()} | Tavsif: ${t.description}\n` +
          `-------------------------------------------------------------------------`
        );
        doc.moveDown(0.5);
      });

      doc.end();
    } else {
      return res.status(400).json({ error: 'Invalid export format' });
    }

  } catch (err) {
    console.error("Error exporting transactions:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
