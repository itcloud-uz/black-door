# Mobil ilovani to'liq ma'lumotlar bilan to'ldirish va UI xatoliklarini tuzatish rejasi

Ushbu reja mobil ilovadagi barcha bo'sh bo'limlarni (Admin, Moliya, Menejer) real ma'lumotlar bilan to'ldirish, qizil xatolikni (BoxDecoration error) bartaraf etish va brendingni mukammallashtirishni ko'zda tutadi.

## Foydalanuvchi ko'rib chiqishi kerak bo'lgan bandlar

> [!IMPORTANT]
> Ilovadagi "Tez orada..." yozuvlari olib tashlanadi va backend API orqali real ma'lumotlar (Foydalanuvchilar, Obyektlar, Tranzaksiyalar) yuklanadi.
> Brending (Logo) foydalanuvchi taqdim etgan rasmga (ark va kalit teshigi) to'liq moslashtiriladi.

## Taklif etilayotgan o'zgarishlar

### 1. UI Xatoliklarini bartaraf etish (Red Screen Fix)
- `InsetBoxDecoration` va Flutter'ning `BoxDecoration` turlari o'rtasidagi ziddiyatni butunlay hal qilish uchun barcha neumorfik konteynerlar yagona turga o'tkaziladi.
- `AnimatedContainer` ichidagi `decoration` transitionlari uchun `null` qiymatlar xavfsiz holatga keltiriladi.

### 2. Admin Paneli (To'liq implementatsiya)
- **Foydalanuvchilar ro'yxati**: `/admin/users` API orqali barcha xodimlarni ko'rish, qidirish va holatini (faol/nofaol) o'zgartirish.
- **Obyektlar ro'yxati**: `/admin/objects` orqali zavod, ombor va qurilish maydonlarini boshqarish.
- **Sozlamalar**: Valyuta kursini o'zgartirish va Audit jurnallarini ko'rish imkoniyati.

### 3. Moliya Paneli (To'liq implementatsiya)
- **Hisobotlar**: Kirim va chiqimlar bo'yicha tahliliy grafiklarni (fl_chart) va umumiy statistikalarni yuklash.
- **Kontragentlar**: Hamkorlar bilan hisob-kitoblar va qarzlar holatini batafsil ko'rish.

### 4. Menejer Paneli
- **Obyekt tranzaksiyalari**: Obyekt kassa harakatlarini (ish haqi to'lovi, sarf-xarajatlar) alohida bo'limda ko'rish.
- **Ombor zahiralari**: Kam qolgan mahsulotlar uchun ogohlantirishlar va to'liq inventarizatsiya holati.

### 5. Brending (Logo)
- `NeumorphicLogo` widgetini foydalanuvchi yuborgan rasmga (Soft White Arch + Glowing Keyhole) 100% o'xshash qilib qayta yozish.

## Tekshirish rejasi

### Qo'lda tekshirish
- Har bir tab (Foydalanuvchilar, Obyektlar, Tranzaksiyalar) ma'lumot yuklayotganini tasdiqlash.
- Splash screen va Login ekranida yangi logoni ko'rish.
- `Logout` tugmasi barcha panellarda to'g'ri ishlayotganini tekshirish.
