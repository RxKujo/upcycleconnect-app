<?php

// Configuration centralisée de la boîte à idées : statuts, libellés et couleurs.
// Source de vérité unique côté front — aucune chaîne / couleur en dur dans les vues.
// Les clés de statut DOIVENT correspondre à l'ENUM SQL et aux constantes Go
// (services/idee_service.go : StatutIdee*).

return [

    // Statuts du cycle de vie d'une idée.
    'statuts' => [
        'en_attente' => [
            'label' => 'En attente',
            // Charte : Wheat (neutre / en cours) sur texte Coffee.
            'bg'   => '#D8C99B',
            'text' => '#120309',
        ],
        'realise' => [
            'label' => 'Réalisé',
            // Charte : Forest (validé / abouti) sur Cream.
            'bg'   => '#244F26',
            'text' => '#F5F0E1',
        ],
        'non_retenu' => [
            'label' => 'Non retenu',
            // Charte : Cherry (rejeté) sur Cream.
            'bg'   => '#A4243B',
            'text' => '#F5F0E1',
        ],
    ],

    // Statut par défaut à la création (aligné sur le DEFAULT SQL).
    'statut_defaut' => 'en_attente',

    // Modes de tri du flux principal.
    'tris' => [
        'populaire' => 'Populaire',
        'recent'    => 'Récent',
    ],
];
