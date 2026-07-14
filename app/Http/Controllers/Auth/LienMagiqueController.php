<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Mail\LienConnexion;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LienMagiqueController extends Controller
{
    private const PREFIXE = 'magic-login:';

    private const DUREE_MINUTES = 30;

    /** Envoie un lien de connexion à usage unique par email. */
    public function envoyer(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $email = Str::lower($request->string('email')->toString());

        // Anti-abus : 3 demandes / 10 min par email + IP.
        $cle = 'magic-send:'.sha1($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($cle, 3)) {
            $message = 'Trop de demandes. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 429)
                : back()->withErrors(['email' => $message]);
        }
        RateLimiter::hit($cle, 600);

        $token = Str::random(64);
        Cache::put(self::PREFIXE.$token, $email, now()->addMinutes(self::DUREE_MINUTES));

        Mail::to($email)->send(new LienConnexion(route('magic.login', ['token' => $token])));

        $message = 'Lien envoyé ! Ouvrez votre boîte mail pour vous connecter.';

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    /** Consomme le token (usage unique) : crée le compte si besoin puis connecte. */
    public function connexion(Request $request, string $token): RedirectResponse
    {
        $email = Cache::pull(self::PREFIXE.$token);

        if (! $email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Ce lien est invalide ou a expiré. Demandez-en un nouveau.']);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $pseudo = User::pseudoUnique(Str::before($email, '@'));
            $user = User::create([
                'email' => $email,
                'pseudo_public' => $pseudo,
                'name' => $pseudo,
            ]);
        }

        // Le clic sur le lien prouve la possession de l'email → vérifié (poids ↑ des avis).
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        Evenement::log(TypeEvenement::Connexion, null, ['user_id' => $user->id]);

        return redirect()->intended(route('classement.index'))
            ->with('success', 'Vous êtes connecté 🎉');
    }
}
