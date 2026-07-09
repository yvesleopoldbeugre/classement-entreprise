<?php

namespace App\Services;

use App\Enums\StatutModeration;
use App\Models\Entreprise;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les scores de classement des entreprises.
 *
 * Le score de classement est une MOYENNE BAYÉSIENNE (approche « IMDb ») :
 *
 *        score = (v / (v + m)) * R  +  (m / (v + m)) * C
 *
 *   R = note moyenne de l'entreprise (moyenne des 4 dimensions, avis publiés)
 *   v = nombre d'avis publiés de l'entreprise
 *   m = seuil de confiance (config('classement.seuil_avis'))
 *   C = note moyenne globale du site (tous avis publiés confondus)
 *
 * Effet : une entreprise avec peu d'avis est tirée vers la moyenne du site C ;
 * il faut accumuler des avis (v >> m) pour que sa vraie note R domine le score.
 * On évite ainsi qu'un unique avis 5★ propulse une entreprise en tête.
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
     */
    public function recalculerTout(): void
    {
        $moyenneSite = $this->moyenneGlobaleSite();

        Entreprise::query()->chunkById(200, function ($entreprises) use ($moyenneSite) {
            foreach ($entreprises as $entreprise) {
                $stats = $this->statsAvis($entreprise->id);
                $this->appliquerStats($entreprise, $stats, $moyenneSite);
                $entreprise->save();
            }
        });
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
     * Agrège les avis publiés d'une entreprise.
     *
     * @return object{nb:int, ambiance:?float, management:?float, salaire:?float, evolution:?float}
     */
    private function statsAvis(int $entrepriseId): object
    {
        return DB::table('avis_entreprises')
            ->where('entreprise_id', $entrepriseId)
            ->where('statut_moderation', StatutModeration::Publie->value)
            ->selectRaw('COUNT(*) as nb')
            ->selectRaw('AVG(note_ambiance) as ambiance')
            ->selectRaw('AVG(note_management) as management')
            ->selectRaw('AVG(note_salaire) as salaire')
            ->selectRaw('AVG(note_evolution) as evolution')
            ->first();
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

        // R : note moyenne de l'entreprise (moyenne des 4 dimensions).
        $r = ($moyAmbiance + $moyManagement + $moySalaire + $moyEvolution) / 4;

        $m = (int) config('classement.seuil_avis');

        // Moyenne bayésienne.
        $score = ($v / ($v + $m)) * $r + ($m / ($v + $m)) * $moyenneSite;

        $entreprise->forceFill([
            'nb_avis_total' => $v,
            'moy_ambiance' => round($moyAmbiance, 2),
            'moy_management' => round($moyManagement, 2),
            'moy_salaire' => round($moySalaire, 2),
            'moy_evolution' => round($moyEvolution, 2),
            'note_globale' => round($r, 2),
            'score_bayesien' => round($score, 3),
        ]);
    }
}
