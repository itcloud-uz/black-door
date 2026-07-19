import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';
import '../../models/models.dart';

class UserFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? user;
  final VoidCallback onSuccess;

  const UserFormScreen({Key? key, this.user, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<UserFormScreen> createState() => _UserFormScreenState();
}

class _UserFormScreenState extends ConsumerState<UserFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  late TextEditingController _emailController;
  late TextEditingController _passwordController;
  late TextEditingController _pinController;
  String _selectedRole = 'employee';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.user?['name']);
    _phoneController = TextEditingController(text: widget.user?['phone']);
    _emailController = TextEditingController(text: widget.user?['email']);
    _passwordController = TextEditingController();
    _pinController = TextEditingController();
    if (widget.user != null) {
      _selectedRole = widget.user!['role'];
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _pinController.dispose();
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
          'email': _emailController.text,
          'role': _selectedRole,
        };

        if (_passwordController.text.isNotEmpty) {
          data['password'] = _passwordController.text;
        }
        if (_pinController.text.isNotEmpty) {
          data['pin_code'] = _pinController.text;
        }

        final response = widget.user == null
            ? await client.post('/admin/users', data: data)
            : await client.put('/admin/users/${widget.user!['id']}', data: data);

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(widget.user == null ? 'Foydalanuvchi yaratildi' : 'Yangilandi')),
          );
        }
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Xatolik yuz berdi')),
        );
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
        title: Text(widget.user == null ? 'YANGI FOYDALANUVCHI' : 'TAHRIRLASH'),
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
                hintText: 'Masalan: Aliyev Vali',
                validator: (v) => v?.isEmpty ?? true ? 'Ismni kiriting' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _phoneController,
                labelText: 'Telefon',
                hintText: '+998901234567',
                keyboardType: TextInputType.phone,
                validator: (v) => v?.isEmpty ?? true ? 'Telefonni kiriting' : null,
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _emailController,
                labelText: 'Email',
                hintText: 'user@example.com',
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 20),

              // Role Selector
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(left: 8, bottom: 8),
                    child: Text('Tizimdagi roli', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: NeumorphicDecorations.sunken(radius: 14),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedRole,
                        isExpanded: true,
                        items: const [
                          DropdownMenuItem(value: 'super_admin', child: Text('Super Admin')),
                          DropdownMenuItem(value: 'financier', child: Text('Finansist')),
                          DropdownMenuItem(value: 'manager', child: Text('Obyekt Menejeri')),
                          DropdownMenuItem(value: 'employee', child: Text('Xodim')),
                        ],
                        onChanged: (v) => setState(() => _selectedRole = v!),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              NeumorphicTextField(
                controller: _passwordController,
                labelText: widget.user == null ? 'Parol' : 'Yangi parol (ixtiyoriy)',
                obscureText: true,
                validator: (v) {
                  if (widget.user == null && (v?.isEmpty ?? true)) return 'Parolni kiriting';
                  if (v!.isNotEmpty && v.length < 8) return 'Kamida 8 belgi';
                  return null;
                },
              ),
              const SizedBox(height: 20),
              NeumorphicTextField(
                controller: _pinController,
                labelText: 'Moliya PIN kodi (4 ta raqam)',
                hintText: '1234',
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
            ],
          ),
        ),
      ),
    );
  }
}
