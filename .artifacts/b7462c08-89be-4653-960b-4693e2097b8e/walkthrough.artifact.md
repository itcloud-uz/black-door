# Black Door Mobile — Profil Sozlamalari va UI Fix Hisoboti

Barcha dashbordlarga Profil sozlamalari va Chiqish tugmalari muvaffaqiyatli qo'shildi. UI xatoliklari (red screen) to'liq bartaraf etildi.

## Amalga oshirilgan ishlar:

1.  **Profil Sozlamalari**:
    - Yangi `ProfileScreen` yaratildi (F.I.Sh, Telefon, Email va Rol ma'lumotlari bilan).
    - Barcha dashbordlarning `AppBar` qismiga Profil (person icon) va Chiqish (logout icon) tugmalari qo'shildi.
2.  **UI/UX Tuzatishlari**:
    - `BoxDecoration` va `BoxShadow` turlari o'rtasidagi ziddiyat (custom vs material) bartaraf etildi.
    - Bu orqali "red screen" (exception) va noto'g'ri render xatoliklari hal qilindi.
3.  **GitHub integratsiyasi**:
    - Yangi fayllar va tuzatishlar GitHub'ga yuklandi.
    - **Commit:** `fix: resolve BoxDecoration type conflict and add Profile settings to all dashboards`

## Tekshiruv natijalari:

- **Profil Ekran**: ✅ User ma'lumotlari to'g'ri ko'rinmoqda.
- **Logout**: ✅ Dashbord va Profil ekranidan chiqish muvaffaqiyatli.
- **UI Render**: ✅ Hech qanday "overflow" yoki "type error" yo'q.

> [!TIP]
> Profilga kirish uchun yuqoridagi odamcha ikonasini bosing.
