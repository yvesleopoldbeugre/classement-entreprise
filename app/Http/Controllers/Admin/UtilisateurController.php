<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\Mission;
use App\Models\RetourEntretien;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtilisateurController extends Controller
{
    private const PERIODES = [7, 30, 90];

    private const VISITE = 'visite';

    public function index(Request $request): View
    {
        $jours = $this->periode($request);
        $depuis = now()->subDays($jours - 1)->startOfDay();

        // Liste : totaux tous temps + dernière activité, triée par engagement.
        $users = User::query()
            ->withCount([
                'evenements as actions_total' => fn ($q) => $q->where('type', '!=', self::VISITE),
                'avis as avis_total',
                'retoursEntretiens as entretiens_total',
                'missions as missions_total',
            ])
            ->withMax('evenements', 'created_at')
            ->orderByDesc('actions_total')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Comparatif : top 12 utilisateurs par actions sur la période choisie.
        $top = User::query()
            ->withCount(['evenements as actions_periode' => fn ($q) => $q
                ->where('type', '!=', self::VISITE)
                ->where('created_at', '>=', $depuis)])
            ->orderByDesc('actions_periode')
            ->limit(12)
            ->get()
            ->filter(fn ($u) => $u->actions_periode > 0)
            ->values();

        return view('admin.utilisateurs.index', [
            'users' => $users,
            'jours' => $jours,
            'periodes' => self::PERIODES,
            'comparatif' => [
                'labels' => $top->pluck('pseudo_public'),
                'valeurs' => $top->pluck('actions_periode'),
            ],
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $jours = $this->periode($request);
        $depuis = now()->subDays($jours - 1)->startOfDay();

        // Répartition tous temps par type (cartes KPI).
        $totaux = $user->evenements()
            ->selectRaw('type, COUNT(*) as n')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($l) => [$l->type->value => (int) $l->n]);

        // Série par jour sur la période (visites + actions).
        $parJourType = $user->evenements()
            ->where('created_at', '>=', $depuis)
            ->selectRaw('DATE(created_at) as jour, type, COUNT(*) as n')
            ->groupBy('jour', 'type')
            ->get()
            ->keyBy(fn ($l) => $l->jour.'|'.$l->type->value);

        $labels = $visites = $actions = [];
        for ($curseur = $depuis->copy(), $i = 0; $i < $jours; $i++, $curseur->addDay()) {
            $cle = $curseur->format('Y-m-d');
            $labels[] = $curseur->translatedFormat('d M');
            $visites[] = (int) ($parJourType->get($cle.'|'.self::VISITE)->n ?? 0);
            $actions[] = collect(TypeEvenement::cases())
                ->reject->estVisite()
                ->sum(fn (TypeEvenement $t) => (int) ($parJourType->get($cle.'|'.$t->value)->n ?? 0));
        }

        // Journal d'activité : toutes les actions ET les visites (avec la page consultée).
        $recentes = $user->evenements()
            ->with(['sujet' => fn (MorphTo $m) => $m->morphWith([
                AvisEntreprise::class => ['entreprise'],
                RetourEntretien::class => ['entreprise'],
                Mission::class => ['entreprise'],
            ])])
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn ($e) => [
                'type' => $e->type->libelle(),
                'estVisite' => $e->type->estVisite(),
                'date' => $e->created_at,
                'sujet' => $this->decrireSujet($e->sujet),
                'page' => $e->url,
            ]);

        return view('admin.utilisateurs.show', [
            'utilisateur' => $user,
            'jours' => $jours,
            'periodes' => self::PERIODES,
            'totaux' => $totaux,
            'recentes' => $recentes,
            'graphe' => [
                'labels' => $labels,
                'visites' => $visites,
                'actions' => $actions,
            ],
        ]);
    }

    private function periode(Request $request): int
    {
        $jours = (int) $request->integer('jours', 30);

        return \in_array($jours, self::PERIODES, true) ? $jours : 30;
    }

    /**
     * Libellé + lien éventuel décrivant le sujet d'un événement.
     *
     * @return array{libelle:?string, url:?string}
     */
    private function decrireSujet(?Model $sujet): array
    {
        $entreprise = match (true) {
            $sujet instanceof Entreprise => $sujet,
            $sujet instanceof AvisEntreprise,
            $sujet instanceof RetourEntretien,
            $sujet instanceof Mission => $sujet->entreprise,
            default => null,
        };

        return $entreprise
            ? ['libelle' => $entreprise->nom, 'url' => route('entreprises.show', $entreprise)]
            : ['libelle' => null, 'url' => null];
    }
}
