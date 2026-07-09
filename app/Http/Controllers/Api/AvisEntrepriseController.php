<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatutModeration;
use App\Http\Controllers\Controller;
use App\Http\Requests\AvisEntreprise\StoreAvisEntrepriseRequest;
use App\Http\Requests\AvisEntreprise\UpdateAvisEntrepriseRequest;
use App\Http\Resources\AvisEntrepriseResource;
use App\Models\AvisEntreprise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvisEntrepriseController extends Controller
{
    /** Avis publiés, filtrables par ?entreprise_id=. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $avis = AvisEntreprise::query()
            ->publie()
            ->when($request->query('entreprise_id'), fn ($q, $id) => $q->where('entreprise_id', $id))
            ->with(['user', 'entreprise'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return AvisEntrepriseResource::collection($avis);
    }

    public function store(StoreAvisEntrepriseRequest $request): JsonResponse
    {
        // user_id vient de l'auth ; l'avis part en modération.
        $avis = AvisEntreprise::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut_moderation' => StatutModeration::EnAttente,
        ]);

        return (new AvisEntrepriseResource($avis->load('entreprise')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAvisEntrepriseRequest $request, AvisEntreprise $avi): AvisEntrepriseResource
    {
        abort_unless($avi->user_id === $request->user()->id, 403);

        $avi->update($request->validated());

        return new AvisEntrepriseResource($avi);
    }

    public function destroy(Request $request, AvisEntreprise $avi): JsonResponse
    {
        abort_unless($avi->user_id === $request->user()->id, 403);

        $avi->delete();

        return response()->json(status: 204);
    }
}
