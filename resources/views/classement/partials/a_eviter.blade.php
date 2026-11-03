<section>
    <div class="mb-3">
        <h2 class="text-2xl font-extrabold tracking-tight text-encre font-stretch-condensed">Les 10 à mieux connaître avant de s’y aventurer</h2>
        <p class="text-sm text-encre-doux">Sélection de la communauté.</p>
    </div>

    {{-- Avertissement (protection juridique + transparence) : note en marge, au stylo rouge --}}
    <p class="mb-4 max-w-3xl border-l-2 border-stylo-rouge pl-3 text-sm text-encre-doux">
        Sélection <strong class="text-encre">subjective</strong> reflétant les retours et opinions de membres de la communauté.
        Il ne s’agit pas de faits établis. Une entreprise concernée peut demander un droit de réponse ou une rectification.
    </p>

    <ol class="border-y-2 border-encre">
        @forelse ($entreprises as $entreprise)
            <li class="border-b border-regle last:border-b-0">
                <a href="{{ route('entreprises.show', $entreprise) }}"
                   class="group grid grid-cols-[3rem_1fr_auto] items-center gap-3 py-4 hover:bg-white sm:grid-cols-[4rem_1fr_auto] sm:gap-4">
                    <span class="text-xl font-extrabold tabular-nums text-stylo-rouge font-stretch-condensed">{{ $entreprise->rang_a_eviter }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-base font-semibold text-encre group-hover:underline group-hover:decoration-2 group-hover:underline-offset-4">{{ $entreprise->nom }}</span>
                        <span class="mt-0.5 block truncate text-sm text-encre-doux">
                            {{ $entreprise->secteur_activite->libelle() }},
                            {{ $entreprise->nb_avis_total > 0 ? $entreprise->nb_avis_total.' avis' : 'aucun avis publié' }}
                        </span>
                    </span>
                    <span class="text-sm font-semibold text-encre underline decoration-regle decoration-2 underline-offset-4 group-hover:decoration-encre">Témoigner</span>
                </a>
            </li>
        @empty
            <li class="py-12 text-center text-sm text-encre-doux">
                Aucune entreprise de cette sélection ne correspond à ces critères.
            </li>
        @endforelse
    </ol>
</section>
