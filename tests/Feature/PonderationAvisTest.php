<?php

namespace Tests\Feature;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PonderationAvisTest extends TestCase
{
    use RefreshDatabase;

    private function avis(Entreprise $entreprise, User $user, int $note, ?Carbon $creeLe = null): void
    {
        AvisEntreprise::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $user->id,
            'note_ambiance' => $note,
            'note_management' => $note,
            'note_salaire' => $note,
            'note_evolution' => $note,
            'statut_moderation' => StatutModeration::Publie,
            'created_at' => $creeLe ?? now(),
        ]);
    }

    public function test_un_avis_linkedin_verifie_pese_plus_qu_un_anonyme(): void
    {
        config([
            'classement.ponderation.confiance.linkedin' => 1.0,
            'classement.ponderation.confiance.email' => 0.6,
            'classement.ponderation.confiance.defaut' => 0.3,
            'classement.ponderation.recence_demi_vie_jours' => 540,
        ]);

        $entreprise = Entreprise::factory()->create();
        $verifie = User::factory()->create(['linkedin_verifie' => true]);
        $anonyme = User::factory()->unverified()->create(['linkedin_verifie' => false]);

        $this->avis($entreprise, $verifie, 5);   // poids 1.0
        $this->avis($entreprise, $anonyme, 1);   // poids 0.3

        // Moyenne pondérée = (5×1.0 + 1×0.3) / 1.3 ≈ 4.08 (vs 3.0 non pondéré).
        $this->assertEqualsWithDelta(4.08, (float) $entreprise->fresh()->note_globale, 0.05);
    }

    public function test_un_avis_ancien_pese_moins_qu_un_recent(): void
    {
        config([
            'classement.ponderation.confiance.linkedin' => 1.0,
            'classement.ponderation.confiance.email' => 1.0,
            'classement.ponderation.confiance.defaut' => 1.0, // neutralise la confiance
            'classement.ponderation.recence_demi_vie_jours' => 540,
        ]);

        $entreprise = Entreprise::factory()->create();

        $this->avis($entreprise, User::factory()->create(), 5, now());               // récence 1.0
        $this->avis($entreprise, User::factory()->create(), 1, now()->subDays(540));  // récence 0.5

        // (5×1.0 + 1×0.5) / 1.5 ≈ 3.67 (vs 3.0 non pondéré).
        $this->assertEqualsWithDelta(3.67, (float) $entreprise->fresh()->note_globale, 0.05);
    }
}
