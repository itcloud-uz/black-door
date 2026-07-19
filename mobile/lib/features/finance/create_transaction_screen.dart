import 'package:flutter/material.dart';
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:dio/dio.dart';
import 'dart:io';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class CreateTransactionScreen extends ConsumerStatefulWidget {
  final List<dynamic> accounts;
  final List<dynamic> categories;
  final List<dynamic> counterparties;
  final VoidCallback onSuccess;

  const CreateTransactionScreen({
    Key? key,
    required this.accounts,
    required this.categories,
    required this.counterparties,
    required this.onSuccess,
  }) : super(key: key);

  @override
  ConsumerState<CreateTransactionScreen> createState() => _CreateTransactionScreenState();
}

class _CreateTransactionScreenState extends ConsumerState<CreateTransactionScreen> {
  final _formKey = GlobalKey<FormState>();
  
  String _type = 'income'; // income, expense, transfer, exchange
  String _amountStr = '0';
  String _currency = 'USD';
  double? _currentRate;
  
  int? _selectedAccountId;
  int? _selectedDestAccountId;
  int? _selectedCategoryId;
  int? _selectedCounterpartyId;
  
  final _noteController = TextEditingController();
  final _rateController = TextEditingController();
  
  String _toCurrency = 'UZS';
  File? _imageFile;
  final ImagePicker _picker = ImagePicker();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _fetchCurrentRate();
  }

  Future<void> _fetchCurrentRate() async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/finance/currency-rate');
      if (response.statusCode == 200 && response.data != null) {
        setState(() {
          _currentRate = (response.data['rate'] as num).toDouble();
          _rateController.text = _currentRate!.toStringAsFixed(0);
        });
      }
    } catch (_) {}
  }

  void _onKeyPress(String val) {
    setState(() {
      if (_amountStr == '0') {
        _amountStr = val;
      } else {
        _amountStr += val;
      }
    });
  }

  void _onBackspace() {
    setState(() {
      if (_amountStr.length > 1) {
        _amountStr = _amountStr.substring(0, _amountStr.length - 1);
      } else {
        _amountStr = '0';
      }
    });
  }

  Future<void> _pickImage() async {
    final pickedFile = await _picker.pickImage(source: ImageSource.camera);
    if (pickedFile != null) {
      setState(() {
        _imageFile = File(pickedFile.path);
      });
    }
  }

  void _submit() async {
    if (_selectedAccountId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Iltimos, kassa hisobini tanlang.')),
      );
      return;
    }

    final double amount = double.tryParse(_amountStr) ?? 0.0;
    if (amount <= 0.0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Summa noldan katta bo\'lishi kerak.')),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final client = ref.read(apiClientProvider);
      
      // Build form data to support file uploads
      final Map<String, dynamic> fields = {
        'cash_account_id': _selectedAccountId,
        'type': _type,
        'amount': amount,
        'currency': _currency,
        'note': _noteController.text,
      };

      if (_type == 'income' || _type == 'expense') {
        if (_selectedCategoryId != null) fields['category_id'] = _selectedCategoryId;
        if (_selectedCounterpartyId != null) fields['counterparty_id'] = _selectedCounterpartyId;
      }

      if (_type == 'transfer') {
        fields['destination_cash_account_id'] = _selectedDestAccountId;
      }

      if (_type == 'exchange') {
        fields['to_currency'] = _toCurrency;
        fields['exchange_rate'] = double.tryParse(_rateController.text) ?? 1.0;
      }

      FormData formData = FormData.fromMap(fields);

      if (_imageFile != null) {
        formData.files.add(MapEntry(
          'attachment',
          await MultipartFile.fromFile(_imageFile!.path, filename: 'receipt.jpg'),
        ));
      }

      final response = await client.post(
        '/finance/transactions',
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );

      if (response.statusCode == 201 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tranzaksiya muvaffaqiyatli qo\'shildi.')),
        );
        widget.onSuccess();
        Navigator.pop(context);
      }
    } on DioException catch (e) {
      final msg = e.response?.data['message'] ?? 'Xatolik yuz berdi.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Kutilmagan xatolik yuz berdi.')),
      );
    }

    setState(() => _isLoading = false);
  }

  @override
  void dispose() {
    _noteController.dispose();
    _rateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        title: const Text('YANGI TRANZAKSIYA', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Type Toggle Selector (Neumorphic Row)
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _buildTypeBtn('income', 'KIRIM'),
                  _buildTypeBtn('expense', 'CHIQIM'),
                  _buildTypeBtn('transfer', 'O\'TKAZMA'),
                  _buildTypeBtn('exchange', 'ALMAST'),
                ],
              ),
              const SizedBox(height: 16),

              if (_currentRate != null) ...[
                Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: NeumorphicDecorations.sunken(radius: 8),
                    child: Text(
                      'Joriy Kurs: 1 USD = ${_currentRate!.toStringAsFixed(0)} so\'m',
                      style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.success, fontSize: 12),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // Screen showing Amount (Sunken)
              NeumorphicCard(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      _amountStr,
                      style: const TextStyle(fontSize: 32, fontWeight: FontWeight.w900, color: AppColors.textPrimary),
                    ),
                    DropdownButton<String>(
                      value: _currency,
                      dropdownColor: AppColors.surface,
                      items: const [
                        DropdownMenuItem(value: 'USD', child: Text('USD')),
                        DropdownMenuItem(value: 'UZS', child: Text('UZS')),
                      ],
                      onChanged: (val) {
                        if (val != null) setState(() => _currency = val);
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Custom Numeric Keypad directly on form
              NeumorphicCard(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        _buildNumKey('1'),
                        _buildNumKey('2'),
                        _buildNumKey('3'),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        _buildNumKey('4'),
                        _buildNumKey('5'),
                        _buildNumKey('6'),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        _buildNumKey('7'),
                        _buildNumKey('8'),
                        _buildNumKey('9'),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        const SizedBox(width: 60),
                        _buildNumKey('0'),
                        IconButton(
                          icon: const Icon(Icons.backspace_outlined),
                          onPressed: _onBackspace,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Core Transaction fields
              DropdownButtonFormField<int>(
                value: _selectedAccountId,
                decoration: const InputDecoration(labelText: 'Kassa Hisobi'),
                items: widget.accounts.map<DropdownMenuItem<int>>((acc) {
                  return DropdownMenuItem<int>(
                    value: acc['id'],
                    child: Text(acc['name']),
                  );
                }).toList(),
                onChanged: (val) => setState(() => _selectedAccountId = val),
              ),
              const SizedBox(height: 16),

              // Conditional views
              if (_type == 'income' || _type == 'expense') ...[
                DropdownButtonFormField<int>(
                  value: _selectedCategoryId,
                  decoration: const InputDecoration(labelText: 'Kategoriya (Daraxt)'),
                  items: widget.categories.map<DropdownMenuItem<int>>((cat) {
                    return DropdownMenuItem<int>(
                      value: cat['id'],
                      child: Text(cat['name']),
                    );
                  }).toList(),
                  onChanged: (val) => setState(() => _selectedCategoryId = val),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: _selectedCounterpartyId,
                  decoration: const InputDecoration(labelText: 'Kontragent'),
                  items: widget.counterparties.map<DropdownMenuItem<int>>((cp) {
                    return DropdownMenuItem<int>(
                      value: cp['id'],
                      child: Text(cp['name']),
                    );
                  }).toList(),
                  onChanged: (val) => setState(() => _selectedCounterpartyId = val),
                ),
                const SizedBox(height: 16),
              ],

              if (_type == 'transfer') ...[
                DropdownButtonFormField<int>(
                  value: _selectedDestAccountId,
                  decoration: const InputDecoration(labelText: 'Qabul qiluvchi Kassa'),
                  items: widget.accounts.map<DropdownMenuItem<int>>((acc) {
                    return DropdownMenuItem<int>(
                      value: acc['id'],
                      child: Text(acc['name']),
                    );
                  }).toList(),
                  onChanged: (val) => setState(() => _selectedDestAccountId = val),
                ),
                const SizedBox(height: 16),
              ],

              if (_type == 'exchange') ...[
                DropdownButtonFormField<String>(
                  value: _toCurrency,
                  decoration: const InputDecoration(labelText: 'Qabul qiluvchi Valyuta'),
                  items: const [
                    DropdownMenuItem(value: 'USD', child: Text('USD')),
                    DropdownMenuItem(value: 'UZS', child: Text('UZS')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _toCurrency = val);
                  },
                ),
                const SizedBox(height: 16),
                NeumorphicTextField(
                  controller: _rateController,
                  labelText: 'Ayirboshlash Kursi',
                  hintText: 'Masalan, 12700',
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                const SizedBox(height: 16),
              ],

              // Note Field
              NeumorphicTextField(
                controller: _noteController,
                labelText: 'Izoh',
                hintText: 'Tranzaksiya tafsilotlari...',
              ),
              const SizedBox(height: 24),

              // File / Camera upload
              NeumorphicButton(
                onTap: _pickImage,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.camera_alt_outlined, color: AppColors.textPrimary),
                    const SizedBox(width: 12),
                    Text(
                      _imageFile == null ? 'FOTO/CHEK ILОВА QILISH' : 'RASM TANLANDI',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
              if (_imageFile != null) ...[
                const SizedBox(height: 12),
                Center(
                  child: Image.file(_imageFile!, height: 120, fit: BoxFit.cover),
                ),
              ],
              const SizedBox(height: 40),

              // Submit Button
              NeumorphicButton(
                onTap: _isLoading ? null : _submit,
                gradientColors: AppColors.greenGradient,
                child: Center(
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text(
                          'TRANZAKSIYANI SAQLASH',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTypeBtn(String value, String label) {
    final isSelected = _type == value;
    return GestureDetector(
      onTap: () => setState(() => _type = value),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: isSelected
            ? NeumorphicDecorations.sunken(radius: 8)
            : NeumorphicDecorations.extruded(radius: 8),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.bold,
            color: isSelected ? AppColors.success : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }

  Widget _buildNumKey(String value) {
    return GestureDetector(
      onTap: () => _onKeyPress(value),
      child: Container(
        width: 60,
        height: 44,
        alignment: Alignment.center,
        child: Text(
          value,
          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
        ),
      ),
    );
  }
}
