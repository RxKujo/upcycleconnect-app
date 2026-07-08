<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FinanceController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('admin_token'); }

    // --- Lecture ---

    public function index(Request $request)
    {
        $filters = $request->only(['type', 'mois', 'annee']);
        $dashboard = $this->callApi('/api/v1/admin/finances/dashboard') ?? [];
        $revenus = $this->callApi('/api/v1/admin/finances/revenus', $filters) ?? [];
        return view('admin.finances.index', compact('dashboard', 'revenus', 'filters'));
    }

    // --- Exports ---

    public function exportCsv(Request $request)
    {
        $params = http_build_query($request->only(['type', 'mois', 'annee']));
        $apiUrl = $this->api() . '/api/v1/admin/finances/export-csv' . ($params ? '?' . $params : '');

        $r = Http::withToken($this->token())->timeout(30)->get($apiUrl);

        if (!$r->successful()) {
            return back()->with('error', 'Erreur export CSV');
        }

        $annee = $request->input('annee', date('Y'));
        return response($r->body(), 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="finances_' . $annee . '.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $params = http_build_query($request->only(['type', 'mois', 'annee']));
        $apiUrl = $this->api() . '/api/v1/admin/finances/export-pdf' . ($params ? '?' . $params : '');

        $r = Http::withToken($this->token())->timeout(30)->get($apiUrl);

        if (!$r->successful()) {
            return back()->with('error', 'Erreur export PDF');
        }

        $annee = $request->input('annee', date('Y'));
        return response($r->body(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="finances_' . $annee . '.pdf"',
        ]);
    }

    // --- Utilitaires ---

    private function callApi(string $path, array $query = []): ?array
    {
        $r = Http::withToken($this->token())->timeout(10)->get($this->api() . $path, $query);
        return $r->successful() ? $r->json() : null;
    }
}
