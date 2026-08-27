<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RebuildMobileApp implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600; // 10 minutes timeout

    public function __construct()
    {
    }

    public function handle(): void
    {
        Log::info("RebuildMobileApp job started.");
        
        $flutterPath = 'C:\\flutter\\bin\\flutter.bat';
        if (!file_exists($flutterPath)) {
            // Fallback to path
            $flutterPath = 'flutter';
        }

        $mobilePath = base_path('mobile');

        // 1. Run flutter clean
        $cmdClean = "cd /d " . escapeshellarg($mobilePath) . " && " . escapeshellarg($flutterPath) . " clean";
        Log::info("Executing: " . $cmdClean);
        exec($cmdClean, $outputClean, $codeClean);
        Log::info("Flutter clean exit code: {$codeClean}");

        // 2. Run flutter pub get
        $cmdPub = "cd /d " . escapeshellarg($mobilePath) . " && " . escapeshellarg($flutterPath) . " pub get";
        Log::info("Executing: " . $cmdPub);
        exec($cmdPub, $outputPub, $codePub);
        Log::info("Flutter pub get exit code: {$codePub}");

        // 3. Run flutter_launcher_icons
        $cmdIcons = "cd /d " . escapeshellarg($mobilePath) . " && " . escapeshellarg($flutterPath) . " pub run flutter_launcher_icons";
        Log::info("Executing: " . $cmdIcons);
        exec($cmdIcons, $outputIcons, $codeIcons);
        Log::info("Flutter launcher icons exit code: {$codeIcons}");

        // 4. Run flutter_native_splash
        $cmdSplash = "cd /d " . escapeshellarg($mobilePath) . " && " . escapeshellarg($flutterPath) . " pub run flutter_native_splash:create";
        Log::info("Executing: " . $cmdSplash);
        exec($cmdSplash, $outputSplash, $codeSplash);
        Log::info("Flutter native splash exit code: {$codeSplash}");

        // 5. Build APK
        $cmdBuild = "cd /d " . escapeshellarg($mobilePath) . " && " . escapeshellarg($flutterPath) . " build apk --release";
        Log::info("Executing: " . $cmdBuild);
        exec($cmdBuild, $outputBuild, $codeBuild);
        Log::info("Flutter build apk exit code: {$codeBuild}");

        if ($codeBuild === 0) {
            $srcApk = $mobilePath . '/build/app/outputs/flutter-apk/app-release.apk';
            $dstApk = public_path('downloads/blackdoor.apk');
            
            $dstDir = dirname($dstApk);
            if (!file_exists($dstDir)) {
                mkdir($dstDir, 0777, true);
            }

            if (file_exists($srcApk)) {
                copy($srcApk, $dstApk);
                Log::info("Successfully copied rebuilt APK to: " . $dstApk);
            } else {
                Log::error("Build succeeded but APK file not found at: " . $srcApk);
            }
        } else {
            Log::error("Flutter build failed with exit code: {$codeBuild}");
        }
    }
}
