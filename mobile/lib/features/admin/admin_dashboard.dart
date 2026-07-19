import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';
import '../auth/pin_screen.dart';
import '../finance/finance_dashboard.dart';

class AdminDashboard extends ConsumerStatefulWidget {
  const AdminDashboard({Key? key}) : super(key: key);

  @override
  ConsumerState<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends ConsumerState<AdminDashboard> {
  int _selectedIndex = 0;
  bool _isLoading = false;
  Map<String, dynamic> _data = {};

  @override
  void initState() {
    super.initState();
    _fetchDashboardData();
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

  void _logout() {
    ref.read(authProvider.notifier).logout();
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
    final recentLogs = (_data['recent_audit_logs'] as List?) ?? [];

    return RefreshIndicator(
      onRefresh: _fetchDashboardData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Row with balances
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

            // Navigation button to Finance module (Soft Coral accent)
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

            // Statistics Counts
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

            // Current rate card
            NeumorphicCard(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Joriy Kurs', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
                      Text('1 USD = UZS', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                    ],
                  ),
                  Text(
                    '${currentRate.toStringAsFixed(0)} UZS',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.textPrimary),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Recent Transactions
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
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildUsersList() {
    return const Center(child: Text('Foydalanuvchilar Ro\'yxati (Tez orada...)'));
  }

  Widget _buildObjectsList() {
    return const Center(child: Text('Obyektlar Ro\'yxati (Tez orada...)'));
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
                  onTap: () {},
                ),
                const Divider(),
                ListTile(
                  leading: const Icon(Icons.history_toggle_off, color: AppColors.textPrimary),
                  title: const Text('Tizim Audit jurnali'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {},
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
    final user = ref.watch(authProvider).user;

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
                      : 'SOZLAMALAR',
          style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchDashboardData,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      bottomNavigationBar: Container(
        height: 80,
        decoration: const BoxDecoration(
          color: AppColors.background,
          boxShadow: [
            BoxShadow(color: AppColors.shadowDark, offset: Offset(0, -6), blurRadius: 10),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _buildNavItem(0, Icons.dashboard_outlined, 'Bosh sahifa'),
            _buildNavItem(1, Icons.people_outline, 'Foydalanuvchilar'),
            _buildNavItem(2, Icons.business_outlined, 'Obyektlar'),
            _buildNavItem(3, Icons.settings_outlined, 'Sozlamalar'),
          ],
        ),
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildDashboardHome(),
          _buildUsersList(),
          _buildObjectsList(),
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
            : const BoxDecoration(),
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
