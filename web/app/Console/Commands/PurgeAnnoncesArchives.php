<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeAnnoncesArchives extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upcycle:purge-annonces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime les annonces refusées ou vendues datant de plus de 3 mois pour alléger la BDD.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de la purge des annonces anciennes...');

        // Purge des annonces dont la date_creation remonte à plus de 3 mois
        // et dont le statut est 'refusee' ou 'vendue'
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
