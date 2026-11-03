@php
    // Titres/descriptions ciblés (mots-clés + localisation) selon la vue affichée.
    $pageTitre = match ($vue) {
        'classement' => 'Classement des entreprises par les salariés · Côte d’Ivoire',
        'nouvelles' => 'Nouvelles entreprises ajoutées · Côte d’Ivoire',
        default => 'Entreprises à mieux connaître avant de postuler · Côte d’Ivoire',
    };
    $pageDescription = 'Avis vérifiés de salariés, stagiaires et candidats sur les entreprises de Côte d’Ivoire. Des notes fiables (score bayésien) pour choisir où postuler en confiance.';
@endphp
<x-layout :title="$pageTitre" :description="$pageDescription" :open-modal="old('_form')" :robots="$recherche !== '' ? 'noindex, follow' : 'index, follow'">
    {{-- Données structurées : site + moteur de recherche interne (sitelinks searchbox) --}}
    <x-schema :data="[
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => config('app.name'),
        'url' => url('/'),
        'inLanguage' => 'fr',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/').'?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('og-image.png'),
        ],
    ]" />

    {{-- En-tête du bulletin : le classement lui-même ouvre la page --}}
    <section class="mx-auto max-w-6xl px-4 pt-10 sm:pt-14">
        <h1 class="max-w-4xl text-4xl font-extrabold leading-[0.95] tracking-tight text-encre font-stretch-condensed sm:text-6xl">
            Le bulletin des entreprises de Côte d’Ivoire
        </h1>
        <p class="mt-4 max-w-2xl text-base text-encre-doux sm:text-lg">
            Noté par celles et ceux qui y ont travaillé : salariés, stagiaires, freelances et candidats.
            {{ $nbSuivies }} entreprises suivies, {{ $nbAvis }} avis publiés.
        </p>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Filtres : recherche, secteur, et la vue affichée (onglets) --}}
        <form method="GET" action="{{ route('classement.index') }}" id="filtre-form" class="mb-8 space-y-4">
            <div class="flex gap-2">
                <label class="sr-only" for="recherche">Rechercher une entreprise</label>
                <input id="recherche" type="search" name="q" value="{{ $recherche }}" placeholder="Rechercher une entreprise"
                       class="min-w-0 flex-1 rounded-md border-2 border-encre bg-white px-4 py-3 text-base text-encre placeholder:text-encre-doux/70 outline-none focus:ring-4 focus:ring-orange-ci/30">
                <button type="submit" aria-label="Rechercher"
                        class="shrink-0 rounded-md bg-encre px-4 text-papier hover:bg-indigo-800 sm:px-6">
                    <svg class="h-5 w-5 sm:hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                    </svg>
                    <span class="hidden font-semibold sm:inline">Rechercher</span>
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <fieldset class="flex gap-1 border-b border-regle">
                    <legend class="sr-only">Vue</legend>
                    <label class="-mb-px cursor-pointer border-b-2 border-transparent px-3 py-2 text-sm font-semibold text-encre-doux hover:text-encre has-checked:border-encre has-checked:text-encre has-focus-visible:outline-2 has-focus-visible:outline-orange-ci">
                        <input type="radio" name="vue" value="classement" class="sr-only" @checked($vue === 'classement')>Classement
                    </label>
                    <label class="-mb-px cursor-pointer border-b-2 border-transparent px-3 py-2 text-sm font-semibold text-encre-doux hover:text-encre has-checked:border-encre has-checked:text-encre has-focus-visible:outline-2 has-focus-visible:outline-orange-ci">
                        <input type="radio" name="vue" value="a_eviter" class="sr-only" @checked($vue === 'a_eviter')>À mieux connaître
                    </label>
                    <label class="-mb-px cursor-pointer border-b-2 border-transparent px-3 py-2 text-sm font-semibold text-encre-doux hover:text-encre has-checked:border-encre has-checked:text-encre has-focus-visible:outline-2 has-focus-visible:outline-orange-ci">
                        <input type="radio" name="vue" value="nouvelles" class="sr-only" @checked($vue === 'nouvelles')>Nouvelles
                    </label>
                </fieldset>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <label class="sr-only" for="secteur">Secteur</label>
                    <select id="secteur" name="secteur"
                            class="rounded-md border border-regle bg-white px-3 py-2 text-sm text-encre outline-none focus:border-encre">
                        <option value="">Tous les secteurs</option>
                        @foreach ($secteurs as $secteur)
                            <option value="{{ $secteur->value }}" @selected($secteurActif === $secteur->value)>
                                {{ $secteur->libelle() }}
                            </option>
                        @endforeach
                    </select>
                    @if ($recherche !== '' || $secteurActif)
                        <a href="{{ route('classement.index') }}" class="text-encre-doux underline underline-offset-4 hover:text-encre">Effacer les filtres</a>
                    @endif
                    @auth
                        <a href="{{ route('entreprises.create') }}" data-modal-open="proposer"
                           class="font-semibold text-encre underline decoration-regle decoration-2 underline-offset-4 hover:decoration-encre">Proposer une entreprise</a>
                    @else
                        <a href="{{ route('login') }}"
                           class="font-semibold text-encre underline decoration-regle decoration-2 underline-offset-4 hover:decoration-encre">Proposer une entreprise</a>
                    @endauth
                </div>
            </div>
        </form>

        {{-- Liste (rechargée en AJAX par le filtre / le toggle) --}}
        <div class="relative min-h-40">
            <div id="liste-loader"
                 class="absolute inset-0 z-10 hidden items-start justify-center bg-papier/70 pt-16">
                <span class="inline-flex items-center gap-2 rounded-md border border-regle bg-white px-4 py-2 text-sm font-medium text-encre">
                    <svg class="h-4 w-4 animate-spin text-encre" viewBox="0 0 24 24" fill="none">
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

        @include('classement.partials.explorer')
    </div>

    @auth
        <x-modal id="proposer" title="Ajouter une entreprise">
            @include('entreprises.partials.proposer')
        </x-modal>
    @endauth
</x-layout>
