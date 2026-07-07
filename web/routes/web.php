<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\CategorieObjetController;
use App\Http\Controllers\Admin\MateriauController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\EvenementController;
use App\Http\Controllers\Admin\AnnonceController;
use App\Http\Controllers\Admin\ConteneurController;
use App\Http\Controllers\Admin\CommandeController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\AbonnementController as AdminAbonnementController;
use App\Http\Controllers\Admin\PubliciteController as AdminPubliciteController;
use App\Http\Controllers\Admin\LangueController as AdminLangueController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Salarie\DashboardController as SalarieDashboardController;
use App\Http\Controllers\Salarie\EvenementController as SalarieEvenementController;
use App\Http\Controllers\Salarie\TemplateController as SalarieTemplateController;
use App\Http\Controllers\Salarie\ArticleController as SalarieArticleController;
use App\Http\Controllers\Salarie\ModerationController as SalarieModerationController;
use App\Http\Controllers\Salarie\PlanningController as SalariePlanningController;
use App\Http\Controllers\Salarie\BoiteIdeeController as SalarieBoiteIdeeController;
use App\Http\Controllers\Salarie\MaterielController as SalarieMaterielController;
use App\Http\Controllers\EvenementCatalogueController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarcheController;
use App\Http\Controllers\RessourceController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\Pro\DashboardController as ProDashboardController;
use App\Http\Controllers\Pro\AlertesController as ProAlertesController;
use App\Http\Controllers\Pro\PublicitesController as ProPublicitesController;
use App\Http\Controllers\Pro\ConteneursController as ProConteneursController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/annonces', [MarcheController::class, 'index'])->name('annonces.index');
Route::get('/annonces/{id}', [MarcheController::class, 'show'])->name('annonces.show');

Route::get('/evenements', [EvenementCatalogueController::class, 'index'])->name('evenements.index');
Route::get('/evenements/{id}', [EvenementCatalogueController::class, 'show'])->name('evenements.show');

// Page « Conseils » fusionnée dans « Ressources » — redirections de compat.
Route::redirect('/conseils', '/ressources');
Route::redirect('/conseils/{id}', '/ressources');

// « Formations » fusionnées dans le catalogue d'événements — redirections de compat.
Route::get('/formations', fn() => redirect()->route('evenements.index'))->name('formations.index');
Route::get('/formations/{id}', fn($id) => redirect()->route('evenements.show', $id))->name('formations.show');

Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');
Route::get('/forum/{id}', [ForumController::class, 'show'])->name('forum.show');

Route::view('/panier', 'public.panier.index')->name('panier.index');
Route::view('/mes-commandes', 'public.commandes.index')->name('commandes.index');

Route::get('/services-pro', fn() => view('public.services-pro'))->name('services-pro');
Route::get('/a-propos', fn() => view('public.a-propos'))->name('a-propos');
Route::view('/cgu', 'public.cgu')->name('cgu');
Route::view('/rgpd', 'public.rgpd')->name('rgpd');

Route::get('/forgot-password', fn() => view('auth.forgot-password'))->name('auth.forgot-password');
Route::get('/reset-password', fn() => view('auth.reset-password'))->name('auth.reset-password');
Route::get('/register', fn() => view('auth.register'))->name('particulier.register');
Route::get('/register-pro', fn() => view('auth.register-pro'))->name('professionnel.register');
Route::get('/login', fn() => view('auth.login'))->name('particulier.login');

Route::post('/auth/set-admin-session', [SessionController::class, 'setAdminSession'])->name('auth.set-admin-session');
Route::post('/auth/set-salarie-session', [SessionController::class, 'setSalarieSession'])->name('auth.set-salarie-session');
Route::post('/auth/set-pro-session', [SessionController::class, 'setProSession'])->name('auth.set-pro-session');
Route::post('/auth/clear-role-sessions', [SessionController::class, 'clearRoleSessions'])->name('auth.clear-role-sessions');

Route::get('/ressources', [RessourceController::class, 'index'])->name('ressources.index');
Route::get('/ressources/{id}', [RessourceController::class, 'show'])->name('ressources.show');
Route::get('/tutoriels', fn() => view('public.tutoriels.index'))->name('tutoriels.index');
Route::get('/depot', fn() => view('public.depot.index'))->name('depot.index');

Route::prefix('particulier')->group(function () {
    Route::get('/', fn() => redirect()->route('particulier.dashboard'));
    Route::get('/dashboard', fn() => view('particulier.dashboard'))->name('particulier.dashboard');

    Route::get('/annonces', fn() => view('particulier.annonces.index'))->name('particulier.annonces.index');
    Route::get('/annonces/create', function () {
        $api = rtrim(config('services.api.url'), '/');
        $get = function (string $path) use ($api) {
            $r = \Illuminate\Support\Facades\Http::get($api . $path);
            return $r->successful() && is_array($r->json()) ? $r->json() : [];
        };
        return view('particulier.annonces.create', [
            'materiaux'   => $get('/api/v1/public/materiaux'),
            'categories'  => $get('/api/v1/public/categories-objets'),
            'conteneurs'  => $get('/api/v1/public/conteneurs'),
        ]);
    })->name('particulier.annonces.create');

    Route::get('/formations', fn() => view('particulier.formations.index'))->name('particulier.formations.index');

    Route::get('/profile', fn() => view('particulier.profile.show'))->name('particulier.profile.show');
    Route::get('/planning', fn() => view('particulier.planning.index'))->name('particulier.planning.index');
});

Route::prefix('professionnel')->name('pro.')->middleware('pro.auth')->group(function () {
    Route::get('/profile', fn() => view('professionnel.profile.show'))->name('profile.show');
    Route::get('/abonnement', fn() => view('professionnel.abonnement.index'))->name('abonnement.index');

    // Dashboard Essential Pro
    Route::get('/dashboard', [ProDashboardController::class, 'essential'])->name('dashboard.essential');
    // Dashboard Expert Pro (annuel)
    Route::get('/dashboard/annuel', [ProDashboardController::class, 'expert'])->name('dashboard.expert');
    // Export PDF annuel (Expert Pro)
    Route::get('/dashboard/export-pdf', [ProDashboardController::class, 'exportPdf'])->name('dashboard.export-pdf');

    // Alertes matériaux
    Route::get('/alertes', [ProAlertesController::class, 'index'])->name('alertes.index');
    Route::post('/alertes', [ProAlertesController::class, 'store'])->name('alertes.store');
    Route::delete('/alertes/{id}', [ProAlertesController::class, 'destroy'])->name('alertes.destroy');

    // Publicités
    Route::prefix('publicites')->name('publicites.')->group(function () {
        Route::get('/', [ProPublicitesController::class, 'index'])->name('index');
        Route::get('/creer', [ProPublicitesController::class, 'create'])->name('create');
        Route::post('/', [ProPublicitesController::class, 'store'])->name('store');
        Route::delete('/{id}', [ProPublicitesController::class, 'destroy'])->name('destroy');
    });

    // Badges (recalcul manuel)
    Route::post('/badges/recalculer', function () {
        $token = session('pro_token');
        $apiUrl = rtrim(config('services.api.url', env('API_URL', 'http://localhost:8080')), '/');
        \Illuminate\Support\Facades\Http::withToken($token)
            ->post($apiUrl . '/api/v1/pro/badges/recalculer');
        return redirect()->route('pro.dashboard.expert')->with('success', 'Badges recalculés.');
    })->name('badges.recalculer');

    // Conteneurs — récupération en conteneur
    Route::prefix('conteneurs')->name('conteneurs.')->group(function () {
        Route::get('/', [ProConteneursController::class, 'index'])->name('index');
        Route::get('/historique', [ProConteneursController::class, 'historique'])->name('historique');
        Route::post('/valider', [ProConteneursController::class, 'validerReception'])->name('valider');
    });
});

Route::get('/abonnement/succes', fn() => view('professionnel.abonnement.succes'))->name('abonnement.succes');
Route::get('/abonnement/annule', fn() => view('professionnel.abonnement.annule'))->name('abonnement.annule');
Route::get('/paiement/succes', fn() => view('public.paiement.succes'))->name('paiement.succes');

Route::prefix('admin')->group(function () {
    Route::get('/login', fn() => redirect('/login'))->name('admin.login');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', fn() => view('admin.dashboard'))->name('admin.dashboard');

        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('admin.utilisateurs.index');
        Route::get('/utilisateurs/{id}', [UtilisateurController::class, 'show'])->name('admin.utilisateurs.show');
        Route::post('/utilisateurs/{id}/ban', [UtilisateurController::class, 'ban'])->name('admin.utilisateurs.ban');
        Route::post('/utilisateurs/{id}/unban', [UtilisateurController::class, 'unban'])->name('admin.utilisateurs.unban');
        Route::put('/utilisateurs/{id}/role', [UtilisateurController::class, 'changeRole'])->name('admin.utilisateurs.role');
        Route::delete('/utilisateurs/{id}', [UtilisateurController::class, 'delete'])->name('admin.utilisateurs.delete');
        Route::post('/utilisateurs/{id}/abonnement', [UtilisateurController::class, 'assignAbonnement'])->name('admin.utilisateurs.abonnement.assign');
        Route::delete('/utilisateurs/{id}/abonnement', [UtilisateurController::class, 'revokeAbonnement'])->name('admin.utilisateurs.abonnement.revoke');

        Route::get('/materiaux', [MateriauController::class, 'index'])->name('admin.materiaux.index');
        Route::post('/materiaux', [MateriauController::class, 'store'])->name('admin.materiaux.store');
        Route::put('/materiaux/{id}', [MateriauController::class, 'update'])->name('admin.materiaux.update');
        Route::put('/materiaux/{id}/toggle', [MateriauController::class, 'toggle'])->name('admin.materiaux.toggle');

        Route::get('/templates', [TemplateController::class, 'index'])->name('admin.templates.index');
        Route::post('/templates', [TemplateController::class, 'store'])->name('admin.templates.store');
        Route::put('/templates/{id}', [TemplateController::class, 'update'])->name('admin.templates.update');
        Route::put('/templates/{id}/toggle', [TemplateController::class, 'toggle'])->name('admin.templates.toggle');
        Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('admin.templates.destroy');

        Route::get('/categories-objets', [CategorieObjetController::class, 'index'])->name('admin.categories-objets.index');
        Route::post('/categories-objets', [CategorieObjetController::class, 'store'])->name('admin.categories-objets.store');
        Route::put('/categories-objets/{id}', [CategorieObjetController::class, 'update'])->name('admin.categories-objets.update');
        Route::delete('/categories-objets/{id}', [CategorieObjetController::class, 'destroy'])->name('admin.categories-objets.destroy');

        Route::get('/categories', [CategorieController::class, 'index'])->name('admin.categories.index');
        Route::get('/categories/create', [CategorieController::class, 'create'])->name('admin.categories.create');
        Route::post('/categories', [CategorieController::class, 'store'])->name('admin.categories.store');
        Route::get('/categories/{id}/edit', [CategorieController::class, 'edit'])->name('admin.categories.edit');
        Route::put('/categories/{id}', [CategorieController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{id}', [CategorieController::class, 'destroy'])->name('admin.categories.destroy');


        Route::get('/evenements', [EvenementController::class, 'index'])->name('admin.evenements.index');
        Route::get('/evenements/create', [EvenementController::class, 'create'])->name('admin.evenements.create');
        Route::post('/evenements', [EvenementController::class, 'store'])->name('admin.evenements.store');
        Route::get('/evenements/{id}/edit', [EvenementController::class, 'edit'])->name('admin.evenements.edit');
        Route::put('/evenements/{id}', [EvenementController::class, 'update'])->name('admin.evenements.update');
        Route::delete('/evenements/{id}', [EvenementController::class, 'destroy'])->name('admin.evenements.destroy');
        Route::get('/evenements/{id}', [EvenementController::class, 'show'])->name('admin.evenements.show');
        Route::put('/evenements/{id}/valider', [EvenementController::class, 'valider'])->name('admin.evenements.valider');
        Route::put('/evenements/{id}/refuser', [EvenementController::class, 'refuser'])->name('admin.evenements.refuser');
        Route::put('/evenements/{id}/attente', [EvenementController::class, 'attente'])->name('admin.evenements.attente');

        Route::get('/annonces', [AnnonceController::class, 'index'])->name('admin.annonces.index');
        Route::get('/annonces/{id}', [AnnonceController::class, 'show'])->name('admin.annonces.show');
        Route::put('/annonces/{id}/valider', [AnnonceController::class, 'valider'])->name('admin.annonces.valider');
        Route::put('/annonces/{id}/refuser', [AnnonceController::class, 'refuser'])->name('admin.annonces.refuser');
        Route::put('/annonces/{id}/attente', [AnnonceController::class, 'attente'])->name('admin.annonces.attente');

        Route::get('/commandes', [CommandeController::class, 'index'])->name('admin.commandes.index');
        Route::get('/commandes/{id}', [CommandeController::class, 'show'])->name('admin.commandes.show');
        Route::put('/commandes/{id}/statut', [CommandeController::class, 'updateStatut'])->name('admin.commandes.updateStatut');

        Route::get('/conteneurs', [ConteneurController::class, 'index'])->name('admin.conteneurs.index');
        Route::post('/conteneurs', [ConteneurController::class, 'store'])->name('admin.conteneurs.store');
        Route::get('/conteneurs/{id}', [ConteneurController::class, 'show'])->name('admin.conteneurs.show');
        Route::put('/conteneurs/{id}', [ConteneurController::class, 'update'])->name('admin.conteneurs.update');
        Route::delete('/conteneurs/{id}/photos/{photoId}', [ConteneurController::class, 'deletePhoto'])->name('admin.conteneurs.photos.delete');
        Route::post('/conteneurs/{id}/scan', [ConteneurController::class, 'scanBarcode'])->name('admin.conteneurs.scan');
        Route::put('/conteneurs/{id}/tickets/{ticketId}/resolve', [ConteneurController::class, 'resolveTicket'])->name('admin.conteneurs.tickets.resolve');
        Route::get('/commandes/{idCommande}/barcode/pdf', [ConteneurController::class, 'generateBarcodePdf'])->name('admin.commandes.barcode.pdf');

        // Catalogue fusionné dans Événements — redirection de compat.
        Route::get('/catalogue', fn() => redirect()->route('admin.evenements.index'))->name('admin.catalogue.index');

        Route::get('/abonnements', [AdminAbonnementController::class, 'index'])->name('admin.abonnements.index');

        Route::get('/scores', [ScoreController::class, 'index'])->name('admin.scores.index');
        Route::put('/scores/paliers/{id}', [ScoreController::class, 'updatePalier'])->name('admin.scores.palier.update');
        Route::post('/scores/recompute', [ScoreController::class, 'recompute'])->name('admin.scores.recompute');

        Route::get('/depot/demandes', fn() => view('admin.depot.index'))->name('admin.depot.index');
        Route::get('/tutoriel/etapes', fn() => view('admin.tutoriel.index'))->name('admin.tutoriel.index');

        // Publicités — modération + stats + rotation
        Route::prefix('publicites')->name('admin.publicites.')->group(function () {
            Route::get('/', [AdminPubliciteController::class, 'index'])->name('index');
            Route::get('/stats', [AdminPubliciteController::class, 'stats'])->name('stats');
            Route::get('/rotation', [AdminPubliciteController::class, 'rotation'])->name('rotation');
            Route::put('/{id}/valider', [AdminPubliciteController::class, 'valider'])->name('valider');
            Route::put('/{id}/refuser', [AdminPubliciteController::class, 'refuser'])->name('refuser');
        });

        // Langues & traductions multilingue
        Route::prefix('langues')->name('admin.langues.')->group(function () {
            Route::get('/', [AdminLangueController::class, 'index'])->name('index');
            Route::post('/', [AdminLangueController::class, 'storeLangue'])->name('store');
            Route::put('/{id}', [AdminLangueController::class, 'updateLangue'])->name('update');
            Route::delete('/{id}', [AdminLangueController::class, 'destroyLangue'])->name('destroy');
            Route::post('/translations', [AdminLangueController::class, 'upsertTranslation'])->name('translations.upsert');
            Route::post('/translations/bulk', [AdminLangueController::class, 'saveTranslations'])->name('translations.bulk');
            Route::delete('/translations/{id}', [AdminLangueController::class, 'destroyTranslation'])->name('translations.destroy');
        });

        // Supervision notifications
        Route::prefix('notifications')->name('admin.notifications.')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::post('/groupe', [AdminNotificationController::class, 'sendGroupe'])->name('groupe');
            Route::get('/prefs/{userId}', [AdminNotificationController::class, 'getUserPrefs'])->name('prefs.show');
            Route::put('/prefs/{userId}', [AdminNotificationController::class, 'updateUserPrefs'])->name('prefs.update');
        });

        // Pilotage financier
        Route::prefix('finances')->name('admin.finances.')->group(function () {
            Route::get('/', [AdminFinanceController::class, 'index'])->name('index');
            Route::get('/export-csv', [AdminFinanceController::class, 'exportCsv'])->name('export-csv');
            Route::get('/export-pdf', [AdminFinanceController::class, 'exportPdf'])->name('export-pdf');
        });
    });
});

Route::prefix('salarie')->group(function () {
    Route::post('/logout', [SalarieDashboardController::class, 'logout'])->name('salarie.logout');

    Route::middleware('salarie.auth')->group(function () {
        Route::get('/', fn() => redirect('/salarie/dashboard'));
        Route::get('/dashboard', [SalarieDashboardController::class, 'index'])->name('salarie.dashboard');

        Route::get('/evenements', [SalarieEvenementController::class, 'index'])->name('salarie.evenements.index');
        Route::get('/evenements/create', [SalarieEvenementController::class, 'create'])->name('salarie.evenements.create');
        Route::post('/evenements', [SalarieEvenementController::class, 'store'])->name('salarie.evenements.store');
        Route::get('/evenements/{id}/edit', [SalarieEvenementController::class, 'edit'])->name('salarie.evenements.edit');
        Route::put('/evenements/{id}', [SalarieEvenementController::class, 'update'])->name('salarie.evenements.update');

        Route::get('/templates', [SalarieTemplateController::class, 'index'])->name('salarie.templates.index');
        Route::post('/templates', [SalarieTemplateController::class, 'store'])->name('salarie.templates.store');
        Route::put('/templates/{id}', [SalarieTemplateController::class, 'update'])->name('salarie.templates.update');
        Route::delete('/templates/{id}', [SalarieTemplateController::class, 'destroy'])->name('salarie.templates.destroy');

        Route::get('/articles', [SalarieArticleController::class, 'index'])->name('salarie.articles.index');
        Route::get('/articles/create', [SalarieArticleController::class, 'create'])->name('salarie.articles.create');
        Route::post('/articles', [SalarieArticleController::class, 'store'])->name('salarie.articles.store');
        Route::get('/articles/{id}/edit', [SalarieArticleController::class, 'edit'])->name('salarie.articles.edit');
        Route::put('/articles/{id}', [SalarieArticleController::class, 'update'])->name('salarie.articles.update');
        Route::delete('/articles/{id}', [SalarieArticleController::class, 'destroy'])->name('salarie.articles.destroy');

        // Matériel / inventaire
        Route::get('/materiels', [SalarieMaterielController::class, 'index'])->name('salarie.materiels.index');
        Route::post('/materiels', [SalarieMaterielController::class, 'store'])->name('salarie.materiels.store');
        Route::get('/materiels/{id}', [SalarieMaterielController::class, 'show'])->name('salarie.materiels.show');
        Route::put('/materiels/{id}', [SalarieMaterielController::class, 'update'])->name('salarie.materiels.update');
        Route::delete('/materiels/{id}', [SalarieMaterielController::class, 'destroy'])->name('salarie.materiels.destroy');
        Route::delete('/materiels/{id}/photos/{photoId}', [SalarieMaterielController::class, 'deletePhoto'])->name('salarie.materiels.photos.delete');
        Route::post('/materiels/{id}/reserver', [SalarieMaterielController::class, 'reserver'])->name('salarie.materiels.reserver');
        Route::post('/materiels/{id}/retour', [SalarieMaterielController::class, 'retour'])->name('salarie.materiels.retour');

        // Planning salarié
        Route::get('/planning', [SalariePlanningController::class, 'index'])->name('salarie.planning.index');
        Route::post('/planning', [SalariePlanningController::class, 'store'])->name('salarie.planning.store');
        Route::put('/planning/{id}', [SalariePlanningController::class, 'update'])->name('salarie.planning.update');
        Route::delete('/planning/{id}', [SalariePlanningController::class, 'destroy'])->name('salarie.planning.destroy');

        // Boîte à idées
        Route::get('/idees', [SalarieBoiteIdeeController::class, 'index'])->name('salarie.idees.index');
        Route::post('/idees', [SalarieBoiteIdeeController::class, 'store'])->name('salarie.idees.store');
        Route::post('/idees/{id}/voter', [SalarieBoiteIdeeController::class, 'voter'])->name('salarie.idees.voter');
        Route::put('/idees/{id}/statut', [SalarieBoiteIdeeController::class, 'statut'])->name('salarie.idees.statut');
        Route::post('/idees/{id}/archiver', [SalarieBoiteIdeeController::class, 'archiver'])->name('salarie.idees.archiver');
        Route::post('/idees/{id}/desarchiver', [SalarieBoiteIdeeController::class, 'desarchiver'])->name('salarie.idees.desarchiver');
        Route::put('/idees/{id}', [SalarieBoiteIdeeController::class, 'update'])->name('salarie.idees.update');
        Route::delete('/idees/{id}', [SalarieBoiteIdeeController::class, 'destroy'])->name('salarie.idees.destroy');

        Route::get('/forum/signalements', [SalarieModerationController::class, 'signalements'])->name('salarie.forum.signalements');
        Route::put('/forum/messages/{id}/masquer', [SalarieModerationController::class, 'masquerMessage'])->name('salarie.forum.masquer');
        Route::put('/forum/messages/{id}/restaurer', [SalarieModerationController::class, 'restaurerMessage'])->name('salarie.forum.restaurer');
        Route::get('/forum/sujets', [SalarieModerationController::class, 'sujets'])->name('salarie.forum.sujets');
        Route::put('/forum/sujets/{id}/lock', [SalarieModerationController::class, 'lockSujet'])->name('salarie.forum.sujets.lock');
        Route::put('/forum/sujets/{id}/unlock', [SalarieModerationController::class, 'unlockSujet'])->name('salarie.forum.sujets.unlock');
        Route::get('/forum/mots-bannis', [SalarieModerationController::class, 'motsBannis'])->name('salarie.forum.mots-bannis');
        Route::post('/forum/mots-bannis', [SalarieModerationController::class, 'addMotBanni'])->name('salarie.forum.mots-bannis.add');
        Route::delete('/forum/mots-bannis/{id}', [SalarieModerationController::class, 'deleteMotBanni'])->name('salarie.forum.mots-bannis.delete');
    });
});
