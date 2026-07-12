<?php

namespace App\Console\Commands;

use App\Enums\TypeEvenement;
use App\Models\Evenement;
use Illuminate\Console\Command;

class PurgerStatistiques extends Command
{
    protected $signature = 'stats:purger {--mois=12 : Ancienneté (en mois) au-delà de laquelle purger les visites}';

    protected $description = 'Purge les événements de visite trop anciens (les actions sont conservées)';

    public function handle(): int
    {
        $mois = max(1, (int) $this->option('mois'));
        $limite = now()->subMonths($mois);

        // On ne purge que les visites (volumineuses) ; les actions restent pour l'historique.
        $supprimes = Evenement::where('type', TypeEvenement::Visite)
            ->where('created_at', '<', $limite)
            ->delete();

        $this->components->info("{$supprimes} visite(s) de plus de {$mois} mois purgée(s).");

        return self::SUCCESS;
    }
}
