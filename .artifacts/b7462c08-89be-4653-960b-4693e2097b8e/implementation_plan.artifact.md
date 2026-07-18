# Black Door — Loyihani to'liq ishga tushirish rejasi

Ushbu reja loyihaning barcha qismlarini (Backend, Hisobot xizmati va WebSocket) ishga tushirishni ko'zda tutadi.

## Foydalanuvchi ko'rib chiqishi kerak bo'lgan bandlar

> [!IMPORTANT]
> Loyihani ishga tushirish uchun PHP va Python muhitlari tayyor bo'lishi kerak. Biz fon rejimida serverlarni ishga tushiramiz.

## Taklif etilayotgan o'zgarishlar

Loyihani ishga tushirish uchun quyidagi xizmatlar fon rejimida yoqiladi:

### 1. Backend (Laravel)
- **Buyruq:** `php artisan serve`
- **Port:** `8000`
- **Vazifasi:** Asosiy ERP tizimi va API.

### 2. Hisobot xizmati (Python FastAPI)
- **Buyruq:** `services/reports/.venv/Scripts/python -m uvicorn main:app --port 8001`
- **Port:** `8001`
- **Vazifasi:** Excel va PDF hisobotlarini yaratish.

### 3. WebSocket xizmati (Laravel Reverb)
- **Buyruq:** `php artisan reverb:start`
- **Port:** `8080`
- **Vazifasi:** Real-vaqtda ma'lumotlarni yangilash.

### 4. Navbatlar (Queue Worker)
- **Buyruq:** `php artisan queue:work`
- **Vazifasi:** Fon vazifalarini bajarish (masalan, hisobotlarni qayta ishlash).

## Tekshirish rejasi

### Avtomatlashtirilgan tekshiruvlar
- Portlar ochiqligini tekshirish (`8000`, `8001`, `8080`).
- `/health` endpointlari orqali xizmatlar holatini tekshirish.

### Qo'lda tekshirish
- Brauzerda `http://localhost:8000` manziliga kirib ko'rish.
