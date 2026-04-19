<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ConteneurController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.url') . '/api/v1/admin/conteneurs';
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl);
        $conteneurs = $response->successful() ? ($response->json() ?: []) : [];

        return view('admin.conteneurs.index', compact('conteneurs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'conteneur_ref' => 'required',
            'adresse' => 'required',
            'ville' => 'required',
            'capacite' => 'required|numeric'
        ]);

        $data = $request->all();
        $data['capacite'] = (int) $data['capacite'];

        $response = Http::withToken(session('admin_token'))->post($this->apiUrl, $data);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la création du conteneur.');
        }

        return redirect()->route('admin.conteneurs.index')->with('success', 'Conteneur créé avec succès.');
    }

    public function show($id)
    {
        $resAll = Http::withToken(session('admin_token'))->get($this->apiUrl);
        $conteneurs = $resAll->successful() ? ($resAll->json() ?: []) : [];
        $conteneur = collect($conteneurs)->firstWhere('id_conteneur', (int) $id);

        if (!$conteneur) {
            return redirect()->route('admin.conteneurs.index')->with('error', 'Conteneur introuvable.');
        }

        $response = Http::withToken(session('admin_token'))->get("{$this->apiUrl}/{$id}");
        $details = $response->successful() ? $response->json() : ['commandes' => [], 'tickets' => []];

        return view('admin.conteneurs.show', [
            'conteneur' => $conteneur,
            'commandes' => $details['commandes'] ?: [],
            'tickets' => $details['tickets'] ?: [],
        ]);
    }

    public function scanBarcode(Request $request, $id)
    {
        $request->validate(['code_valeur' => 'required']);

        $response = Http::withToken(session('admin_token'))->post("{$this->apiUrl}/scan", [
            'code_valeur' => $request->code_valeur
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Code barre invalide ou déjà utilisé.');
        }

        $newStatut = $response->json()['nouveau_statut'] ?? 'inconnu';
        return back()->with('success', "Commande mise à jour avec le statut : {$newStatut}");
    }

    public function resolveTicket($idConteneur, $idTicket)
    {
        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/tickets/{$idTicket}/resolve");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la résolution du ticket.');
        }

        return back()->with('success', 'Ticket marqué comme résolu.');
    }

    public function generateBarcodePdf(Request $request, $idCommande)
    {
        $typeCode = $request->get('type_code', 'depot_particulier');
        $codeValeur = strtoupper(uniqid('UC-'));

        $response = Http::withToken(session('admin_token'))->post("{$this->apiUrl}/codes-barres", [
            'id_commande' => (int) $idCommande,
            'code_valeur' => $codeValeur,
            'type_code' => $typeCode,
            'pdf_url' => ''
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Impossible de générer le code barre.');
        }

        $generator = new BarcodeGeneratorPNG();
        $barcodeBase64 = base64_encode($generator->getBarcode($codeValeur, $generator::TYPE_CODE_128));

        $pdf = Pdf::loadView('admin.conteneurs.pdf_barcode', [
            'codeValeur' => $codeValeur,
            'barcodeBase64' => $barcodeBase64,
            'typeCode' => $typeCode,
            'idCommande' => $idCommande
        ]);

        return $pdf->download("CodeBarre_{$codeValeur}.pdf");
    }
}
