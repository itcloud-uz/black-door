import 'package:flutter/material.dart' hide InsetBoxDecoration, InsetBoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';
import '../auth/profile_screen.dart';
import 'salary_payment_screen.dart';
import 'warehouse_movement_screen.dart';
import 'employee_form_screen.dart';
import '../../models/models.dart';

class ManagerDashboard extends ConsumerStatefulWidget {
  const ManagerDashboard({Key? key}) : super(key: key);

  @override
  ConsumerState<ManagerDashboard> createState() => _ManagerDashboardState();
}

class _ManagerDashboardState extends ConsumerState<ManagerDashboard> {
  int _selectedIndex = 0;
  bool _isLoading = false;
  Map<String, dynamic> _data = {};
  List<dynamic> _employees = [];
  List<dynamic> _stocks = [];
  List<dynamic> _transactions = [];

  @override
  void initState() {
    super.initState();
    _fetchManagerData();
  }

  Future<void> _fetchManagerData() async {
    setState(() => _isLoading = true);
    try {
      final client = ref.read(apiClientProvider);

      final dashRes = await client.get('/manager/dashboard');
      final empRes = await client.get('/manager/employees');
      final stockRes = await client.get('/manager/stocks');
      final txRes = await client.get('/manager/transactions');

      if (mounted) {
        setState(() {
          _data = dashRes.data ?? {};
          _employees = empRes.data ?? [];
          _stocks = stockRes.data ?? [];
          _transactions = txRes.data['data'] ?? [];
        });
      }
    } catch (_) {}
    setState(() => _isLoading = false);
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

  void _paySalary(Map<String, dynamic> emp) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SalaryPaymentScreen(
          employee: emp,
          cashAccounts: _data['balances_detailed'] ?? [],
          onSuccess: _fetchManagerData,
        ),
      ),
    );
  }

  void _openEmployeeForm([Map<String, dynamic>? emp]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => EmployeeFormScreen(
          employee: emp,
          onSuccess: _fetchManagerData,
        ),
      ),
    );
  }

  void _openWarehouseMovement() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => WarehouseMovementScreen(
          products: _stocks,
          onSuccess: _fetchManagerData,
        ),
      ),
    );
  }

  void _openInventoryCheck() async {
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Inventarizatsiya funksiyasi Web App orqali tavsiya etiladi')));
  }

  Widget _buildDashboardHome() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    final obj = _data['object'] ?? {'name': 'Yuklanmoqda...'};
    final balances = _data['balances'] ?? {'usd': 0.0, 'uzs': 0.0};
    final empCount = _data['employee_count'] ?? 0;
    final lowStock = (_data['low_stock_warnings'] as List?) ?? [];

    return RefreshIndicator(
      onRefresh: _fetchManagerData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            NeumorphicCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(obj['name'], style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text('Turi: ${obj['type']?.toUpperCase() ?? ""}', style: const TextStyle(color: AppColors.textMuted)),
                ],
              ),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: NeumorphicCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Mini-Kassa (USD)', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                        const SizedBox(height: 4),
                        Text(
                          '\$ ${balances['usd'].toStringAsFixed(2)}',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.success),
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
                        const Text('Mini-Kassa (UZS)', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                        const SizedBox(height: 4),
                        Text(
                          '${balances['uzs'].toStringAsFixed(0)} UZS',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: AppColors.blueEnd),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            NeumorphicCard(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Biriktirilgan Xodimlar', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(
                    '$empCount ta xodim',
                    style: const TextStyle(fontWeight: FontWeight.w900, color: AppColors.textPrimary),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            if (lowStock.isNotEmpty) ...[
              const Text(
                'Minimal Qoldiqdan Kamlar',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.danger),
              ),
              const SizedBox(height: 12),
              ...lowStock.map((w) {
                return Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: NeumorphicCard(
                    color: const Color(0xFFFDE8E8),
                    child: Row(
                      children: [
                        const Icon(Icons.warning_amber_outlined, color: AppColors.danger),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(w['name'], style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.danger)),
                              Text(
                                'Mavjud: ${w['quantity']} ${w['unit']} (Min: ${w['min_limit']})',
                                style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ] else
              const NeumorphicCard(
                child: Center(
                  child: Text('Ombor minimal qoldiqlari joyida.'),
                ),
              ),
            const SizedBox(height: 24),
            const Text(
              'So\'nggi Tranzaksiyalar',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
            ),
            const SizedBox(height: 12),
            if (_transactions.isEmpty)
              const NeumorphicCard(child: Center(child: Text('Tranzaksiyalar yo\'q')))
            else
              ..._transactions.take(5).map((tx) {
                final isIncome = tx['type'] == 'income';
                final amountColor = isIncome ? AppColors.success : AppColors.danger;
                final sign = isIncome ? '+' : '-';
                return Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: NeumorphicCard(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(tx['note'] ?? tx['type'].toUpperCase(), style: const TextStyle(fontWeight: FontWeight.bold)),
                              Text(
                                '${tx['currency']} • ${tx['transaction_date']}',
                                style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                              ),
                            ],
                          ),
                        ),
                        Text(
                          '$sign ${(tx['amount'] / 100).toStringAsFixed(2)}',
                          style: TextStyle(fontWeight: FontWeight.bold, color: amountColor),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildEmployeesTab() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchManagerData,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _employees.length,
        itemBuilder: (context, index) {
          final emp = _employees[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 16.0),
            child: GestureDetector(
              onTap: () => _openEmployeeForm(emp),
              child: NeumorphicCard(
                child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(emp['name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(emp['position'], style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                      if (emp['phone'] != null)
                        Text(emp['phone'], style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: NeumorphicDecorations.sunken(radius: 6),
                        child: Text(
                          emp['is_active'] ? 'FAOL' : 'NOFAOL',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: emp['is_active'] ? AppColors.success : AppColors.danger,
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      IconButton(
                        icon: const Icon(Icons.payments_outlined, color: AppColors.blueEnd),
                        onPressed: () => _paySalary(emp),
                      ),
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

  Widget _buildOmborTab() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: NeumorphicButton(
            onTap: _openInventoryCheck,
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.inventory_outlined, size: 20, color: AppColors.blueEnd),
                SizedBox(width: 12),
                Text('INVENTARIZATSIYA (TEKSHIRUV)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
              ],
            ),
          ),
        ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: _fetchManagerData,
            child: ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: _stocks.length,
              itemBuilder: (context, index) {
                final st = _stocks[index];
                final product = st['product'] ?? {'name': 'Noma\'lum', 'unit': 'ta'};
                final isLow = st['quantity'] < (product['min_limit'] ?? 0);

                return Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: NeumorphicCard(
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(product['name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                            Text('Min limit: ${product['min_limit'] ?? 0} ${product['unit']}', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                          ],
                        ),
                        Text(
                          '${st['quantity']} ${product['unit']}',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: isLow ? AppColors.danger : AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSettingsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          NeumorphicButton(
            onTap: _logout,
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
              ? 'OBYEKT PANELI'
              : _selectedIndex == 1
                  ? 'XODIMLAR'
                  : _selectedIndex == 2
                      ? 'OMBOR ZAHIRALARI'
                      : 'SOZLAMALAR',
          style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchManagerData,
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
      floatingActionButton: _selectedIndex == 1 || _selectedIndex == 2
          ? Padding(
              padding: const EdgeInsets.only(bottom: 12.0),
              child: NeumorphicButton(
                onTap: _selectedIndex == 1 ? _openEmployeeForm : _openWarehouseMovement,
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
            _buildNavItem(1, Icons.people_outline, 'Xodimlar'),
            _buildNavItem(2, Icons.warehouse_outlined, 'Ombor'),
            _buildNavItem(3, Icons.settings_outlined, 'Sozlamalar'),
          ],
        ),
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildDashboardHome(),
          _buildEmployeesTab(),
          _buildOmborTab(),
          _buildSettingsTab(),
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
