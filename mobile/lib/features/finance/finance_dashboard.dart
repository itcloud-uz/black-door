import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import 'package:fl_chart/fl_chart.dart';
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';
import '../auth/profile_screen.dart';
import 'counterparty_form_screen.dart';
import 'category_form_screen.dart';
import 'create_transaction_screen.dart';

class FinanceDashboard extends ConsumerStatefulWidget {
  final bool showBackButton;

  const FinanceDashboard({Key? key, this.showBackButton = false}) : super(key: key);

  @override
  ConsumerState<FinanceDashboard> createState() => _FinanceDashboardState();
}

class _FinanceDashboardState extends ConsumerState<FinanceDashboard> {
  int _selectedIndex = 0;
  bool _isLoading = false;
  List<dynamic> _accounts = [];
  List<dynamic> _counterparties = [];
  List<dynamic> _transactions = [];
  List<dynamic> _categories = [];
  Map<String, dynamic> _reportData = {};

  @override
  void initState() {
    super.initState();
    _fetchFinanceData();
    _fetchReportData();
  }

  Future<void> _fetchFinanceData() async {
    setState(() => _isLoading = true);
    try {
      final client = ref.read(apiClientProvider);
      
      final accRes = await client.get('/finance/cash-accounts');
      final cpRes = await client.get('/finance/counterparties');
      final txRes = await client.get('/finance/transactions');
      final catRes = await client.get('/finance/categories');

      if (mounted) {
        setState(() {
          _accounts = accRes.data ?? [];
          _counterparties = cpRes.data ?? [];
          _transactions = txRes.data['data'] ?? [];
          _categories = catRes.data ?? [];
        });
      }
    } catch (_) {}
    setState(() => _isLoading = false);
  }

  Future<void> _fetchReportData() async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/finance/reports', queryParameters: {
        'type': 'category_breakdown',
        'category_type': 'expense',
      });
      if (response.statusCode == 200) {
        setState(() {
          _reportData = response.data;
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

  void _openCounterpartyForm([Map<String, dynamic>? cp]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CounterpartyFormScreen(
          counterparty: cp,
          onSuccess: _fetchFinanceData,
        ),
      ),
    );
  }

  void _openCategoryForm([Map<String, dynamic>? cat]) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CategoryFormScreen(
          category: cat,
          onSuccess: _fetchFinanceData,
        ),
      ),
    );
  }

  void _openCreateTransaction() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CreateTransactionScreen(
          accounts: _accounts,
          categories: _categories,
          counterparties: _counterparties,
          onSuccess: () {
            _fetchFinanceData();
          },
        ),
      ),
    );
  }

  void _stornoTransaction(Map<String, dynamic> tx) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Storno (Bekor qilish)'),
        content: const Text('Haqiqatan ham ushbu tranzaksiyani bekor qilmoqchimisiz?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('YO\'Q')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('HA, BEKOR QIL')),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final client = ref.read(apiClientProvider);
        await client.post('/finance/transactions/${tx['id']}/storno');
        _fetchFinanceData();
      } catch (_) {}
    }
  }

  Widget _buildKassalarTab() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchFinanceData,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _accounts.length,
        itemBuilder: (context, index) {
          final acc = _accounts[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 16.0),
            child: NeumorphicCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(acc['name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: NeumorphicDecorations.sunken(radius: 6),
                        child: Text(
                          acc['type'].toUpperCase(),
                          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.textMuted),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('USD Qoldiq', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                          Text(
                            '\$ ${acc['usd_balance'].toStringAsFixed(2)}',
                            style: const TextStyle(fontWeight: FontWeight.w900, color: AppColors.success),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('UZS Qoldiq', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                          Text(
                            '${acc['uzs_balance'].toStringAsFixed(0)} UZS',
                            style: const TextStyle(fontWeight: FontWeight.w900, color: AppColors.blueEnd),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildKontragentlarTab() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchFinanceData,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _counterparties.length,
        itemBuilder: (context, index) {
          final cp = _counterparties[index];
          final usd = cp['usd_balance'];
          final uzs = cp['uzs_balance'];

          final usdColor = usd >= 0 ? AppColors.success : AppColors.danger;
          final uzsColor = uzs >= 0 ? AppColors.success : AppColors.danger;

          return Padding(
            padding: const EdgeInsets.only(bottom: 16.0),
            child: GestureDetector(
              onTap: () => _openCounterpartyForm(cp),
              child: NeumorphicCard(
                child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(cp['name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  if (cp['phone'] != null)
                    Text(cp['phone'], style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'USD: ${usd >= 0 ? "+" : ""}${usd.toStringAsFixed(2)}',
                        style: TextStyle(fontWeight: FontWeight.bold, color: usdColor),
                      ),
                      Text(
                        'UZS: ${uzs >= 0 ? "+" : ""}${uzs.toStringAsFixed(0)} UZS',
                        style: TextStyle(fontWeight: FontWeight.bold, color: uzsColor),
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

  Widget _buildTranzaksiyalarTab() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    return RefreshIndicator(
      onRefresh: _fetchFinanceData,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount: _transactions.length,
        itemBuilder: (context, index) {
          final tx = _transactions[index];
          final isIncome = tx['type'] == 'income' || tx['type'] == 'transfer_in';
          final amountColor = isIncome ? AppColors.success : AppColors.danger;
          final sign = isIncome ? '+' : '-';

          return Padding(
            padding: const EdgeInsets.only(bottom: 12.0),
            child: NeumorphicCard(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(tx['note'] ?? tx['type'].toUpperCase(), style: const TextStyle(fontWeight: FontWeight.bold)),
                      Row(
                        children: [
                          Text('${tx['currency']} • ${tx['transaction_date']}', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                          if (tx['exchange_rate'] != null) ...[
                            const SizedBox(width: 8),
                            Text(
                              '• Kurs: ${(tx['exchange_rate'] / 100).toStringAsFixed(0)} so\'m',
                              style: const TextStyle(fontSize: 11, color: AppColors.success, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                  Text(
                    '$sign ${(tx['amount'] / 100).toStringAsFixed(2)}',
                    style: TextStyle(fontWeight: FontWeight.bold, color: amountColor),
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    icon: const Icon(Icons.history, size: 18, color: AppColors.textMuted),
                    onPressed: () => _stornoTransaction(tx),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildReportsTab() {
    if (_reportData.isEmpty) return const Center(child: CircularProgressIndicator(color: AppColors.success));

    final categories = (_reportData['categories'] as List?) ?? [];
    final totalUsd = _reportData['grand_total_usd'] ?? 0.0;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text('XARAJATLAR TAHLILI (USD)', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.2)),
          const SizedBox(height: 24),

          if (categories.isEmpty)
            const NeumorphicCard(child: Center(child: Text('Ma\'lumotlar mavjud emas')))
          else ...[
            SizedBox(
              height: 200,
              child: PieChart(
                PieChartData(
                  sections: categories.map((cat) {
                    final index = categories.indexOf(cat);
                    final colors = [AppColors.success, AppColors.blueEnd, AppColors.warning, AppColors.danger, Colors.purple, Colors.orange];
                    return PieChartSectionData(
                      color: colors[index % colors.length],
                      value: (cat['total_usd'] as num).toDouble(),
                      title: '${cat['percentage_usd']}%',
                      radius: 50,
                      titleStyle: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white),
                    );
                  }).toList(),
                ),
              ),
            ),
            const SizedBox(height: 32),
            ...categories.map((cat) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 12.0),
                child: Row(
                  children: [
                    Container(width: 12, height: 12, decoration: InsetBoxDecoration(color: [AppColors.success, AppColors.blueEnd, AppColors.warning, AppColors.danger, Colors.purple, Colors.orange][categories.indexOf(cat) % 6], shape: BoxShape.circle)),
                    const SizedBox(width: 12),
                    Expanded(child: Text(cat['category'], style: const TextStyle(fontWeight: FontWeight.w600))),
                    Text('\$ ${cat['total_usd'].toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
              );
            }).toList(),
          ],
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: AppColors.background,
        leading: widget.showBackButton
            ? IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: () => Navigator.pop(context),
              )
            : null,
        title: Text(
          _selectedIndex == 0
              ? 'KASSALAR'
              : _selectedIndex == 1
                  ? 'KONTRAGENTLAR'
                  : _selectedIndex == 2
                      ? 'TRANZAKSIYALAR'
                      : 'HISOBOTLAR',
          style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.category_outlined),
            onPressed: () => _openCategoryForm(),
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchFinanceData,
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
                onTap: _selectedIndex == 1 ? _openCounterpartyForm : _openCreateTransaction,
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
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            _buildNavItem(0, Icons.account_balance_outlined, 'Kassalar'),
            _buildNavItem(1, Icons.contact_page_outlined, 'Kontragentlar'),
            _buildNavItem(2, Icons.list_alt_outlined, 'Tranzaksiyalar'),
            _buildNavItem(3, Icons.analytics_outlined, 'Hisobotlar'),
          ],
        ),
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          _buildKassalarTab(),
          _buildKontragentlarTab(),
          _buildTranzaksiyalarTab(),
          _buildReportsTab(),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, IconData icon, String label) {
    final isSelected = _selectedIndex == index;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedIndex = index),
        child: Container(
          color: Colors.transparent,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: isSelected
                    ? NeumorphicDecorations.sunken(radius: 10)
                    : const InsetBoxDecoration(),
                child: Icon(
                  icon,
                  color: isSelected ? AppColors.success : AppColors.textMuted,
                  size: 22,
                ),
              ),
              if (isSelected) ...[
                const SizedBox(height: 2),
                Text(
                  label,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
