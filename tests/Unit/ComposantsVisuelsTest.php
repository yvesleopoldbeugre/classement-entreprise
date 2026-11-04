<?php

namespace Tests\Unit;

use Tests\TestCase;

/** Rendu des composants Blade de la refonte (anneau de score, monogramme). */
class ComposantsVisuelsTest extends TestCase
{
    public function test_l_anneau_se_remplit_selon_la_note(): void
    {
        $this->blade('<x-anneau :note="4.4" :taille="48" />')
            ->assertSee('stroke-dasharray="88 100"', false)
            ->assertSee('text-vert', false)
            ->assertSee('4,4')
            ->assertSee('width: 48px', false);
    }

    public function test_la_couleur_de_l_anneau_suit_les_paliers(): void
    {
        $this->blade('<x-anneau :note="3.5" />')->assertSee('text-vert', false);
        $this->blade('<x-anneau :note="2.8" />')->assertSee('text-ambre', false);
        $this->blade('<x-anneau :note="2.79" />')->assertSee('text-rouge', false);
    }

    public function test_l_anneau_sans_note_affiche_un_tiret_sans_remplissage(): void
    {
        $this->blade('<x-anneau :note="null" />')
            ->assertSee('–')
            ->assertDontSee('anneau-valeur', false);
    }

    public function test_l_anneau_borne_une_note_hors_echelle(): void
    {
        $this->blade('<x-anneau :note="7" />')->assertSee('stroke-dasharray="100 100"', false);
    }

    public function test_l_anneau_peut_etre_anime(): void
    {
        $this->blade('<x-anneau :note="4" tracer />')->assertSee('data-tracer', false);
        $this->blade('<x-anneau :note="4" />')->assertDontSee('data-tracer', false);
    }

    public function test_le_monogramme_prend_les_initiales_des_deux_premiers_mots(): void
    {
        $this->blade('<x-monogramme nom="Akwaba Tech Services" />')->assertSee('>AT<', false);
        $this->blade('<x-monogramme nom="djamo" />')->assertSee('>D<', false);
        $this->blade('<x-monogramme nom="Société  Générale" />')->assertSee('>SG<', false);
    }

    public function test_le_monogramme_garde_la_meme_couleur_pour_un_meme_nom(): void
    {
        $a = (string) $this->blade('<x-monogramme nom="Lagune Télécom" />');
        $b = (string) $this->blade('<x-monogramme nom="Lagune Télécom" />');

        $this->assertSame($a, $b);
    }
}
