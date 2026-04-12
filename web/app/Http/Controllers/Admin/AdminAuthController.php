<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_token') && session('admin_role') === 'admin') {
            return redirect()->route('admin.utilisateurs.index');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $response = Http::post('http://localhost:8080/api/v1/auth/login', [
            'email' => $request->email,
            'mot_de_passe' => $request->password,
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Identifiants incorrects.');
        }

        $token = $response->json('token');

        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);

        session([
            'admin_token' => $token,
            'admin_role' => $payload['role'] ?? '',
            'admin_id' => $payload['id'] ?? null,
        ]);

        if (session('admin_role') !== 'admin') {
            session()->forget(['admin_token', 'admin_role', 'admin_id']);
            return back()->with('error', 'Accès réservé aux administrateurs.');
        }

        return redirect()->route('admin.utilisateurs.index');
    }

    public function logout()
    {
        session()->forget(['admin_token', 'admin_role', 'admin_id']);
        return redirect()->route('admin.login');
    }
}
