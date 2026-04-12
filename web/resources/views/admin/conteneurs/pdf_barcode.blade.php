<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Code Barre - {{ $codeValeur }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        .container {
            border: 2px dashed #333;
            padding: 30px;
            display: inline-block;
        }
        .barcode-container {
            margin: 20px 0;
        }
        .barcode-number {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .meta-info {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
        }
        .title {
            text-transform: uppercase;
            color: #A4243B; /* Cherry */
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="title">
            @if($typeCode == 'depot_particulier')
                BON DE DÉPÔT EN CONTENEUR
            @else
                BON DE RÉCUPÉRATION ARTISAN
            @endif
        </h2>
        
        <p><strong>Commande #{{ $idCommande }}</strong></p>

        <div class="barcode-container">
            <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="barcode" width="300" height="80" />
            <br>
            <span class="barcode-number">{{ $codeValeur }}</span>
        </div>

        <p class="meta-info">Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</p>
        <p class="meta-info">UpcycleConnect</p>
    </div>
</body>
</html>
