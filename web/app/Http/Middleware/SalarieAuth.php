<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SalarieAuth
{
    public function handle(Request $request, Closure $next)
    {
        $role = session('salarie_role');
        if (!session('salarie_token') || !in_array($role, ['salarie', 'admin'])) {
            return redirect('/login?intent=salarie');
        }

        return $next($request);
    }
}
