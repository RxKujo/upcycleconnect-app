<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function setAdminSession(Request $request)
    {
        try {
            $token = $request->input('token') ?: $request->bearerToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token manquant'], 400);
            }

            $decoded = $this->verifyAndDecodeJWT($token);

            if (!$decoded) {
                return response()->json(['success' => false, 'message' => 'Token invalide ou expiré'], 401);
            }

            if (($decoded['role'] ?? '') !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Accès réservé aux administrateurs'], 403);
            }

            session([
                'admin_token' => $token,
                'admin_role'  => 'admin',
                'admin_id'    => $decoded['id'] ?? null,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Session établie avec succès',
                'redirect' => route('admin.dashboard'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du traitement du token'], 500);
        }
    }

    public function setSalarieSession(Request $request)
    {
        try {
            $token = $request->input('token') ?: $request->bearerToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token manquant'], 400);
            }

            $decoded = $this->verifyAndDecodeJWT($token);

            if (!$decoded) {
                return response()->json(['success' => false, 'message' => 'Token invalide ou expiré'], 401);
            }

            $role = $decoded['role'] ?? '';
            if (!in_array($role, ['salarie', 'admin'])) {
                return response()->json(['success' => false, 'message' => 'Accès réservé au personnel'], 403);
            }

            session([
                'salarie_token' => $token,
                'salarie_role'  => $role,
                'salarie_id'    => $decoded['id'] ?? null,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Session établie',
                'redirect' => '/salarie/dashboard',
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du traitement'], 500);
        }
    }

    public function setProSession(Request $request)
    {
        try {
            $token = $request->input('token') ?: $request->bearerToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token manquant'], 400);
            }

            $decoded = $this->verifyAndDecodeJWT($token);

            if (!$decoded) {
                return response()->json(['success' => false, 'message' => 'Token invalide ou expiré'], 401);
            }

            if (($decoded['role'] ?? '') !== 'professionnel') {
                return response()->json(['success' => false, 'message' => 'Accès réservé aux professionnels'], 403);
            }

            session([
                'pro_token' => $token,
                'pro_role'  => 'professionnel',
                'pro_id'    => $decoded['id'] ?? null,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Session établie',
                'redirect' => route('pro.dashboard.essential'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du traitement'], 500);
        }
    }

    /**
     * Vérifie la signature HMAC-SHA256 du JWT et retourne le payload décodé,
     * ou null si le token est invalide, forgé ou expiré.
     */
    private function verifyAndDecodeJWT(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $secret = config('services.jwt.secret');
        if (!$secret) {
            return null;
        }

        // Recalcule la signature attendue et compare en temps constant
        $expectedSig = hash_hmac('sha256', "$header.$payload", $secret, true);
        $expectedSigB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

        if (!hash_equals($expectedSigB64, $signature)) {
            return null;
        }

        $decoded = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($decoded)) {
            return null;
        }

        if (isset($decoded['exp']) && time() >= $decoded['exp']) {
            return null;
        }

        return $decoded;
    }

    private function base64UrlDecode(string $input): string|false
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), true);
    }

    public function clearRoleSessions()
    {
        session()->forget(['pro_token', 'pro_role', 'pro_id',
                           'admin_token', 'admin_role', 'admin_id',
                           'salarie_token', 'salarie_role', 'salarie_id']);
        return response()->json(['success' => true]);
    }
}
