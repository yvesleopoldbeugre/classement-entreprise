<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use App\Services\ClassementService;
use Illuminate\Console\Command;

class RecalculerClassement extends Command
{
    protected $signature = 'classement:recalculer
                            {--entreprise= : ID ou slug d\'une seule entreprise à recalculer}';

    protected $description = 'Recalcule les scores bayésiens de classement des entreprises';

    public function handle(ClassementService $service): int
    {
        $cible = $this->option('entreprise');

        if ($cible !== null) {
            return $this->recalculerUne($service, $cible);
        }

        return $this->recalculerTout($service);
    }

    private function recalculerUne(ClassementService $service, string $cible): int
    {
        $entreprise = Entreprise::query()
            ->where('slug', $cible)
            ->when(is_numeric($cible), fn ($q) => $q->orWhere('id', (int) $cible))
            ->first();

        if ($entreprise === null) {
            $this->components->error("Entreprise introuvable : {$cible}");

            return self::FAILURE;
        }

        $service->recalculerEntreprise($entreprise);

        $this->components->info(sprintf(
            '%s recalculée → score %s (%d avis).',
            $entreprise->nom,
            $entreprise->score_bayesien ?? '—',
            $entreprise->nb_avis_total,
        ));

        return self::SUCCESS;
    }

    private function recalculerTout(ClassementService $service): int
    {
        $total = Entreprise::count();

        if ($total === 0) {
            $this->components->warn('Aucune entreprise à recalculer.');

            return self::SUCCESS;
        }

        $this->components->info('Moyenne globale du site (C) = '.round($service->moyenneGlobaleSite(), 3));

        $debut = microtime(true);

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $service->recalculerTout(fn () => $bar->advance());
        $bar->finish();
        $this->newLine(2);

        $duree = round(microtime(true) - $debut, 2);
        $classees = Entreprise::classable()->count();

        $this->components->info("{$total} entreprises recalculées en {$duree}s — {$classees} classées.");

        return self::SUCCESS;
    }
}
