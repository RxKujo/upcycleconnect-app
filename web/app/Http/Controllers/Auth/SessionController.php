<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    
    public function setAdminSession(Request $request)
    {
        try {
            
            $token = $request->input('token');

            if (!$token && $request->bearerToken()) {
                $token = $request->bearerToken();
            }

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token manquant'
                ], 400);
            }

            $decoded = $this->decodeJWT($token);

            if (!$decoded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré'
                ], 401);
            }

            if (($decoded['role'] ?? '') !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux administrateurs'
                ], 403);
            }

            session([
                'admin_token' => $token,
                'admin_role' => 'admin',
                'admin_id' => $decoded['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session établie avec succès',
                'redirect' => route('admin.utilisateurs.index')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du token'
            ], 500);
        }
    }

    public function setSalarieSession(Request $request)
    {
        try {
            $token = $request->input('token') ?: $request->bearerToken();
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token manquant'], 400);
            }
            $decoded = $this->decodeJWT($token);
            if (!$decoded) {
                return response()->json(['success' => false, 'message' => 'Token invalide ou expiré'], 401);
            }
            $role = $decoded['role'] ?? '';
            if (!in_array($role, ['salarie', 'admin'])) {
                return response()->json(['success' => false, 'message' => 'Accès réservé au personnel'], 403);
            }

            session([
                'salarie_token' => $token,
                'salarie_role' => $role,
                'salarie_id' => $decoded['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session établie',
                'redirect' => '/salarie/dashboard'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du traitement'], 500);
        }
    }

    private function decodeJWT($token)
    {
        try {
            
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            $payload = $this->base64UrlDecode($parts[1]);
            $decoded = json_decode($payload, true);

            if (!$decoded) {
                return null;
            }

            if (isset($decoded['exp'])) {
                if (time() >= $decoded['exp']) {
                    return null; 
                }
            }

            return $decoded;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), true);
    }
}
