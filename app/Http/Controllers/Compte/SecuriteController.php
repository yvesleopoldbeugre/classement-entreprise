<?php

namespace App\Http\Controllers\Compte;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
