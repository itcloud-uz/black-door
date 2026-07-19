import 'package:flutter/material.dart';
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import '../theme/app_theme.dart';
import '../theme/neumorphic_decorations.dart';

/// --- Neumorphic Card ---
class NeumorphicCard extends StatelessWidget {
  final Widget child;
  final double radius;
  final EdgeInsets padding;
  final Color color;
  final BoxBorder? border;

  const NeumorphicCard({
    Key? key,
    required this.child,
    this.radius = 16,
    this.padding = const EdgeInsets.all(16),
    this.color = AppColors.surface,
    this.border,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: padding,
      decoration: NeumorphicDecorations.extruded(radius: radius, color: color).copyWith(
        border: border,
      ),
      child: child,
    );
  }
}

/// --- Neumorphic Pressable (Button) ---
class NeumorphicButton extends StatefulWidget {
  final Widget child;
  final VoidCallback? onTap;
  final double radius;
  final EdgeInsets padding;
  final List<Color>? gradientColors;
  final bool isCircular;
  final Color color;

  const NeumorphicButton({
    Key? key,
    required this.child,
    this.onTap,
    this.radius = 16,
    this.padding = const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
    this.gradientColors,
    this.isCircular = false,
    this.color = AppColors.surface,
  }) : super(key: key);

  @override
  State<NeumorphicButton> createState() => _NeumorphicButtonState();
}

class _NeumorphicButtonState extends State<NeumorphicButton> {
  bool _isPressed = false;

  void _handleTapDown(TapDownDetails details) {
    if (widget.onTap != null) {
      setState(() => _isPressed = true);
    }
  }

  void _handleTapUp(TapUpDetails details) {
    if (widget.onTap != null) {
      setState(() => _isPressed = false);
      widget.onTap!();
    }
  }

  void _handleTapCancel() {
    if (widget.onTap != null) {
      setState(() => _isPressed = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    InsetBoxDecoration dec;

    if (widget.isCircular) {
      dec = NeumorphicDecorations.circular(color: widget.color, pressed: _isPressed);
    } else if (widget.gradientColors != null) {
      dec = NeumorphicDecorations.gradient(
        colors: widget.gradientColors!,
        radius: widget.radius,
        pressed: _isPressed,
      );
    } else {
      dec = _isPressed
          ? NeumorphicDecorations.sunken(radius: widget.radius, color: widget.color)
          : NeumorphicDecorations.extruded(radius: widget.radius, color: widget.color);
    }

    return GestureDetector(
      onTapDown: _handleTapDown,
      onTapUp: _handleTapUp,
      onTapCancel: _handleTapCancel,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 100),
        padding: widget.padding,
        decoration: dec,
        child: widget.child,
      ),
    );
  }
}

/// --- Neumorphic Text Field ---
class NeumorphicTextField extends StatelessWidget {
  final TextEditingController? controller;
  final String? labelText;
  final String? hintText;
  final bool obscureText;
  final TextInputType keyboardType;
  final ValueChanged<String>? onChanged;
  final FormFieldValidator<String>? validator;
  final IconData? prefixIcon;
  final Widget? suffixIcon;
  final bool readOnly;
  final VoidCallback? onTap;

  const NeumorphicTextField({
    Key? key,
    this.controller,
    this.labelText,
    this.hintText,
    this.obscureText = false,
    this.keyboardType = TextInputType.text,
    this.onChanged,
    this.validator,
    this.prefixIcon,
    this.suffixIcon,
    this.readOnly = false,
    this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (labelText != null) ...[
          Padding(
            padding: const EdgeInsets.only(left: 8.0, bottom: 6.0),
            child: Text(
              labelText!,
              style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.bold),
            ),
          ),
        ],
        Container(
          decoration: NeumorphicDecorations.sunken(radius: 14),
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: TextFormField(
            controller: controller,
            obscureText: obscureText,
            keyboardType: keyboardType,
            onChanged: onChanged,
            validator: validator,
            readOnly: readOnly,
            onTap: onTap,
            style: textTheme.bodyLarge,
            decoration: InputDecoration(
              hintText: hintText,
              hintStyle: textTheme.bodyMedium?.copyWith(color: AppColors.textMuted),
              border: InputBorder.none,
              prefixIcon: prefixIcon != null ? Icon(prefixIcon, color: AppColors.textMuted) : null,
              suffixIcon: suffixIcon,
              contentPadding: const EdgeInsets.symmetric(vertical: 14),
            ),
          ),
        ),
      ],
    );
  }
}

/// --- Neumorphic Custom Keyboard for PIN ---
class NeumorphicKeyboard extends StatelessWidget {
  final Function(String) onKeyPressed;
  final VoidCallback onDeletePressed;

  const NeumorphicKeyboard({
    Key? key,
    required this.onKeyPressed,
    required this.onDeletePressed,
  }) : super(key: key);

  Widget _buildKey(String value, BuildContext context) {
    return NeumorphicButton(
      isCircular: true,
      onTap: () => onKeyPressed(value),
      padding: const EdgeInsets.all(22),
      child: Center(
        child: Text(
          value,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontSize: 24,
                fontWeight: FontWeight.w800,
              ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _buildKey('1', context),
            _buildKey('2', context),
            _buildKey('3', context),
          ],
        ),
        const SizedBox(height: 18),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _buildKey('4', context),
            _buildKey('5', context),
            _buildKey('6', context),
          ],
        ),
        const SizedBox(height: 18),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _buildKey('7', context),
            _buildKey('8', context),
            _buildKey('9', context),
          ],
        ),
        const SizedBox(height: 18),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            const SizedBox(width: 80), // Spacer for placeholder
            _buildKey('0', context),
            NeumorphicButton(
              isCircular: true,
              onTap: onDeletePressed,
              padding: const EdgeInsets.all(22),
              child: const Center(
                child: Icon(Icons.backspace_outlined, color: AppColors.textPrimary, size: 24),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

/// --- Neumorphic Logo (Arch with Keyhole) ---
class NeumorphicLogo extends StatelessWidget {
  final double size;
  const NeumorphicLogo({Key? key, this.size = 100}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: NeumorphicDecorations.extruded(radius: size * 0.25),
      child: Center(
        child: Container(
          width: size * 0.55,
          height: size * 0.65,
          decoration: InsetBoxDecoration(
            color: const Color(0xFFF0F3F8),
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(size * 0.3),
              topRight: Radius.circular(size * 0.3),
              bottomLeft: Radius.circular(size * 0.08),
              bottomRight: Radius.circular(size * 0.08),
            ),
            boxShadow: [
              const InsetBoxShadow(
                color: Color(0xFFC9D2DE),
                offset: Offset(4, 4),
                blurRadius: 8,
              ),
              const InsetBoxShadow(
                color: Colors.white,
                offset: Offset(-4, -4),
                blurRadius: 8,
              ),
            ],
          ),
          child: Center(
            child: Stack(
              alignment: Alignment.center,
              children: [
                // Glowing Keyhole
                Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: size * 0.12,
                      height: size * 0.12,
                      decoration: InsetBoxDecoration(
                        color: AppColors.success.withOpacity(0.9),
                        shape: BoxShape.circle,
                        boxShadow: [
                          InsetBoxShadow(
                            color: AppColors.success.withOpacity(0.6),
                            blurRadius: 12,
                            spreadRadius: 2,
                          ),
                        ],
                      ),
                    ),
                    Container(
                      width: size * 0.08,
                      height: size * 0.15,
                      margin: const EdgeInsets.only(top: 1),
                      decoration: InsetBoxDecoration(
                        color: AppColors.success.withOpacity(0.9),
                        borderRadius: BorderRadius.circular(size * 0.02),
                        boxShadow: [
                          InsetBoxShadow(
                            color: AppColors.success.withOpacity(0.4),
                            blurRadius: 8,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
