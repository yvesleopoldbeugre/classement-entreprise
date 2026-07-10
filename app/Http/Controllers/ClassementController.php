<?php

namespace App\Http\Controllers;

use App\Enums\SecteurActivite;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassementController extends Controller
{
    /** Page d'accueil : liste « à éviter » (par défaut) ou classement communautaire. */
    public function index(Request $request): View
    {
        // Toggle « 10 pires » : coché par défaut (au premier chargement, sans paramètre).
        $pires = $request->has('pires') ? $request->boolean('pires') : true;

        $secteur = $request->query('secteur');
        $terme = $request->query('q');

        if ($pires) {
            // Liste éditoriale « à éviter ».
            $entreprises = Entreprise::aEviter()
                ->when($secteur, fn ($q, $s) => $q->where('secteur_activite', $s))
                ->when($terme, fn ($q, $t) => $q->where('nom', 'like', '%'.$t.'%'))
                ->get();

            $donnees = ['entreprises' => $entreprises];
            $partial = 'classement.partials.a_eviter';
        } else {
            // Classement communautaire (par score, entreprises « classables »).
            $paginator = Entreprise::query()
                ->when($secteur, fn ($q, $s) => $q->where('secteur_activite', $s))
                ->when($terme, fn ($q, $t) => $q->where('nom', 'like', '%'.$t.'%'))
                ->classable()
                ->parClassement()
                ->paginate(12)
                ->withQueryString();

            $donnees = [
                'entreprises' => $paginator,
                'rangDepart' => ($paginator->currentPage() - 1) * $paginator->perPage(),
            ];
            $partial = 'classement.partials.liste';
        }

        // Requête AJAX (toggle / filtre / pagination) : on ne renvoie que la liste.
        if ($request->ajax()) {
            return view($partial, $donnees);
        }

        return view('classement.index', array_merge($donnees, [
            'partial' => $partial,
            'pires' => $pires,
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
