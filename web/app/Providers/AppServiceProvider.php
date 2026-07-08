<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

/**
 * Fournisseur de services applicatif : amorçage global de l'application.
 * Ici, force le schéma HTTPS des URLs générées lorsqu'on est derrière un proxy TLS.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Derrière le proxy nginx (TLS terminé en amont), Laravel croit être en http.
        // On force la génération d'URLs https dès que APP_URL est en https.
        if (str_starts_with((string) config('app.url'), 'https')) {
            URL::forceScheme('https');
        }
    }
}
