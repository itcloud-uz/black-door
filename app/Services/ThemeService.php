<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class ThemeService
{
    public static function generateThemeCss(): void
    {
        $accent = Setting::get('accent_color', 'green');
        
        $css = "/* Black Door Generated Theme CSS */\n";
        $css .= ":root {\n";
        if ($accent === 'blue') {
            $css .= "    --accent-green: var(--accent-blue) !important;\n";
            $css .= "    --accent-green-start: var(--accent-blue-start) !important;\n";
            $css .= "    --accent-green-end: var(--accent-blue-end) !important;\n";
        } elseif ($accent === 'red') {
            $css .= "    --accent-green: var(--accent-red) !important;\n";
            $css .= "    --accent-green-start: var(--accent-red-start) !important;\n";
            $css .= "    --accent-green-end: var(--accent-red-end) !important;\n";
        }
        $css .= "}\n";

        // Save to public disk (public/css/theme.css)
        $publicDir = public_path('css');
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        file_put_contents($publicDir . '/theme.css', $css);

        // Also save to MinIO S3 branding bucket as theme.css
        try {
            Storage::disk('s3')->put('branding/theme.css', $css);
        } catch (\Exception $e) {
            // Ignore if S3 is not ready/configured yet (e.g. during migrations/tests)
        }

        // Generate a new version hash and save to settings
        Setting::set('theme_css_version', md5($css));
    }
}
