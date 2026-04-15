<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\PrestationController;
use App\Http\Controllers\Admin\EvenementController;
use App\Http\Controllers\Admin\AnnonceController;
use App\Http\Controllers\Admin\ConteneurController;
use App\Http\Controllers\Admin\CatalogueController;

Route::get('/', function () {
    return view('auth.login');
});

// Routes publiques d'authentification (particuliers)
Route::get('/register', function () {
    return view('auth.register');
})->name('particulier.register');

Route::get('/login', function () {
    return view('auth.login');
})->name('particulier.login');

// Auth routes (unified session management)
Route::post('/auth/set-admin-session', [SessionController::class, 'setAdminSession'])
    ->name('auth.set-admin-session');

// Particulier routes (Task 3 & 4)
Route::prefix('particulier')->group(function () {
    Route::get('/annonces/create', function () {
        return view('particulier.annonces.create');
    })->name('particulier.annonces.create');

    Route::get('/profile', function () {
        return view('particulier.profile.show');
    })->name('particulier.profile.show');
});

// Admin routes
Route::prefix('admin')->group(function () {
    // Redirect old admin login to unified login
    Route::get('/login', fn() => redirect('/login'))->name('admin.login');

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.utilisateurs.index'));

        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('admin.utilisateurs.index');
        Route::get('/utilisateurs/{id}', [UtilisateurController::class, 'show'])->name('admin.utilisateurs.show');
        Route::post('/utilisateurs/{id}/ban', [UtilisateurController::class, 'ban'])->name('admin.utilisateurs.ban');
        Route::post('/utilisateurs/{id}/unban', [UtilisateurController::class, 'unban'])->name('admin.utilisateurs.unban');
        Route::delete('/utilisateurs/{id}', [UtilisateurController::class, 'delete'])->name('admin.utilisateurs.delete');

        Route::get('/categories', [CategorieController::class, 'index'])->name('admin.categories.index');
        Route::get('/categories/create', [CategorieController::class, 'create'])->name('admin.categories.create');
        Route::post('/categories', [CategorieController::class, 'store'])->name('admin.categories.store');
        Route::get('/categories/{id}/edit', [CategorieController::class, 'edit'])->name('admin.categories.edit');
        Route::put('/categories/{id}', [CategorieController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{id}', [CategorieController::class, 'destroy'])->name('admin.categories.destroy');

        Route::get('/prestations', [PrestationController::class, 'index'])->name('admin.prestations.index');
        Route::get('/prestations/{id}', [PrestationController::class, 'show'])->name('admin.prestations.show');
        Route::post('/prestations/{id}/valider', [PrestationController::class, 'valider'])->name('admin.prestations.valider');
        Route::post('/prestations/{id}/refuser', [PrestationController::class, 'refuser'])->name('admin.prestations.refuser');

        Route::get('/catalogue', [CatalogueController::class, 'index'])->name('admin.catalogue.index');
        Route::get('/catalogue/{id}', [CatalogueController::class, 'show'])->name('admin.catalogue.show');
        Route::delete('/catalogue/{id}', [CatalogueController::class, 'destroy'])->name('admin.catalogue.destroy');
        Route::put('/catalogue/{id}/valider', [CatalogueController::class, 'valider'])->name('admin.catalogue.valider');
        Route::put('/catalogue/{id}/refuser', [CatalogueController::class, 'refuser'])->name('admin.catalogue.refuser');

        Route::get('/commandes', [\App\Http\Controllers\Admin\CommandeController::class, 'index'])->name('admin.commandes.index');
        Route::get('/commandes/{id}', [\App\Http\Controllers\Admin\CommandeController::class, 'show'])->name('admin.commandes.show');
        Route::put('/commandes/{id}/statut', [\App\Http\Controllers\Admin\CommandeController::class, 'updateStatut'])->name('admin.commandes.updateStatut');

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

        Route::get('/conteneurs', [ConteneurController::class, 'index'])->name('admin.conteneurs.index');
        Route::post('/conteneurs', [ConteneurController::class, 'store'])->name('admin.conteneurs.store');
        Route::get('/conteneurs/{id}', [ConteneurController::class, 'show'])->name('admin.conteneurs.show');
        Route::post('/conteneurs/{id}/scan', [ConteneurController::class, 'scanBarcode'])->name('admin.conteneurs.scan');
        Route::put('/conteneurs/{id}/tickets/{ticketId}/resolve', [ConteneurController::class, 'resolveTicket'])->name('admin.conteneurs.tickets.resolve');
        Route::get('/commandes/{idCommande}/barcode', [ConteneurController::class, 'generateBarcodePdf'])->name('admin.commandes.barcode.pdf');
    });
});
