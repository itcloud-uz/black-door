# Black Door ERP — Moliyaviy Buxgalteriya & Zavod Boshqaruvi Tizimi

**Black Door** — bu ko'p obyektli korxonalar (zavodlar, omborxonalar, shaxslar) uchun maxsus ishlab chiqilgan, ikki valyutada (UZS va USD) moliyaviy buxgalteriya, materiallar hisobi va operatsiyalar logini yurituvchi veb-tizim. Tizim interfeysi zamonaviy **Neumorphic (Soft UI / yumshoq skeuomorfizm)** uslubiga ega.

---

## 🎨 Texnologik Stack (Tech Stack)

- **Backend:** Node.js (v18+) / Express.js
- **Frontend:** React (Vite, Tailwind CSS, Lucide Icons, Axios)
- **Database:** PostgreSQL (v14+)
- **Integration:** Telegram Bot API (Telegraf) & Google OAuth 2.0
- **Orchestration:** Docker & Docker Compose

---

## ⚙️ Tizimni Ishga Tushirish (Quick Start via Docker)

Tizim to'liq dockerizatsiya qilingan. Uni birgina buyruq bilan ishga tushirishingiz mumkin.

### Prerevizitlar:
- Docker va Docker Compose o'rnatilgan bo'lishi kerak.

### Ishga tushirish qadamlari:

1. Loyiha papkasiga o'ting:
   ```bash
   cd D:\Loyihalar\Black-door
   ```

2. Docker konteynerlarni quring va ishga tushiring:
   ```bash
   docker-compose up --build
   ```

3. Portlar taqsimoti:
   - **React Frontend:** `http://localhost:3000`
   - **Express API Backend:** `http://localhost:5000`
   - **PostgreSQL Database:** `http://localhost:5432`

*Eslatma: PostgreSQL ishga tushganda `database/schema.sql` va `database/seed.sql` fayllari yordamida bazani avtomatik qurib, namunaviy demo ma'lumotlar bilan to'ldiradi.*

---

## 🔑 Demo / Test Rejimida Ishlatish (Demo Credentials)

Tizimda sinovdan o'tkazish uchun tayyor foydalanuvchilar mavjud. Tizimga kirishda Google login o'rniga demo tugmalarni bosishingiz yoki mock tokenlardan foydalanishingiz mumkin:

| Rol | Kirish Emaili | Demo Google Token | Telegram 2FA |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@blackdoor.uz` | `mock-google-token-admin` | Telegram kod yuboriladi yoki mock kod |
| **Xodim / Omborchi** | `employee@blackdoor.uz` | `mock-google-token-employee` | Telegram kod yuboriladi yoki mock kod |

*Hamma foydalanuvchilar uchun mock 2FA kodi avtomatik to'ldirish yoki ekranda ko'rsatilgan mock kodi yordamida taqdim etiladi.*

---

## 📂 Loyiha Tuzilishi (Directory Structure)

```text
/Black-door
│
├── /backend                 # Node.js Express server
│   ├── /controllers         # Controller biznes logikasi
│   ├── /middleware          # JWT va ruxsatnomalar middleware
│   ├── /routes              # Marshrutlar (auth, transactions, reports ...)
│   ├── /services            # telegramBot.js 2FA xizmati
│   ├── db.js                # PostgreSQL pool wrapper
│   ├── Dockerfile
│   ├── package.json
│   └── server.js
│
├── /frontend                # React SPA client
│   ├── /src
│   │   ├── /components      # Admin va Employee modular subcomponents
│   │   ├── /pages           # Login, AdminDashboard, EmployeeDashboard
│   │   ├── /services        # api.js (Axios)
│   │   ├── App.jsx          # Yo'naltirish (Router)
│   │   ├── index.css        # Soft UI (Neumorphic) stillari
│   │   └── main.jsx
│   ├── Dockerfile
│   ├── package.json
│   └── vite.config.js
│
├── /database                # Baza fayllari
│   ├── schema.sql           # PostgreSQL DDL
│   └── seed.sql             # Namunaviy seederlar
│
├── docker-compose.yml       # Konteynerlar orkestratsiyasi
├── ARCHITECTURE.md          # Tizim arxitekturasi bayoni
└── API_DOCUMENTATION.md     # Barcha REST API kontraktlari
```

---

## 📄 Hujjatlar (Documentation Links)

1. [Tizim Arxitekturasi (ARCHITECTURE.md)](file:///d:/Loyihalar/Black-door/ARCHITECTURE.md)
2. [REST API Hujjatlari (API_DOCUMENTATION.md)](file:///d:/Loyihalar/Black-door/API_DOCUMENTATION.md)
