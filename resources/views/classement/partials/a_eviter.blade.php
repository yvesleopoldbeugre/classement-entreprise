<div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
    <h2 class="text-2xl font-extrabold tracking-tight text-encre">À mieux connaître avant de s’y aventurer</h2>
    <p class="text-sm text-encre-doux">Sélection de la communauté.</p>
</div>

{{-- Avertissement (protection juridique + transparence) --}}
<p class="mt-3 rounded-xl bg-rouge-doux px-4 py-3 text-sm text-encre-doux">
    Sélection <strong class="text-encre">subjective</strong> reflétant les retours et opinions de membres de la communauté.
    Il ne s’agit pas de faits établis. Une entreprise concernée peut demander un droit de réponse ou une rectification.
</p>

<ol class="mt-2">
    @forelse ($entreprises as $entreprise)
        <li class="border-t border-ligne first:border-t-0">
            <a href="{{ route('entreprises.show', $entreprise) }}" class="group flex items-center gap-3 py-3.5 sm:gap-4">
                <span class="w-6 shrink-0 text-center text-base font-extrabold tabular-nums text-rouge">{{ $entreprise->rang_a_eviter }}</span>
                <x-monogramme :nom="$entreprise->nom" :taille="44" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-base font-semibold text-encre group-hover:underline group-hover:underline-offset-4">{{ $entreprise->nom }}</span>
                    <span class="mt-0.5 block truncate text-[13px] text-encre-doux">
                        {{ $entreprise->secteur_activite->libelle() }},
                        {{ $entreprise->nb_avis_total > 0 ? $entreprise->nb_avis_total.' avis' : 'aucun avis publié' }}
                    </span>
                </span>
                <span class="shrink-0 rounded-full bg-surface px-3 py-1.5 text-sm font-semibold text-encre group-hover:bg-encre group-hover:text-white">Témoigner</span>
            </a>
        </li>
    @empty
        <li class="py-10 text-center text-sm text-encre-doux">
            Aucune entreprise de cette sélection ne correspond à ces critères.
        </li>
    @endforelse
</ol>
