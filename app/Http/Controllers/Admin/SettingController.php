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

        // 1. Update Global Settings
        if ($request->has('company_name')) {
            $request->validate([
                'company_name' => 'required|string|max:50',
                'company_tagline' => 'required|string|max:100',
                'accent_color' => 'required|string|in:green,blue,red',
            ]);

            Setting::set('company_name', $request->input('company_name'));
            Setting::set('company_tagline', $request->input('company_tagline'));
            Setting::set('accent_color', $request->input('accent_color'));

            AuditLogger::log('settings_global_update', $user, null, [
                'company_name' => $request->input('company_name'),
                'accent_color' => $request->input('accent_color'),
            ]);

            return back()->with('success', 'Global sozlamalar muvaffaqiyatli saqlandi!');
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
