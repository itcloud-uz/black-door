-- Default seed data for Black Door ERP

-- Hash for 'password123'
-- $2b$10$R9hGcEXuiM2A1pQ4cI3H1edV4.XpM/gTz2W.h9Xv9j8sW5Fm1mOpe

INSERT INTO users (id, email, telegram_id, telegram_username, full_name, role, password_hash, is_telegram_verified, is_email_verified)
VALUES 
('d506d862-4217-48f8-a1be-715a9ffbe60f', 'admin@blackdoor.uz', '123456789', 'bd_admin', 'Sardor Abdullayev', 'admin', '$2b$10$R9hGcEXuiM2A1pQ4cI3H1edV4.XpM/gTz2W.h9Xv9j8sW5Fm1mOpe', true, true),
('e9d7c35d-ef6e-4148-be58-bc4a2e2d83b1', 'employee@blackdoor.uz', '987654321', 'bd_employee', 'Oybek Aliyev', 'employee', '$2b$10$R9hGcEXuiM2A1pQ4cI3H1edV4.XpM/gTz2W.h9Xv9j8sW5Fm1mOpe', true, true);

-- Add accounts (for persons, companies, factories)
INSERT INTO accounts (id, account_type, account_holder_name, account_number, phone, email, current_balance, currency, created_by)
VALUES
('a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d', 'company', 'Asosiy Kassa USD', 'ACC-USD-001', '+998901234567', 'cash@blackdoor.uz', 50000.00, 'USD', 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('b2c3d4e5-f6a7-8b9c-0d1e-2f3a4b5c6d7e', 'company', 'Asosiy Kassa UZS', 'ACC-UZS-001', '+998901234567', 'cash@blackdoor.uz', 150000000.00, 'UZS', 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f', 'person', 'Akmal Zokirov (Hamkor)', 'ACC-PR-001', '+998907654321', 'akmal@gmail.com', -1200.00, 'USD', 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('d4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9a', 'person', 'Barno Turgunova (Mijoz)', 'ACC-PR-002', '+998908889900', 'barno@gmail.com', 4500000.00, 'UZS', 'd506d862-4217-48f8-a1be-715a9ffbe60f');

-- Add factories
INSERT INTO factories (id, name, address, phone, manager_name, equipment_type, rental_rate_per_day, production_commission_percent, is_active, created_by)
VALUES
('f1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 'Toshkent Temir Zavodi', 'Toshkent, Sergeli d-1', '+998712345678', 'Jamshid Toshmatov', 'Pech va Qoliplash uskunalari', 250.00, 5.00, true, 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('f2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d', 'Samarqand Oyna Zavodi', 'Samarqand, Urgut Sanoat zonasi', '+998669876543', 'Bekzod Rahimov', 'Shisha kesish va quyish uskunalari', 180.00, 3.50, true, 'd506d862-4217-48f8-a1be-715a9ffbe60f');

-- Add products
INSERT INTO products (id, name, description, unit_type, base_price, cost_price, quantity_in_stock, category, manufacturer, is_active, created_by)
VALUES
('e1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 'Armatura 12mm', 'Yuqori chidamli qurilish armaturasi', 'ton', 850.00, 680.00, 24.50, 'Temir buyumlar', 'Toshkent Temir Zavodi', true, 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('e2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d', 'Oyna varag''i 4mm', 'Binolar uchun shaffof oyna', 'meter', 12.00, 8.50, 450.00, 'Shisha mahsulotlari', 'Samarqand Oyna Zavodi', true, 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('e3c4d5e6-f7a8-9b0c-1d2e-3f4a5b6c7d8e', 'Sement M500', 'Yuqori sifatli quruq sement qorishmasi', 'box', 6.50, 4.80, 1200.00, 'Qurilish materiallari', 'Qizilqum Sement', true, 'd506d862-4217-48f8-a1be-715a9ffbe60f');

-- Add relationships factory_products
INSERT INTO factory_products (factory_id, product_id, production_capacity_per_day, production_status)
VALUES
('f1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 'e1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 10.00, 'active'),
('f2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d', 'e2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d', 500.00, 'active');

-- Add warehouse inventory
INSERT INTO warehouse_inventory (product_id, quantity, location)
VALUES
('e1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 24.50, 'A-sektor, 3-tokcha'),
('e2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d', 450.00, 'B-sektor, 12-tokcha'),
('e3c4d5e6-f7a8-9b0c-1d2e-3f4a5b6c7d8e', 1200.00, 'C-sektor, Zamin');

-- Add transactions
INSERT INTO transactions (transaction_type, amount, currency, from_account, to_account, description, factory_id, product_id, person_id, status, receipt_number, created_by)
VALUES
('factory_rental', 250.00, 'USD', 'Toshkent Temir Zavodi', 'ACC-USD-001', 'Zavod kunlik ijara to''lovi', 'f1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', null, null, 'completed', 'REC-2026-0001', 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('product_sale', 4250.00, 'USD', 'ACC-PR-001', 'ACC-USD-001', '5 tonna Armatura sotilishi', 'f1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 'e1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f', 'completed', 'REC-2026-0002', 'd506d862-4217-48f8-a1be-715a9ffbe60f'),
('domestic_payment', 1500000.00, 'UZS', 'ACC-UZS-001', 'ACC-PR-002', 'Qurilish tovarlari uchun to''lov', null, null, 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9a', 'completed', 'REC-2026-0003', 'd506d862-4217-48f8-a1be-715a9ffbe60f');

-- Add warehouse operations
INSERT INTO warehouse_operations (operation_type, product_id, quantity, from_location, to_location, notes, performed_by)
VALUES
('receive', 'e1a2b3c4-d5e6-7f8a-9b0c-1d2e3f4a5b6c', 10.00, 'Zavod sexi', 'A-sektor, 3-tokcha', 'Zavoddan qabul qilindi', 'e9d7c35d-ef6e-4148-be58-bc4a2e2d83b1'),
('dispatch', 'e2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d', 100.00, 'B-sektor, 12-tokcha', 'Xaridor yuk mashinasi', 'Buyurtmaga asosan yuklandi', 'e9d7c35d-ef6e-4148-be58-bc4a2e2d83b1');
