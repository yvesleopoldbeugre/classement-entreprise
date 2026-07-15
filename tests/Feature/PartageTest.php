<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartageTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_fiche_affiche_les_boutons_de_partage(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->get(route('entreprises.show', $entreprise))
            ->assertOk()
            ->assertSee('Inviter à donner un avis')
            ->assertSee('wa.me/?text=', false)
            ->assertSee('facebook.com/sharer', false)
            ->assertSee('linkedin.com/sharing', false)
            ->assertSee('twitter.com/intent', false);
    }
}
