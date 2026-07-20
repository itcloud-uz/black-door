# Black Door Control — Markaziy Sotuv va Litsenziyalash Tizimi Yo'riqnomasi

Ushbu loyiha tarkibida ikkita tizim integratsiya qilingan:
1. **Black Door Control** — Markaziy litsenziya sotish va boshqarish platformasi (Port: `9000`).
2. **License Client** — Black Door ilovasining o'zida ishlaydigan litsenziya tekshiruvi moduli (Port: `8000`).

---

## 1. DOCKER STACK ORQALI ISHGA TUSHIRISH

Ikkala tizim ham yagona Docker compose konfiguratsiyasida izolyatsiya qilingan muhitda ko'tariladi:

```bash
# 1. Konteynerlarni yig'ish va ishga tushirish
docker-compose up -d --build

# 2. Control platformasi ma'lumotlar bazasini tayyorlash (Port 5433 dagi postgres)
docker-compose exec control-app php artisan migrate --seed

# 3. Client ma'lumotlar bazasini tayyorlash (Port 5432 dagi postgres)
docker-compose exec app php artisan migrate --seed
```

### Konteynerlar tuzilishi:
* `blackdoor-control-nginx` (Port `9000`): Markaziy sotuv boshqaruv paneli va arizalar portali.
* `blackdoor-nginx` (Port `8000`): Mijozning asosiy Black Door tizimi.
* `blackdoor-control-postgres` (Port `5433`): Control platformasi bazasi.
* `blackdoor-postgres` (Port `5432`): Mijoz tizimi bazasi.

---

## 2. LITSENZIYALASH ARXITEKTURASI VA XAVFSIZLIK

* **Asimmetrik kriptografiya (RSA 2048-bit):** Control platformasi litsenziya tokenini o'zining **Private Key** si yordamida imzolaydi. Client faqat **Public Key** ga ega bo'lib, imzoni offlayn tekshiradi.
* **Kalitlar boshqaruvi:** Kalitlar `storage/app/control_private_key.pem` va `control_public_key.pem` da saqlanadi. Agar kalitlar mavjud bo'lmasa, tizim avtomatik ravishda xavfsiz kalitlar juftligini yaratadi.
* **Oflayn Grace Period:** Tarmoq uzilganda mijoz dasturi to'xtamaydi. 7 kun davomida heartbeat bo'lmasa, ogohlantirish beradi, lekin ishlashda davom etadi.
* **Muddati tugash rejimlar:**
  * Muddat tugagach 7 kunlik **Faqat O'qish (Read-only)** grace-period boshlanami (POST/PUT/DELETE amallari bloklanadi).
  * 7 kundan keyin tizim to'liq qulflanib, faollashtirish sahifasiga otadi.

---

## 3. REAL TEST STSENARIYLARINI SINOVDAN O'TKAZISH

Barcha 7 bosqichli xavfsizlik sinovlarini avtomatlashtirilgan holda ishga tushirish:

```bash
# Litsenziyalash testlarini ishga tushirish
php artisan test --filter=LicenseActivationTest
```

### Test qamrovi:
1. **Litsenziya faollashishi (Activation):** Kalit kiritilganda serverga bog'lanib, imzolangan token olish va saqlash.
2. **Oflayn ishlash (Offline check):** Tarmoqsiz ham tokenning asimmetrik imzosini tekshirish.
3. **Grace period & Limitlar (Limits):** Foydalanuvchilar va obyektlar soni limitdan oshishini taqiqlash.
4. **Feature flags:** Hisobotlar va mobil API litsenziyada o'chirilgan bo'lsa, ularga kirishni to'liq cheklash.
5. **Read-only holati:** Muddati o'tganda ma'lumotlarni o'zgartirishni bloklash.
6. **Masofadan bloklash (Suspended):** Heartbeat orqali tizim bloklanganda darhol qulflanish.
