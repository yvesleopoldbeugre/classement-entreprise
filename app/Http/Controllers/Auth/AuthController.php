<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StatutModeration;
use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // Pseudo public et nom auto-générés si non fournis (inscription rapide).
        $base = $data['name'] ?? Str::before($data['email'], '@');
        $data['pseudo_public'] ??= User::pseudoUnique($base);
        $data['name'] ??= $data['pseudo_public'];

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        // Flux « avis d'abord » : publie l'avis mémorisé si présent.
        $entrepriseAvis = $this->publierAvisEnAttente($user);
        $redirect = $entrepriseAvis
            ? route('entreprises.show', $entrepriseAvis)
            : route('classement.index');

        // Inscription rapide via le modal (AJAX) → réponse JSON, sinon redirection.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => $redirect]);
        }

        return redirect()->to($redirect)->with('success', $entrepriseAvis
            ? 'Compte créé et avis envoyé ! Il sera publié après modération.'
            : 'Bienvenue '.$user->pseudo_public.' ! Votre compte est créé.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        // Authentification + anti-force brute (voir LoginRequest::authenticate).
        $request->authenticate();

        $request->session()->regenerate();
        Evenement::log(TypeEvenement::Connexion, null, ['user_id' => Auth::id()]);

        // Flux « avis d'abord » : si un avis était en attente, on le publie.
        if ($entreprise = $this->publierAvisEnAttente($request->user())) {
            return redirect()->route('entreprises.show', $entreprise)
                ->with('success', 'Avis envoyé ! Il sera publié après modération.');
        }

        return redirect()->intended(route('classement.index'))
            ->with('success', 'Content de vous revoir !');
    }

    /**
     * Publie l'avis mémorisé en session (flux « avis d'abord ») pour l'utilisateur
     * qui vient de se connecter/s'inscrire. Retourne l'entreprise concernée, ou null.
     */
    protected function publierAvisEnAttente(User $user): ?Entreprise
    {
        $data = session()->pull('avis_en_attente');
        $entrepriseId = session()->pull('avis_en_attente_entreprise');

        if (empty($data) || empty($entrepriseId) || ! ($entreprise = Entreprise::find($entrepriseId))) {
            return null;
        }

        // Ne pas dupliquer si ce compte a déjà un avis sur cette entreprise.
        $dejaAvis = AvisEntreprise::where('entreprise_id', $entreprise->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $dejaAvis) {
            AvisEntreprise::create([
                ...$data,
                'user_id' => $user->id,
                'statut_moderation' => StatutModeration::parDefaut(),
            ]);
        }

        return $entreprise;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('classement.index');
    }
}
