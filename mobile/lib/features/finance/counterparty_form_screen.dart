import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class CounterpartyFormScreen extends ConsumerStatefulWidget {
  final VoidCallback onSuccess;

  const CounterpartyFormScreen({Key? key, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<CounterpartyFormScreen> createState() => _CounterpartyFormScreenState();
}

class _CounterpartyFormScreenState extends ConsumerState<CounterpartyFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _noteController = TextEditingController();
  String _selectedCategory = 'supplier';
  bool _isLoading = false;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        final response = await client.post('/finance/counterparties', data: {
          'name': _nameController.text,
          'phone': _phoneController.text,
          'note': _noteController.text,
          'category': _selectedCategory,
        });

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kontragent yaratildi')));
        }
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
        title: const Text('YANGI KONTRAGENT'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              NeumorphicTextField(
                controller: _nameController,
                labelText: 'Nomi / F.I.Sh',
                hintText: 'Masalan: Akmal aka',
                validator: (v) => v?.isEmpty ?? true ? 'Nomni kiriting' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _phoneController,
                labelText: 'Telefon',
                hintText: '+99890...',
              ),
              const SizedBox(height: 20),

              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(left: 8, bottom: 8),
                    child: Text('Toifa', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: NeumorphicDecorations.sunken(radius: 14),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedCategory,
                        isExpanded: true,
                        items: const [
                          DropdownMenuItem(value: 'supplier', child: Text('Yetkazib beruvchi')),
                          DropdownMenuItem(value: 'client', child: Text('Mijoz')),
                          DropdownMenuItem(value: 'partner', child: Text('Hamkor')),
                          DropdownMenuItem(value: 'other', child: Text('Boshqa')),
                        ],
                        onChanged: (v) => setState(() => _selectedCategory = v!),
                      ),
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
                onTap: _isLoading ? null : _save,
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
