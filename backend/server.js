const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const rateLimit = require('express-rate-limit');
const db = require('./db');
require('dotenv').config();

// Start Telegram 2FA Bot Service
require('./services/telegramBot');

// Route Modules
const authRoutes = require('./routes/auth');
const transactionRoutes = require('./routes/transactions');
const accountRoutes = require('./routes/accounts');
const factoryRoutes = require('./routes/factories');
const productRoutes = require('./routes/products');
const warehouseRoutes = require('./routes/warehouse');
const reportRoutes = require('./routes/reports');
const auditRoutes = require('./routes/audit');
const userRoutes = require('./routes/users');

const app = express();
app.set('trust proxy', true);
const PORT = process.env.PORT || 5000;

// Enable CORS
app.use(cors({
  origin: '*',
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));

// Parsing json body and logging
app.use(express.json());
app.use(morgan('dev'));

// Rate limiting on API endpoints to prevent brute-force attacks
const apiLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 300,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: 'Too many requests from this IP. Please try again after 15 minutes.' }
});
app.use('/api/', apiLimiter);

// Test database connection
db.query('SELECT NOW()')
  .then((res) => console.log('PostgreSQL database successfully connected at:', res.rows[0].now))
  .catch((err) => console.error('Database connection error during startup:', err));

// ==========================================
// MOUNT MODULAR ROUTES
// ==========================================
app.use('/api/auth', authRoutes);
app.use('/api/admin/transactions', transactionRoutes);
app.use('/api/admin/accounts', accountRoutes);
app.use('/api/admin/factories', factoryRoutes);
app.use('/api/admin/products', productRoutes);
app.use('/api/warehouse', warehouseRoutes);
app.use('/api/admin/reports', reportRoutes);
app.use('/api/admin/audit-log', auditRoutes);
app.use('/api/admin/users', userRoutes);

// Global Error Handler
app.use((err, req, res, next) => {
  console.error("Unhandled API Server Error:", err.stack);
  res.status(500).json({ error: 'Internal Server Error' });
});

// Start Server
app.listen(PORT, () => {
  console.log(`Black Door backend server successfully running on port ${PORT}`);
});
