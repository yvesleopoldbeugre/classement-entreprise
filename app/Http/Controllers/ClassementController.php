<?php

namespace App\Http\Controllers;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClassementController extends Controller
{
    /** Page d'accueil : 3 vues (à éviter / classement / nouvelles entreprises). */
    public function index(Request $request): View
    {
        // Vue par défaut = le classement ; « à éviter » / « nouvelles » en option.
        $vue = in_array($request->query('vue'), ['a_eviter', 'nouvelles'], true)
            ? $request->query('vue')
            : 'classement';

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
                $titre = 'Les mieux notées';
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
            'communes' => self::communes(),
            'recherche' => (string) $terme,
            // Colonne latérale de l'accueil (vue classement) : aperçu des deux autres listes.
            'apercuAEviter' => $vue === 'classement' ? Entreprise::aEviter()->limit(3)->get() : collect(),
            'apercuNouvelles' => $vue === 'classement'
                ? Entreprise::where('statut', StatutEntreprise::Verifiee)->whereNull('rang_a_eviter')
                    ->where('nb_avis_total', 0)->latest()->limit(3)->get()
                : collect(),
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

        // Rang dans le classement : uniquement si l'entreprise est réellement classable
        // (assez d'avis), sinon on n'affiche pas de rang trompeur.
        $estClassable = $entreprise->score_bayesien !== null
            && $entreprise->nb_avis_total >= (int) config('classement.min_avis_classement');
        $rang = $estClassable
            ? Entreprise::classable()->where('score_bayesien', '>', $entreprise->score_bayesien)->count() + 1
            : null;

        // Maillage interne : quelques entreprises publiques du même secteur.
        $similaires = Entreprise::query()
            ->where('secteur_activite', $entreprise->secteur_activite)
            ->whereKeyNot($entreprise->getKey())
            ->publique()
            ->orderByDesc('score_bayesien')
            ->limit(4)
            ->get(['nom', 'slug', 'secteur_activite', 'score_bayesien']);

        return view('entreprises.show', [
            'entreprise' => $entreprise,
            'rang' => $rang,
            'similaires' => $similaires,
            'totalClassees' => Entreprise::classable()->count(),
        ]);
    }

    /** Page d'atterrissage SEO : classement d'un secteur (« /secteur/ssii »). */
    public function secteur(string $slug): View
    {
        $secteur = SecteurActivite::depuisSlug($slug) ?? abort(404);

        return $this->categorie(
            fn ($q) => $q->where('secteur_activite', $secteur),
            titre: 'Meilleures entreprises '.$secteur->libelle().' en Côte d’Ivoire',
            intro: 'Classement des entreprises du secteur '.$secteur->libelle().' selon les avis de leurs salariés, stagiaires et prestataires.',
            ariane: $secteur->libelle(),
        );
    }

    /** Page d'atterrissage SEO : classement d'une commune (« /commune/cocody »). */
    public function commune(string $slug): View
    {
        $commune = self::communes()->first(fn (string $c) => Str::slug($c) === $slug) ?? abort(404);

        return $this->categorie(
            fn ($q) => $q->where('commune', $commune),
            titre: 'Entreprises où travailler à '.$commune,
            intro: 'Les entreprises de '.$commune.' classées par les avis de celles et ceux qui y ont travaillé.',
            ariane: $commune,
        );
    }

    /** Communes ayant au moins une entreprise publique (libellés distincts). */
    public static function communes(): Collection
    {
        return Entreprise::publique()
            ->whereNotNull('commune')
            ->where('commune', '!=', '')
            ->distinct()
            ->orderBy('commune')
            ->pluck('commune')
            ->unique(fn (string $c) => Str::slug($c))
            ->values();
    }

    private function categorie(\Closure $filtre, string $titre, string $intro, string $ariane): View
    {
        $paginator = $filtre(Entreprise::query()
            ->where('statut', StatutEntreprise::Verifiee)
            ->whereNull('rang_a_eviter'))
            ->orderByRaw('score_bayesien IS NULL')
            ->orderByDesc('score_bayesien')
            ->orderByDesc('nb_avis_total')
            ->paginate(12);

        return view('classement.categorie', [
            'entreprises' => $paginator,
            'rangDepart' => ($paginator->currentPage() - 1) * $paginator->perPage(),
            'titre' => $titre,
            'intro' => $intro,
            'ariane' => $ariane,
            'secteurs' => SecteurActivite::cases(),
            'communes' => self::communes(),
        ]);
    }
}
