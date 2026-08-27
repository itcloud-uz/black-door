import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import 'package:webview_flutter_wkwebview/webview_flutter_wkwebview.dart';
import 'package:permission_handler/permission_handler.dart';
import '../../core/network/providers.dart';
import '../../core/theme/app_theme.dart';

class WebViewScreen extends ConsumerStatefulWidget {
  final String token;
  final String? redirectPath;
  
  const WebViewScreen({Key? key, required this.token, this.redirectPath}) : super(key: key);

  @override
  ConsumerState<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends ConsumerState<WebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  Future<void> _initWebView() async {
    // Request app-level Camera permission
    await Permission.camera.request();

    final baseUrl = kDebugMode ? 'http://10.0.2.2:8000' : 'https://blackdoor.uz';
    var autoLoginUrl = '$baseUrl/auth/autologin?token=${widget.token}';
    if (widget.redirectPath != null) {
      autoLoginUrl += '&redirect=${Uri.encodeComponent(widget.redirectPath!)}';
    }

    late final PlatformWebViewControllerCreationParams params;

    if (WebViewPlatform.instance is WebKitWebViewPlatform) {
      params = WebKitWebViewControllerCreationParams(
        allowsInlineMediaPlayback: true,
        mediaTypesRequiringUserAction: const <PlaybackMediaTypes>{},
      );
    } else {
      params = const PlatformWebViewControllerCreationParams();
    }

    _controller = WebViewController.fromPlatformCreationParams(
      params,
      onPermissionRequest: (WebViewPermissionRequest request) {
        request.grant();
      },
    )
      ..setUserAgent('BlackDoorMobile')
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onUrlChange: (UrlChange change) {
            if (change.url != null) {
              if (change.url!.contains('/login')) {
                // Web app logged out, trigger native logout
                ref.read(authProvider.notifier).logout();
              } else if (change.url!.contains('/finance/dashboard')) {
                // Face ID verified successfully!
                ref.read(pinProvider.notifier).setVerified(true);
                if (Navigator.canPop(context)) {
                  Navigator.pop(context);
                }
              }
            }
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint("WebView Error: ${error.description}");
          },
        ),
      );

    if (_controller.platform is AndroidWebViewController) {
      (_controller.platform as AndroidWebViewController)
          .setMediaPlaybackRequiresUserGesture(false);
    }

    _controller.loadRequest(Uri.parse(autoLoginUrl));
  }

  @override
  Widget build(BuildContext context) {
    final showAppBar = widget.redirectPath != null;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: showAppBar
          ? AppBar(
              backgroundColor: AppColors.background,
              elevation: 0,
              title: const Text('TIZIM SOZLAMALARI', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              leading: IconButton(
                icon: const Icon(Icons.arrow_back, color: AppColors.textPrimary),
                onPressed: () => Navigator.pop(context),
              ),
            )
          : null,
      body: SafeArea(
        child: Stack(
          children: [
            WebViewWidget(controller: _controller),
            if (_isLoading)
              const Center(
                child: CircularProgressIndicator(color: AppColors.success),
              ),
          ],
        ),
      ),
    );
  }
}
