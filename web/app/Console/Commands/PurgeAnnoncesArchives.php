<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeAnnoncesArchives extends Command
{
    
    protected $signature = 'upcycle:purge-annonces';

    protected $description = 'Supprime les annonces refusées ou vendues datant de plus de 3 mois pour alléger la BDD.';

    public function handle()
    {
        $this->info('Début de la purge des annonces anciennes...');

        $deleted = DB::table('annonces')
            ->whereIn('statut', ['refusee', 'vendue'])
            ->whereRaw('date_creation < DATE_SUB(NOW(), INTERVAL 3 MONTH)')
            ->delete();

        if ($deleted) {
            $this->info("Purge terminée avec succès. {$deleted} annonce(s) supprimée(s).");
        } else {
            $this->info("Aucune annonce à purger.");
        }
    }
}
