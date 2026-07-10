<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

    public function callback(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);

        if (! $this->estConfigure($driver)) {
            return redirect()->route('register')
                ->withErrors(['email' => "La connexion via {$provider} n’est pas encore configurée."]);
        }

        try {
            $oauthUser = Socialite::driver($driver)
                ->redirectUrl(route('social.callback', $provider))
                ->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => "Échec de la connexion via {$provider}. Réessayez."]);
        }

        $user = $this->trouverOuCreer($driver, $oauthUser);

        Auth::login($user, remember: true);

        return redirect()->route('classement.index')
            ->with('success', 'Connecté via '.Str::of($provider)->ucfirst().'.');
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

    private function trouverOuCreer(string $driver, SocialiteUser $oauthUser): User
    {
        // 1. Compte déjà lié à ce fournisseur.
        $user = User::where('provider', $driver)->where('provider_id', $oauthUser->getId())->first();
        if ($user) {
            return $user;
        }

        // 2. Compte existant avec le même email → on le lie.
        if ($email = $oauthUser->getEmail()) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['provider' => $driver, 'provider_id' => $oauthUser->getId()]);

                return $user;
            }
        }

        // 3. Nouveau compte.
        return User::create([
            'name' => $oauthUser->getName() ?: $oauthUser->getNickname() ?: 'Utilisateur',
            'email' => $oauthUser->getEmail() ?: $driver.'_'.$oauthUser->getId().'@sso.local',
            'pseudo_public' => $this->pseudoUnique($oauthUser),
            'provider' => $driver,
            'provider_id' => $oauthUser->getId(),
        ]);
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
