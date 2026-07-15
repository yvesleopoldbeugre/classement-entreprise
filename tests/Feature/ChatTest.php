<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_le_heartbeat_enregistre_la_presence(): void
    {
        $this->postJson(route('presence'), ['visiteur_token' => 'visiteur-1', 'url' => '/'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('presences', ['visiteur_token' => 'visiteur-1']);
    }

    public function test_le_bot_repond_a_une_question_connue(): void
    {
        $this->postJson(route('chat.message'), ['visiteur_token' => 'visiteur-1', 'corps' => 'C’est gratuit ?'])
            ->assertOk();

        $conversation = Conversation::where('visiteur_token', 'visiteur-1')->firstOrFail();
        $this->assertTrue(
            $conversation->messages()->where('expediteur', 'bot')->where('corps', 'like', '%gratuit%')->exists(),
        );
    }

    public function test_le_bot_transmet_quand_il_ne_sait_pas(): void
    {
        $this->postJson(route('chat.message'), ['visiteur_token' => 'visiteur-2', 'corps' => 'zzz question totalement inconnue xyz'])
            ->assertOk();

        $conversation = Conversation::where('visiteur_token', 'visiteur-2')->firstOrFail();
        $this->assertTrue(
            $conversation->messages()->where('expediteur', 'bot')->where('corps', 'like', '%transmets%')->exists(),
        );
    }

    public function test_les_messages_sont_cloisonnes_par_token(): void
    {
        $this->postJson(route('chat.message'), ['visiteur_token' => 'visiteur-A', 'corps' => 'coucou']);

        // Un autre token ne voit pas la conversation du premier.
        $this->getJson(route('chat.messages', ['visiteur_token' => 'visiteur-B']))
            ->assertOk()
            ->assertJson(['messages' => []]);
    }

    public function test_la_liste_des_visiteurs_est_reservee_aux_admins(): void
    {
        $this->get(route('admin.live.visiteurs'))->assertRedirect(route('login')); // invité → login

        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.live.visiteurs'))->assertForbidden();

        $this->actingAs($this->admin())
            ->getJson(route('admin.live.visiteurs'))->assertOk()->assertJsonStructure(['total', 'visiteurs']);
    }

    public function test_quand_un_admin_repond_le_bot_se_tait(): void
    {
        $this->postJson(route('chat.message'), ['visiteur_token' => 'visiteur-C', 'corps' => 'salut']);
        $conversation = Conversation::where('visiteur_token', 'visiteur-C')->firstOrFail();

        $this->actingAs($this->admin())
            ->postJson(route('admin.live.repondre', $conversation), ['corps' => 'Bonjour, je suis un humain 👋'])
            ->assertOk();

        $this->assertTrue($conversation->fresh()->humain_actif);

        // Le visiteur réécrit : plus de réponse auto du bot.
        $avant = $conversation->messages()->where('expediteur', 'bot')->count();
        $this->postJson(route('chat.message'), ['visiteur_token' => 'visiteur-C', 'corps' => 'gratuit ?']);
        $apres = $conversation->fresh()->messages()->where('expediteur', 'bot')->count();

        $this->assertSame($avant, $apres);
    }
}
