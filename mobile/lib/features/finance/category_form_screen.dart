import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class CategoryFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? category;
  final VoidCallback onSuccess;

  const CategoryFormScreen({Key? key, this.category, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<CategoryFormScreen> createState() => _CategoryFormScreenState();
}

class _CategoryFormScreenState extends ConsumerState<CategoryFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  String _selectedType = 'expense';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.category?['name']);
    if (widget.category != null) {
      _selectedType = widget.category!['type'];
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        final data = {
          'name': _nameController.text,
          'type': _selectedType,
        };

        final response = widget.category == null
            ? await client.post('/finance/categories', data: data)
            : await client.put('/finance/categories/${widget.category!['id']}', data: data);

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(widget.category == null ? 'Kategoriya yaratildi' : 'Yangilandi')),
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
        content: const Text('Haqiqatan ham ushbu kategoriyani o\'chirmoqchimisiz?'),
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
        await client.delete('/finance/categories/${widget.category!['id']}');
        widget.onSuccess();
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kategoriya o\'chirildi')));
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
        title: Text(widget.category == null ? 'YANGI KATEGORIYA' : 'TAHRIRLASH'),
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
                hintText: 'Masalan: Qurilish mollari',
                validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
              ),
              const SizedBox(height: 20),

              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(left: 8, bottom: 8),
                    child: Text('Turi', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: NeumorphicDecorations.sunken(radius: 14),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedType,
                        isExpanded: true,
                        items: const [
                          DropdownMenuItem(value: 'income', child: Text('Kirim')),
                          DropdownMenuItem(value: 'expense', child: Text('Chiqim')),
                        ],
                        onChanged: (v) => setState(() => _selectedType = v!),
                      ),
                    ),
                  ),
                ],
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
              if (widget.category != null) ...[
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
