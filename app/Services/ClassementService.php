<?php

namespace App\Services;

use App\Enums\StatutModeration;
use App\Models\Entreprise;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les scores de classement des entreprises.
 *
 * La note d'une entreprise (R) est une MOYENNE PONDÉRÉE des avis publiés :
 * chaque avis pèse `poids = confiance(auteur) × récence`. Le score de classement
 * est ensuite une MOYENNE BAYÉSIENNE (approche « IMDb ») autour de R :
 *
 *        score = (v / (v + m)) * R  +  (m / (v + m)) * C
 *
 *   R = moyenne pondérée des 4 dimensions (avis publiés)
 *   v = nombre d'avis publiés de l'entreprise
 *   m = seuil de confiance (config('classement.seuil_avis'))
 *   C = note moyenne globale du site (tous avis publiés confondus)
 *
 * Pondération (config('classement.ponderation')) :
 *   - confiance : LinkedIn vérifié > email vérifié > compte non vérifié.
 *   - récence : décroissance exponentielle (demi-vie configurable).
 * Effet : les avis de professionnels vérifiés et récents comptent davantage,
 * et le bayésien évite qu'un unique avis fausse le classement.
 */
class ClassementService
{
    /**
     * Recalcule les moyennes + le score bayésien d'une seule entreprise.
     * Utilise la moyenne globale du site (C) à l'instant du calcul.
     */
    public function recalculerEntreprise(Entreprise $entreprise, ?float $moyenneSite = null): void
    {
        $stats = $this->statsAvis($entreprise->id);
        $moyenneSite ??= $this->moyenneGlobaleSite();

        $this->appliquerStats($entreprise, $stats, $moyenneSite);
        $entreprise->save();
    }

    /**
     * Recalcule TOUTES les entreprises de façon cohérente.
     * À appeler après un import massif / seeding, ou périodiquement,
     * car ajouter des avis fait bouger la moyenne globale C de tout le monde.
     *
     * @param  callable(Entreprise):void|null  $apres  appelé après chaque entreprise (progression)
     * @return int nombre d'entreprises recalculées
     */
    public function recalculerTout(?callable $apres = null): int
    {
        $moyenneSite = $this->moyenneGlobaleSite();
        $total = 0;

        Entreprise::query()->chunkById(200, function ($entreprises) use ($moyenneSite, $apres, &$total) {
            foreach ($entreprises as $entreprise) {
                $stats = $this->statsAvis($entreprise->id);
                $this->appliquerStats($entreprise, $stats, $moyenneSite);
                $entreprise->save();
                $total++;

                if ($apres !== null) {
                    $apres($entreprise);
                }
            }
        });

        return $total;
    }

    /**
     * Moyenne globale du site (C) : moyenne des notes de TOUS les avis publiés,
     * chaque avis pesant sa moyenne des 4 dimensions. Repli sur la moyenne
     * neutre configurée tant qu'aucun avis n'est publié.
     */
    public function moyenneGlobaleSite(): float
    {
        $moyenne = DB::table('avis_entreprises')
            ->where('statut_moderation', StatutModeration::Publie->value)
            ->avg(DB::raw('(note_ambiance + note_management + note_salaire + note_evolution) / 4.0'));

        return $moyenne !== null
            ? (float) $moyenne
            : (float) config('classement.moyenne_defaut');
    }

    /**
     * Agrège les avis publiés d'une entreprise en moyennes PONDÉRÉES
     * (poids = confiance de l'auteur × récence de l'avis).
     *
     * @return object{nb:int, ambiance:?float, management:?float, salaire:?float, evolution:?float}
     */
    private function statsAvis(int $entrepriseId): object
    {
        $avis = DB::table('avis_entreprises as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.entreprise_id', $entrepriseId)
            ->where('a.statut_moderation', StatutModeration::Publie->value)
            ->get([
                'a.note_ambiance', 'a.note_management', 'a.note_salaire', 'a.note_evolution',
                'a.created_at', 'u.linkedin_verifie', 'u.email_verified_at',
            ]);

        $nb = $avis->count();

        if ($nb === 0) {
            return (object) ['nb' => 0, 'ambiance' => null, 'management' => null, 'salaire' => null, 'evolution' => null];
        }

        $sommePoids = 0.0;
        $sommes = ['ambiance' => 0.0, 'management' => 0.0, 'salaire' => 0.0, 'evolution' => 0.0];

        foreach ($avis as $a) {
            $poids = $this->poidsConfiance($a) * $this->poidsRecence($a->created_at);
            $sommePoids += $poids;
            $sommes['ambiance'] += $a->note_ambiance * $poids;
            $sommes['management'] += $a->note_management * $poids;
            $sommes['salaire'] += $a->note_salaire * $poids;
            $sommes['evolution'] += $a->note_evolution * $poids;
        }

        // Garde-fou théorique (poids toujours > 0) : repli sur une moyenne simple.
        if ($sommePoids <= 0) {
            $sommePoids = (float) $nb;
            $sommes = [
                'ambiance' => $avis->sum('note_ambiance'),
                'management' => $avis->sum('note_management'),
                'salaire' => $avis->sum('note_salaire'),
                'evolution' => $avis->sum('note_evolution'),
            ];
        }

        return (object) [
            'nb' => $nb,
            'ambiance' => $sommes['ambiance'] / $sommePoids,
            'management' => $sommes['management'] / $sommePoids,
            'salaire' => $sommes['salaire'] / $sommePoids,
            'evolution' => $sommes['evolution'] / $sommePoids,
        ];
    }

    /** Poids de confiance selon le niveau de vérification de l'auteur. */
    private function poidsConfiance(object $avis): float
    {
        $conf = config('classement.ponderation.confiance');

        if (! empty($avis->linkedin_verifie)) {
            return (float) $conf['linkedin'];
        }

        if (! empty($avis->email_verified_at)) {
            return (float) $conf['email'];
        }

        return (float) $conf['defaut'];
    }

    /** Poids de récence : décroissance exponentielle (demi-vie configurable). */
    private function poidsRecence(?string $creeLe): float
    {
        $demiVie = (int) config('classement.ponderation.recence_demi_vie_jours');

        if ($demiVie <= 0 || $creeLe === null) {
            return 1.0;
        }

        $ageJours = abs(Carbon::parse($creeLe)->diffInDays(Carbon::now()));

        return 2 ** (-$ageJours / $demiVie);
    }

    /**
     * Applique les stats agrégées + le calcul bayésien sur le modèle (sans save()).
     */
    private function appliquerStats(Entreprise $entreprise, object $stats, float $moyenneSite): void
    {
        $v = (int) $stats->nb;

        if ($v === 0) {
            $entreprise->forceFill([
                'nb_avis_total' => 0,
                'moy_ambiance' => null,
                'moy_management' => null,
                'moy_salaire' => null,
                'moy_evolution' => null,
                'note_globale' => null,
                'score_bayesien' => null,
            ]);

            return;
        }

        $moyAmbiance = (float) $stats->ambiance;
        $moyManagement = (float) $stats->management;
        $moySalaire = (float) $stats->salaire;
        $moyEvolution = (float) $stats->evolution;

        // R : moyenne pondérée des 4 dimensions (déjà pondérées par confiance × récence).
        $r = ($moyAmbiance + $moyManagement + $moySalaire + $moyEvolution) / 4;

        $m = (int) config('classement.seuil_avis');

        // Moyenne bayésienne.
        $score = ($v / ($v + $m)) * $r + ($m / ($v + $m)) * $moyenneSite;

        $attributs = [
            'nb_avis_total' => $v,
            'moy_ambiance' => round($moyAmbiance, 2),
            'moy_management' => round($moyManagement, 2),
            'moy_salaire' => round($moySalaire, 2),
            'moy_evolution' => round($moyEvolution, 2),
            'note_globale' => round($r, 2),
            'score_bayesien' => round($score, 3),
        ];

        // Sortie automatique de la liste « à éviter » quand la note (étoiles)
        // atteint le seuil sur assez d'avis publiés.
        if ($entreprise->rang_a_eviter !== null
            && $v >= (int) config('classement.sortie_a_eviter.avis_min')
            && $r >= (float) config('classement.sortie_a_eviter.note_min')) {
            $attributs['rang_a_eviter'] = null;
        }

        $entreprise->forceFill($attributs);
    }
}
