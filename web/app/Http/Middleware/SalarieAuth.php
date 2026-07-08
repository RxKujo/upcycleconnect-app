<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware : restreint l'accès à l'espace salarié.
 * Exige un jeton en session et le rôle « salarie » ou « admin »,
 * sinon redirige vers la connexion en conservant l'URL de retour.
 */
class SalarieAuth
{
    public function handle(Request $request, Closure $next)
    {
        $role = session('salarie_role');
        if (!session('salarie_token') || !in_array($role, ['salarie', 'admin'])) {
            return redirect('/login?return=' . urlencode($request->getPathInfo()));
        }

        return $next($request);
    }
}
