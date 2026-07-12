<x-layout :open-modal="old('_form')">
    {{-- Hero --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <p class="mb-2 inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                Côte d’Ivoire · Tech &amp; entreprises
            </p>
            <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                Le classement des entreprises par ceux qui y ont travaillé.
            </h1>
            <p class="mt-3 max-w-2xl text-slate-600">
                Salariés, stagiaires, freelances et candidats partagent leur expérience.
                Un score fiable pour choisir en confiance où postuler ou travailler.
            </p>

            <div class="mt-6 flex flex-wrap gap-6">
                <div>
                    <div class="text-2xl font-bold text-slate-900 tabular-nums">{{ $nbSuivies }}</div>
                    <div class="text-sm text-slate-500">entreprises suivies</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900 tabular-nums">{{ $nbAvis }}</div>
                    <div class="text-sm text-slate-500">avis publiés</div>
                </div>
            </div>

            <div class="mt-6">
                @auth
                    <a href="{{ route('entreprises.create') }}" data-modal-open="proposer"
                       class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Proposer une entreprise</a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Proposer une entreprise</a>
                @endauth
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Filtres (en haut) : recherche, secteur, et la vue affichée --}}
        <form method="GET" action="{{ route('classement.index') }}" id="filtre-form"
              class="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <input type="search" name="q" value="{{ $recherche }}" placeholder="Rechercher une entreprise…"
                       class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
            </div>
            <select name="secteur"
                    class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                <option value="">Tous les secteurs</option>
                @foreach ($secteurs as $secteur)
                    <option value="{{ $secteur->value }}" @selected($secteurActif === $secteur->value)>
                        {{ $secteur->libelle() }}
                    </option>
                @endforeach
            </select>

            {{-- Vue : à mieux connaître (défaut) · classement · nouvelles entreprises (sans avis) --}}
            <select name="vue"
                    class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                <option value="a_eviter" @selected($vue === 'a_eviter')>Les 10 à mieux connaître</option>
                <option value="classement" @selected($vue === 'classement')>Classement</option>
                <option value="nouvelles" @selected($vue === 'nouvelles')>Nouvelles entreprises</option>
            </select>

            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Filtrer
            </button>
            @if ($recherche !== '' || $secteurActif)
                <a href="{{ route('classement.index') }}"
                   class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800">Réinitialiser</a>
            @endif
        </form>

        {{-- Liste (rechargée en AJAX par le filtre / le toggle) --}}
        <div class="relative min-h-40">
            <div id="liste-loader"
                 class="absolute inset-0 z-10 hidden items-start justify-center rounded-xl bg-slate-50/70 pt-16">
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm">
                    <svg class="h-4 w-4 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    Chargement…
                </span>
            </div>
            <div id="liste-classement">
                @include($partial)
            </div>
        </div>
    </div>

    @auth
        <x-modal id="proposer" title="Ajouter une entreprise">
            @include('entreprises.partials.proposer')
        </x-modal>
    @endauth
</x-layout>
