<?php

namespace App\Http\Controllers;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassementController extends Controller
{
    /** Page d'accueil : 3 vues (à éviter / classement / nouvelles entreprises). */
    public function index(Request $request): View
    {
        $vue = in_array($request->query('vue'), ['classement', 'nouvelles'], true)
            ? $request->query('vue')
            : 'a_eviter';

        $secteur = $request->query('secteur');
        $terme = $request->query('q');

        $filtres = fn ($q) => $q
            ->when($secteur, fn ($x, $s) => $x->where('secteur_activite', $s))
            ->when($terme, fn ($x, $t) => $x->where('nom', 'like', '%'.$t.'%'));

        if ($vue === 'a_eviter') {
            // Liste éditoriale « à éviter ».
            $donnees = ['entreprises' => $filtres(Entreprise::aEviter())->get()];
            $partial = 'classement.partials.a_eviter';
        } else {
            // Entreprises vérifiées uniquement, hors liste « à éviter ».
            $base = Entreprise::query()
                ->where('statut', StatutEntreprise::Verifiee)
                ->whereNull('rang_a_eviter');

            if ($vue === 'nouvelles') {
                $base->where('nb_avis_total', 0);
                $titre = 'Nouvelles entreprises';
                $intro = 'Proposées et vérifiées récemment — en attente de premiers avis.';
            } else {
                $titre = 'Classement communautaire';
                $intro = 'Entreprises vérifiées, classées par les avis. Les nouvelles apparaissent en bas.';
            }

            $paginator = $filtres($base)
                ->orderByRaw('score_bayesien IS NULL')  // les notées d'abord, les nouvelles en bas
                ->orderByDesc('score_bayesien')
                ->orderByDesc('nb_avis_total')
                ->paginate(12)
                ->withQueryString();

            $donnees = [
                'entreprises' => $paginator,
                'rangDepart' => ($paginator->currentPage() - 1) * $paginator->perPage(),
                'titre' => $titre,
                'intro' => $intro,
            ];
            $partial = 'classement.partials.liste';
        }

        // Requête AJAX (changement de vue / filtre / pagination) : liste seule.
        if ($request->ajax()) {
            return view($partial, $donnees);
        }

        return view('classement.index', array_merge($donnees, [
            'partial' => $partial,
            'vue' => $vue,
            'secteurs' => SecteurActivite::cases(),
            'secteurActif' => $secteur,
            'recherche' => (string) $terme,
            'nbSuivies' => Entreprise::count(),
            'nbAvis' => AvisEntreprise::publie()->count(),
        ]));
    }

    /** Fiche détaillée d'une entreprise et ses retours. */
    public function show(Entreprise $entreprise): View
    {
        $entreprise->load([
            'avis' => fn ($q) => $q->publie()->with('user')->latest(),
            'retoursEntretiens' => fn ($q) => $q->publie()->with('user')->latest(),
            'missions' => fn ($q) => $q->publie()->with('user')->latest(),
        ]);

        // Rang dans le classement (seulement si l'entreprise est classée).
        $rang = $entreprise->score_bayesien !== null
            ? Entreprise::classable()->where('score_bayesien', '>', $entreprise->score_bayesien)->count() + 1
            : null;

        return view('entreprises.show', [
            'entreprise' => $entreprise,
            'rang' => $rang,
        ]);
    }
}
