<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disque d'écriture des médias
    |--------------------------------------------------------------------------
    | Disque Laravel utilisé pour ÉCRIRE les médias (photos de conteneurs,
    | icônes de matériaux). 'uploads_local' = public/uploads (comportement
    | historique) ; 's3' = bucket S3-compatible (MinIO en dev, prod).
    */
    'disk' => env('MEDIA_DISK', 'uploads_local'),

    /*
    |--------------------------------------------------------------------------
    | Base d'URL publique de lecture
    |--------------------------------------------------------------------------
    | Préfixe appliqué aux chemins relatifs stockés en base (url_photo,
    | photo_profil_url…) pour construire l'URL affichée. Par défaut '/uploads'
    | (fichiers servis par Laravel) ; en S3, l'URL publique du bucket.
    */
    'url' => env('MEDIA_URL', '/uploads'),

];
