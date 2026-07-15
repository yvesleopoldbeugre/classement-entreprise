<?php

namespace App\Http\Controllers;

use App\Enums\Expediteur;
use App\Models\Conversation;
use App\Models\Presence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    /** Battement de cœur du visiteur : met à jour sa présence + signale les messages non lus. */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'visiteur_token' => ['required', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:500'],
        ]);

        $token = $data['visiteur_token'];

        Presence::updateOrCreate(
            ['visiteur_token' => $token],
            [
                'user_id' => $request->user()?->id,
                'url' => $data['url'] ?? $request->headers->get('referer'),
                'user_agent' => $request->userAgent(),
                'ip_hash' => hash('sha256', $request->ip().'|'.config('app.key')),
                'derniere_activite' => now(),
            ],
        );

        // Messages du bot/équipe non encore lus par le visiteur (pour la pastille de la bulle).
        $conversation = Conversation::where('visiteur_token', $token)->first();
        $nouveaux = $conversation
            ? $conversation->messages()
                ->where('expediteur', '!=', Expediteur::Visiteur->value)
                ->whereNull('lu_at')->count()
            : 0;

        return response()->json(['ok' => true, 'nouveaux' => $nouveaux]);
    }
}
