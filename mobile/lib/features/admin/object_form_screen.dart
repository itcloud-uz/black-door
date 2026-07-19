import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';

class ObjectFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? object;
  final VoidCallback onSuccess;

  const ObjectFormScreen({Key? key, this.object, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<ObjectFormScreen> createState() => _ObjectFormScreenState();
}

class _ObjectFormScreenState extends ConsumerState<ObjectFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _addressController;
  late TextEditingController _noteController;
  String _selectedType = 'factory';
  int? _selectedManagerId;
  bool _isLoading = false;
  List<dynamic> _managers = [];

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.object?['name']);
    _addressController = TextEditingController(text: widget.object?['address']);
    _noteController = TextEditingController(text: widget.object?['note']);
    if (widget.object != null) {
      _selectedType = widget.object!['type'];
      _selectedManagerId = widget.object!['manager_id'];
    }
    _fetchManagers();
  }

  Future<void> _fetchManagers() async {
    try {
      final client = ref.read(apiClientProvider);
      final response = await client.get('/admin/users');
      if (response.statusCode == 200) {
        setState(() {
          _managers = (response.data['data'] as List).where((u) => u['role'] == 'manager').toList();
        });
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _nameController.dispose();
    _addressController.dispose();
    _noteController.dispose();
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
          'address': _addressController.text,
          'note': _noteController.text,
          'manager_id': _selectedManagerId,
        };

        final response = widget.object == null
            ? await client.post('/admin/objects', data: data)
            : await client.put('/admin/objects/${widget.object!['id']}', data: data);

        if (response.statusCode == 200 || response.statusCode == 201) {
          widget.onSuccess();
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(widget.object == null ? 'Obyekt yaratildi' : 'Yangilandi')),
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
        title: Text(widget.object == null ? 'YANGI OBYEKT' : 'TAHRIRLASH'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              NeumorphicTextField(
                controller: _nameController,
                labelText: 'Obyekt nomi',
                hintText: 'Masalan: TTZ Zavodi',
                validator: (v) => v?.isEmpty ?? true ? 'Nomni kiriting' : null,
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
                          DropdownMenuItem(value: 'factory', child: Text('Zavod')),
                          DropdownMenuItem(value: 'construction', child: Text('Qurilish obyekti')),
                          DropdownMenuItem(value: 'warehouse', child: Text('Omborxona')),
                        ],
                        onChanged: (v) => setState(() => _selectedType = v!),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              NeumorphicTextField(
                controller: _addressController,
                labelText: 'Manzil',
                hintText: 'Toshkent sh., ...',
              ),
              const SizedBox(height: 20),

              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(left: 8, bottom: 8),
                    child: Text('Mas\'ul menejer', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: NeumorphicDecorations.sunken(radius: 14),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<int>(
                        value: _selectedManagerId,
                        isExpanded: true,
                        hint: const Text('Menejerni tanlang'),
                        items: _managers.map((m) {
                          return DropdownMenuItem<int>(
                            value: m['id'],
                            child: Text(m['name']),
                          );
                        }).toList(),
                        onChanged: (v) => setState(() => _selectedManagerId = v),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              NeumorphicTextField(
                controller: _noteController,
                labelText: 'Izoh',
                hintText: 'Qo\'shimcha ma\'lumotlar...',
              ),
              const SizedBox(height: 40),

              NeumorphicButton(
                onTap: _isLoading ? null : _save,
                gradientColors: AppColors.blueGradient,
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
