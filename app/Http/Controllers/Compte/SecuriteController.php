<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecuriteController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = $this->sessionsBaseDeDonnees()
            ? $this->sessionsUtilisateur($request)
            : collect();

        return view('compte.securite', [
            'sessions' => $sessions,
            'driverSupporte' => $this->sessionsBaseDeDonnees(),
        ]);
    }

    public function deconnecterAutres(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [], ['password' => 'mot de passe']);

        // Invalide toutes les autres sessions (nécessite le middleware auth.session).
        Auth::logoutOtherDevices($request->string('password'));

        return back()->with('success', 'Vous avez été déconnecté de tous les autres appareils.');
    }

    /** Définit (compte lien magique / SSO) ou change le mot de passe. */
    public function motDePasse(Request $request): RedirectResponse
    {
        $user = $request->user();
        $aDejaUnMotDePasse = ! is_null($user->password);

        // Le mot de passe actuel n'est exigé que si le compte en a déjà un.
        $regles = ['password' => ['required', 'confirmed', Password::defaults()]];
        if ($aDejaUnMotDePasse) {
            $regles['current_password'] = ['required', 'current_password'];
        }

        $request->validateWithBag('motDePasse', $regles, [], [
            'current_password' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ]);

        // Le cast 'hashed' chiffre à l'enregistrement ; AuthenticateSession met à jour
        // l'empreinte de la session courante (l'utilisateur reste connecté), les autres
        // sessions sont invalidées à leur prochaine requête.
        $user->password = $request->string('password')->toString();
        $user->save();

        return back()->with('success', $aDejaUnMotDePasse
            ? 'Mot de passe mis à jour.'
            : 'Mot de passe défini : vous pouvez désormais vous connecter avec votre email et ce mot de passe.');
    }

    private function sessionsBaseDeDonnees(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * Sessions actives de l'utilisateur, lues dans la table `sessions`.
     *
     * @return Collection<int, object>
     */
    private function sessionsUtilisateur(Request $request)
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => (object) [
                'appareil' => $this->decrireAgent($s->user_agent),
                'ip' => $s->ip_address,
                'derniere_activite' => Carbon::createFromTimestamp($s->last_activity),
                'actuelle' => $s->id === $request->session()->getId(),
            ]);
    }

    /** Description lisible du couple navigateur / système à partir du User-Agent. */
    private function decrireAgent(?string $ua): string
    {
        if (blank($ua)) {
            return 'Appareil inconnu';
        }

        $navigateur = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Navigateur',
        };

        $systeme = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'système inconnu',
        };

        return "{$navigateur} sur {$systeme}";
    }
}
