<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use App\Services\SiteWebInspecteur;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Complète les fiches depuis le site web officiel de chaque entreprise
 * (meta description, page LinkedIn). Ne remplit que les champs vides et
 * horodate chaque tentative pour ne pas re-solliciter un site à chaque passage.
 */
class EnrichirEntreprises extends Command
{
    protected $signature = 'entreprises:enrichir
                            {--entreprise= : ID ou slug d\'une seule entreprise}
                            {--limite=50 : Nombre maximum de sites interrogés}
                            {--force : Retente aussi les fiches déjà enrichies}
                            {--pause=500 : Pause entre deux requêtes (ms)}';

    protected $description = 'Enrichit les fiches entreprises depuis leur site web (description, LinkedIn)';

    public function handle(SiteWebInspecteur $inspecteur): int
    {
        $cible = $this->option('entreprise');

        $entreprises = Entreprise::query()
            ->whereNotNull('site_web')
            ->when($cible !== null, fn ($q) => $q->where(fn ($w) => $w
                ->where('slug', $cible)
                ->when(is_numeric($cible), fn ($x) => $x->orWhere('id', (int) $cible))))
            ->when($cible === null && ! $this->option('force'), fn ($q) => $q->whereNull('enrichi_le'))
            ->orderBy('id')
            ->limit((int) $this->option('limite'))
            ->get();

        if ($entreprises->isEmpty()) {
            $this->components->info('Aucune fiche à enrichir.');

            return self::SUCCESS;
        }

        $enrichies = 0;

        foreach ($entreprises as $i => $entreprise) {
            if ($i > 0) {
                usleep((int) $this->option('pause') * 1000);
            }

            try {
                $infos = $inspecteur->inspecter($entreprise->site_web);
            } catch (RuntimeException $e) {
                $entreprise->forceFill(['enrichi_le' => now()])->save();
                $this->components->twoColumnDetail($entreprise->nom, '<fg=red>'.$e->getMessage().'</>');

                continue;
            }

            if (blank($entreprise->description) && filled($infos['description'])) {
                $entreprise->description = Str::limit($infos['description'], 500);
            }
            if (blank($entreprise->linkedin_url) && filled($infos['linkedin_url'])) {
                $entreprise->linkedin_url = $infos['linkedin_url'];
            }

            $modifiee = $entreprise->isDirty();
            $entreprise->enrichi_le = now();
            $entreprise->save();

            $enrichies += (int) $modifiee;
            $this->components->twoColumnDetail($entreprise->nom, $modifiee ? '<fg=green>complétée</>' : '<fg=gray>rien de neuf</>');
        }

        $this->components->info("{$enrichies} / {$entreprises->count()} fiches complétées.");

        return self::SUCCESS;
    }
}
