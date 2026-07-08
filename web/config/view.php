<?php

// Configuration des vues Blade : chemins des templates et dossier des vues
// compilées.

return [

    'paths' => [
        resource_path('views'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
