@php $minAvis = (int) config('classement.min_avis_classement'); @endphp
<div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
    <h2 class="text-2xl font-extrabold tracking-tight text-encre font-stretch-condensed">{{ $titre ?? 'Classement communautaire' }}</h2>
    <p class="text-sm text-encre-doux">{{ $intro ?? 'Basé sur les avis publiés et vérifiés.' }}</p>
</div>

{{-- Bulletin : une ligne par entreprise, colonnes Rang / Entreprise / Avis / Moyenne / Appréciation --}}
<div class="border-y-2 border-encre">
    <div class="hidden grid-cols-[4rem_1fr_6rem_7rem_9rem] gap-4 border-b border-regle py-2 text-xs font-semibold text-encre-doux sm:grid">
        <span>Rang</span><span>Entreprise</span><span class="text-right">Avis</span><span class="text-center">Moyenne /5</span><span>Appréciation</span>
    </div>

    @forelse ($entreprises as $i => $entreprise)
        @php
            $assezDAvis = $entreprise->nb_avis_total >= $minAvis && $entreprise->score_bayesien !== null;
            $rang = $rangDepart + $i + 1;
        @endphp
        <a href="{{ route('entreprises.show', $entreprise) }}"
           class="group grid grid-cols-[3rem_1fr_auto] items-center gap-3 border-b border-regle py-4 last:border-b-0 hover:bg-white sm:grid-cols-[4rem_1fr_6rem_7rem_9rem] sm:gap-4">
            <span class="text-xl font-extrabold tabular-nums font-stretch-condensed {{ $assezDAvis ? 'text-encre' : 'text-encre-doux/40' }}">
                {{ $assezDAvis ? \App\Models\Entreprise::rangOrdinal($rang) : '–' }}
            </span>

            <span class="min-w-0">
                <span class="block truncate text-base font-semibold text-encre group-hover:underline group-hover:decoration-2 group-hover:underline-offset-4">{{ $entreprise->nom }}</span>
                <span class="mt-0.5 block truncate text-sm text-encre-doux">
                    {{ $entreprise->secteur_activite->libelle() }}@if ($entreprise->commune), {{ $entreprise->commune }}@endif
                </span>
            </span>

            <span class="hidden text-right text-sm tabular-nums text-encre-doux sm:block">
                {{ $entreprise->nb_avis_total }} avis
            </span>

            <span class="text-center">
                @if ($assezDAvis)
                    <x-moyenne :note="$entreprise->score_bayesien" />
                @else
                    <span class="text-xs text-encre-doux sm:hidden">{{ $entreprise->nb_avis_total }}/{{ $minAvis }} avis</span>
                    <span class="hidden text-2xl font-extrabold text-regle font-stretch-condensed sm:inline">–</span>
                @endif
            </span>

            <span class="hidden text-sm sm:block">
                @if ($assezDAvis)
                    <span class="font-semibold text-encre">{{ $entreprise->appreciation() }}</span>
                @else
                    <span class="text-encre-doux">Pas encore notée, {{ $entreprise->nb_avis_total }}/{{ $minAvis }} avis</span>
                @endif
            </span>
        </a>
    @empty
        <p class="py-12 text-center text-encre-doux">Aucune entreprise ne correspond à cette recherche. Essayez un autre nom ou un autre secteur.</p>
    @endforelse
</div>

<div class="mt-6">
    {{ $entreprises->links() }}
</div>
