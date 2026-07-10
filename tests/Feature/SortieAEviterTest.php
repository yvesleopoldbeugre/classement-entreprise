<?php

namespace Tests\Feature;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortieAEviterTest extends TestCase
{
    use RefreshDatabase;

    /** Crée N avis publiés avec la même note sur les 4 dimensions. */
    private function ajouterAvis(Entreprise $entreprise, int $nombre, int $note): void
    {
        AvisEntreprise::factory()->count($nombre)->create([
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => $note,
            'note_management' => $note,
            'note_salaire' => $note,
            'note_evolution' => $note,
            'statut_moderation' => StatutModeration::Publie,
        ]);
    }

    public function test_une_entreprise_quitte_la_zone_a_eviter_quand_la_note_atteint_le_seuil(): void
    {
        config(['classement.sortie_a_eviter.note_min' => 3.5, 'classement.sortie_a_eviter.avis_min' => 5]);

        $entreprise = Entreprise::factory()->create(['rang_a_eviter' => 1]);

        // 5 avis 4/5 → note_globale = 4 ≥ 3.5 sur 5 avis ≥ 5.
        $this->ajouterAvis($entreprise, 5, 4);

        $this->assertNull($entreprise->fresh()->rang_a_eviter, 'Devrait avoir quitté la zone à éviter.');
    }

    public function test_reste_a_eviter_si_pas_assez_d_avis(): void
    {
        config(['classement.sortie_a_eviter.note_min' => 3.5, 'classement.sortie_a_eviter.avis_min' => 5]);

        $entreprise = Entreprise::factory()->create(['rang_a_eviter' => 1]);

        // Note excellente mais seulement 4 avis (< avis_min).
        $this->ajouterAvis($entreprise, 4, 5);

        $this->assertSame(1, $entreprise->fresh()->rang_a_eviter, 'Trop peu d’avis : reste à éviter.');
    }

    public function test_reste_a_eviter_si_note_trop_basse(): void
    {
        config(['classement.sortie_a_eviter.note_min' => 3.5, 'classement.sortie_a_eviter.avis_min' => 5]);

        $entreprise = Entreprise::factory()->create(['rang_a_eviter' => 1]);

        // Assez d'avis mais note = 2 (< note_min).
        $this->ajouterAvis($entreprise, 6, 2);

        $this->assertSame(1, $entreprise->fresh()->rang_a_eviter, 'Note trop basse : reste à éviter.');
    }
}
