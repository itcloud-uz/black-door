# Black Door Mobile Application (Flutter Client)

Ushbu mobil ilova **Black Door** korxona moliya va ombor boshqaruvi tizimining Flutter'da yozilgan to'laqonli neumorfik (Soft UI) mijozidir. Ilova ham Android, ham iOS qurilmalari uchun mo'ljallangan.

## 🚀 Texnik Talablar va Kutubxonalar

* **Flutter SDK:** `^3.0.0` yoki undan yuqori.
* **Null-safety:** To'liq qo'llab-quvvatlanadi.
* **State Management:** Riverpod (`flutter_riverpod`).
* **Tarmoq:** Dio (`dio`) token interceptorlari bilan.
* **Xavfsizlik:** `flutter_secure_storage` (Keychain/Keystore) va `local_auth` (Face ID / barmoq izi).
* **Dizayn:** Custom Neumorphic components + `flutter_inset_box_shadow` (inset soyalar uchun).
* **Grafiklar:** `fl_chart`.

---

## ⚙️ Loyihani Sozlash va Ishga Tushirish

### 1. Backend Manzilini Sozlash
Ilovada API manzilini o'zgartirish uchun `mobile/lib/core/network/api_client.dart` faylidagi `baseUrlDefault` konstantasini tahrirlang:

```dart
static const String baseUrlDefault = 'http://10.0.2.2:8000/api'; // Android Emulator uchun
// Yoki haqiqiy qurilma / staging uchun:
// static const String baseUrlDefault = 'https://api.blackdoor.uz/api';
```

### 2. Loyihani Yuklash va Ishga Tushirish
Quyidagi buyruqlarni terminal orqali mobil loyiha katalogida bajaring:

```bash
# 1. Kutubxonalarni yuklash
flutter pub get

# 2. Ilovani ishga tushirish (Emulator yoki ulangan qurilmada)
flutter run
```

---

## 🔑 Sinov Uchun Login Ma'lumotlari

Barcha foydalanuvchilar paroli: `password123`  
Moliya moduli PIN kodi (Karimova Nilufar uchun): `1234`

| F.I.SH. | Telefon Raqami | Rol |
| :--- | :--- | :--- |
| **Abdullayev Sardor** | `+998901234567` | Super Admin |
| **Karimova Nilufar** | `+998901234568` | Moliyachi (Financier) |
| **Toshmatov Jamshid** | `+998901234569` | Obyekt Menejeri (Manager) |
| **Aliyev Oybek** | `+998901234571` | Xodim (Employee) |

---

## 📦 Reliz (Release Build) Tayyorlash

### Android (APK) yig'ish:
```bash
flutter build apk --release
```
Tayyor fayl: `build/app/outputs/flutter-apk/app-release.apk`

### iOS (App Store) yig'ish:
```bash
flutter build ipa --release
```
Natijada `.ipa` arxivi hosil bo'ladi va uni Transporter ilovasi orqali App Store Connect'ga yuklash mumkin.

---

## 🔒 Xavfsizlik Tamoyillari

1. **App Switcher Maxfiyligi:** Ilova fonga o'tganida (app switcher'da) barcha moliyaviy ma'lumotlar avtomatik ravishda blur qilinadi / yashiriladi.
2. **PIN-Kod Lockout:** PIN kod 3 marta noto'g'ri kiritilsa, moliya moduli 15 daqiqaga qulflanadi va backend orqali admin audit jurnalida aks etadi.
3. **Biometrik Kirish:** Face ID yoki barmoq izi orqali moliya bo'limini tezda ochish imkoniyati mavjud.
