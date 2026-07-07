<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            'conteneur_ref'  => 'required',
            'adresse'        => 'required',
            'ville'          => 'required',
            'code_postal'    => 'nullable|string|max:10',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'image_base64'   => 'nullable|array',
            'image_base64.*' => 'string',
            'capacite'       => 'required|numeric',
        ]);

        // Photos en base64 (comme les annonces) : décodées et écrites dans public/uploads/conteneurs.
        $images = $this->saveBase64Images($request->input('image_base64', []));

        $data = [
            'conteneur_ref' => $request->conteneur_ref,
            'adresse'       => $request->adresse,
            'ville'         => $request->ville,
            'code_postal'   => $request->code_postal,
            'latitude'      => $request->filled('latitude') ? (float) $request->latitude : null,
            'longitude'     => $request->filled('longitude') ? (float) $request->longitude : null,
            'images'        => $images,
            'capacite'      => (int) $request->capacite,
        ];

        $response = Http::withToken(session('admin_token'))->asJson()->post($this->apiUrl, $data);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la création du conteneur.');
        }

        return redirect()->route('admin.conteneurs.index')->with('success', 'Conteneur créé avec succès.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'conteneur_ref'  => 'required',
            'adresse'        => 'required',
            'ville'          => 'required',
            'code_postal'    => 'nullable|string|max:10',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'image_base64'   => 'nullable|array',
            'image_base64.*' => 'string',
            'capacite'       => 'required|numeric',
            'statut'         => 'required|in:actif,plein,maintenance,hors_service',
        ]);

        // Nouvelles photos à ajouter (les existantes se suppriment via deletePhoto).
        $images = $this->saveBase64Images($request->input('image_base64', []));

        $data = [
            'conteneur_ref' => $request->conteneur_ref,
            'adresse'       => $request->adresse,
            'ville'         => $request->ville,
            'code_postal'   => $request->code_postal,
            'latitude'      => $request->filled('latitude') ? (float) $request->latitude : null,
            'longitude'     => $request->filled('longitude') ? (float) $request->longitude : null,
            'images'        => $images,
            'capacite'      => (int) $request->capacite,
            'statut'        => $request->statut,
        ];

        $response = Http::withToken(session('admin_token'))->asJson()->put("{$this->apiUrl}/{$id}", $data);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la mise à jour du conteneur.');
        }

        return redirect()->route('admin.conteneurs.show', $id)->with('success', 'Conteneur mis à jour.');
    }

    /**
     * Décode un tableau d'images base64 (data URLs) et les écrit sur le disque
     * média (public/uploads en local, bucket S3 en prod, cf. config/media.php).
     * Retourne la liste des chemins relatifs valides.
     */
    private function saveBase64Images(array $b64List): array
    {
        $paths = [];
        foreach ($b64List as $b64) {
            if (!is_string($b64) || !preg_match('/^data:image\/(\w+);base64,/', $b64, $m)) {
                continue;
            }
            $ext  = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            $data = base64_decode(substr($b64, strpos($b64, ',') + 1), true);
            if (!in_array($ext, ['jpg', 'png', 'webp'], true) || $data === false || strlen($data) > 5 * 1024 * 1024) {
                continue;
            }
            $key = 'conteneurs/' . uniqid('cont-') . '.' . $ext;
            Storage::disk(media_disk())->put($key, $data);
            $paths[] = $key;
        }
        return $paths;
    }

    public function deletePhoto(Request $request, $id, $photoId)
    {
        $response = Http::withToken(session('admin_token'))
            ->delete("{$this->apiUrl}/photos/{$photoId}");

        // Suppression du fichier physique sur le disque média.
        $url = $request->input('url_photo');
        if ($url) {
            Storage::disk(media_disk())->delete($url);
        }

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la suppression de la photo.');
        }
        return back()->with('success', 'Photo supprimée.');
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
        $details = $response->successful() ? $response->json() : ['commandes' => [], 'tickets' => [], 'photos' => []];

        return view('admin.conteneurs.show', [
            'conteneur' => $conteneur,
            'commandes' => $details['commandes'] ?: [],
            'tickets' => $details['tickets'] ?: [],
            'photos' => $details['photos'] ?? [],
        ]);
    }

    public function scanBarcode(Request $request, $id)
    {
        $request->validate(['code_valeur' => 'required']);

        $response = Http::withToken(session('admin_token'))->asJson()->post("{$this->apiUrl}/scan", [
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

        $response = Http::withToken(session('admin_token'))->asJson()->post("{$this->apiUrl}/codes-barres", [
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

        return $pdf->stream("CodeBarre_{$codeValeur}.pdf");
    }
}
