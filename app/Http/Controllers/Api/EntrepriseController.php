<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entreprise\StoreEntrepriseRequest;
use App\Http\Requests\Entreprise\UpdateEntrepriseRequest;
use App\Http\Resources\EntrepriseResource;
use App\Models\Entreprise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntrepriseController extends Controller
{
    /**
     * Classement public des entreprises (score bayésien décroissant).
     * Filtres : ?secteur=, ?q= (recherche nom), ?classees=1 (assez d'avis).
     */
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

    public function show(Entreprise $entreprise): EntrepriseResource
    {
        // On ne charge que les retours modérés « publie ».
        $entreprise->load([
            'avis' => fn ($q) => $q->publie()->with('user')->latest(),
            'retoursEntretiens' => fn ($q) => $q->publie()->latest(),
            'missions' => fn ($q) => $q->publie()->latest(),
        ]);

        return new EntrepriseResource($entreprise);
    }

    public function store(StoreEntrepriseRequest $request): JsonResponse
    {
        $entreprise = Entreprise::create($request->validated());

        return (new EntrepriseResource($entreprise))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEntrepriseRequest $request, Entreprise $entreprise): EntrepriseResource
    {
        $entreprise->update($request->validated());

        return new EntrepriseResource($entreprise);
    }

    public function destroy(Entreprise $entreprise): JsonResponse
    {
        $entreprise->delete();

        return response()->json(status: 204);
    }
}
