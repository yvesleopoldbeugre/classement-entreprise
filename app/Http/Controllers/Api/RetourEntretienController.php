<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatutModeration;
use App\Http\Controllers\Controller;
use App\Http\Requests\RetourEntretien\StoreRetourEntretienRequest;
use App\Http\Requests\RetourEntretien\UpdateRetourEntretienRequest;
use App\Http\Resources\RetourEntretienResource;
use App\Models\RetourEntretien;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Retours d\'entretien', weight: 3)]
class RetourEntretienController extends Controller
{
    /**
     * Lister les retours d'entretien
     *
     * Retours publiés, filtrables par `entreprise_id`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $retours = RetourEntretien::query()
            ->publie()
            ->when($request->query('entreprise_id'), fn ($q, $id) => $q->where('entreprise_id', $id))
            ->with(['user', 'entreprise'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return RetourEntretienResource::collection($retours);
    }

    /**
     * Partager un retour d'entretien
     *
     * Enregistre un retour pour l'utilisateur authentifié (en modération).
     */
    public function store(StoreRetourEntretienRequest $request): JsonResponse
    {
        $retour = RetourEntretien::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut_moderation' => StatutModeration::EnAttente,
        ]);

        return (new RetourEntretienResource($retour->load('entreprise')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Modifier son retour d'entretien
     */
    public function update(UpdateRetourEntretienRequest $request, RetourEntretien $retoursEntretien): RetourEntretienResource
    {
        abort_unless($retoursEntretien->user_id === $request->user()->id, 403);

        $retoursEntretien->update($request->validated());

        return new RetourEntretienResource($retoursEntretien);
    }

    /**
     * Supprimer son retour d'entretien
     */
    public function destroy(Request $request, RetourEntretien $retoursEntretien): JsonResponse
    {
        abort_unless($retoursEntretien->user_id === $request->user()->id, 403);

        $retoursEntretien->delete();

        return response()->json(status: 204);
    }
}
