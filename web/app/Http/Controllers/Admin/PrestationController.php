<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PrestationController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->get('http://localhost:8888/api/v1/admin/prestations');
=======
            ->get('http://localhost:8080/api/v1/admin/prestations');
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        $prestations = $response->successful() ? $response->json() : [];

        return view('admin.prestations.index', compact('prestations'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->get("http://localhost:8888/api/v1/admin/prestations/{$id}");
=======
            ->get("http://localhost:8080/api/v1/admin/prestations/{$id}");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        if ($response->failed()) {
            return redirect()->route('admin.prestations.index')->with('error', 'Prestation introuvable.');
        }

        return view('admin.prestations.show', ['prestation' => $response->json()]);
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->put("http://localhost:8888/api/v1/admin/prestations/{$id}/valider");
=======
            ->put("http://localhost:8080/api/v1/admin/prestations/{$id}/valider");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        return back()->with('success', 'Prestation validée.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->put("http://localhost:8888/api/v1/admin/prestations/{$id}/refuser");
=======
            ->put("http://localhost:8080/api/v1/admin/prestations/{$id}/refuser");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        return back()->with('success', 'Prestation refusée.');
    }
}
