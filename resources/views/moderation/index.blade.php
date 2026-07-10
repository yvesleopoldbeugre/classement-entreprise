<x-layout title="Modération · ClassementCI">
    @php
        $total = $avis->count() + $entretiens->count() + $missions->count();
        $badge = fn ($statut) => $statut->value === 'signale'
            ? 'bg-rose-50 text-rose-700'
            : 'bg-amber-50 text-amber-700';
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Modération</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $total }} contribution(s) en attente de traitement.</p>
        </div>

        @php
            $actions = fn ($type, $id) => view('moderation.actions', ['type' => $type, 'id' => $id])->render();
        @endphp

        {{-- Avis --}}
        <section class="mb-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Avis <span class="text-sm font-normal text-slate-400">({{ $avis->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($avis as $item)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm">
                                <a href="{{ route('entreprises.show', $item->entreprise) }}" class="font-semibold text-slate-900 hover:text-indigo-600">{{ $item->entreprise->nom }}</a>
                                <span class="text-slate-400">· {{ $item->user->pseudo_public ?? 'Anonyme' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($item->signalements_count > 0)
                                    <span class="rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700" title="signalements">⚑ {{ $item->signalements_count }}</span>
                                @endif
                                <span class="rounded px-2 py-0.5 text-xs font-medium {{ $badge($item->statut_moderation) }}">{{ $item->statut_moderation->value }}</span>
                            </div>
                        </div>
                        @php $motifs = $item->signalements->pluck('motif')->filter()->unique(); @endphp
                        @if ($motifs->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($motifs as $motif)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs text-rose-700">⚑ {{ $motif }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-2 text-xs text-slate-500">
                            Ambiance {{ $item->note_ambiance }} · Management {{ $item->note_management }} · Salaire {{ $item->note_salaire }} · Évolution {{ $item->note_evolution }}
                        </div>
                        @if ($item->commentaire)<p class="mt-2 text-sm text-slate-600">{{ $item->commentaire }}</p>@endif
                        {!! $actions('avis', $item->id) !!}
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Aucun avis à modérer.</p>
                @endforelse
            </div>
        </section>

        {{-- Retours d'entretien --}}
        <section class="mb-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Retours d’entretien <span class="text-sm font-normal text-slate-400">({{ $entretiens->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($entretiens as $item)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm">
                                <a href="{{ route('entreprises.show', $item->entreprise) }}" class="font-semibold text-slate-900 hover:text-indigo-600">{{ $item->entreprise->nom }}</a>
                                <span class="text-slate-400">· {{ $item->poste_vise }} · {{ $item->user->pseudo_public ?? 'Anonyme' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($item->signalements_count > 0)
                                    <span class="rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700" title="signalements">⚑ {{ $item->signalements_count }}</span>
                                @endif
                                <span class="rounded px-2 py-0.5 text-xs font-medium {{ $badge($item->statut_moderation) }}">{{ $item->statut_moderation->value }}</span>
                            </div>
                        </div>
                        @php $motifs = $item->signalements->pluck('motif')->filter()->unique(); @endphp
                        @if ($motifs->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($motifs as $motif)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs text-rose-700">⚑ {{ $motif }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($item->ressenti_general)<p class="mt-2 text-sm text-slate-600">{{ $item->ressenti_general }}</p>@endif
                        {!! $actions('entretien', $item->id) !!}
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Aucun retour d’entretien à modérer.</p>
                @endforelse
            </div>
        </section>

        {{-- Missions --}}
        <section class="mb-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Missions <span class="text-sm font-normal text-slate-400">({{ $missions->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($missions as $item)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm">
                                <a href="{{ route('entreprises.show', $item->entreprise) }}" class="font-semibold text-slate-900 hover:text-indigo-600">{{ $item->entreprise->nom }}</a>
                                <span class="text-slate-400">· {{ $item->type_mission->libelle() }} · {{ $item->user->pseudo_public ?? 'Anonyme' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($item->signalements_count > 0)
                                    <span class="rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700" title="signalements">⚑ {{ $item->signalements_count }}</span>
                                @endif
                                <span class="rounded px-2 py-0.5 text-xs font-medium {{ $badge($item->statut_moderation) }}">{{ $item->statut_moderation->value }}</span>
                            </div>
                        </div>
                        @php $motifs = $item->signalements->pluck('motif')->filter()->unique(); @endphp
                        @if ($motifs->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($motifs as $motif)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs text-rose-700">⚑ {{ $motif }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($item->commentaire)<p class="mt-2 text-sm text-slate-600">{{ $item->commentaire }}</p>@endif
                        {!! $actions('mission', $item->id) !!}
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Aucune mission à modérer.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layout>
