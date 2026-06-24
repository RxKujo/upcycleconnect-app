<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MateriauController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url'), '/') . '/api/v1/admin/materiaux';
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl());
        $materiaux = $response->successful() ? ($response->json() ?: []) : [];

        return view('admin.materiaux.index', compact('materiaux'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'         => 'required|string|max:50|regex:/^[a-z0-9_]+$/',
            'libelle'      => 'required|string|max:100',
            'icone_base64' => 'nullable|string',
        ]);

        $data = [
            'code'    => $request->code,
            'libelle' => $request->libelle,
            'icone'   => $this->saveBase64Icon($request->icone_base64),
        ];

        $response = Http::withToken(session('admin_token'))->asJson()->post($this->apiUrl(), $data);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la création (code déjà utilisé ?).');
        }
        return redirect()->route('admin.materiaux.index')->with('success', 'Matériau créé.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle'      => 'required|string|max:100',
            'actif'        => 'nullable|boolean',
            'icone_base64' => 'nullable|string',
        ]);

        $data = [
            'libelle' => $request->libelle,
            'actif'   => $request->boolean('actif'),
            'icone'   => $this->saveBase64Icon($request->icone_base64),
        ];

        $response = Http::withToken(session('admin_token'))->asJson()->put($this->apiUrl() . "/{$id}", $data);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la modification.');
        }
        return redirect()->route('admin.materiaux.index')->with('success', 'Matériau mis à jour.');
    }

    public function toggle($id)
    {
        $response = Http::withToken(session('admin_token'))->put($this->apiUrl() . "/{$id}/toggle");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du changement de statut.');
        }
        return back()->with('success', 'Statut mis à jour.');
    }

    /**
     * Décode une icône base64 (data URL) et l'écrit dans public/uploads/materiaux.
     * Retourne le chemin relatif (materiaux/xxx.ext) ou null si absent/invalide.
     */
    private function saveBase64Icon(?string $b64): ?string
    {
        if (!$b64 || !preg_match('/^data:image\/(\w+);base64,/', $b64, $m)) {
            return null;
        }
        $ext  = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $data = base64_decode(substr($b64, strpos($b64, ',') + 1), true);
        if (!in_array($ext, ['jpg', 'png', 'webp', 'svg'], true) || $data === false || strlen($data) > 2 * 1024 * 1024) {
            return null;
        }
        $dir = public_path('uploads/materiaux');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = uniqid('mat-') . '.' . $ext;
        file_put_contents($dir . '/' . $filename, $data);
        return 'materiaux/' . $filename;
    }
}
