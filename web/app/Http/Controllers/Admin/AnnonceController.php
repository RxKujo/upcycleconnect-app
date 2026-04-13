<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class AnnonceController extends Controller
{
    private $apiUrl = 'http://localhost:8888/api/v1/admin/annonces';

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl);
        $annonces = $response->successful() ? $response->json() : [];

        return view('admin.annonces.index', compact('annonces'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))->get("{$this->apiUrl}/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.annonces.index')->with('error', 'Annonce introuvable.');
        }

        return view('admin.annonces.show', ['annonce' => $response->json()]);
    }

    public function valider($id)
    {
        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/valider");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la validation.');
        }

        $data = $response->json();
        
        // Si dépôt conteneur, génération du code-barre
        if (!empty($data['requires_barcode']) && $data['requires_barcode'] === true) {
            $codeValeur = strtoupper(uniqid('UC-DEPOT-'));
            
            // Appel API pour générer en base le barcode lié à l'annonce (grâce au alter table)
            // On utilise la route existante ou une spécifique. Si la route conteneurs ne prend pas id_annonce
            // On peut l'insérer via une nouvelle route qu'on a fait ou utiliser le code métier.
            // Pour l'instant, on laisse l'alerte à l'écran :
            return back()->with('success', "Annonce validée. Code-barre à envoyer au vendeur : {$codeValeur}");
            /*
            Dans un cas réel de production, on ferait l'envoi de mail ici :
            Mail::to($vendeur)->send(new BarcodeMail($codeValeur));
            */
        }

        return back()->with('success', 'Annonce validée et publiée.');
    }

    public function refuser(Request $request, $id)
    {
        $payload = ['motif_refus' => $request->input('motif_refus', 'Non conforme')];
        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/refuser", $payload);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du refus.');
        }

        return back()->with('success', 'Annonce refusée et archivée.');
    }

    public function attente($id)
    {
        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/attente");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la mise en attente.');
        }

        return back()->with('success', 'Annonce remise en attente.');
    }
}
