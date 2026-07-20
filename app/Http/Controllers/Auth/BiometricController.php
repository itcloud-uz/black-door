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

        $storedEmbeddingJson = $user->getFaceEmbedding();
        if (!$storedEmbeddingJson) {
            return response()->json([
                'success' => false,
                'message' => 'Yuz profili ro\'yxatga olinmagan.'
            ], 404);
        }

        $stored = json_decode($storedEmbeddingJson, true);
        $provided = json_decode($request->input('embedding'), true);

        $similarity = $this->compareEmbeddings($stored, $provided);

        // Verification threshold (e.g. 0.85 cosine similarity)
        if ($similarity >= 0.85) {
            $user->update([
                'failed_face_attempts' => 0,
                'face_locked_until' => null,
            ]);

            session()->put('finance_pin_verified', true);
            session()->forget('finance_pin_verified_temp');

            AuditLogger::log('biometric_verify_success', $user, null, ['similarity' => $similarity]);

            return response()->json([
                'success' => true,
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
        AuditLogger::log('biometric_verify_failed', $user, null, ['attempts' => $attempts, 'similarity' => $similarity]);

        return response()->json([
            'success' => false,
            'message' => 'Yuz mos kelmadi. Qolgan urinishlar: ' . (3 - $attempts)
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
