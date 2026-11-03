@php
    // Titres/descriptions ciblés (mots-clés + localisation) selon la vue affichée.
    $pageTitre = match ($vue) {
        'classement' => 'Classement des entreprises par les salariés · Côte d’Ivoire',
        'nouvelles' => 'Nouvelles entreprises ajoutées · Côte d’Ivoire',
        default => 'Entreprises à mieux connaître avant de postuler · Côte d’Ivoire',
    };
    $pageDescription = 'Avis vérifiés de salariés, stagiaires et candidats sur les entreprises de Côte d’Ivoire. Des notes fiables (score bayésien) pour choisir où postuler en confiance.';
@endphp
<x-layout :title="$pageTitre" :description="$pageDescription" :open-modal="old('_form')" :robots="$recherche !== '' ? 'noindex, follow' : 'index, follow'" sombre>
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

    {{-- Bandeau : la recherche d'abord --}}
    <section class="bg-encre">
        <div class="mx-auto max-w-6xl px-4 pb-14 pt-8 sm:pb-20 sm:pt-14">
            <h1 class="max-w-3xl text-[38px] font-extrabold leading-[1.02] tracking-tight text-white sm:text-6xl">
                Avant de postuler, vérifiez la boîte.
            </h1>
            <p class="mt-4 max-w-xl text-base text-white/75 sm:text-lg">
                {{ $nbAvis }} avis de salariés, stagiaires et freelances sur {{ $nbSuivies }} entreprises de Côte d’Ivoire.
            </p>

            <form method="GET" action="{{ route('classement.index') }}" id="filtre-form"
                  class="mt-7 flex max-w-2xl items-center gap-2 rounded-2xl bg-white p-1.5 pl-4">
                <svg class="h-5 w-5 shrink-0 text-encre-doux" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
                <label class="sr-only" for="recherche">Rechercher une entreprise</label>
                <input id="recherche" type="search" name="q" value="{{ $recherche }}" placeholder="Nom d’une entreprise"
                       class="min-w-0 flex-1 bg-transparent py-2.5 text-base text-encre placeholder:text-encre-doux outline-none">
                @if ($secteurActif)<input type="hidden" name="secteur" value="{{ $secteurActif }}">@endif
                @if ($vue !== 'classement')<input type="hidden" name="vue" value="{{ $vue }}">@endif
                <button type="submit" class="shrink-0 rounded-xl bg-orange px-4 py-2.5 text-[15px] font-semibold text-encre hover:brightness-105 sm:px-5">
                    Rechercher
                </button>
            </form>

            <ul class="mt-5 flex flex-wrap gap-2 text-[13px]">
                @foreach ($secteurs as $secteur)
                    @continue($secteur === \App\Enums\SecteurActivite::Autre)
                    <li><a href="{{ route('classement.secteur', $secteur->slug()) }}"
                           class="inline-block rounded-full bg-white/10 px-3 py-1.5 font-medium text-white hover:bg-white/20">{{ $secteur->libelle() }}</a></li>
                @endforeach
            </ul>
        </div>
    </section>

    <div class="bg-surface">
        <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:py-12">
            <div class="grid items-start gap-6 {{ $vue === 'classement' ? 'lg:grid-cols-[1fr_360px]' : '' }}">
                {{-- Liste (rechargée en AJAX par la recherche / la pagination) --}}
                <section class="relative min-w-0 rounded-2xl border border-ligne bg-white p-5 sm:p-6">
                    <div id="liste-loader"
                         class="absolute inset-0 z-10 hidden items-start justify-center rounded-2xl bg-white/70 pt-16">
                        <span class="inline-flex items-center gap-2 rounded-full border border-ligne bg-white px-4 py-2 text-sm font-medium text-encre">
                            <svg class="h-4 w-4 animate-spin text-orange" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                            </svg>
                            Chargement…
                        </span>
                    </div>
                    @if ($recherche !== '' || $secteurActif || $vue !== 'classement')
                        <a href="{{ route('classement.index') }}" class="mb-3 inline-block text-sm font-semibold text-teal hover:underline">Tout le classement</a>
                    @endif
                    <div id="liste-classement">
                        @include($partial)
                    </div>
                </section>

                @if ($vue === 'classement')
                    <aside class="min-w-0 space-y-5">
                        <section class="rounded-2xl bg-rouge-doux p-5">
                            <h2 class="text-lg font-extrabold text-encre">À mieux connaître</h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-encre-doux">Sélection de la communauté. Ce sont des opinions, pas des faits établis : chaque entreprise peut répondre.</p>
                            <ul class="mt-3">
                                @forelse ($apercuAEviter as $entreprise)
                                    <li class="border-t border-rouge/20">
                                        <a href="{{ route('entreprises.show', $entreprise) }}" class="flex items-center justify-between gap-3 py-2.5 hover:underline">
                                            <span class="truncate text-[15px] font-semibold text-encre">{{ $entreprise->nom }}</span>
                                            <span class="shrink-0 text-[13px] text-rouge">{{ $entreprise->nb_avis_total }} avis</span>
                                        </a>
                                    </li>
                                @empty
                                    <li class="border-t border-rouge/20 py-2.5 text-[13px] text-encre-doux">Aucune entreprise signalée pour le moment.</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('classement.index', ['vue' => 'a_eviter']) }}" class="mt-1 inline-block text-sm font-semibold text-encre underline underline-offset-4">Voir la sélection</a>
                        </section>

                        <section class="rounded-2xl border border-ligne bg-white p-5">
                            <h2 class="text-lg font-extrabold text-encre">Nouvelles, sans avis</h2>
                            <ul class="mt-2">
                                @forelse ($apercuNouvelles as $entreprise)
                                    <li>
                                        <a href="{{ route('entreprises.show', $entreprise) }}" class="flex items-center gap-3 py-2">
                                            <x-monogramme :nom="$entreprise->nom" :taille="36" />
                                            <span class="min-w-0 flex-1 truncate text-[15px] font-semibold text-encre">{{ $entreprise->nom }}</span>
                                            <span class="shrink-0 text-sm font-semibold text-teal">Noter</span>
                                        </a>
                                    </li>
                                @empty
                                    <li class="py-2 text-[13px] text-encre-doux">Toutes les entreprises ont déjà des avis.</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('classement.index', ['vue' => 'nouvelles']) }}" class="mt-1 inline-block text-sm font-semibold text-encre underline underline-offset-4">Toutes les nouvelles</a>
                        </section>
                    </aside>
                @endif
            </div>

            <section class="flex flex-col gap-5 rounded-2xl bg-orange-doux p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-encre">Vous avez travaillé quelque part ?</h2>
                    <p class="mt-1 max-w-xl text-[15px] text-encre-doux">Notez l’entreprise en 30 secondes. C’est anonyme, et ça aide le prochain candidat.</p>
                </div>
                <a href="#recherche" class="shrink-0 rounded-xl bg-orange px-5 py-3 text-center text-[15px] font-semibold text-encre hover:brightness-105">Chercher mon entreprise</a>
            </section>

            @include('classement.partials.explorer')

            @auth
                <p class="text-sm text-encre-doux">
                    Une entreprise manque ?
                    <a href="{{ route('entreprises.create') }}" data-modal-open="proposer" class="font-semibold text-teal hover:underline">Proposer une entreprise</a>
                </p>
            @endauth
        </div>
    </div>

    @auth
        <x-modal id="proposer" title="Ajouter une entreprise">
            @include('entreprises.partials.proposer')
        </x-modal>
    @endauth
</x-layout>
