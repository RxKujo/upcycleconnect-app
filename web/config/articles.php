<?php

// Catégories d'articles / news — liste FIXE (source de vérité unique).
// Clé = valeur stockée en base ; valeur = libellé affiché.
// Permet de savoir précisément dans quelle rubrique l'article est publié,
// et d'éviter les catégories incohérentes saisies à la main.

return [
    'categories' => [
        'actualites' => 'Actualités',
        'conseils'   => 'Conseils & astuces',
        'tutoriel'   => 'Tutoriel',
        'materiaux'  => 'Matériaux',
        'evenement'  => 'Événement',
    ],
];
