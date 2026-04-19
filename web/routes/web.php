<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\PrestationController;
use App\Http\Controllers\Admin\EvenementController;
use App\Http\Controllers\Admin\ConteneurController;
use App\Http\Controllers\Admin\CatalogueController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.utilisateurs.index'));

        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('admin.utilisateurs.index');
        Route::get('/utilisateurs/{id}', [UtilisateurController::class, 'show'])->name('admin.utilisateurs.show');
        Route::post('/utilisateurs/{id}/ban', [UtilisateurController::class, 'ban'])->name('admin.utilisateurs.ban');
        Route::post('/utilisateurs/{id}/unban', [UtilisateurController::class, 'unban'])->name('admin.utilisateurs.unban');

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
        Route::get('/catalogue/create', [CatalogueController::class, 'create'])->name('admin.catalogue.create');
        Route::post('/catalogue', [CatalogueController::class, 'store'])->name('admin.catalogue.store');
        Route::get('/catalogue/{id}/edit', [CatalogueController::class, 'edit'])->name('admin.catalogue.edit');
        Route::put('/catalogue/{id}', [CatalogueController::class, 'update'])->name('admin.catalogue.update');
        Route::delete('/catalogue/{id}', [CatalogueController::class, 'destroy'])->name('admin.catalogue.destroy');
        Route::get('/catalogue/{id}', [CatalogueController::class, 'show'])->name('admin.catalogue.show');
        Route::post('/catalogue/{id}/valider', [CatalogueController::class, 'valider'])->name('admin.catalogue.valider');
        Route::get('/catalogue/{id}/reservations', [CatalogueController::class, 'reservations'])->name('admin.catalogue.reservations');

        Route::get('/evenements', [EvenementController::class, 'index'])->name('admin.evenements.index');
        Route::get('/evenements/{id}', [EvenementController::class, 'show'])->name('admin.evenements.show');
        Route::post('/evenements/{id}/valider', [EvenementController::class, 'valider'])->name('admin.evenements.valider');
        Route::post('/evenements/{id}/refuser', [EvenementController::class, 'refuser'])->name('admin.evenements.refuser');
        Route::get('/conteneurs', [ConteneurController::class, 'index'])->name('admin.conteneurs.index');
        Route::post('/conteneurs', [ConteneurController::class, 'store'])->name('admin.conteneurs.store');
        Route::get('/conteneurs/{id}', [ConteneurController::class, 'show'])->name('admin.conteneurs.show');
        Route::post('/conteneurs/{id}/scan', [ConteneurController::class, 'scanBarcode'])->name('admin.conteneurs.scan');
        Route::put('/conteneurs/{id}/tickets/{ticketId}/resolve', [ConteneurController::class, 'resolveTicket'])->name('admin.conteneurs.tickets.resolve');
        Route::get('/commandes/{idCommande}/barcode', [ConteneurController::class, 'generateBarcodePdf'])->name('admin.commandes.barcode.pdf');
    });
});
