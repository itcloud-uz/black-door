const db = require('../db');

exports.getDailyReport = async (req, res) => {
  try {
    const today = new Date().toISOString().split('T')[0];
    
    // Transactions today
    const txRes = await db.query(
      `SELECT t.*, a.account_holder_name as person_name 
       FROM transactions t 
       LEFT JOIN accounts a ON t.person_id = a.id
       WHERE DATE(t.created_at) = $1`,
      [today]
    );

    // Sum income/expenses grouped by currency
    const sumsRes = await db.query(
      `SELECT currency, 
              SUM(CASE WHEN transaction_type IN ('cash_deposit', 'product_sale', 'factory_rental', 'factory_commission') THEN amount ELSE 0 END) as total_income,
              SUM(CASE WHEN transaction_type IN ('personal_withdrawal', 'product_purchase', 'domestic_payment', 'foreign_payment') THEN amount ELSE 0 END) as total_expense
       FROM transactions
       WHERE DATE(created_at) = $1 AND status = 'completed'
       GROUP BY currency`,
      [today]
    );

    return res.status(200).json({
      date: today,
      count: txRes.rows.length,
      summaries: sumsRes.rows,
      transactions: txRes.rows
    });

  } catch (err) {
    console.error("Error getting daily report:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getMonthlyReport = async (req, res) => {
  try {
    const startOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString();
    const endOfMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0, 23, 59, 59).toISOString();

    const sumsRes = await db.query(
      `SELECT currency, 
              SUM(CASE WHEN transaction_type IN ('cash_deposit', 'product_sale', 'factory_rental', 'factory_commission') THEN amount ELSE 0 END) as total_income,
              SUM(CASE WHEN transaction_type IN ('personal_withdrawal', 'product_purchase', 'domestic_payment', 'foreign_payment') THEN amount ELSE 0 END) as total_expense
       FROM transactions
       WHERE created_at >= $1 AND created_at <= $2 AND status = 'completed'
       GROUP BY currency`,
      [startOfMonth, endOfMonth]
    );

    const categoryBreakdown = await db.query(
      `SELECT transaction_type, currency, SUM(amount) as total_amount, COUNT(*) as tx_count
       FROM transactions
       WHERE created_at >= $1 AND created_at <= $2 AND status = 'completed'
       GROUP BY transaction_type, currency
       ORDER BY total_amount DESC`,
      [startOfMonth, endOfMonth]
    );

    return res.status(200).json({
      period: 'Monthly',
      start: startOfMonth.split('T')[0],
      end: endOfMonth.split('T')[0],
      summaries: sumsRes.rows,
      categories: categoryBreakdown.rows
    });

  } catch (err) {
    console.error("Error getting monthly report:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getFactoryProductivity = async (req, res) => {
  try {
    const result = await db.query(
      `SELECT f.id as factory_id, f.name as factory_name, f.manager_name,
              COUNT(fp.id) as products_count,
              SUM(fp.production_capacity_per_day) as total_capacity_per_day
       FROM factories f
       LEFT JOIN factory_products fp ON f.id = fp.factory_id
       GROUP BY f.id, f.name, f.manager_name
       ORDER BY total_capacity_per_day DESC NULLS LAST`
    );

    return res.status(200).json(result.rows);
  } catch (err) {
    console.error("Error getting factory productivity:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getBalanceSheet = async (req, res) => {
  try {
    // Current Balances in Company Cash Accounts
    const cashRes = await db.query(
      `SELECT currency, SUM(current_balance) as cash_balance
       FROM accounts
       WHERE account_type = 'company'
       GROUP BY currency`
    );

    // Outstanding balances from partners/persons
    const partnerRes = await db.query(
      `SELECT currency, 
              SUM(CASE WHEN current_balance > 0 THEN current_balance ELSE 0 END) as client_prepayments,
              SUM(CASE WHEN current_balance < 0 THEN current_balance ELSE 0 END) as client_debts
       FROM accounts
       WHERE account_type = 'person'
       GROUP BY currency`
    );

    // Total products valuation in inventory
    const productsRes = await db.query(
      `SELECT SUM(quantity_in_stock * cost_price) as inventory_valuation_cost,
              SUM(quantity_in_stock * base_price) as inventory_valuation_sale
       FROM products`
    );

    return res.status(200).json({
      cashBalances: cashRes.rows,
      partnerBalances: partnerRes.rows,
      inventoryValuation: productsRes.rows[0]
    });

  } catch (err) {
    console.error("Error getting balance sheet:", err);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
};
