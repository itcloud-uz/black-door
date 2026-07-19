enum UserRole {
  superAdmin('super_admin'),
  financier('financier'),
  manager('manager'),
  employee('employee');

  final String value;
  const UserRole(this.value);

  static UserRole fromString(String val) {
    return UserRole.values.firstWhere(
      (e) => e.value == val,
      orElse: () => UserRole.employee,
    );
  }
}

class User {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final UserRole role;
  final bool isActive;

  User({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    required this.role,
    required this.isActive,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      name: json['name'] as String,
      phone: json['phone'] as String,
      email: json['email'] as String?,
      role: UserRole.fromString(json['role'] as String),
      isActive: json['is_active'] == true || json['is_active'] == 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'email': email,
      'role': role.value,
      'is_active': isActive,
    };
  }
}

class CashAccount {
  final int id;
  final String name;
  final String type;
  final String? note;
  final double usdBalance;
  final double uzsBalance;

  CashAccount({
    required this.id,
    required this.name,
    required this.type,
    this.note,
    required this.usdBalance,
    required this.uzsBalance,
  });

  factory CashAccount.fromJson(Map<String, dynamic> json) {
    return CashAccount(
      id: json['id'] as int,
      name: json['name'] as String,
      type: json['type'] as String,
      note: json['note'] as String?,
      usdBalance: (json['usd_balance'] as num?)?.toDouble() ?? 0.0,
      uzsBalance: (json['uzs_balance'] as num?)?.toDouble() ?? 0.0,
    );
  }
}

class Counterparty {
  final int id;
  final String name;
  final String? phone;
  final String? note;
  final String category;
  final double usdBalance;
  final double uzsBalance;
  final List<String> tags;

  Counterparty({
    required this.id,
    required this.name,
    this.phone,
    this.note,
    required this.category,
    required this.usdBalance,
    required this.uzsBalance,
    required this.tags,
  });

  factory Counterparty.fromJson(Map<String, dynamic> json) {
    final tagsList = (json['tags'] as List?)
        ?.map((t) => (t as Map)['name'] as String)
        .toList() ?? [];
    return Counterparty(
      id: json['id'] as int,
      name: json['name'] as String,
      phone: json['phone'] as String?,
      note: json['note'] as String?,
      category: json['category'] as String,
      usdBalance: (json['usd_balance'] as num?)?.toDouble() ?? 0.0,
      uzsBalance: (json['uzs_balance'] as num?)?.toDouble() ?? 0.0,
      tags: List<String>.from(tagsList),
    );
  }
}

class Transaction {
  final int id;
  final String? cashAccount;
  final String? counterparty;
  final String? category;
  final String type;
  final String currency;
  final double amount;
  final String? note;
  final String createdAt;

  Transaction({
    required this.id,
    this.cashAccount,
    this.counterparty,
    this.category,
    required this.type,
    required this.currency,
    required this.amount,
    this.note,
    required this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'] as int,
      cashAccount: json['cash_account'] as String?,
      counterparty: json['counterparty'] as String?,
      category: json['category'] as String?,
      type: json['type'] as String,
      currency: json['currency'] as String,
      amount: (json['amount'] as num).toDouble(),
      note: json['note'] as String?,
      createdAt: json['created_at'] as String,
    );
  }
}

class Product {
  final int id;
  final String name;
  final String unit;
  final int minLimit;

  Product({
    required this.id,
    required this.name,
    required this.unit,
    required this.minLimit,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'] as int,
      name: json['name'] as String,
      unit: json['unit'] as String,
      minLimit: json['min_limit'] as int? ?? 0,
    );
  }
}

class WarehouseStock {
  final int productId;
  final String name;
  final int quantity;
  final String unit;
  final int minLimit;
  final bool isLow;

  WarehouseStock({
    required this.productId,
    required this.name,
    required this.quantity,
    required this.unit,
    required this.minLimit,
    required this.isLow,
  });

  factory WarehouseStock.fromJson(Map<String, dynamic> json) {
    return WarehouseStock(
      productId: json['product_id'] as int,
      name: json['name'] as String,
      quantity: json['quantity'] as int,
      unit: json['unit'] as String,
      minLimit: json['min_limit'] as int? ?? 0,
      isLow: json['is_low'] == true,
    );
  }
}
