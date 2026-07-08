<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Modération du forum : signalements, messages, sujets et mots bannis.
 * Proxy vers l'API Go.
 */
class ModerationController extends Controller
{
    // --- Helpers API ---
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

    // --- Signalements & messages ---

    /** Liste les messages signalés en attente de modération. */
    public function signalements()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/signalements');
        $items = $r->successful() ? $r->json() : [];
        return view('salarie.forum.signalements', compact('items'));
    }

    /** Masque un message du forum. */
    public function masquerMessage($id)
    {
        $r = Http::withToken($this->token())->put($this->api() . '/api/v1/salarie/messages/' . $id . '/masquer');
        if (!$r->successful()) return back()->with('error', $r->json('erreur') ?? 'Erreur');
        return back()->with('success', 'Message masqué.');
    }

    /** Restaure un message précédemment masqué. */
    public function restaurerMessage($id)
    {
        $r = Http::withToken($this->token())->put($this->api() . '/api/v1/salarie/messages/' . $id . '/restaurer');
        if (!$r->successful()) return back()->with('error', $r->json('erreur') ?? 'Erreur');
        return back()->with('success', 'Message restauré.');
    }

    // --- Sujets du forum ---

    /** Liste les sujets du forum. */
    public function sujets()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/sujets');
        $sujets = $r->successful() ? $r->json() : [];
        return view('salarie.forum.sujets', compact('sujets'));
    }

    /** Verrouille un sujet (empêche les nouvelles réponses). */
    public function lockSujet($id)
    {
        $r = Http::withToken($this->token())->put($this->api() . '/api/v1/salarie/sujets/' . $id . '/lock');
        if (!$r->successful()) return back()->with('error', $r->json('erreur') ?? 'Erreur');
        return back()->with('success', 'Sujet verrouillé.');
    }

    /** Rouvre un sujet précédemment verrouillé. */
    public function unlockSujet($id)
    {
        $r = Http::withToken($this->token())->put($this->api() . '/api/v1/salarie/sujets/' . $id . '/unlock');
        if (!$r->successful()) return back()->with('error', $r->json('erreur') ?? 'Erreur');
        return back()->with('success', 'Sujet rouvert.');
    }

    // --- Mots bannis ---

    /** Liste les mots bannis du forum. */
    public function motsBannis()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/mots-bannis');
        $mots = $r->successful() ? $r->json() : [];
        return view('salarie.forum.mots-bannis', compact('mots'));
    }

    /** Ajoute un mot à la liste des mots bannis. */
    public function addMotBanni(Request $request)
    {
        $data = $request->validate(['mot' => 'required|string|max:100']);
        $r = Http::withToken($this->token())->asJson()->post($this->api() . '/api/v1/salarie/mots-bannis', $data);
        if (!$r->successful()) return back()->withInput()->with('error', $r->json('erreur') ?? 'Erreur');
        return back()->with('success', 'Mot banni ajouté.');
    }

    /** Retire un mot de la liste des mots bannis. */
    public function deleteMotBanni($id)
    {
        $r = Http::withToken($this->token())->delete($this->api() . '/api/v1/salarie/mots-bannis/' . $id);
        if (!$r->successful()) return back()->with('error', $r->json('erreur') ?? 'Erreur');
        return back()->with('success', 'Mot banni supprimé.');
    }
}
