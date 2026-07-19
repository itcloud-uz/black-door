import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class EmployeeFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? employee;
  final VoidCallback onSuccess;

  const EmployeeFormScreen({Key? key, this.employee, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<EmployeeFormScreen> createState() => _EmployeeFormScreenState();
}

class _EmployeeFormScreenState extends ConsumerState<EmployeeFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  late TextEditingController _positionController;
  late TextEditingController _passwordController;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.employee?['name']);
    _phoneController = TextEditingController(text: widget.employee?['phone']);
    _positionController = TextEditingController(text: widget.employee?['position']);
    _passwordController = TextEditingController();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _positionController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      try {
        final client = ref.read(apiClientProvider);
        final data = {
          'name': _nameController.text,
          'phone': _phoneController.text,
          'position': _positionController.text,
        };

        if (_passwordController.text.isNotEmpty) {
          data['password'] = _passwordController.text;
        }

        final response = widget.employee == null
            ? await client.post('/manager/employees', data: data)
            : await client.put('/manager/employees/${widget.employee!['id']}', data: data);

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(widget.employee == null ? 'Xodim qo\'shildi' : 'Yangilandi')),
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
        content: const Text('Haqiqatan ham ushbu xodimni o\'chirmoqchimisiz?'),
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
        await client.delete('/manager/employees/${widget.employee!['id']}');
        widget.onSuccess();
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Xodim o\'chirildi')));
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
        title: Text(widget.employee == null ? 'YANGI XODIM' : 'TAHRIRLASH'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              NeumorphicTextField(
                controller: _nameController,
                labelText: 'F.I.Sh',
                hintText: 'Aliyev Vali',
                validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _phoneController,
                labelText: 'Telefon',
                hintText: '+998...',
                keyboardType: TextInputType.phone,
                validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _positionController,
                labelText: 'Lavozimi',
                hintText: 'Usta / Qorovul / ...',
                validator: (v) => v?.isEmpty ?? true ? 'Majburiy' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _passwordController,
                labelText: widget.employee == null ? 'Parol' : 'Yangi parol (ixtiyoriy)',
                obscureText: true,
                validator: (v) {
                  if (widget.employee == null && (v?.isEmpty ?? true)) return 'Majburiy';
                  return null;
                },
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
              if (widget.employee != null) ...[
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
