<?php

namespace Tests\Unit;

use App\Services\ChatBot;
use Tests\TestCase;

class ChatBotTest extends TestCase
{
    private ChatBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'chatbot.greeting' => 'Bonjour !',
            'chatbot.fallback' => 'Je transmets à l’équipe.',
            'chatbot.escalade' => ['humain', 'conseiller'],
            'chatbot.regles' => [
                ['mots' => ['gratuit', 'prix'], 'reponse' => 'C’est gratuit.'],
                ['mots' => ['anonym'], 'reponse' => 'C’est anonyme.'],
                ['mots' => ['prix'], 'reponse' => 'Jamais atteinte (la 1re règle gagne).'],
            ],
        ]);
        $this->bot = new ChatBot;
    }

    public function test_accueil_et_repli_viennent_de_la_configuration(): void
    {
        $this->assertSame('Bonjour !', $this->bot->accueil());
        $this->assertSame('Je transmets à l’équipe.', $this->bot->fallback());
    }

    public function test_un_mot_cle_declenche_la_reponse(): void
    {
        $this->assertSame('C’est gratuit.', $this->bot->repondre('Est-ce que le site est gratuit ?'));
    }

    public function test_la_comparaison_ignore_casse_et_accents(): void
    {
        $this->assertSame('C’est anonyme.', $this->bot->repondre('Mon avis est-il ANONYME ?'));
        $this->assertSame('C’est gratuit.', $this->bot->repondre('Quel PRIX ?'));
    }

    public function test_la_premiere_regle_qui_correspond_gagne(): void
    {
        $this->assertSame('C’est gratuit.', $this->bot->repondre('prix'));
    }

    public function test_une_demande_d_humain_n_est_pas_auto_repondue(): void
    {
        $this->assertNull($this->bot->repondre('Je veux parler à un humain, c’est gratuit ?'));
    }

    public function test_une_question_inconnue_renvoie_null(): void
    {
        $this->assertNull($this->bot->repondre('Quelle est la capitale du Ghana ?'));
    }
}
