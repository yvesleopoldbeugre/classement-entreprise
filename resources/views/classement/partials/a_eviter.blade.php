<section>
    <div class="mb-4">
        <h2 class="text-xl font-bold text-slate-900">
            Les 10 à mieux connaître avant de s’y aventurer
            <span class="text-sm font-normal text-slate-400">— sélection de la communauté</span>
        </h2>
    </div>

    {{-- Avertissement (protection juridique + transparence) --}}
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Sélection <strong>subjective</strong> reflétant les retours et opinions de membres de la communauté.
        Il ne s’agit pas de faits établis. Une entreprise concernée peut demander un droit de réponse ou une rectification.
    </div>

    <ol class="space-y-3">
        @forelse ($entreprises as $entreprise)
            <li>
                <a href="{{ route('entreprises.show', $entreprise) }}"
                   class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-100 text-sm font-bold text-amber-700 tabular-nums">
                        {{ $entreprise->rang_a_eviter }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-slate-900 group-hover:text-amber-700">{{ $entreprise->nom }}</h3>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $entreprise->secteur_activite->libelle() }}</span>
                            @if ($entreprise->nb_avis_total > 0)
                                <span>{{ $entreprise->nb_avis_total }} avis</span>
                            @else
                                <span class="text-slate-400">Aucun avis publié — soyez le premier à témoigner</span>
                            @endif
                        </div>
                    </div>
                    <span class="hidden shrink-0 text-sm font-medium text-amber-600 group-hover:underline sm:block">Voir &amp; témoigner →</span>
                </a>
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                Aucune entreprise de cette sélection ne correspond à ces critères.
            </li>
        @endforelse
    </ol>
</section>
