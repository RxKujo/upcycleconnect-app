<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware : restreint l'accès aux routes d'administration.
 * Exige un jeton en session et le rôle « admin », sinon redirige vers la connexion.
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_token') || session('admin_role') !== 'admin') {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
