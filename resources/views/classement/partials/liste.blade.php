@php $minAvis = (int) config('classement.min_avis_classement'); @endphp
<div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
    <h2 class="text-2xl font-extrabold tracking-tight text-encre">{{ $titre ?? 'Les mieux notées' }}</h2>
    <p class="text-sm text-encre-doux">{{ $intro ?? 'Basé sur les avis publiés et vérifiés.' }}</p>
</div>

<div class="mt-3">
    @forelse ($entreprises as $i => $entreprise)
        @php
            $assezDAvis = $entreprise->nb_avis_total >= $minAvis && $entreprise->score_bayesien !== null;
            $rang = $rangDepart + $i + 1;
            $score = $assezDAvis ? (float) $entreprise->score_bayesien : null;
            $tonAppreciation = match (true) {
                $score === null => 'text-encre-doux font-medium',
                $score >= 3.5 => 'text-vert',
                $score >= 2.8 => 'text-ambre',
                default => 'text-rouge',
            };
        @endphp
        <a href="{{ route('entreprises.show', $entreprise) }}"
           class="group flex items-center gap-3 border-t border-ligne py-3.5 first:border-t-0 sm:gap-4">
            <span class="w-6 shrink-0 text-center text-base font-extrabold tabular-nums {{ $assezDAvis ? 'text-encre-doux' : 'text-encre-doux/40' }}">
                {{ $assezDAvis ? $rang : '–' }}
            </span>
            <x-monogramme :nom="$entreprise->nom" :taille="44" />
            <span class="min-w-0 flex-1">
                <span class="block truncate text-base font-semibold text-encre group-hover:underline group-hover:underline-offset-4">{{ $entreprise->nom }}</span>
                <span class="mt-0.5 block truncate text-[13px] text-encre-doux">
                    {{ $entreprise->secteur_activite->libelle() }}@if ($entreprise->commune), {{ $entreprise->commune }}@endif<span class="sm:hidden">, {{ $entreprise->nb_avis_total }} avis</span>
                </span>
            </span>
            <span class="hidden w-16 shrink-0 text-right text-[13px] tabular-nums text-encre-doux sm:block">{{ $entreprise->nb_avis_total }} avis</span>
            <span class="hidden w-28 shrink-0 text-sm font-semibold sm:block {{ $tonAppreciation }}">
                {{ $assezDAvis ? $entreprise->appreciation() : 'Pas encore notée' }}
            </span>
            <x-anneau :note="$score" :taille="46" />
        </a>
    @empty
        <p class="py-10 text-center text-encre-doux">Aucune entreprise ne correspond à cette recherche. Essayez un autre nom ou un autre secteur.</p>
    @endforelse
</div>

@if ($entreprises->hasPages())
    <div class="mt-4 border-t border-ligne pt-4">
        {{ $entreprises->links() }}
    </div>
@endif
