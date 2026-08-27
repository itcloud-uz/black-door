<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiometricController extends Controller
{
    /**
     * Minimum Cosine Similarity required for biometric match (Standard 0.85 = high security with flexibility)
     */
    public const FACE_SIMILARITY_THRESHOLD = 0.85;

    /**
     * Show biometric authentication screen.
     */
    public function showFaceAuthForm(Request $request)
    {
        $user = Auth::user();

        // Must have completed PIN verification first
        if (!session()->get('finance_pin_verified_temp')) {
            return redirect()->route('finance.pin');
        }

        // If locked out, check if lock expired
        if ($user->face_locked_until && $user->face_locked_until->isFuture()) {
            $diff = $user->face_locked_until->diffInSeconds(now());
            return view('auth.face', [
                'isLocked' => true,
                'lockTimer' => $diff,
            ]);
        }

        return view('auth.face', [
            'isLocked' => false,
            'lockTimer' => 0,
        ]);
    }

    /**
     * Register a new face embedding.
     */
    public function register(Request $request)
    {
        $request->validate([
            'embedding' => 'required|json',
        ]);

        $vector = json_decode($request->input('embedding'), true);
        if (!is_array($vector) || count($vector) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'Yuz vektori formati noto\'g\'ri (128 kalitli massiv bo\'lishi shart).'
            ], 400);
        }

        $user = Auth::user();
        $user->setFaceEmbedding($request->input('embedding'));

        AuditLogger::log('biometric_register', $user);

        return response()->json([
            'success' => true,
            'message' => 'Yuz ma\'lumotlari muvaffaqiyatli saqlandi.'
        ]);
    }

    /**
     * Verify face embedding and liveness challenge.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'embedding' => 'required|json',
            'liveness_verified' => 'required|boolean',
        ]);

        $user = Auth::user();

        if ($user->face_locked_until && $user->face_locked_until->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Yuz tanish vaqtincha bloklangan.'
            ], 423);
        }

        // Liveness check validation
        if (!$request->input('liveness_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Tiriklik testi (Liveness check) bajarilmadi.'
            ], 400);
        }

        $provided = json_decode($request->input('embedding'), true);
        if (!is_array($provided) || count($provided) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'Noto\'g\'ri yuz vektori formati.'
            ], 400);
        }

        $storedEmbeddingJson = $user->getFaceEmbedding();
        if (!$storedEmbeddingJson) {
            return response()->json([
                'success' => false,
                'message' => 'Yuz profili ro\'yxatga olinmagan.'
            ], 404);
        }

        $stored = json_decode($storedEmbeddingJson, true);
        if (!is_array($stored) || count($stored) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'Saqlangan yuz shabloni eskirgan. Qaytadan ro\'yxatdan o\'ting.'
            ], 400);
        }

        $similarity = $this->compareEmbeddings($stored, $provided);

        // Verification threshold (Standard 0.85 cosine similarity cutoff)
        if ($similarity >= self::FACE_SIMILARITY_THRESHOLD) {
            $updateData = [
                'failed_face_attempts' => 0,
                'face_locked_until' => null,
            ];

            // Adaptive Biometric Template Update:
            // If match is verified, but shows variance (e.g. user shaved beard),
            // dynamically blend new look with stored template (learning rate 0.20).
            // Re-normalize to unit vector.
            if ($similarity < 0.96) {
                $blended = [];
                for ($i = 0; $i < 128; $i++) {
                    $blended[] = $stored[$i] * 0.8 + $provided[$i] * 0.2;
                }

                $sumSq = 0.0;
                for ($i = 0; $i < 128; $i++) {
                    $sumSq += $blended[$i] * $blended[$i];
                }
                $norm = sqrt($sumSq);
                if ($norm > 0) {
                    for ($i = 0; $i < 128; $i++) {
                        $blended[$i] = round($blended[$i] / $norm, 6);
                    }
                    $updateData['face_embedding'] = json_encode($blended);
                }
            }

            $user->update($updateData);

            session()->put('finance_pin_verified', true);
            session()->forget('finance_pin_verified_temp');

            AuditLogger::log('biometric_verify_success', $user, null, [
                'similarity' => round($similarity, 4),
                'adapted' => isset($updateData['face_embedding'])
            ]);

            return response()->json([
                'success' => true,
                'similarity' => round($similarity, 4),
                'redirect_url' => route('finance.dashboard')
            ]);
        }

        // Increment attempts on failure
        $attempts = $user->failed_face_attempts + 1;
        $updateData = ['failed_face_attempts' => $attempts];

        if ($attempts >= 3) {
            $updateData['face_locked_until'] = now()->addMinutes(15);
            $updateData['failed_face_attempts'] = 0; // reset
            $user->update($updateData);

            AuditLogger::log('biometric_lockout', $user);

            return response()->json([
                'success' => false,
                'lockout' => true,
                'message' => 'Urinishlar ko\'pligi sababli biometrika 15 daqiqaga bloklandi. Zaxira yo\'lidan foydalaning.'
            ], 423);
        }

        $user->update($updateData);
        AuditLogger::log('biometric_verify_failed', $user, null, ['attempts' => $attempts, 'similarity' => round($similarity, 4)]);

        return response()->json([
            'success' => false,
            'similarity' => round($similarity, 4),
            'message' => 'Yuz mos kelmadi (O\'xshashlik: ' . round($similarity * 100, 1) . '%). Qolgan urinishlar: ' . (3 - $attempts)
        ], 401);
    }

    /**
     * Delete biometric profile.
     */
    public function delete(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'face_embedding' => null,
            'face_id_enabled' => false,
            'failed_face_attempts' => 0,
            'face_locked_until' => null,
        ]);

        AuditLogger::log('biometric_delete', $user);

        return back()->with('success', 'Biometrik ma\'lumotlar muvaffaqiyatli o\'chirildi.');
    }

    /**
     * Toggle Face ID status.
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $user = Auth::user();
        
        if ($request->input('enabled') && !$user->hasFaceId()) {
            return back()->withErrors(['face_id' => 'Avval yuz profilini ro\'yxatdan o\'tkazing.']);
        }

        $user->update([
            'face_id_enabled' => $request->input('enabled'),
        ]);

        AuditLogger::log('biometric_toggle', $user, null, ['enabled' => $request->input('enabled')]);

        return back()->with('success', 'Face ID sozlamalari yangilandi.');
    }

    /**
     * Compare two high-dimensional embeddings using Cosine Similarity.
     */
    private function compareEmbeddings(array $arr1, array $arr2): float
    {
        if (count($arr1) !== count($arr2) || empty($arr1)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($arr1); $i++) {
            $dotProduct += $arr1[$i] * $arr2[$i];
            $normA += $arr1[$i] * $arr1[$i];
            $normB += $arr2[$i] * $arr2[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
