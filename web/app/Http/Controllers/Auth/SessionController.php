<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Establish admin session from JWT token received from frontend
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAdminSession(Request $request)
    {
        try {
            // Get token from request body or Authorization header
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

            // Decode JWT and validate
            $decoded = $this->decodeJWT($token);

            if (!$decoded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré'
                ], 401);
            }

            // Verify role is admin
            if (($decoded['role'] ?? '') !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux administrateurs'
                ], 403);
            }

            // Set session keys
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

    /**
     * Decode JWT token without signature verification (signature verified in Go API)
     *
     * @param string $token
     * @return array|null
     */
    private function decodeJWT($token)
    {
        try {
            // Split JWT into parts
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            // Decode payload (base64url)
            $payload = $this->base64UrlDecode($parts[1]);
            $decoded = json_decode($payload, true);

            if (!$decoded) {
                return null;
            }

            // Check expiration
            if (isset($decoded['exp'])) {
                if (time() >= $decoded['exp']) {
                    return null; // Token expired
                }
            }

            return $decoded;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Decode base64url encoded string
     *
     * @param string $input
     * @return string
     */
    private function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), true);
    }
}
