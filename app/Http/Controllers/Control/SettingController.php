<?php

declare(strict_types=1);

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Jobs\ProcessLogoBranding;
use App\Jobs\RebuildMobileApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        return view('control.settings.index', [
            'companyName' => Setting::get('company_name', 'Black Door'),
            'companyTagline' => Setting::get('company_tagline', 'Moliyaviy Boshqaruv'),
            'accentColor' => Setting::get('accent_color', 'green'),
            'supportPhone' => Setting::get('support_phone', '+998911873730'),
            'supportEmail' => Setting::get('support_email', 'itclouduz@gmail.com'),
            'supportTelegram' => Setting::get('support_telegram', '@ITclouduz_me'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'nullable|string|max:50',
            'company_tagline' => 'nullable|string|max:100',
            'accent_color' => 'nullable|string|in:green,blue,red',
            'support_phone' => 'nullable|string|max:50',
            'support_email' => 'nullable|string|max:100',
            'support_telegram' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:4096',
        ]);

        if ($request->has('company_name')) {
            Setting::set('company_name', $request->input('company_name'));
            Setting::set('company_tagline', $request->input('company_tagline'));
            Setting::set('accent_color', $request->input('accent_color'));
            Setting::set('support_phone', $request->input('support_phone'));
            Setting::set('support_email', $request->input('support_email'));
            Setting::set('support_telegram', $request->input('support_telegram'));

            // Regenerate theme CSS
            \App\Services\ThemeService::generateThemeCss();
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $tempPath = $file->store('temp_branding', 'local');
            
            // Run logo processor synchronously
            ProcessLogoBranding::dispatchSync($tempPath, Auth::id());
        }

        return back()->with('success', 'Tizim sozlamalari va mobil ilova logosi muvaffaqiyatli saqlandi! Yuz tanish logosi hamda mobil ilova ikonkalari fonda yangilanmoqda.');
    }

    public function rebuildApk()
    {
        $flutterPath = 'C:\\flutter\\bin\\flutter.bat';
        if (!file_exists($flutterPath)) {
            // Check path
            $output = [];
            $code = 0;
            exec('flutter --version', $output, $code);
            if ($code !== 0) {
                return back()->withErrors(['error' => 'Tizimda (Serverda) Flutter SDK o\'rnatilmagan. Ilovani faqat kompyuteringiz orqali build qilishingiz mumkin.']);
            }
        }

        // Dispatch rebuild job
        RebuildMobileApp::dispatch();

        return back()->with('success', 'Mobil ilovani qayta qurish vazifasi fonda ishga tushirildi! Bir ozdan so\'ng yangilangan APK yuklab olishga tayyor bo\'ladi.');
    }
}
