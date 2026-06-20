<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = session('pro_token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Veuillez vous connecter pour accéder à l\'espace professionnel.');
        }

        // Vérification de l'expiration via le payload JWT (sans re-vérifier la signature —
        // le Go API rejette les tokens forgés à chaque appel API).
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = base64_decode(strtr($parts[1], '-_', '+/'));
            $decoded = json_decode($payload, true);
            if (isset($decoded['exp']) && time() >= $decoded['exp']) {
                session()->forget(['pro_token', 'pro_role', 'pro_id']);
                return redirect('/login')
                    ->with('error', 'Votre session a expiré, veuillez vous reconnecter.');
            }
        }

        return $next($request);
    }
}
