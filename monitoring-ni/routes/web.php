<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController; // Ajoutez cette ligne

// Routes de Breeze pour l'authentification (déjà présentes)
// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', function () {
    // Nous pourrions rediriger vers la liste des sites ou créer une vue de tableau de bord dédiée
    return redirect()->route('sites.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Routes pour le profil utilisateur (générées par Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Nos routes pour les sites
    // 'names' permet de nommer les routes pour les utiliser facilement dans les vues (ex: route('sites.index'))
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create'); // Pour afficher le formulaire
Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    // Nous ajouterons 'create', 'show', 'edit', 'update' plus tard si besoin.
});

require __DIR__.'/auth.php'; // Routes d'authentification de Breeze
