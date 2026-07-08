<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LangueController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('admin_token'); }

    // --- Lecture ---

    public function index()
    {
        $langues = $this->callApi('/api/v1/admin/langues') ?? [];
        $translations = $this->callApi('/api/v1/admin/translations') ?? [];
        return view('admin.langues.index', compact('langues', 'translations'));
    }

    // --- Langues ---

    public function storeLangue(Request $request)
    {
        $data = $request->validate([
            'code_iso' => 'required|string|min:2|max:5',
            'libelle'  => 'required|string|max:100',
            'rtl'      => 'boolean',
        ]);
        $data['rtl'] = $request->boolean('rtl');

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/admin/langues', $data);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur création langue');
        }
        return redirect()->route('admin.langues.index')->with('success', 'Langue ajoutée.');
    }

    public function updateLangue(Request $request, $id)
    {
        $data = $request->validate([
            'libelle'    => 'nullable|string|max:100',
            'est_active' => 'boolean',
            'rtl'        => 'boolean',
        ]);
        $data['est_active'] = $request->boolean('est_active');
        $data['rtl']        = $request->boolean('rtl');

        $r = Http::withToken($this->token())->asJson()
            ->put($this->api() . '/api/v1/admin/langues/' . $id, $data);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur mise à jour');
        }
        return redirect()->route('admin.langues.index')->with('success', 'Langue mise à jour.');
    }

    public function destroyLangue($id)
    {
        $r = Http::withToken($this->token())
            ->delete($this->api() . '/api/v1/admin/langues/' . $id);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Impossible de supprimer (traductions liées ?)');
        }
        return redirect()->route('admin.langues.index')->with('success', 'Langue supprimée.');
    }

    // --- Traductions ---

    public function upsertTranslation(Request $request)
    {
        $data = $request->validate([
            'cle'       => 'required|string|max:200',
            'id_langue' => 'required|integer',
            'valeur'    => 'required|string',
        ]);
        $data['id_langue'] = (int) $data['id_langue'];

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/admin/translations', $data);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur traduction');
        }
        return redirect()->route('admin.langues.index')->with('success', 'Traduction enregistrée.');
    }

    public function saveTranslations(Request $request)
    {
        $cles = $request->input('cle', []);
        $vals = $request->input('val', []);

        $items = [];
        foreach ($cles as $i => $cle) {
            $cle = trim((string) $cle);
            if ($cle === '') {
                continue;
            }
            foreach (($vals[$i] ?? []) as $idLangue => $valeur) {
                $items[] = [
                    'cle'       => $cle,
                    'id_langue' => (int) $idLangue,
                    'valeur'    => (string) $valeur,
                ];
            }
        }

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/admin/translations/bulk', ['items' => $items]);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur enregistrement');
        }
        return redirect()->route('admin.langues.index')->with('success', 'Traductions enregistrées.');
    }

    public function destroyTranslation($id)
    {
        $r = Http::withToken($this->token())
            ->delete($this->api() . '/api/v1/admin/translations/' . $id);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur suppression traduction');
        }
        return redirect()->route('admin.langues.index')->with('success', 'Traduction supprimée.');
    }

    // --- Utilitaires ---

    private function callApi(string $path): ?array
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . $path);
        return $r->successful() ? $r->json() : null;
    }
}
