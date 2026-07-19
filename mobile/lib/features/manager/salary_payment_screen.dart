import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class SalaryPaymentScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic> employee;
  final List<dynamic> cashAccounts;
  final VoidCallback onSuccess;

  const SalaryPaymentScreen({
    Key? key,
    required this.employee,
    required this.cashAccounts,
    required this.onSuccess,
  }) : super(key: key);

  @override
  ConsumerState<SalaryPaymentScreen> createState() => _SalaryPaymentScreenState();
}

class _SalaryPaymentScreenState extends ConsumerState<SalaryPaymentScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();
  String _paymentType = 'salary';
  String _currency = 'UZS';
  int? _selectedAccountId;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    if (widget.cashAccounts.isNotEmpty) {
      _selectedAccountId = widget.cashAccounts.first['id'];
    }
  }

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate() && _selectedAccountId != null) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        final response = await client.post('/manager/employees/${widget.employee['id']}/pay', data: {
          'object_cash_account_id': _selectedAccountId,
          'type': _paymentType,
          'amount': _amountController.text,
          'currency': _currency,
          'period_start': DateTime.now().subtract(const Duration(days: 30)).toIso8601String().split('T')[0],
          'period_end': DateTime.now().toIso8601String().split('T')[0],
          'note': _noteController.text,
        });

        if (response.statusCode == 200) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('To\'lov muvaffaqiyatli amalga oshirildi')));
        }
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Xatolik: Mablag\' yetarli emas yoki tarmoq xatosi')));
      }
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        title: const Text('TO\'LOV QILISH'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              NeumorphicCard(
                child: Column(
                  children: [
                    Text(widget.employee['name'], style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    Text(widget.employee['position'], style: const TextStyle(color: AppColors.textMuted)),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('To\'lov turi', style: TextStyle(fontWeight: FontWeight.bold)),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: NeumorphicDecorations.sunken(radius: 14),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _paymentType,
                              items: const [
                                DropdownMenuItem(value: 'salary', child: Text('Oylik')),
                                DropdownMenuItem(value: 'advance', child: Text('Avans')),
                              ],
                              onChanged: (v) => setState(() => _paymentType = v!),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Valyuta', style: TextStyle(fontWeight: FontWeight.bold)),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: NeumorphicDecorations.sunken(radius: 14),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _currency,
                              items: const [
                                DropdownMenuItem(value: 'UZS', child: Text('UZS')),
                                DropdownMenuItem(value: 'USD', child: Text('USD')),
                              ],
                              onChanged: (v) => setState(() => _currency = v!),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              const Text('Kassa / Hisob', style: TextStyle(fontWeight: FontWeight.bold)),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: NeumorphicDecorations.sunken(radius: 14),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<int>(
                    value: _selectedAccountId,
                    isExpanded: true,
                    items: widget.cashAccounts.map((a) {
                      return DropdownMenuItem<int>(value: a['id'], child: Text(a['name']));
                    }).toList(),
                    onChanged: (v) => setState(() => _selectedAccountId = v),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              NeumorphicTextField(
                controller: _amountController,
                labelText: 'Summa',
                hintText: '0.00',
                keyboardType: TextInputType.number,
                validator: (v) => v?.isEmpty ?? true ? 'Summani kiriting' : null,
              ),
              const SizedBox(height: 20),

              NeumorphicTextField(
                controller: _noteController,
                labelText: 'Izoh',
                hintText: 'To\'lov haqida...',
              ),
              const SizedBox(height: 40),

              NeumorphicButton(
                onTap: _isLoading ? null : _submit,
                gradientColors: AppColors.greenGradient,
                child: Center(
                  child: _isLoading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('TASDIQLASH', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.5)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
