# Dashbordlarda "Chiqish" funksiyasini qo'shish va GitHubga yuklash rejasi

Ushbu reja loyihaning barcha mobil panellariga tizimdan chiqish (logout) tugmasini qo'shish va barcha qilingan o'zgarishlarni GitHub serveriga yuborishni o'z ichiga oladi.

## Foydalanuvchi ko'rib chiqishi kerak bo'lgan bandlar

> [!IMPORTANT]
> GitHubga yuklash uchun amaldagi `main` branch ishlatiladi. Token allaqachon git remote URL'da mavjud.

## Taklif etilayotgan o'zgarishlar

### 1. Mobil ilova (Flutter)
Barcha dashbordlarning `AppBar` qismiga `Icons.logout` tugmasi qo'shiladi:
- **Admin Dashboard**: `mobile/lib/features/admin/admin_dashboard.dart`
- **Finance Dashboard**: `mobile/lib/features/finance/finance_dashboard.dart`
- **Manager Dashboard**: `mobile/lib/features/manager/manager_dashboard.dart`
- *(Employee Dashboard'da allaqachon mavjud)*

Har bir faylda `_logout` metodi quyidagicha ishlaydi:
```dart
void _logout() {
  ref.read(authProvider.notifier).logout();
}
```

### 2. GitHub integratsiyasi
Barcha o'zgarishlar stage qilinadi, commit qilinadi va push qilinadi.
- **Commit xabari:** `feat: add logout to all dashboards and fix build environment issues`

## Tekshirish rejasi

### Avtomatlashtirilgan tekshiruvlar
- Kodning sintaktik to'g'riligini tekshirish (`flutter analyze`).

### Qo'lda tekshirish
- Emulatorda har bir rol bilan kirib, `Logout` tugmasi ishlayotganini va foydalanuvchini login ekraniga qaytarayotganini tekshirish.
- GitHub'da repo yangilanganini tasdiqlash.
