<div class="mb-4">
    <h2 class="text-xl font-bold text-slate-900">Classement communautaire</h2>
    <p class="text-sm text-slate-500">Basé sur les avis publiés et vérifiés. Se construit au fil des témoignages.</p>
</div>

@forelse ($entreprises as $i => $entreprise)
    @php
        $rang = $rangDepart + $i + 1;
        $score = (float) $entreprise->score_bayesien;
        $classesTon = match (true) {
            $score >= 4 => 'bg-emerald-50 text-emerald-700',
            $score >= 3 => 'bg-amber-50 text-amber-700',
            default => 'bg-rose-50 text-rose-700',
        };
    @endphp
    <a href="{{ route('entreprises.show', $entreprise) }}"
       class="group mb-3 flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:shadow-sm">
        <div class="w-10 shrink-0 text-center">
            @if ($rang <= 3)
                <span class="text-2xl">{{ ['🥇','🥈','🥉'][$rang - 1] }}</span>
            @else
                <span class="text-lg font-bold text-slate-400 tabular-nums">{{ $rang }}</span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h2 class="truncate font-semibold text-slate-900 group-hover:text-indigo-700">{{ $entreprise->nom }}</h2>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $entreprise->secteur_activite->libelle() }}</span>
                @if ($entreprise->commune)
                    <span>📍 {{ $entreprise->commune }}</span>
                @endif
                <span>{{ $entreprise->nb_avis_total }} avis</span>
            </div>
        </div>

        <div class="hidden shrink-0 sm:block">
            <x-note-etoiles :note="$entreprise->note_globale" />
        </div>

        <div class="shrink-0 text-right">
            <div class="inline-flex items-baseline gap-1 rounded-lg px-2.5 py-1 {{ $classesTon }}">
                <span class="text-lg font-bold tabular-nums">{{ number_format($score, 2) }}</span>
                <span class="text-xs opacity-70">/5</span>
            </div>
        </div>
    </a>
@empty
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
        <p class="text-slate-500">Aucune entreprise classée ne correspond à ces critères.</p>
        <a href="{{ route('classement.index') }}" class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:underline">Voir tout le classement</a>
    </div>
@endforelse

<div class="mt-6">
    {{ $entreprises->links() }}
</div>
