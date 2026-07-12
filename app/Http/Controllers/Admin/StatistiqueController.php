<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Models\Evenement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatistiqueController extends Controller
{
    /** Périodes proposées (en jours). */
    private const PERIODES = [7, 30, 90];

    public function index(Request $request): View
    {
        $jours = (int) $request->integer('jours', 30);
        if (! \in_array($jours, self::PERIODES, true)) {
            $jours = 30;
        }

        $depuis = now()->subDays($jours - 1)->startOfDay();

        // Comptes par (jour, type) sur la période, puis remplissage des jours vides.
        $parJourType = Evenement::where('created_at', '>=', $depuis)
            ->selectRaw('DATE(created_at) as jour, type, COUNT(*) as n')
            ->groupBy('jour', 'type')
            ->get()
            ->keyBy(fn ($ligne) => $ligne->jour.'|'.$ligne->type->value);

        $labels = $visites = $actions = [];
        for ($curseur = $depuis->copy(), $i = 0; $i < $jours; $i++, $curseur->addDay()) {
            $cle = $curseur->format('Y-m-d');
            $labels[] = $curseur->translatedFormat('d M');

            $visitesJour = (int) ($parJourType->get($cle.'|'.TypeEvenement::Visite->value)->n ?? 0);
            $actionsJour = collect(TypeEvenement::cases())
                ->reject->estVisite()
                ->sum(fn (TypeEvenement $t) => (int) ($parJourType->get($cle.'|'.$t->value)->n ?? 0));

            $visites[] = $visitesJour;
            $actions[] = $actionsJour;
        }

        // Totaux par type (cartes KPI) + visiteurs uniques. Clés = valeur string du type.
        $totaux = Evenement::where('created_at', '>=', $depuis)
            ->selectRaw('type, COUNT(*) as n')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($ligne) => [$ligne->type->value => (int) $ligne->n]);

        $visiteursUniques = Evenement::query()
            ->where('type', TypeEvenement::Visite)
            ->where('created_at', '>=', $depuis)
            ->distinct()
            ->count('visiteur_hash');

        return view('admin.statistiques', [
            'jours' => $jours,
            'periodes' => self::PERIODES,
            'totaux' => $totaux,
            'visiteursUniques' => $visiteursUniques,
            'graphe' => [
                'labels' => $labels,
                'visites' => $visites,
                'actions' => $actions,
            ],
        ]);
    }
}
