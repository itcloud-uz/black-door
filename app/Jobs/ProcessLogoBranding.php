<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Events\BrandingUpdated;
use App\Models\User;
use App\Notifications\BrandingFailedNotification;

class ProcessLogoBranding implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    protected string $tempFilePath;
    protected int $userId;

    public function __construct(string $tempFilePath, int $userId)
    {
        $this->tempFilePath = $tempFilePath;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        if (!Storage::disk('local')->exists($this->tempFilePath)) {
            throw new \Exception("Source file not found in storage: " . $this->tempFilePath);
        }

        $tempDir = storage_path('app/temp_branding_' . uniqid());
        if (!mkdir($tempDir, 0777, true)) {
            throw new \Exception("Could not create temp directory: " . $tempDir);
        }

        $sourcePath = $tempDir . '/uploaded_mark.png';
        file_put_contents($sourcePath, Storage::disk('local')->get($this->tempFilePath));

        try {
            // Load source image
            $info = getimagesize($sourcePath);
            if (!$info) {
                throw new \Exception("Could not read image details from " . $sourcePath);
            }
            $mime = $info['mime'];
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                $srcImg = imagecreatefromjpeg($sourcePath);
            } elseif ($mime === 'image/png') {
                $srcImg = imagecreatefrompng($sourcePath);
            } else {
                throw new \Exception("Unsupported image type: " . $mime);
            }

            if (!$srcImg) {
                throw new \Exception("Could not load image from " . $sourcePath);
            }

            // 1. Generate Favicons
            $favicons = [
                'favicon-16x16.png' => 16,
                'favicon-32x32.png' => 32,
                'apple-touch-icon.png' => 180,
                'android-chrome-192x192.png' => 192,
                'android-chrome-512x512.png' => 512,
            ];
            foreach ($favicons as $name => $size) {
                $dstImg = imagecreatetruecolor($size, $size);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                imagefilledrectangle($dstImg, 0, 0, $size, $size, $transparent);
                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $size, $size, imagesx($srcImg), imagesy($srcImg));
                imagepng($dstImg, $tempDir . '/' . $name);
                imagedestroy($dstImg);
            }
            copy($tempDir . '/favicon-32x32.png', $tempDir . '/favicon.ico');

            // 2. Generate Android Launcher Icons
            $launcherSizes = [
                'mipmap-mdpi' => 48,
                'mipmap-hdpi' => 72,
                'mipmap-xhdpi' => 96,
                'mipmap-xxhdpi' => 144,
                'mipmap-xxxhdpi' => 192,
            ];
            foreach ($launcherSizes as $folder => $size) {
                $dstImg = imagecreatetruecolor($size, $size);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                imagefilledrectangle($dstImg, 0, 0, $size, $size, $transparent);
                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $size, $size, imagesx($srcImg), imagesy($srcImg));
                
                if (!file_exists($tempDir . '/' . $folder)) {
                    mkdir($tempDir . '/' . $folder, 0777, true);
                }
                imagepng($dstImg, $tempDir . '/' . $folder . '/ic_launcher.png');
                imagedestroy($dstImg);
            }

            // Foreground Adaptive Icons
            $foregroundSizes = [
                'mipmap-mdpi' => 108,
                'mipmap-hdpi' => 162,
                'mipmap-xhdpi' => 216,
                'mipmap-xxhdpi' => 324,
                'mipmap-xxxhdpi' => 432,
            ];
            foreach ($foregroundSizes as $folder => $size) {
                $dstImg = imagecreatetruecolor($size, $size);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                imagefilledrectangle($dstImg, 0, 0, $size, $size, $transparent);
                
                $logoSize = (int)($size * 0.66);
                $offset = (int)(($size - $logoSize) / 2);
                imagecopyresampled($dstImg, $srcImg, $offset, $offset, 0, 0, $logoSize, $logoSize, imagesx($srcImg), imagesy($srcImg));
                
                if (!file_exists($tempDir . '/' . $folder)) {
                    mkdir($tempDir . '/' . $folder, 0777, true);
                }
                imagepng($dstImg, $tempDir . '/' . $folder . '/ic_launcher_foreground.png');
                imagedestroy($dstImg);
            }

            // 3. Save custom vertical and horizontal logos directly from the uploaded source image preserving transparency
            $markWidth = imagesx($srcImg);
            $markHeight = imagesy($srcImg);

            // Vertical logo
            $vCanvas = imagecreatetruecolor($markWidth, $markHeight);
            imagealphablending($vCanvas, false);
            imagesavealpha($vCanvas, true);
            $transparent = imagecolorallocatealpha($vCanvas, 255, 255, 255, 127);
            imagefilledrectangle($vCanvas, 0, 0, $markWidth, $markHeight, $transparent);
            imagecopyresampled($vCanvas, $srcImg, 0, 0, 0, 0, $markWidth, $markHeight, $markWidth, $markHeight);
            imagepng($vCanvas, $tempDir . '/custom_logo_vertical.png');
            imagedestroy($vCanvas);

            // Horizontal logo
            $hCanvas = imagecreatetruecolor($markWidth, $markHeight);
            imagealphablending($hCanvas, false);
            imagesavealpha($hCanvas, true);
            $transparentH = imagecolorallocatealpha($hCanvas, 255, 255, 255, 127);
            imagefilledrectangle($hCanvas, 0, 0, $markWidth, $markHeight, $transparentH);
            imagecopyresampled($hCanvas, $srcImg, 0, 0, 0, 0, $markWidth, $markHeight, $markWidth, $markHeight);
            imagepng($hCanvas, $tempDir . '/custom_logo_horizontal.png');
            imagedestroy($hCanvas);

            imagedestroy($srcImg);

            // Copy source file to tempDir as custom_mark.png
            copy($sourcePath, $tempDir . '/custom_mark.png');

            // 4. ATOMIC MOVE: Copy to final locations (Local public and S3/MinIO)
            $this->atomicPublishBranding($tempDir);

            // Notify Admin of success via Reverb
            try {
                broadcast(new BrandingUpdated("Brending yangilandi"))->toOthers();
            } catch (\Exception $e) {
                Log::warning("Reverb broadcast failed: " . $e->getMessage());
            }

        } catch (\Exception $e) {
            $this->cleanUpDir($tempDir);
            throw $e;
        } finally {
            $this->cleanUpDir($tempDir);
            Storage::disk('local')->delete($this->tempFilePath);
        }
    }

    protected function atomicPublishBranding(string $tempDir): void
    {
        // Save locally to public/branding and base_path('branding')
        $brandingDir = base_path('branding');
        $publicBrandingDir = public_path('branding');
        
        if (!file_exists($brandingDir)) mkdir($brandingDir, 0777, true);
        if (!file_exists($publicBrandingDir)) mkdir($publicBrandingDir, 0777, true);

        // Copy custom mark
        copy($tempDir . '/custom_mark.png', $brandingDir . '/custom_mark.png');
        copy($tempDir . '/custom_mark.png', $publicBrandingDir . '/custom_mark.png');

        // Copy favicons to public root
        $favicons = ['favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'favicon.ico'];
        foreach ($favicons as $fav) {
            if (file_exists($tempDir . '/' . $fav)) {
                copy($tempDir . '/' . $fav, public_path($fav));
                
                // Write to S3/MinIO if bucket ready
                try {
                    Storage::disk('s3')->put('branding/' . $fav, file_get_contents($tempDir . '/' . $fav));
                } catch (\Exception $e) {
                    Log::warning("S3 upload failed for {$fav}: " . $e->getMessage());
                }
            }
        }

        // Copy vertical/horizontal logos
        $logos = ['custom_logo_vertical.png', 'custom_logo_horizontal.png'];
        foreach ($logos as $logo) {
            if (file_exists($tempDir . '/' . $logo)) {
                copy($tempDir . '/' . $logo, $brandingDir . '/' . $logo);
                copy($tempDir . '/' . $logo, $publicBrandingDir . '/' . $logo);
                
                // Mobile assets
                $mobileDir = base_path('mobile/assets/branding');
                if (!file_exists($mobileDir)) mkdir($mobileDir, 0777, true);
                copy($tempDir . '/' . $logo, $mobileDir . '/' . $logo);
                
                // Write to S3/MinIO
                try {
                    Storage::disk('s3')->put('branding/' . $logo, file_get_contents($tempDir . '/' . $logo));
                } catch (\Exception $e) {
                    Log::warning("S3 upload failed for {$logo}: " . $e->getMessage());
                }
            }
        }

        // Copy android launcher icons
        $launcherFolders = ['mipmap-mdpi', 'mipmap-hdpi', 'mipmap-xhdpi', 'mipmap-xxhdpi', 'mipmap-xxxhdpi'];
        $resPath = base_path('mobile/android/app/src/main/res');
        foreach ($launcherFolders as $folder) {
            if (file_exists($tempDir . '/' . $folder)) {
                $targetFolder = $resPath . '/' . $folder;
                if (!file_exists($targetFolder)) mkdir($targetFolder, 0777, true);
                
                copy($tempDir . '/' . $folder . '/ic_launcher.png', $targetFolder . '/ic_launcher.png');
                copy($tempDir . '/' . $folder . '/ic_launcher_foreground.png', $targetFolder . '/ic_launcher_foreground.png');

                // Write to S3/MinIO
                try {
                    Storage::disk('s3')->put("branding/android/{$folder}/ic_launcher.png", file_get_contents($tempDir . '/' . $folder . '/ic_launcher.png'));
                    Storage::disk('s3')->put("branding/android/{$folder}/ic_launcher_foreground.png", file_get_contents($tempDir . '/' . $folder . '/ic_launcher_foreground.png'));
                } catch (\Exception $e) {
                    Log::warning("S3 upload failed for launcher {$folder}: " . $e->getMessage());
                }
            }
        }
    }

    protected function cleanUpDir(string $dir): void
    {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->cleanUpDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessLogoBranding failed: " . $exception->getMessage());
        $user = User::find($this->userId);
        if ($user) {
            try {
                $user->notify(new BrandingFailedNotification($exception->getMessage()));
            } catch (\Exception $e) {
                Log::error("Failed to notify user: " . $e->getMessage());
            }
        }
    }
}
