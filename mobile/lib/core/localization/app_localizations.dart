import 'package:flutter/material.dart';

class AppLocalizations {
  final Locale locale;
  AppLocalizations(this.locale);

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const Map<String, Map<String, String>> _localizedValues = {
    'uz': {
      'login': 'Tizimga Kirish',
      'phone': 'Telefon raqami',
      'password': 'Parol',
      'enter_phone': 'Telefon raqamini kiriting',
      'enter_password': 'Parolni kiriting',
      'required_field': 'Ushbu maydon to\'ldirilishi shart',
      'invalid_credentials': 'Telefon raqami yoki parol noto\'g\'ri',
      'role_admin': 'Super Admin',
      'role_financier': 'Moliyachi',
      'role_manager': 'Obyekt Menejeri',
      'role_employee': 'Xodim',
      
      'logout': 'Tizimdan chiqish',
      'dashboard': 'Dashboard',
      'users': 'Foydalanuvchilar',
      'objects': 'Obyektlar',
      'rates': 'Valyuta Kursi',
      'audit': 'Audit Jurnali',
      
      'pin_entry': 'Moliya bo\'limi PIN kodi',
      'pin_hint': '4 xonali PIN kodni kiriting',
      'pin_error': 'PIN kod xato. Qolgan urinishlar: {attempts}',
      'pin_locked': 'Bo\'lim vaqtincha qulflangan. {timer} soniyadan keyin qayta urinib ko\'ring.',
      
      'cash_accounts': 'Kassalar',
      'counterparties': 'Kontragentlar',
      'transactions': 'Tranzaksiyalar',
      'categories': 'Kategoriyalar',
      'reports': 'Hisobotlar',
      
      'incoming': 'Kirim',
      'outgoing': 'Chiqim',
      'transfer': 'O\'tkazma',
      'exchange': 'Valyuta ayirboshlash',
      'storno': 'Storno',
      
      'warehouse': 'Omborxona',
      'employees': 'Xodimlar',
      'salary': 'Maosh & Avans',
      'stock_limit_warning': 'Omborda minimal qoldiq ogohlantirishi!',
      'offline_mode': 'Siz oflayn rejimdasiz. Ma\'lumotlar keshdan o\'qilmoqda.',
      'offline_block': 'Oflayn rejimda moliyaviy yozuvlarni saqlash taqiqlangan.',
    }
  };

  String translate(String key) {
    return _localizedValues[locale.languageCode]?[key] ?? key;
  }
}

class AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) => ['uz'].contains(locale.languageCode);

  @override
  Future<AppLocalizations> load(Locale locale) async {
    return AppLocalizations(locale);
  }

  @override
  bool shouldReload(AppLocalizationsDelegate old) => false;
}
