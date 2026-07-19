import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class ProductFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? product;
  final VoidCallback onSuccess;

  const ProductFormScreen({Key? key, this.product, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<ProductFormScreen> createState() => _ProductFormScreenState();
}

class _ProductFormScreenState extends ConsumerState<ProductFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _unitController;
  late TextEditingController _minLimitController;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.product?['name']);
    _unitController = TextEditingController(text: widget.product?['unit'] ?? 'ta');
    _minLimitController = TextEditingController(text: widget.product?['min_limit']?.toString() ?? '0');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _unitController.dispose();
    _minLimitController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        final data = {
          'name': _nameController.text,
          'unit': _unitController.text,
          'min_limit': int.tryParse(_minLimitController.text) ?? 0,
        };

        final response = widget.product == null
            ? await client.post('/admin/products', data: data)
            : await client.put('/admin/products/${widget.product!['id']}', data: data);

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(widget.product == null ? 'Mahsulot yaratildi' : 'Yangilandi')),
          );
        }
      } catch (_) {}
      setState(() => _isLoading = false);
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('O\'chirish'),
        content: const Text('Haqiqatan ham ushbu mahsulotni o\'chirmoqchimisiz?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('YO\'Q')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('HA, O\'CHIR')),
        ],
      ),
    );

    if (confirmed == true) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        await client.delete('/admin/products/${widget.product!['id']}');
        widget.onSuccess();
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mahsulot o\'chirildi')));
      } catch (_) {}
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        title: Text(widget.product == null ? 'YANGI MAHSULOT' : 'TAHRIRLASH'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              NeumorphicTextField(
                controller: _nameController,
                labelText: 'Nomi',
                hintText: 'G\'isht / Sement / ...',
                validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _unitController,
                labelText: 'O\'lchov birligi',
                hintText: 'ta / kg / m2 / ...',
                validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _minLimitController,
                labelText: 'Minimal qoldiq (limit)',
                hintText: '0',
                keyboardType: TextInputType.number,
              ),
              const SizedBox(height: 40),

              NeumorphicButton(
                onTap: _isLoading ? null : _save,
                gradientColors: AppColors.greenGradient,
                child: Center(
                  child: _isLoading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('SAQLASH', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.5)),
                ),
              ),
              if (widget.product != null) ...[
                const SizedBox(height: 20),
                NeumorphicButton(
                  onTap: _isLoading ? null : _delete,
                  gradientColors: AppColors.redGradient,
                  child: const Center(
                    child: Text('O\'CHIRISH', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.5)),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
