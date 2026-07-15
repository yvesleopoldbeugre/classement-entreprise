<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Bot de chat rule-based : accueil, réponses FAQ par mots-clés, et détection
 * des demandes nécessitant un humain. Base de connaissances dans config/chatbot.php.
 */
class ChatBot
{
    /** Message d'accueil à l'ouverture d'une conversation. */
    public function accueil(): string
    {
        return (string) config('chatbot.greeting');
    }

    /**
     * Réponse automatique à un message visiteur.
     * Retourne null si aucune règle ne correspond OU si le visiteur demande un humain
     * → dans ce cas l'appelant escalade (fallback + notification équipe).
     */
    public function repondre(string $message): ?string
    {
        $texte = $this->normaliser($message);

        // Demande explicite d'un humain → on n'auto-répond pas.
        foreach ((array) config('chatbot.escalade') as $mot) {
            if (str_contains($texte, $this->normaliser($mot))) {
                return null;
            }
        }

        foreach ((array) config('chatbot.regles') as $regle) {
            foreach ($regle['mots'] as $mot) {
                if (str_contains($texte, $this->normaliser($mot))) {
                    return $regle['reponse'];
                }
            }
        }

        return null;
    }

    /** Message envoyé quand le bot ne sait pas répondre (question transmise à l'équipe). */
    public function fallback(): string
    {
        return (string) config('chatbot.fallback');
    }

    /** Minuscule + sans accents, pour une comparaison tolérante. */
    private function normaliser(string $texte): string
    {
        return Str::lower(Str::ascii($texte));
    }
}
