<?php

use App\Http\Controllers\Admin\StatistiqueController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ClassementController;
use App\Http\Controllers\Compte\SecuriteController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ReponseEntrepriseController;
use App\Http\Controllers\SignalementController;
use Illuminate\Support\Facades\Route;

// --- Public : classement + fiches ---
Route::get('/', [ClassementController::class, 'index'])->name('classement.index');
Route::get('/entreprises/{entreprise}', [ClassementController::class, 'show'])->name('entreprises.show');

// --- Authentification ---
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register']);
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login']);

    // SSO (Google, GitHub, Facebook, LinkedIn) — désactivable via SSO_ENABLED.
    if (config('services.sso.enabled')) {
        Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
            ->whereIn('provider', ['google', 'github', 'facebook', 'linkedin'])->name('social.redirect');
        Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
            ->whereIn('provider', ['google', 'github', 'facebook', 'linkedin'])->name('social.callback');
    }
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Contributions (authentifié) ---
Route::middleware('auth')->group(function () {
    // Proposer une entreprise (utilisateur → a_verifier ; admin → verifiee).
    Route::get('/proposer-entreprise', [EntrepriseController::class, 'create'])->name('entreprises.create');
    Route::post('/proposer-entreprise', [EntrepriseController::class, 'store'])->name('entreprises.proposer');

    Route::get('/entreprises/{entreprise}/avis', [ContributionController::class, 'avisCreate'])->name('contrib.avis.create');
    Route::post('/entreprises/{entreprise}/avis', [ContributionController::class, 'avisStore'])->name('contrib.avis.store');
    Route::get('/entreprises/{entreprise}/entretien', [ContributionController::class, 'entretienCreate'])->name('contrib.entretien.create');
    Route::post('/entreprises/{entreprise}/entretien', [ContributionController::class, 'entretienStore'])->name('contrib.entretien.store');
    Route::get('/entreprises/{entreprise}/mission', [ContributionController::class, 'missionCreate'])->name('contrib.mission.create');
    Route::post('/entreprises/{entreprise}/mission', [ContributionController::class, 'missionStore'])->name('contrib.mission.store');

    // Signalement d'une contribution (utilisateur).
    Route::post('/signaler/{type}/{id}', [SignalementController::class, 'signaler'])
        ->whereIn('type', ['avis', 'entretien', 'mission'])->name('signaler');

    // Sécurité du compte : sessions actives + déconnexion des autres appareils.
    Route::get('/compte/securite', [SecuriteController::class, 'index'])->name('compte.securite');
    Route::post('/compte/securite/deconnecter-autres', [SecuriteController::class, 'deconnecterAutres'])
        ->name('compte.securite.deconnecter-autres');
});

// --- Modération (administrateur) ---
Route::middleware(['auth', 'can:moderer'])->group(function () {
    // Droit de réponse de l'entreprise (géré par un admin).
    Route::put('/entreprises/{entreprise}/reponse', [ReponseEntrepriseController::class, 'update'])->name('entreprises.reponse');
});

// --- Espace admin (statistiques) ---
Route::middleware(['auth', 'can:moderer'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/statistiques', [StatistiqueController::class, 'index'])->name('stats.index');
    Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('users.index');
    Route::get('/utilisateurs/{user}', [UtilisateurController::class, 'show'])->name('users.show');
});

Route::middleware(['auth', 'can:moderer'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [ModerationController::class, 'index'])->name('index');
    Route::post('/{type}/{id}/publier', [ModerationController::class, 'publier'])
        ->whereIn('type', ['avis', 'entretien', 'mission'])->name('publier');
    Route::post('/{type}/{id}/retirer', [ModerationController::class, 'retirer'])
        ->whereIn('type', ['avis', 'entretien', 'mission'])->name('retirer');

    // Vérification / suppression des entreprises proposées.
    Route::put('/entreprises/{entreprise}/verifier', [ModerationController::class, 'verifierEntreprise'])->name('entreprise.verifier');
    Route::delete('/entreprises/{entreprise}', [ModerationController::class, 'supprimerEntreprise'])->name('entreprise.supprimer');
});
