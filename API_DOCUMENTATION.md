# Black Door ERP — API Hujjatlari (API Documentation)

Barcha API so'rovlar uchun asosiy manzil (Base URL): `http://localhost:5000/api`

Barcha himoyalangan API endpoints uchun `Authorization: Bearer <accessToken>` headerini yuborish talab qilinadi.

---

## 1. Avtorizatsiya (Authentication API)

### 1.1 Google Login (Test & Real)
- **Endpoint:** `POST /auth/google-login`
- **Tavsif:** Google OAuth credentials orqali login qilish. Test maqsadlarida `mock-google-token-admin` yoki `mock-google-token-employee` yuborilishi mumkin.
- **Request Body:**
  ```json
  { "credential": "mock-google-token-admin" }
  ```
- **Response (2FA talab etilsa):**
  ```json
  {
    "require2FA": true,
    "tempToken": "JWT_TEMP_TOKEN_STRING"
  }
  ```

### 1.2 Telegram 2FA Havolasini Olish
- **Endpoint:** `POST /auth/telegram-request`
- **Headers:** `Authorization: Bearer <tempToken>`
- **Response:**
  ```json
  {
    "startLink": "https://t.me/blackdoor_2fa_bot?start=TOKEN_UUID",
    "mockCode": "123456"
  }
  ```

### 1.3 Telegram 2FA Kodini Tasdiqlash
- **Endpoint:** `POST /auth/telegram-verify`
- **Headers:** `Authorization: Bearer <tempToken>`
- **Request Body:**
  ```json
  { "code": "123456" }
  ```
- **Response:**
  ```json
  {
    "accessToken": "JWT_ACCESS_TOKEN",
    "refreshToken": "JWT_REFRESH_TOKEN",
    "user": { "id": "UUID", "email": "admin@blackdoor.uz", "role": "admin", "fullName": "Super Admin" }
  }
  ```

---

## 2. Tranzaksiyalar (Transactions API)

### 2.1 Tranzaksiyalar Ro'yxatini Olish
- **Endpoint:** `GET /admin/transactions`
- **Query Parametrlari:** `startDate`, `endDate`, `type`, `personId`, `search`
- **Response:**
  ```json
  [
    {
      "id": "UUID",
      "transaction_type": "factory_rental",
      "amount": "1200.00",
      "currency": "USD",
      "description": "Zavod iyul oyi ijara haqi",
      "receipt_number": "BD-TX-17180000000",
      "created_at": "2026-08-27T07:45:00.000Z"
    }
  ]
  ```

### 2.2 Tranzaksiya Yaratish
- **Endpoint:** `POST /admin/transactions`
- **Request Body:**
  ```json
  {
    "transaction_type": "cash_deposit",
    "amount": 2500,
    "currency": "USD",
    "from_account": "Client A",
    "to_account": "Kassa 1",
    "description": "Prepayment for order 4",
    "person_id": "UUID_OF_ACCOUNT"
  }
  ```

---

## 3. Kassalar & Shaxslar (Accounts API)

### 3.1 Kassalarni Olish
- **Endpoint:** `GET /admin/accounts`
- **Response:**
  ```json
  [
    {
      "id": "UUID",
      "account_holder_name": "Asosiy USD Kassasi",
      "account_number": "ACC-USD-001",
      "current_balance": "85000.00",
      "currency": "USD",
      "account_type": "company"
    }
  ]
  ```

### 3.2 Balansni Qo'lda To'g'rilash (Manual Adjustment)
- **Endpoint:** `POST /admin/accounts/:id/adjust-balance`
- **Request Body:**
  ```json
  {
    "adjustmentAmount": -500,
    "description": "Oylik yopilishda kamomad tuzatildi"
  }
  ```

---

## 4. Omborxona (Warehouse API)

### 4.1 Qabul qilish (Receive)
- **Endpoint:** `POST /api/warehouse/employee/receive`
- **Request Body:**
  ```json
  {
    "product_id": "PRODUCT_UUID",
    "quantity": 100,
    "to_location": "Sektor A, Polka 2",
    "notes": "Yangiyo'l zavodidan yuk keldi"
  }
  ```

### 4.2 Chiqim / Jo'natish (Dispatch)
- **Endpoint:** `POST /api/warehouse/employee/dispatch`
- **Request Body:**
  ```json
  {
    "product_id": "PRODUCT_UUID",
    "quantity": 40,
    "from_location": "Sektor A, Polka 2",
    "notes": "Qurilish Obyekti 1 ga jo'natish"
  }
  ```

---

## 5. Hisobotlar (Reports API)

### 5.1 Kunlik Hisobot
- **Endpoint:** `GET /admin/reports/daily`
- **Response:**
  ```json
  {
    "date": "2026-08-27",
    "count": 4,
    "summaries": [
      { "currency": "USD", "total_income": 3500.00, "total_expense": 1200.00 }
    ],
    "transactions": [...]
  }
  ```
