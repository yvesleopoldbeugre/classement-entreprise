<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Expediteur;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Presence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function index(): View
    {
        return view('admin.live');
    }

    /** Liste JSON des visiteurs actuellement en ligne (+ leur conversation éventuelle). */
    public function visiteurs(): JsonResponse
    {
        $depuis = now()->subSeconds((int) config('chatbot.presence_ttl', 90));

        $presences = Presence::with('user')
            ->where('derniere_activite', '>=', $depuis)
            ->orderByDesc('derniere_activite')
            ->get();

        $conversations = Conversation::whereIn('visiteur_token', $presences->pluck('visiteur_token'))
            ->withCount(['messages as non_lus_admin' => fn ($q) => $q
                ->where('expediteur', Expediteur::Visiteur->value)
                ->whereNull('lu_at')])
            ->get()
            ->keyBy('visiteur_token');

        return response()->json([
            'total' => $presences->count(),
            'visiteurs' => $presences->map(function (Presence $p) use ($conversations) {
                $c = $conversations->get($p->visiteur_token);

                return [
                    'token' => $p->visiteur_token,
                    'nom' => $p->user?->pseudo_public ?? 'Visiteur anonyme',
                    'email' => $p->user?->email,
                    'connecte' => $p->user !== null,
                    'url' => $p->url,
                    'appareil' => $this->appareil($p->user_agent),
                    'depuis' => $p->created_at?->diffForHumans(),
                    'activite' => $p->derniere_activite?->diffForHumans(),
                    'conversation_id' => $c?->id,
                    'non_lus' => (int) ($c->non_lus_admin ?? 0),
                ];
            })->values(),
        ]);
    }

    /** Messages d'une conversation (côté admin) — marque les messages visiteur comme lus. */
    public function conversation(Conversation $conversation): JsonResponse
    {
        $conversation->messages()
            ->where('expediteur', Expediteur::Visiteur->value)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);

        return $this->messages($conversation);
    }

    /** L'admin répond → prend la conversation en main (le bot se tait). */
    public function repondre(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate(['corps' => ['required', 'string', 'max:2000']]);

        $conversation->messages()->create([
            'expediteur' => Expediteur::Admin,
            'admin_id' => $request->user()->id,
            'corps' => $data['corps'],
        ]);
        $conversation->update(['humain_actif' => true, 'admin_id' => $request->user()->id]);
        $conversation->touch();

        return $this->messages($conversation);
    }

    /** Démarre (ou récupère) une conversation avec un visiteur en ligne. */
    public function demarrer(Request $request): JsonResponse
    {
        $data = $request->validate(['visiteur_token' => ['required', 'string', 'max:64']]);

        $presence = Presence::where('visiteur_token', $data['visiteur_token'])->first();
        $conversation = Conversation::firstOrCreate(
            ['visiteur_token' => $data['visiteur_token']],
            ['user_id' => $presence?->user_id],
        );

        return response()->json(['conversation_id' => $conversation->id]);
    }

    private function messages(Conversation $conversation): JsonResponse
    {
        return response()->json([
            'id' => $conversation->id,
            'humain_actif' => $conversation->humain_actif,
            'messages' => $conversation->messages()->orderBy('id')->get()->map(fn ($m) => [
                'id' => $m->id,
                'expediteur' => $m->expediteur->value,
                'corps' => $m->corps,
                'date' => $m->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /** Description courte navigateur/OS à partir du User-Agent. */
    private function appareil(?string $ua): string
    {
        if (blank($ua)) {
            return 'Inconnu';
        }

        $navigateur = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Navigateur',
        };

        $systeme = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS'), str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => '—',
        };

        return "{$navigateur} · {$systeme}";
    }
}
