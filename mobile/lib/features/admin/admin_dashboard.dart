import 'package:flutter/material.dart' hide InsetBoxDecoration, InsetBoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';
import '../auth/pin_screen.dart';
import '../auth/profile_screen.dart';
import '../finance/finance_dashboard.dart';
import 'user_form_screen.dart';
import 'object_form_screen.dart';
import 'product_form_screen.dart';
import '../../models/models.dart';

class AdminDashboard extends ConsumerStatefulWidget {
  const AdminDashboard({Key? key}) : super(key: key);

  @override
  ConsumerState<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends ConsumerState<AdminDashboard> {
  int _selectedIndex = 0;
  bool _isLoading = false;
  Map<String, dynamic> _data = {};
  List<dynamic> _users = [];
  List<dynamic> _objects = [];
  List<dynamic> _products = [];

  @override
  void initState() {
    super.initState();
    _fetchDashboardData();
    _fetchUsers();
    _fetchObjects();
    _fetchProducts();
  }

  Future<void> _fetchDashboardData() async {
    setState(() => _isLoading = true);
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/admin/dashboard');
      if (response.statusCode == 200) {
        setState(() {
          _data = response.data;
        });
      }
    } catch (_) {}
    setState(() => _isLoading = false);
  }

  void _showAuditLogs() async {
    final client = ref.read(apiClientProvider);
    final response = await client.get('/admin/audit-logs');
    if (!mounted) return;

    if (response.statusCode == 200) {
      final logs = (response.data['data'] as List?) ?? [];
      showModalBottomSheet(
        context: context,
        backgroundColor: AppColors.background,
        isScrollControlled: true,
        builder: (context) => DraggableScrollableSheet(
          initialChildSize: 0.8,
          maxChildSize: 0.95,
          minChildSize: 0.5,
          expand: false,
          builder: (context, scrollController) => Column(
            children: [
              const SizedBox(height: 20),
              const Text('TIZIM AUDIT JURNALI', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5)),
              const Divider(),
              Expanded(
                child: ListView.builder(
                  controller: scrollController,
                  itemCount: logs.length,
                  itemBuilder: (context, index) {
                    final log = logs[index];
                    return ListTile(
                      title: Text('${log['user']} - ${log['action']}'),
                      subtitle: Text(log['created_at']),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      );
    }
  }

  Future<void> _fetchUsers() async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/admin/users');
      if (response.statusCode == 200) {
        setState(() {
          _users = response.data['data'] ?? [];
        });
      }
    } catch (_) {}
  }

  Future<void> _fetchObjects() async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/admin/objects');
      if (response.statusCode == 200) {
        setState(() {
          _objects = response.data ?? [];
        });
      }
    } catch (_) {}
  }

  Future<void> _fetchProducts() async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/admin/products');
      if (response.statusCode == 200) {
        setState(() {
          _products = response.data ?? [];
        });
      }
    } catch (_) {}
  }

  void _logout() {
    ref.read(authProvider.notifier).logout();
  }

  void _openProfile() {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const ProfileScreen()),
    );
  }

  void _openUserForm([Map<String, dynamic>? user]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => UserFormScreen(
          user: user,
          onSuccess: _fetchUsers,
        ),
      ),
    );
  }

  void _openObjectForm([Map<String, dynamic>? object]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ObjectFormScreen(
          object: object,
          onSuccess: _fetchObjects,
        ),
      ),
    );
  }

  void _openProductForm([Map<String, dynamic>? product]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ProductFormScreen(
          product: product,
          onSuccess: _fetchProducts,
        ),
      ),
    );
  }

  void _toggleUserActive(Map<String, dynamic> user) async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.post('/admin/users/${user['id']}/toggle');
      if (response.statusCode == 200) {
        _fetchUsers();
      }
    } catch (_) {}
  }

  void _deleteTransaction(Map<String, dynamic> tx) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('O\'chirish'),
        content: const Text('Haqiqatan ham ushbu tranzaksiyani butunlay o\'chirmoqchimisiz? (Admin huquqi)'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('YO\'Q')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('HA, O\'CHIR')),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final client = ref.read(apiClientProvider);
        await client.delete('/admin/transactions/${tx['id']}');
        _fetchDashboardData();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tranzaksiya o\'chirildi')));
      } catch (_) {}
    }
  }

  void _updatePin() async {
    final pinController = TextEditingController();
    final pin = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Moliya PIN kodini yangilash'),
        content: TextField(
          controller: pinController,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(hintText: 'Yangi 4 xonali PIN'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('BEKOR')),
          TextButton(onPressed: () => Navigator.pop(context, pinController.text), child: const Text('SAQLASH')),
        ],
      ),
    );

    if (pin != null && pin.length == 4) {
      try {
        final client = ref.read(apiClientProvider);
        final user = ref.read(authProvider).user;
        await client.put('/admin/users/${user!.id}', data: {'pin_code': pin, 'role': user.role.value, 'name': user.name, 'phone': user.phone});
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('PIN kod yangilandi')));
      } catch (_) {}
    }
  }

  void _updateCurrencyRate() async {
    final rateController = TextEditingController();
    final rate = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Valyuta kursini o\'rnatish'),
        content: TextField(
          controller: rateController,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(hintText: '1 USD = ? UZS'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('BEKOR')),
          TextButton(onPressed: () => Navigator.pop(context, rateController.text), child: const Text('SAQLASH')),
        ],
      ),
    );

    if (rate != null && rate.isNotEmpty) {
      try {
        final client = ref.read(apiClientProvider);
        await client.post('/admin/currency-rates', data: {'rate_uzs_per_usd': rate});
        _fetchDashboardData();
      } catch (_) {}
    }
  }

  void _navigateToFinance() {
    final pinState = ref.read(pinProvider);
    if (pinState.isVerified) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (context) => const FinanceDashboard(showBackButton: true)),
      );
    } else {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PinScreen(
            onSuccess: () {
              Navigator.pop(context); // Close PIN screen
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const FinanceDashboard(showBackButton: true)),
              );
            },
          ),
        ),
      );
    }
  }

  Widget _buildDashboardHome() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: AppColors.success));
    }

    final totals = _data['totals'] ?? {'usd': 0.0, 'uzs': 0.0};
    final counts = _data['counts'] ?? {'objects': 0, 'users': 0};
    final currentRate = _data['current_rate'] ?? 0.0;
    final recentTx = (_data['recent_transactions'] as List?) ?? [];

    return RefreshIndicator(
      onRefresh: _fetchDashboardData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Expanded(
                  child: NeumorphicCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Jami USD', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
                        const SizedBox(height: 8),
                        Text(
                          '\$ ${totals['usd'].toStringAsFixed(2)}',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppColors.success),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: NeumorphicCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Jami UZS', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
                        const SizedBox(height: 8),
                        Text(
                          '${totals['uzs'].toStringAsFixed(0)} UZS',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.blueEnd),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            NeumorphicButton(
              onTap: _navigateToFinance,
              gradientColors: AppColors.blueGradient,
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.account_balance_wallet_outlined, color: Colors.white),
                  SizedBox(width: 12),
                  Text(
                    'MOLIYA BO\'LIMIGA O\'TISH',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: NeumorphicCard(
                    child: Row(
                      children: [
                        const Icon(Icons.business, color: AppColors.textMuted),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${counts['objects']}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                            const Text('Obyektlar', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: NeumorphicCard(
                    child: Row(
                      children: [
                        const Icon(Icons.people_outline, color: AppColors.textMuted),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${counts['users']}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                            const Text('Foydalanuvchilar', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            GestureDetector(
              onTap: _updateCurrencyRate,
              child: NeumorphicCard(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Joriy Kurs', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
                        Text('1 USD = UZS (O\'zgartirish uchun bosing)', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
                      ],
                    ),
                    Text(
                      '${currentRate.toStringAsFixed(0)} UZS',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.textPrimary),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'So\'nggi Tranzaksiyalar',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
            ),
            const SizedBox(height: 12),
            if (recentTx.isEmpty)
              const NeumorphicCard(child: Center(child: Text('Tranzaksiyalar yo\'q')))
            else
              ...recentTx.map((tx) {
                final isIncome = tx['type'] == 'income' || tx['type'] == 'transfer_in';
                final amountColor = isIncome ? AppColors.success : AppColors.danger;
                final sign = isIncome ? '+' : '-';
                return Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: GestureDetector(
                    onLongPress: () => _deleteTransaction(tx),
                    child: NeumorphicCard(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(tx['category'] ?? tx['type'], style: const TextStyle(fontWeight: FontWeight.bold)),
                                Text(
                                  '${tx['cash_account'] ?? ''} • ${tx['created_at']}',
                                  style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                                ),
                              ],
                            ),
                          ),
                          Text(
                            '$sign ${tx['amount']} ${tx['currency']}',
                            style: TextStyle(fontWeight: FontWeight.bold, color: amountColor),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildUsersList() {
    if (_isLoading && _users.isEmpty) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchUsers,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _users.length,
        itemBuilder: (context, index) {
          final user = _users[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12.0),
            child: GestureDetector(
              onTap: () => _openUserForm(user),
              child: NeumorphicCard(
                child: Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: const InsetBoxDecoration(color: AppColors.surface, shape: BoxShape.circle),
                      child: const Icon(Icons.person, color: AppColors.success),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(user['name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                          Text('${user['role']} • ${user['phone']}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                        ],
                      ),
                    ),
                    GestureDetector(
                      onTap: () => _toggleUserActive(user),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: NeumorphicDecorations.sunken(radius: 6),
                        child: Text(
                          user['is_active'] ? 'FAOL' : 'NOFAOL',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: user['is_active'] ? AppColors.success : AppColors.danger,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildObjectsList() {
    if (_isLoading && _objects.isEmpty) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchObjects,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _objects.length,
        itemBuilder: (context, index) {
          final obj = _objects[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12.0),
            child: GestureDetector(
              onTap: () => _openObjectForm(obj),
              child: NeumorphicCard(
                child: Row(
                  children: [
                    const Icon(Icons.business, color: AppColors.success),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(obj['name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                          Text(obj['type'].toString().toUpperCase(), style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(obj['manager_name'] ?? 'Menejersiz', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        const Text('Menejer', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildProductsList() {
    if (_isLoading && _products.isEmpty) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchProducts,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _products.length,
        itemBuilder: (context, index) {
          final prod = _products[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12.0),
            child: GestureDetector(
              onTap: () => _openProductForm(prod),
              child: NeumorphicCard(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(prod['name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                        Text('Birlik: ${prod['unit']}', style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                      ],
                    ),
                    Text(
                      'Min: ${prod['min_limit']}',
                      style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.warning),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildSettings() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          NeumorphicCard(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.password, color: AppColors.textPrimary),
                  title: const Text('PIN kodni yangilash'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: _updatePin,
                ),
                const Divider(),
                ListTile(
                  leading: const Icon(Icons.history_toggle_off, color: AppColors.textPrimary),
                  title: const Text('Tizim Audit jurnali'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: _showAuditLogs,
                ),
              ],
            ),
          ),
          const SizedBox(height: 40),
          NeumorphicButton(
            onTap: () {
              ref.read(authProvider.notifier).logout();
            },
            gradientColors: AppColors.redGradient,
            child: const Center(
              child: Text(
                'TIZIMDAN CHIQISH',
                style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.5),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppColors.background,
        title: Text(
          _selectedIndex == 0
              ? 'SUPER ADMIN PANEL'
              : _selectedIndex == 1
                  ? 'FOYDALANUVCHILAR'
                  : _selectedIndex == 2
                      ? 'OBYEKTLAR'
                      : _selectedIndex == 3
                          ? 'MAHSULOTLAR'
                          : 'SOZLAMALAR',
          style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchDashboardData,
          ),
          IconButton(
            icon: const Icon(Icons.person_outline),
            onPressed: _openProfile,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      floatingActionButton: _selectedIndex >= 1 && _selectedIndex <= 3
          ? Padding(
              padding: const EdgeInsets.only(bottom: 12.0),
              child: NeumorphicButton(
                onTap: () {
                  if (_selectedIndex == 1) _openUserForm();
                  if (_selectedIndex == 2) _openObjectForm();
                  if (_selectedIndex == 3) _openProductForm();
                },
                isCircular: true,
                gradientColors: AppColors.greenGradient,
                padding: const EdgeInsets.all(20),
                child: const Icon(Icons.add, color: Colors.white, size: 28),
              ),
            )
          : null,
      bottomNavigationBar: Container(
        height: 80,
        decoration: const InsetBoxDecoration(
          color: AppColors.background,
          boxShadow: [
            InsetBoxShadow(color: AppColors.shadowDark, offset: Offset(0, -6), blurRadius: 10),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _buildNavItem(0, Icons.dashboard_outlined, 'Bosh sahifa'),
            _buildNavItem(1, Icons.people_outline, 'Foydalanuvchilar'),
            _buildNavItem(2, Icons.business_outlined, 'Obyektlar'),
            _buildNavItem(3, Icons.inventory_2_outlined, 'Mahsulotlar'),
            _buildNavItem(4, Icons.settings_outlined, 'Sozlamalar'),
          ],
        ),
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildDashboardHome(),
          _buildUsersList(),
          _buildObjectsList(),
          _buildProductsList(),
          _buildSettings(),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, IconData icon, String label) {
    final isSelected = _selectedIndex == index;
    return GestureDetector(
      onTap: () => setState(() => _selectedIndex = index),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: isSelected
            ? NeumorphicDecorations.sunken(radius: 12)
            : const InsetBoxDecoration(),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              color: isSelected ? AppColors.success : AppColors.textMuted,
              size: 24,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                color: isSelected ? AppColors.textPrimary : AppColors.textMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
