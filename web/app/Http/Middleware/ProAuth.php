<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware : restreint l'accès à l'espace professionnel.
 * Vérifie la présence et la validité du jeton pro en session (expiration, rôle) ;
 * purge la session et redirige avec un message adapté selon le motif du refus.
 */
class ProAuth
{
    private const LOGIN_ROUTE = '/login';
    private const PRO_SESSION_KEYS = ['pro_token', 'pro_role', 'pro_id'];

    /**
     * Point d'entrée du middleware : laisse passer si le jeton pro est valide,
     * sinon nettoie la session et redirige avec le message d'erreur adéquat.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = session('pro_token');

        if (!$token) {
            return $this->safeBack('Accès réservé aux professionnels. Connectez-vous à votre espace pro.');
        }

        ['error' => $error, 'isRoleViolation' => $isRoleViolation] = $this->validateToken($token);

        if (!$error) {
            return $next($request);
        }

        session()->forget(self::PRO_SESSION_KEYS);
        return $isRoleViolation
            ? $this->safeBack($error)
            : redirect(self::LOGIN_ROUTE)->with('toast_error', $error);
    }

    // Redirige vers la page précédente si elle existe et n'est pas une route pro (évite les boucles),
    // sinon vers l'accueil.
    private function safeBack(string $message)
    {
        $prev = url()->previous();
        $safe = ($prev && !str_contains($prev, '/professionnel')) ? $prev : '/';
        return redirect($safe)->with('toast_error', $message);
    }

    // Retourne ['error' => string|null, 'isRoleViolation' => bool]
    private function validateToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['error' => null, 'isRoleViolation' => false];
        }

        $decoded  = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?? [];
        $expired  = isset($decoded['exp']) && time() >= $decoded['exp'];
        $badRole  = !$expired && ($decoded['role'] ?? '') !== 'professionnel';

        if ($expired) {
            $error = 'Votre session a expiré, veuillez vous reconnecter.';
        } else {
            $error = $badRole ? 'Accès réservé aux professionnels.' : null;
        }

        return ['error' => $error, 'isRoleViolation' => $badRole];
    }
}
