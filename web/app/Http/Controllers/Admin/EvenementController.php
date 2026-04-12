<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->get('http://localhost:8888/api/v1/admin/evenements');
=======
            ->get('http://localhost:8080/api/v1/admin/evenements');
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        $evenements = $response->successful() ? $response->json() : [];

        return view('admin.evenements.index', compact('evenements'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->get("http://localhost:8888/api/v1/admin/evenements/{$id}");
=======
            ->get("http://localhost:8080/api/v1/admin/evenements/{$id}");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        if ($response->failed()) {
            return redirect()->route('admin.evenements.index')->with('error', 'Événement introuvable.');
        }

        return view('admin.evenements.show', ['evenement' => $response->json()]);
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/valider");
=======
            ->put("http://localhost:8080/api/v1/admin/evenements/{$id}/valider");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        return back()->with('success', 'Événement validé.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))
<<<<<<< HEAD
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/refuser");
=======
            ->put("http://localhost:8080/api/v1/admin/evenements/{$id}/refuser");
>>>>>>> eef791db5f133b74e1383c5f86b6090caa6ac465

        return back()->with('success', 'Événement refusé.');
    }
}
