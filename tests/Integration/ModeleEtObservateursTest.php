<?php

namespace Tests\Integration;

use App\Enums\StatutEntreprise;
use App\Enums\StatutModeration;
use App\Http\Controllers\ClassementController;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Scopes, slugs et observateurs : le modèle et la base ensemble. */
class ModeleEtObservateursTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_slug_est_genere_et_rendu_unique(): void
    {
        $a = Entreprise::factory()->create(['nom' => 'Akwaba Tech', 'slug' => null]);
        $b = Entreprise::factory()->create(['nom' => 'Akwaba Tech', 'slug' => null]);

        $this->assertSame('akwaba-tech', $a->slug);
        $this->assertSame('akwaba-tech-2', $b->slug);
    }

    public function test_publique_regroupe_verifiees_et_liste_editoriale(): void
    {
        $verifiee = Entreprise::factory()->create(['statut' => StatutEntreprise::Verifiee, 'rang_a_eviter' => null]);
        $editoriale = Entreprise::factory()->create(['statut' => StatutEntreprise::AVerifier, 'rang_a_eviter' => 1]);
        Entreprise::factory()->create(['statut' => StatutEntreprise::AVerifier, 'rang_a_eviter' => null]);

        $this->assertEqualsCanonicalizing([$verifiee->id, $editoriale->id], Entreprise::publique()->pluck('id')->all());
    }

    public function test_classable_exige_le_minimum_d_avis_et_par_classement_trie(): void
    {
        config(['classement.min_avis_classement' => 3]);
        $haut = Entreprise::factory()->create();
        $haut->forceFill(['nb_avis_total' => 5, 'score_bayesien' => 4.2])->save();
        $bas = Entreprise::factory()->create();
        $bas->forceFill(['nb_avis_total' => 3, 'score_bayesien' => 3.1])->save();
        $peu = Entreprise::factory()->create();
        $peu->forceFill(['nb_avis_total' => 2, 'score_bayesien' => 4.9])->save();

        $this->assertSame([$haut->id, $bas->id], Entreprise::classable()->parClassement()->pluck('id')->all());
    }

    public function test_a_eviter_suit_l_ordre_editorial(): void
    {
        $deux = Entreprise::factory()->create(['rang_a_eviter' => 2]);
        $un = Entreprise::factory()->create(['rang_a_eviter' => 1]);
        Entreprise::factory()->create(['rang_a_eviter' => null]);

        $this->assertSame([$un->id, $deux->id], Entreprise::aEviter()->pluck('id')->all());
    }

    public function test_les_communes_sont_dedoublonnees_par_slug(): void
    {
        foreach (['Cocody', 'cocody', 'Plateau'] as $commune) {
            Entreprise::factory()->create(['commune' => $commune, 'statut' => StatutEntreprise::Verifiee]);
        }
        Entreprise::factory()->create(['commune' => 'Abobo', 'statut' => StatutEntreprise::AVerifier, 'rang_a_eviter' => null]);

        $communes = ClassementController::communes();

        $this->assertCount(2, $communes);
        $this->assertNotContains('Abobo', $communes->all()); // non publique
    }

    public function test_publier_un_avis_recalcule_l_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        $avis = AvisEntreprise::factory()->enAttente()->create(['entreprise_id' => $entreprise->id]);
        $this->assertSame(0, $entreprise->refresh()->nb_avis_total);

        $avis->update(['statut_moderation' => StatutModeration::Publie]);

        $this->assertSame(1, $entreprise->refresh()->nb_avis_total);
        $this->assertNotNull($entreprise->score_bayesien);
    }

    public function test_le_statut_par_defaut_suit_la_configuration_de_moderation(): void
    {
        config(['moderation.enabled' => true]);
        $this->assertSame(StatutModeration::EnAttente, StatutModeration::parDefaut());

        config(['moderation.enabled' => false]);
        $this->assertSame(StatutModeration::Publie, StatutModeration::parDefaut());
    }

    public function test_la_note_moyenne_d_un_avis_fait_la_moyenne_des_quatre_criteres(): void
    {
        $avis = AvisEntreprise::factory()->create(['note_ambiance' => 5, 'note_management' => 4, 'note_salaire' => 2, 'note_evolution' => 3]);

        $this->assertSame(3.5, $avis->noteMoyenne());
    }
}
