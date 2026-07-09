<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entreprise\StoreEntrepriseRequest;
use App\Http\Requests\Entreprise\UpdateEntrepriseRequest;
use App\Http\Resources\EntrepriseResource;
use App\Models\Entreprise;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Entreprises', weight: 1)]
class EntrepriseController extends Controller
{
    /**
     * Lister le classement des entreprises
     *
     * Retourne les entreprises triées par score bayésien décroissant (paginé).
     * Filtres disponibles : `secteur`, `q` (recherche sur le nom),
     * `classees=1` (uniquement celles ayant assez d'avis pour être classées).
     */
    #[Endpoint(operationId: 'listerEntreprises')]
    public function index(Request $request): AnonymousResourceCollection
    {
        $entreprises = Entreprise::query()
            ->when($request->query('secteur'), fn ($q, $secteur) => $q->where('secteur_activite', $secteur))
            ->when($request->query('q'), fn ($q, $terme) => $q->where('nom', 'like', '%'.$terme.'%'))
            ->when($request->boolean('classees'), fn ($q) => $q->classable())
            ->parClassement()
            ->paginate(15)
            ->withQueryString();

        return EntrepriseResource::collection($entreprises);
    }

    /**
     * Afficher une entreprise
     *
     * Détail d'une entreprise avec ses avis, retours d'entretien et missions
     * publiés (modération « publie » uniquement).
     */
    public function show(Entreprise $entreprise): EntrepriseResource
    {
        $entreprise->load([
            'avis' => fn ($q) => $q->publie()->with('user')->latest(),
            'retoursEntretiens' => fn ($q) => $q->publie()->latest(),
            'missions' => fn ($q) => $q->publie()->latest(),
        ]);

        return new EntrepriseResource($entreprise);
    }

    /**
     * Créer une entreprise
     */
    public function store(StoreEntrepriseRequest $request): JsonResponse
    {
        $entreprise = Entreprise::create($request->validated());

        return (new EntrepriseResource($entreprise))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mettre à jour une entreprise
     */
    public function update(UpdateEntrepriseRequest $request, Entreprise $entreprise): EntrepriseResource
    {
        $entreprise->update($request->validated());

        return new EntrepriseResource($entreprise);
    }

    /**
     * Supprimer une entreprise
     */
    public function destroy(Entreprise $entreprise): JsonResponse
    {
        $entreprise->delete();

        return response()->json(status: 204);
    }
}
