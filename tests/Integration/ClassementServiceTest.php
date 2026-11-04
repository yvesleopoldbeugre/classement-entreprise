<?php

namespace Tests\Integration;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use App\Services\ClassementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Calcul du score sur une vraie base : moyenne globale ancrée, pondérations, bayésien. */
class ClassementServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClassementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Paramètres fixes et pondérations neutres : les attendus se calculent à la main.
        config([
            'classement.seuil_avis' => 5,
            'classement.prior_avis' => 10,
            'classement.moyenne_defaut' => 3.0,
            'classement.ponderation.confiance' => ['linkedin' => 1.0, 'email' => 1.0, 'defaut' => 1.0],
            'classement.ponderation.recence_demi_vie_jours' => 0,
        ]);
        $this->service = app(ClassementService::class);
    }

    /** @param array{0:int,1:int,2:int,3:int}|int $notes */
    private function avis(Entreprise $entreprise, array|int $notes, array $attributs = [], ?User $user = null): AvisEntreprise
    {
        [$a, $m, $s, $e] = is_int($notes) ? [$notes, $notes, $notes, $notes] : $notes;

        return AvisEntreprise::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => ($user ?? User::factory()->create())->id,
            'note_ambiance' => $a, 'note_management' => $m, 'note_salaire' => $s, 'note_evolution' => $e,
            'statut_moderation' => StatutModeration::Publie,
            ...$attributs,
        ]);
    }

    public function test_sans_avis_la_moyenne_globale_est_la_valeur_neutre(): void
    {
        $this->assertSame(3.0, $this->service->moyenneGlobaleSite());
    }

    public function test_la_moyenne_globale_est_ancree_vers_le_neutre(): void
    {
        $this->avis(Entreprise::factory()->create(), 5);

        // C = (N/(N+K))·moyenne + (K/(N+K))·neutre = (1/11)·5 + (10/11)·3
        $this->assertEqualsWithDelta(3 + 2 / 11, $this->service->moyenneGlobaleSite(), 1e-9);
    }

    public function test_les_avis_non_publies_sont_ignores(): void
    {
        $entreprise = Entreprise::factory()->create();
        $this->avis($entreprise, 1, ['statut_moderation' => StatutModeration::EnAttente]);
        $this->avis($entreprise, 1, ['statut_moderation' => StatutModeration::Signale]);

        $this->assertSame(3.0, $this->service->moyenneGlobaleSite());
        $this->assertSame(0, $entreprise->refresh()->nb_avis_total);
        $this->assertNull($entreprise->score_bayesien);
    }

    public function test_le_score_bayesien_suit_la_formule(): void
    {
        $entreprise = Entreprise::factory()->create();
        $this->avis($entreprise, [5, 4, 3, 4]);
        $this->avis($entreprise, [4, 4, 4, 4]);

        $this->service->recalculerEntreprise($entreprise);

        $r = (4.5 + 4 + 3.5 + 4) / 4;              // moyenne des 4 dimensions
        $c = (2 / 12) * $r + (10 / 12) * 3;        // moyenne globale ancrée (N=2, K=10)
        $attendu = (2 / 7) * $r + (5 / 7) * $c;    // v=2, m=5

        $entreprise->refresh();
        $this->assertSame(2, $entreprise->nb_avis_total);
        $this->assertSame('4.50', (string) $entreprise->moy_ambiance);
        $this->assertSame('3.50', (string) $entreprise->moy_salaire);
        $this->assertSame('4.00', (string) $entreprise->note_globale);
        $this->assertEqualsWithDelta($attendu, (float) $entreprise->score_bayesien, 0.001);
    }

    public function test_la_confiance_de_l_auteur_pondere_la_moyenne(): void
    {
        config(['classement.ponderation.confiance' => ['linkedin' => 1.0, 'email' => 0.6, 'defaut' => 0.3]]);
        $entreprise = Entreprise::factory()->create();
        $this->avis($entreprise, 5, user: User::factory()->create(['linkedin_verifie' => true]));
        $this->avis($entreprise, 1, user: User::factory()->unverified()->create());

        $this->service->recalculerEntreprise($entreprise);

        // (5×1,0 + 1×0,3) / 1,3
        $this->assertEqualsWithDelta(5.3 / 1.3, (float) $entreprise->refresh()->note_globale, 0.01);
    }

    public function test_la_recence_divise_le_poids_par_deux_a_chaque_demi_vie(): void
    {
        config(['classement.ponderation.recence_demi_vie_jours' => 100]);
        Carbon::setTestNow('2026-11-04 12:00:00');
        $entreprise = Entreprise::factory()->create();
        $this->avis($entreprise, 5);
        $this->avis($entreprise, 1, ['created_at' => now()->subDays(100)]);

        $this->service->recalculerEntreprise($entreprise);

        // (5×1 + 1×0,5) / 1,5
        $this->assertEqualsWithDelta(5.5 / 1.5, (float) $entreprise->refresh()->note_globale, 0.01);
        Carbon::setTestNow();
    }

    public function test_sans_avis_publie_les_colonnes_reviennent_a_null(): void
    {
        $entreprise = Entreprise::factory()->create();
        $avis = $this->avis($entreprise, 4);
        $this->assertNotNull($entreprise->refresh()->score_bayesien);

        $avis->delete();

        $entreprise->refresh();
        $this->assertSame(0, $entreprise->nb_avis_total);
        $this->assertNull($entreprise->note_globale);
        $this->assertNull($entreprise->score_bayesien);
    }

    public function test_recalculer_tout_traite_chaque_entreprise(): void
    {
        Entreprise::factory()->count(3)->create();
        $vues = [];

        $total = $this->service->recalculerTout(function (Entreprise $e) use (&$vues) {
            $vues[] = $e->id;
        });

        $this->assertSame(3, $total);
        $this->assertCount(3, array_unique($vues));
    }
}
