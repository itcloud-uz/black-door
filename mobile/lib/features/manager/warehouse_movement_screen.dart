import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class WarehouseMovementScreen extends ConsumerStatefulWidget {
  final List<dynamic> products;
  final VoidCallback onSuccess;

  const WarehouseMovementScreen({Key? key, required this.products, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<WarehouseMovementScreen> createState() => _WarehouseMovementScreenState();
}

class _WarehouseMovementScreenState extends ConsumerState<WarehouseMovementScreen> {
  final _formKey = GlobalKey<FormState>();
  final _quantityController = TextEditingController();
  final _noteController = TextEditingController();
  String _movementType = 'incoming';
  int? _selectedProductId;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    if (widget.products.isNotEmpty) {
      _selectedProductId = widget.products.first['product_id'];
    }
  }

  @override
  void dispose() {
    _quantityController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate() && _selectedProductId != null) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        final response = await client.post('/manager/movements', data: {
          'product_id': _selectedProductId,
          'type': _movementType,
          'quantity': _quantityController.text,
          'note': _noteController.text,
        });

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Muvaffaqiyatli saqlandi')));
        }
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Xatolik: Yetarli qoldiq mavjud emas')));
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
        title: const Text('OMBOR HARAKATI'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(left: 8, bottom: 8),
                    child: Text('Mahsulot', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: NeumorphicDecorations.sunken(radius: 14),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<int>(
                        value: _selectedProductId,
                        isExpanded: true,
                        items: widget.products.map((p) {
                          return DropdownMenuItem<int>(value: p['product_id'], child: Text(p['name']));
                        }).toList(),
                        onChanged: (v) => setState(() => _selectedProductId = v),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Amal turi', style: TextStyle(fontWeight: FontWeight.bold)),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: NeumorphicDecorations.sunken(radius: 14),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _movementType,
                              items: const [
                                DropdownMenuItem(value: 'incoming', child: Text('Kirim')),
                                DropdownMenuItem(value: 'outgoing', child: Text('Chiqim')),
                              ],
                              onChanged: (v) => setState(() => _movementType = v!),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: NeumorphicTextField(
                      controller: _quantityController,
                      labelText: 'Miqdor',
                      hintText: '0',
                      keyboardType: TextInputType.number,
                      validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              NeumorphicTextField(
                controller: _noteController,
                labelText: 'Izoh',
                hintText: 'Qo\'shimcha...',
              ),
              const SizedBox(height: 40),

              NeumorphicButton(
                onTap: _isLoading ? null : _submit,
                gradientColors: AppColors.greenGradient,
                child: Center(
                  child: _isLoading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('SAQLASH', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.5)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
