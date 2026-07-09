<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatutModeration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\StoreMissionRequest;
use App\Http\Requests\Mission\UpdateMissionRequest;
use App\Http\Resources\MissionResource;
use App\Models\Mission;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Missions', weight: 4)]
class MissionController extends Controller
{
    /**
     * Lister les missions
     *
     * Missions publiées, filtrables par `entreprise_id`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $missions = Mission::query()
            ->publie()
            ->when($request->query('entreprise_id'), fn ($q, $id) => $q->where('entreprise_id', $id))
            ->with(['user', 'entreprise'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return MissionResource::collection($missions);
    }

    /**
     * Déclarer une mission
     *
     * Enregistre une mission (interim/freelance/régie) pour l'utilisateur
     * authentifié (en modération).
     */
    public function store(StoreMissionRequest $request): JsonResponse
    {
        $mission = Mission::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut_moderation' => StatutModeration::EnAttente,
        ]);

        return (new MissionResource($mission->load('entreprise')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Modifier sa mission
     */
    public function update(UpdateMissionRequest $request, Mission $mission): MissionResource
    {
        abort_unless($mission->user_id === $request->user()->id, 403);

        $mission->update($request->validated());

        return new MissionResource($mission);
    }

    /**
     * Supprimer sa mission
     */
    public function destroy(Request $request, Mission $mission): JsonResponse
    {
        abort_unless($mission->user_id === $request->user()->id, 403);

        $mission->delete();

        return response()->json(status: 204);
    }
}
