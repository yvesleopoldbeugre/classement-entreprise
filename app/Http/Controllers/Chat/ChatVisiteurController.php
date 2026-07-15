<?php

namespace App\Http\Controllers\Chat;

use App\Enums\Expediteur;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatVisiteurController extends Controller
{
    public function __construct(private readonly ChatBot $bot) {}

    /** Ouvre (ou récupère) la conversation du visiteur ; message d'accueil du bot au 1er contact. */
    public function ouvrir(Request $request): JsonResponse
    {
        return $this->charger($this->conversation($request));
    }

    /** Envoie un message visiteur, puis répond via le bot tant qu'aucun humain n'a pris la main. */
    public function envoyer(Request $request): JsonResponse
    {
        $data = $request->validate(['corps' => ['required', 'string', 'max:2000']]);
        $conversation = $this->conversation($request);

        if ($request->user() && ! $conversation->user_id) {
            $conversation->update(['user_id' => $request->user()->id]);
        }

        $conversation->messages()->create([
            'expediteur' => Expediteur::Visiteur,
            'corps' => $data['corps'],
        ]);
        $conversation->touch();

        // Réponse automatique du bot (sauf si un admin a pris la conversation en main).
        if (! $conversation->humain_actif) {
            $conversation->messages()->create([
                'expediteur' => Expediteur::Bot,
                'corps' => $this->bot->repondre($data['corps']) ?? $this->bot->fallback(),
            ]);
        }

        return $this->charger($conversation);
    }

    /** Poll des nouveaux messages (depuis un id) pour le visiteur. */
    public function messages(Request $request): JsonResponse
    {
        $conversation = Conversation::where('visiteur_token', $this->token($request))->first();

        if (! $conversation) {
            return response()->json(['conversation_id' => null, 'messages' => []]);
        }

        return $this->charger($conversation, (int) $request->integer('depuis'));
    }

    /** Conversation du visiteur (créée avec l'accueil du bot si nouvelle). */
    private function conversation(Request $request): Conversation
    {
        $conversation = Conversation::firstOrCreate(
            ['visiteur_token' => $this->token($request)],
            ['user_id' => $request->user()?->id],
        );

        if ($conversation->wasRecentlyCreated) {
            $conversation->messages()->create([
                'expediteur' => Expediteur::Bot,
                'corps' => $this->bot->accueil(),
            ]);
        }

        return $conversation;
    }

    /** Sérialise les messages (depuis un id) et marque comme lus ceux de l'équipe/bot. */
    private function charger(Conversation $conversation, int $depuis = 0): JsonResponse
    {
        $messages = $conversation->messages()
            ->when($depuis > 0, fn ($q) => $q->where('id', '>', $depuis))
            ->orderBy('id')
            ->get();

        $conversation->messages()
            ->where('expediteur', '!=', Expediteur::Visiteur->value)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'expediteur' => $m->expediteur->value,
                'corps' => $m->corps,
                'date' => $m->created_at?->toIso8601String(),
            ]),
        ]);
    }

    private function token(Request $request): string
    {
        $token = $request->input('visiteur_token') ?: $request->header('X-Visitor-Token');
        abort_unless(is_string($token) && strlen($token) >= 8 && strlen($token) <= 64, 422, 'Token visiteur invalide.');

        return $token;
    }
}
