-- PostgreSQL Schema for Black Door ERP

-- Enable UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS audit_log CASCADE;
DROP TABLE IF EXISTS warehouse_operations CASCADE;
DROP TABLE IF EXISTS warehouse_inventory CASCADE;
DROP TABLE IF EXISTS factory_products CASCADE;
DROP TABLE IF EXISTS transactions CASCADE;
DROP TABLE IF EXISTS accounts CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS factories CASCADE;
DROP TABLE IF EXISTS verification_tokens CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Drop custom types if they exist (for clean setup)
DROP TYPE IF EXISTS user_role CASCADE;
DROP TYPE IF EXISTS verification_type CASCADE;
DROP TYPE IF EXISTS unit_type CASCADE;
DROP TYPE IF EXISTS transaction_type CASCADE;
DROP TYPE IF EXISTS currency_type CASCADE;
DROP TYPE IF EXISTS account_type CASCADE;
DROP TYPE IF EXISTS account_status CASCADE;
DROP TYPE IF EXISTS production_status CASCADE;
DROP TYPE IF EXISTS operation_type CASCADE;

-- Create custom types (enums)
CREATE TYPE user_role AS ENUM ('admin', 'employee');
CREATE TYPE verification_type AS ENUM ('telegram', 'email');
CREATE TYPE unit_type AS ENUM ('kg', 'dona', 'meter', 'liter', 'box', 'piece', 'gram', 'ton');
CREATE TYPE transaction_type AS ENUM (
    'factory_rental', 
    'factory_commission', 
    'foreign_payment', 
    'domestic_payment', 
    'personal_withdrawal', 
    'cash_deposit', 
    'product_sale', 
    'product_purchase', 
    'inventory_adjustment'
);
CREATE TYPE currency_type AS ENUM ('UZS', 'USD', 'EUR', 'RUB');
CREATE TYPE account_type AS ENUM ('person', 'factory', 'company');
CREATE TYPE account_status AS ENUM ('active', 'inactive', 'suspended');
CREATE TYPE production_status AS ENUM ('active', 'stopped', 'maintenance');
CREATE TYPE operation_type AS ENUM ('receive', 'dispatch', 'transfer', 'count', 'damage_report');

-- 1. users Table
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    telegram_id VARCHAR(255) UNIQUE,
    telegram_username VARCHAR(255),
    full_name VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'employee',
    password_hash VARCHAR(255),
    is_telegram_verified BOOLEAN DEFAULT FALSE,
    is_email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP WITH TIME ZONE
);

-- 2. verification_tokens Table
CREATE TABLE IF NOT EXISTS verification_tokens (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    type verification_type NOT NULL,
    telegram_code VARCHAR(6),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. factories Table
CREATE TABLE IF NOT EXISTS factories (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    manager_name VARCHAR(255) NOT NULL,
    equipment_type VARCHAR(255) NOT NULL,
    rental_rate_per_day DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    production_commission_percent DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. products Table
CREATE TABLE IF NOT EXISTS products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit_type unit_type NOT NULL,
    base_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    cost_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    quantity_in_stock DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    category VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. accounts Table (Shaxslar va ularning hisob-kitoblari)
CREATE TABLE IF NOT EXISTS accounts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    account_type account_type NOT NULL DEFAULT 'person',
    account_holder_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(255),
    email VARCHAR(255),
    current_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    currency currency_type NOT NULL DEFAULT 'UZS',
    account_status account_status NOT NULL DEFAULT 'active',
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    transaction_type transaction_type NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency currency_type NOT NULL DEFAULT 'UZS',
    from_account VARCHAR(255),
    to_account VARCHAR(255),
    description VARCHAR(500) NOT NULL,
    factory_id UUID REFERENCES factories(id) ON DELETE SET NULL,
    product_id UUID REFERENCES products(id) ON DELETE SET NULL,
    person_id UUID REFERENCES accounts(id) ON DELETE SET NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- 'pending', 'completed', 'failed', 'cancelled'
    receipt_number VARCHAR(255) UNIQUE,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 7. factory_products Table
CREATE TABLE IF NOT EXISTS factory_products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    factory_id UUID NOT NULL REFERENCES factories(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    production_capacity_per_day DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    production_status production_status NOT NULL DEFAULT 'active',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(factory_id, product_id)
);

-- 8. warehouse_inventory Table
CREATE TABLE IF NOT EXISTS warehouse_inventory (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    product_id UUID NOT NULL UNIQUE REFERENCES products(id) ON DELETE CASCADE,
    quantity DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    location VARCHAR(255) NOT NULL,
    last_counted_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 9. warehouse_operations Table
CREATE TABLE IF NOT EXISTS warehouse_operations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    operation_type operation_type NOT NULL,
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity DECIMAL(15, 2) NOT NULL,
    from_location VARCHAR(255),
    to_location VARCHAR(255),
    notes TEXT,
    performed_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 10. audit_log Table
CREATE TABLE IF NOT EXISTS audit_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(255) NOT NULL,
    entity_id VARCHAR(255) NOT NULL,
    changes JSONB,
    ip_address VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indices for Performance Optimization
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_telegram_id ON users(telegram_id);
CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(transaction_type);
CREATE INDEX IF NOT EXISTS idx_transactions_created_at ON transactions(created_at);
CREATE INDEX IF NOT EXISTS idx_accounts_holder ON accounts(account_holder_name);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_warehouse_operations_product ON warehouse_operations(product_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_user ON audit_log(user_id);

-- Trigger for Auto-Updating updated_at field
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_modtime BEFORE UPDATE ON users FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
CREATE TRIGGER update_factories_modtime BEFORE UPDATE ON factories FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
CREATE TRIGGER update_products_modtime BEFORE UPDATE ON products FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
CREATE TRIGGER update_accounts_modtime BEFORE UPDATE ON accounts FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
CREATE TRIGGER update_transactions_modtime BEFORE UPDATE ON transactions FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
CREATE TRIGGER update_factory_products_modtime BEFORE UPDATE ON factory_products FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
CREATE TRIGGER update_warehouse_inventory_modtime BEFORE UPDATE ON warehouse_inventory FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();
