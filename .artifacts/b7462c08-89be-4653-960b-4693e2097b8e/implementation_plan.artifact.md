# Mobil ilovani Web App darajasiga keltirish rejasi

Ushbu reja mobil ilovaning funksionalligini Web App bilan 1:1 darajaga keltirishni ko'zda tutadi. Hozirda ilovada faqat ko'rish (view) imkoniyati mavjud, biz esa ma'lumotlarni yaratish, tahrirlash va boshqarish funksiyalarini qo'shamiz.

## Foydalanuvchi ko'rib chiqishi kerak bo'lgan bandlar

> [!IMPORTANT]
> Barcha amallar uchun tegishli API endpointlar backendda tekshirildi va kerakli qo'shimchalar (masalan, oylik to'lovi API) kiritildi.

## Taklif etilayotgan o'zgarishlar

### 1. Super Admin Paneli
- **Foydalanuvchilar**: Yangi xodim qo'shish va mavjudlarini tahrirlash (ism, telefon, rol, parol, PIN) formasi.
- **Obyektlar**: Zavod, ombor yoki qurilish maydonlarini yaratish va ularga menejer biriktirish.
- **Valyuta**: Markaziy bank kursini avtomatik tortish va tizim kursini qo'lda yangilash.
- **Audit**: To'liq filtrlanadigan tizim harakatlari jurnali.

### 2. Moliya Paneli
- **Kontragentlar**: Hamkorlarni qo'shish, ularning balansini va tranzaksiyalar tarixini alohida ekranda ko'rish.
- **Tranzaksiyalar**: Mavjud tranzaksiyalarni bekor qilish (Storno) funksiyasi.
- **Hisobotlar**: Excel/PDF formatda eksport qilish tugmalari va batafsil grafiklar.

### 3. Obyekt Menejeri Paneli
- **Xodimlar**: Obyektga biriktirilgan xodimlarni boshqarish va ularga **oylik/avans to'lash** (mini-kassadan).
- **Ombor**: Mahsulotlar kirimi, chiqimi va obyektlararo o'tkazmalar formasi.
- **Inventarizatsiya**: Ombor qoldiqlarini amaldagi bilan solishtirish va bazani yangilash.

### 4. Xodim Paneli
- **Kunlik amallar**: "Ishni boshlash", "Ishni yakunlash" va "Avans so'rovi" tugmalarini real API'ga ulash.
- **Ombor**: Mahsulot sarfini (material consumption) qayd etish.

## Tekshirish rejasi

### Qo'lda tekshirish
- Admin sifatida yangi foydalanuvchi yaratib, u bilan login qilish.
- Menejer sifatida xodimga oylik to'lab, mini-kassa balansi kamayganini tekshirish.
- Ombor harakati amalga oshirilganda qoldiqlar o'zgarganini tasdiqlash.
- Barcha amallar muvaffaqiyatli bo'lganda GitHub repozitoriyasiga push qilish.
