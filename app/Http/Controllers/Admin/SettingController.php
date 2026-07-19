<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    /**
     * Display settings page.
     */
    public function index()
    {
        $user = Auth::user();
        
        return view('admin.settings.index', [
            'user' => $user,
            'companyName' => Setting::get('company_name', 'Black Door'),
            'companyTagline' => Setting::get('company_tagline', 'Moliyaviy Boshqaruv'),
            'accentColor' => Setting::get('accent_color', 'green'),
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // 1. Update Global Settings & Logo
        if ($request->has('company_name') || $request->hasFile('logo')) {
            $request->validate([
                'company_name' => 'nullable|string|max:50',
                'company_tagline' => 'nullable|string|max:100',
                'accent_color' => 'nullable|string|in:green,blue,red',
                'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            ]);

            if ($request->has('company_name')) {
                Setting::set('company_name', $request->input('company_name'));
                Setting::set('company_tagline', $request->input('company_tagline'));
                Setting::set('accent_color', $request->input('accent_color'));
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                
                $brandingPath = base_path('branding/mark.png');
                $publicBrandingPath = public_path('branding/mark.png');
                $mobileBrandingPath = base_path('mobile/assets/branding/mark.png');

                // Ensure directories exist
                if (!file_exists(dirname($brandingPath))) {
                    mkdir(dirname($brandingPath), 0777, true);
                }
                if (!file_exists(dirname($publicBrandingPath))) {
                    mkdir(dirname($publicBrandingPath), 0777, true);
                }
                if (!file_exists(dirname($mobileBrandingPath))) {
                    mkdir(dirname($mobileBrandingPath), 0777, true);
                }

                // Save new logo mark to all three places
                $file->move(dirname($brandingPath), basename($brandingPath));
                copy($brandingPath, $publicBrandingPath);
                copy($brandingPath, $mobileBrandingPath);

                // Run compilation scripts
                $faviconsScript = 'C:\Users\ITCloud\.gemini\antigravity\brain\77ffb933-087e-4693-9873-5fe5adbe620c/scratch/generate_favicons.php';
                $androidScript = 'C:\Users\ITCloud\.gemini\antigravity\brain\77ffb933-087e-4693-9873-5fe5adbe620c/scratch/generate_android_branding.php';
                $assemblerScript = 'C:\Users\ITCloud\.gemini\antigravity\brain\77ffb933-087e-4693-9873-5fe5adbe620c/scratch/assemble_logos.php';

                if (file_exists($faviconsScript)) {
                    shell_exec("php " . escapeshellarg($faviconsScript));
                }
                if (file_exists($androidScript)) {
                    shell_exec("php " . escapeshellarg($androidScript));
                }
                if (file_exists($assemblerScript)) {
                    shell_exec("php " . escapeshellarg($assemblerScript));
                }
                
                // Copy assembled vertical/horizontal logos to public and mobile assets
                if (file_exists(base_path('branding/logo_vertical.png'))) {
                    copy(base_path('branding/logo_vertical.png'), public_path('branding/logo_vertical.png'));
                    copy(base_path('branding/logo_vertical.png'), base_path('mobile/assets/branding/logo_vertical.png'));
                }
                if (file_exists(base_path('branding/logo_horizontal.png'))) {
                    copy(base_path('branding/logo_horizontal.png'), public_path('branding/logo_horizontal.png'));
                    copy(base_path('branding/logo_horizontal.png'), base_path('mobile/assets/branding/logo_horizontal.png'));
                }
            }

            AuditLogger::log('settings_global_update', $user, null, [
                'company_name' => $request->input('company_name'),
                'accent_color' => $request->input('accent_color'),
                'logo_updated' => $request->hasFile('logo'),
            ]);

            return back()->with('success', 'Global sozlamalar va yangi logotip muvaffaqiyatli saqlandi!');
        }

        // 2. Update Security (PIN or Password)
        if ($request->has('old_password') || $request->has('new_password')) {
            $request->validate([
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if (!Hash::check($request->input('old_password'), $user->password)) {
                return back()->withErrors(['old_password' => 'Eski parol noto\'g\'ri!']);
            }

            $user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            AuditLogger::log('settings_password_update', $user);

            return back()->with('success', 'Tizim paroli muvaffaqiyatli yangilandi!');
        }

        if ($request->has('new_pin')) {
            $request->validate([
                'current_password' => 'required|string',
                'new_pin' => 'required|string|size:4',
            ]);

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return back()->withErrors(['current_password' => 'Tasdiqlash paroli noto\'g\'ri!']);
            }

            $user->update([
                'pin_code' => Hash::make($request->input('new_pin')),
            ]);

            AuditLogger::log('settings_pin_update', $user);

            return back()->with('success', 'Moliya PIN kodi muvaffaqiyatli yangilandi!');
        }

        return back();
    }
}
