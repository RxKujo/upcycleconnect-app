<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UtilisateurController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->get('http://localhost:8888/api/v1/admin/utilisateurs');
=======
            ->get('http://localhost:8080/api/v1/admin/utilisateurs');
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        $utilisateurs = $response->successful() ? $response->json() : [];

        return view('admin.utilisateurs.index', compact('utilisateurs'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->get("http://localhost:8888/api/v1/admin/utilisateurs/{$id}");
=======
            ->get("http://localhost:8080/api/v1/admin/utilisateurs/{$id}");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        if ($response->failed()) {
            return redirect()->route('admin.utilisateurs.index')->with('error', 'Utilisateur introuvable.');
        }

        $utilisateur = $response->json();

        return view('admin.utilisateurs.show', compact('utilisateur'));
    }

    public function ban(Request $request, $id)
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->put("http://localhost:8888/api/v1/admin/utilisateurs/{$id}/ban", [
=======
            ->put("http://localhost:8080/api/v1/admin/utilisateurs/{$id}/ban", [
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465
                'date_fin_ban' => $request->input('date_fin_ban', '2099-12-31'),
            ]);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du bannissement.');
        }

        return back()->with('success', 'Utilisateur banni.');
    }

    public function unban($id)
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->put("http://localhost:8888/api/v1/admin/utilisateurs/{$id}/unban");
=======
            ->put("http://localhost:8080/api/v1/admin/utilisateurs/{$id}/unban");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du débannissement.');
        }

        return back()->with('success', 'Utilisateur débanni.');
    }
}
