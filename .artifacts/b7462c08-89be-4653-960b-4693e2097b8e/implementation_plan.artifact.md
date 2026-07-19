# UI Xatoliklarini tuzatish va Brendingni yangilash rejasi

Ushbu reja ekran ostidagi qizil xatolikni bartaraf etish va foydalanuvchi taqdim etgan yangi logoni tizimga integratsiya qilishni o'z ichiga oladi.

## Foydalanuvchi ko'rib chiqishi kerak bo'lgan bandlar

> [!IMPORTANT]
> `Null is not a subtype of BoxDecoration` xatosi Flutter'ning standart turlari va bizning custom inset-shadow turlarimiz o'rtasidagi ziddiyat tufayli yuzaga kelayotgan edi. Biz turlarni nomini o'zgartirish orqali buni to'liq hal qildik.

## Taklif etilayotgan o'zgarishlar

### 1. UI Xatoliklarini bartaraf etish
- **Turlar ziddiyati**: Bizning custom `BoxDecoration` va `BoxShadow` turlarimiz endi `InsetBoxDecoration` va `InsetBoxShadow` deb nomlandi. Bu Flutter'ning standart turlari bilan adashib ketishning oldini oladi.
- **Null safety**: `lerp` va `scale` metodlari null qiymatlarni xavfsiz qayta ishlash uchun yangilandi.

### 2. Brending (Logo) yangilash
- **Yangi Logo**: Foydalanuvchi taqdim etgan rasm asosida (arch + keyhole) `NeumorphicLogo` widgeti yaratildi.
- **Login va Profil**: Ilova logosi barcha asosiy ekranlarda yangisiga almashtirildi.

### 3. GitHubga yuklash
- Barcha tuzatishlar repozitoriyaga yuboriladi.

## Tekshirish rejasi

### Qo'lda tekshirish
- Emulatorda pastki menyularni (tabs) almashtirib ko'rish va qizil xatolik yo'qligiga ishonch hosil qilish.
- Login ekranida yangi logoni tekshirish.
