<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompteLie;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /** Slug d'URL => driver Socialite. */
    private const DRIVERS = [
        'google' => 'google',
        'github' => 'github',
        'facebook' => 'facebook',
        'linkedin' => 'linkedin-openid',
    ];

    /** Connexion : redirige vers le fournisseur (invité). */
    public function redirect(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);

        if (! $this->estConfigure($driver)) {
            return redirect()->route('register')
                ->withErrors(['email' => "La connexion via {$provider} n’est pas encore configurée."]);
        }

        return Socialite::driver($driver)
            ->redirectUrl(route('social.callback', $provider))
            ->redirect();
    }

    /** Liaison : redirige vers le fournisseur pour rattacher le compte à l'utilisateur connecté. */
    public function connecter(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);

        if (! $this->estConfigure($driver)) {
            return redirect()->route('compte.securite')->with('error', ucfirst($provider).' n’est pas configuré.');
        }

        session(['sso_intention' => 'lier']);

        return Socialite::driver($driver)
            ->redirectUrl(route('social.callback', $provider))
            ->redirect();
    }

    /** Callback partagé : connexion (invité) OU liaison (utilisateur connecté). */
    public function callback(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);
        $lier = session()->pull('sso_intention') === 'lier' && Auth::check();
        $retour = Auth::check() ? 'compte.securite' : 'login';

        if (! $this->estConfigure($driver)) {
            return redirect()->route($retour)->with('error', ucfirst($provider).' n’est pas configuré.');
        }

        try {
            $oauthUser = Socialite::driver($driver)
                ->redirectUrl(route('social.callback', $provider))
                ->user();
        } catch (\Throwable) {
            return redirect()->route($retour)->with('error', "Échec de la connexion via {$provider}. Réessayez.");
        }

        if ($lier) {
            return $this->lier($provider, $oauthUser);
        }

        $user = $this->trouverOuCreer($provider, $oauthUser);
        Auth::login($user, remember: true);

        return redirect()->route('classement.index')->with('success', 'Connecté via '.ucfirst($provider).'.');
    }

    /** Délie un fournisseur du compte (sauf s'il s'agit du dernier moyen de connexion). */
    public function delier(string $provider): RedirectResponse
    {
        $this->driver($provider); // valide le slug (404 sinon)
        $user = Auth::user();

        $compte = $user->comptesLies()->where('provider', $provider)->first();
        if (! $compte) {
            return back()->with('info', ucfirst($provider).' n’est pas connecté.');
        }

        // Anti-verrouillage : ne pas retirer le seul moyen de connexion.
        if (is_null($user->password) && $user->comptesLies()->count() <= 1) {
            return back()->with('error', 'Définissez d’abord un mot de passe : c’est votre seul moyen de connexion.');
        }

        $compte->delete();

        return back()->with('success', ucfirst($provider).' déconnecté de votre compte.');
    }

    /** Slugs des fournisseurs SSO réellement configurés. @return list<string> */
    public static function providersConfigures(): array
    {
        return array_values(array_filter(
            array_keys(self::DRIVERS),
            fn (string $slug) => filled(config('services.'.self::DRIVERS[$slug].'.client_id'))
                && filled(config('services.'.self::DRIVERS[$slug].'.client_secret')),
        ));
    }

    private function driver(string $provider): string
    {
        return self::DRIVERS[$provider] ?? abort(404);
    }

    private function estConfigure(string $driver): bool
    {
        return filled(config("services.{$driver}.client_id"))
            && filled(config("services.{$driver}.client_secret"));
    }

    /** Rattache le compte social à l'utilisateur connecté (gère les conflits). */
    private function lier(string $provider, SocialiteUser $oauthUser): RedirectResponse
    {
        $user = Auth::user();

        $existant = CompteLie::where('provider', $provider)->where('provider_id', $oauthUser->getId())->first();
        if ($existant && $existant->user_id !== $user->id) {
            return redirect()->route('compte.securite')
                ->with('error', 'Ce compte '.ucfirst($provider).' est déjà lié à un autre utilisateur.');
        }
        if ($existant) {
            return redirect()->route('compte.securite')->with('info', ucfirst($provider).' est déjà connecté.');
        }

        $this->creerLien($user, $provider, $oauthUser);

        return redirect()->route('compte.securite')->with('success', ucfirst($provider).' connecté à votre compte.');
    }

    /** Connexion : retrouve l'utilisateur via le compte lié, sinon par email, sinon crée. */
    private function trouverOuCreer(string $provider, SocialiteUser $oauthUser): User
    {
        if ($lien = CompteLie::where('provider', $provider)->where('provider_id', $oauthUser->getId())->first()) {
            return $lien->user;
        }

        if ($email = $oauthUser->getEmail()) {
            if ($user = User::where('email', $email)->first()) {
                $this->creerLien($user, $provider, $oauthUser);

                return $user;
            }
        }

        $user = User::create([
            'name' => $oauthUser->getName() ?: $oauthUser->getNickname() ?: 'Utilisateur',
            'email' => $oauthUser->getEmail() ?: $provider.'_'.$oauthUser->getId().'@sso.local',
            'pseudo_public' => $this->pseudoUnique($oauthUser),
        ]);
        $this->creerLien($user, $provider, $oauthUser);

        return $user;
    }

    /** Crée le lien + monte le niveau de confiance (email vérifié, LinkedIn). */
    private function creerLien(User $user, string $provider, SocialiteUser $oauthUser): void
    {
        $user->comptesLies()->firstOrCreate(
            ['provider' => $provider],
            [
                'provider_id' => $oauthUser->getId(),
                'email' => $oauthUser->getEmail(),
                'avatar' => $oauthUser->getAvatar(),
            ],
        );

        if ($provider === 'linkedin') {
            $user->forceFill(['linkedin_verifie' => true])->save();
        }
        if ($oauthUser->getEmail() && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }

    private function pseudoUnique(SocialiteUser $oauthUser): string
    {
        $base = Str::slug(
            $oauthUser->getNickname()
            ?: $oauthUser->getName()
            ?: Str::before((string) $oauthUser->getEmail(), '@')
            ?: 'membre',
            '_'
        ) ?: 'membre';

        $pseudo = $base;
        $i = 2;
        while (User::where('pseudo_public', $pseudo)->exists()) {
            $pseudo = $base.'_'.$i;
            $i++;
        }

        return $pseudo;
    }
}
