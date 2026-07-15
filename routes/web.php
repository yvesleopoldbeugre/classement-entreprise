<?php

use App\Http\Controllers\Admin\LiveController;
use App\Http\Controllers\Admin\StatistiqueController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Chat\ChatVisiteurController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LienMagiqueController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\VerificationEmailController;
use App\Http\Controllers\ClassementController;
use App\Http\Controllers\Compte\SecuriteController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ReponseEntrepriseController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\SignalementController;
use Illuminate\Support\Facades\Route;

// --- Public : classement + fiches ---
Route::get('/', [ClassementController::class, 'index'])->name('classement.index');
Route::get('/entreprises/{entreprise}', [ClassementController::class, 'show'])->name('entreprises.show');

// --- SEO ---
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// --- Avis « d'abord » : ouvrable en invité (le compte est demandé à la validation) ---
Route::get('/entreprises/{entreprise}/avis', [ContributionController::class, 'avisCreate'])->name('contrib.avis.create');
Route::post('/entreprises/{entreprise}/avis', [ContributionController::class, 'avisStore'])->name('contrib.avis.store');

// --- Présence temps réel + chat visiteur (public, throttlé, scoping par visiteur_token) ---
Route::post('/presence', [PresenceController::class, 'heartbeat'])->middleware('throttle:60,1')->name('presence');
Route::middleware('throttle:40,1')->group(function () {
    Route::post('/chat/ouvrir', [ChatVisiteurController::class, 'ouvrir'])->name('chat.ouvrir');
    Route::post('/chat/message', [ChatVisiteurController::class, 'envoyer'])->name('chat.message');
    Route::get('/chat/messages', [ChatVisiteurController::class, 'messages'])->name('chat.messages');
});

// --- Authentification ---
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register']);
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login']);

    // Lien magique : inscription/connexion sans mot de passe.
    Route::post('/connexion/lien', [LienMagiqueController::class, 'envoyer'])->name('magic.send');
    Route::get('/connexion/lien/{token}', [LienMagiqueController::class, 'connexion'])->name('magic.login');

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

    Route::get('/entreprises/{entreprise}/entretien', [ContributionController::class, 'entretienCreate'])->name('contrib.entretien.create');
    Route::post('/entreprises/{entreprise}/entretien', [ContributionController::class, 'entretienStore'])->name('contrib.entretien.store');
    Route::get('/entreprises/{entreprise}/mission', [ContributionController::class, 'missionCreate'])->name('contrib.mission.create');
    Route::post('/entreprises/{entreprise}/mission', [ContributionController::class, 'missionStore'])->name('contrib.mission.store');

    // Signalement d'une contribution (utilisateur).
    Route::post('/signaler/{type}/{id}', [SignalementController::class, 'signaler'])
        ->whereIn('type', ['avis', 'entretien', 'mission'])->name('signaler');

    // Vérification d'email (relève le poids des avis ; non bloquant).
    Route::get('/email/verifier/{id}/{hash}', [VerificationEmailController::class, 'verifier'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verifier/renvoyer', [VerificationEmailController::class, 'renvoyer'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Sécurité du compte : sessions actives + déconnexion des autres appareils.
    Route::get('/compte/securite', [SecuriteController::class, 'index'])->name('compte.securite');
    Route::post('/compte/securite/mot-de-passe', [SecuriteController::class, 'motDePasse'])
        ->name('compte.securite.mot-de-passe');
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
    Route::get('/statistiques/en-ligne', [StatistiqueController::class, 'enLigne'])->name('stats.en-ligne');

    // Visiteurs en direct + chat (admin).
    Route::get('/live', [LiveController::class, 'index'])->name('live.index');
    Route::get('/live/visiteurs', [LiveController::class, 'visiteurs'])->name('live.visiteurs');
    Route::post('/live/conversations', [LiveController::class, 'demarrer'])->name('live.demarrer');
    Route::get('/live/conversations/{conversation}/messages', [LiveController::class, 'conversation'])->name('live.conversation');
    Route::post('/live/conversations/{conversation}/messages', [LiveController::class, 'repondre'])->name('live.repondre');
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
