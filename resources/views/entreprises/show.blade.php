@php
    // Description de partage (OpenGraph) spécifique à l’entreprise.
    $metaDescription = $entreprise->score_bayesien !== null
        ? $entreprise->nom.' : note de '.number_format((float) $entreprise->score_bayesien, 1, ',', ' ').'/5'
            .($rang ? ' (#'.$rang.' au classement)' : '')
            .' d’après les avis d’anciens salariés en Côte d’Ivoire. Secteur : '.$entreprise->secteur_activite->libelle().'.'
        : 'Découvrez les avis sur '.$entreprise->nom.' ('.$entreprise->secteur_activite->libelle().') en Côte d’Ivoire et partagez votre propre expérience.';

    // Données structurées : entreprise + note agrégée (extraits enrichis) + fil d'Ariane.
    $schemaOrg = array_filter([
        '@context' => 'https://schema.org',
        '@type' => $entreprise->commune ? 'LocalBusiness' : 'Organization',
        'name' => $entreprise->nom,
        'url' => route('entreprises.show', $entreprise),
        'sameAs' => $entreprise->site_web ? [$entreprise->site_web] : null,
        'address' => $entreprise->commune ? [
            '@type' => 'PostalAddress',
            'addressLocality' => $entreprise->commune,
            'addressCountry' => 'CI',
        ] : null,
        'aggregateRating' => ($entreprise->score_bayesien !== null && $entreprise->nb_avis_total > 0) ? [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float) $entreprise->score_bayesien, 1, '.', ''),
            'reviewCount' => $entreprise->nb_avis_total,
            'bestRating' => 5,
            'worstRating' => 1,
        ] : null,
    ]);

    $schemaBreadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Classement', 'item' => route('classement.index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $entreprise->nom, 'item' => route('entreprises.show', $entreprise)],
        ],
    ];
@endphp
<x-layout :title="$entreprise->nom.' — avis & note des salariés · Côte d’Ivoire'" :description="$metaDescription" og-type="article" :open-modal="session('avis_en_attente') ? 'compte-avis' : old('_form')">
    <x-schema :data="$schemaOrg" />
    <x-schema :data="$schemaBreadcrumb" />
    @php
        $score = $entreprise->score_bayesien !== null ? (float) $entreprise->score_bayesien : null;
        $classesTon = match (true) {
            $score === null => 'bg-slate-100 text-slate-600',
            $score >= 4 => 'bg-emerald-50 text-emerald-700',
            $score >= 3 => 'bg-amber-50 text-amber-700',
            default => 'bg-rose-50 text-rose-700',
        };
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-8">
        <a href="{{ route('classement.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
            ← Retour au classement
        </a>

        {{-- En-tête --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-900">{{ $entreprise->nom }}</h1>
                        @if ($rang)
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">#{{ $rang }} au classement</span>
                        @endif
                        @if ($entreprise->statut === \App\Enums\StatutEntreprise::AVerifier)
                            <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700" title="En attente de vérification par un modérateur">⏳ En vérification</span>
                        @else
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700" title="Entreprise vérifiée">✓ Vérifiée</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $entreprise->secteur_activite->libelle() }}</span>
                        @if ($entreprise->commune)<span>📍 {{ $entreprise->commune }}</span>@endif
                        @if ($entreprise->taille_estimee)<span>👥 {{ $entreprise->taille_estimee }}</span>@endif
                        @if ($entreprise->site_web)
                            <a href="{{ $entreprise->site_web }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Site web ↗</a>
                        @endif
                    </div>
                </div>

                <div class="shrink-0 text-center">
                    <div class="rounded-xl px-4 py-2 {{ $classesTon }}">
                        <div class="text-3xl font-bold tabular-nums">{{ $score !== null ? number_format($score, 2) : '—' }}</div>
                        <div class="text-xs opacity-70">score /5</div>
                    </div>
                </div>
            </div>

            {{-- Partage : inviter des tiers à donner leur avis --}}
            <x-partage class="mt-5 border-t border-slate-100 pt-4"
                       :url="route('entreprises.show', $entreprise)"
                       :texte="'Donnez votre avis sur '.$entreprise->nom.' 👉'" />

            {{-- Notes détaillées --}}
            @if ($entreprise->nb_avis_total > 0)
                <div class="mt-6 grid gap-6 border-t border-slate-100 pt-6 sm:grid-cols-2">
                    <div class="flex flex-col items-start justify-center gap-2">
                        <x-note-etoiles :note="$entreprise->note_globale" class="text-xl" />
                        <p class="text-sm text-slate-500">
                            Note moyenne <span class="font-semibold text-slate-800">{{ number_format((float) $entreprise->note_globale, 2) }}/5</span>
                            sur <span class="font-semibold text-slate-800">{{ $entreprise->nb_avis_total }}</span> avis
                        </p>
                    </div>
                    <div class="space-y-3">
                        <x-jauge label="Ambiance" :valeur="$entreprise->moy_ambiance !== null ? (float) $entreprise->moy_ambiance : null" />
                        <x-jauge label="Management" :valeur="$entreprise->moy_management !== null ? (float) $entreprise->moy_management : null" />
                        <x-jauge label="Salaire" :valeur="$entreprise->moy_salaire !== null ? (float) $entreprise->moy_salaire : null" />
                        <x-jauge label="Évolution" :valeur="$entreprise->moy_evolution !== null ? (float) $entreprise->moy_evolution : null" />
                    </div>
                </div>
            @else
                <p class="mt-6 border-t border-slate-100 pt-6 text-sm text-slate-500">Pas encore assez d’avis pour établir un score.</p>
            @endif
        </div>

        {{-- Réponse de l'entreprise (droit de réponse) --}}
        @if ($entreprise->reponse_entreprise)
            <section class="mt-6 rounded-2xl border border-sky-200 bg-sky-50 p-5">
                <h2 class="text-sm font-semibold text-sky-900">Réponse de l’entreprise</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-sky-900">{{ $entreprise->reponse_entreprise }}</p>
                @if ($entreprise->reponse_entreprise_le)
                    <p class="mt-2 text-xs text-sky-700">Publiée {{ $entreprise->reponse_entreprise_le->diffForHumans() }}</p>
                @endif
            </section>
        @endif

        @can('moderer')
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-semibold text-slate-900">Droit de réponse <span class="text-xs font-normal text-slate-400">(admin)</span></h2>
                <form method="POST" action="{{ route('entreprises.reponse', $entreprise) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PUT')
                    <textarea name="reponse_entreprise" rows="3" placeholder="Réponse officielle de l’entreprise (laisser vide pour supprimer)…"
                              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">{{ old('reponse_entreprise', $entreprise->reponse_entreprise) }}</textarea>
                    @error('reponse_entreprise')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Enregistrer la réponse</button>
                </form>
            </section>
        @endcan

        {{-- Contribuer --}}
        <section class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
            <h2 class="text-sm font-semibold text-slate-900">Vous avez une expérience avec {{ $entreprise->nom }} ?</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                {{-- Avis : ouvrable même en invité (le compte est créé à l'envoi) --}}
                <a href="{{ route('contrib.avis.create', $entreprise) }}" data-modal-open="avis"
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Donner mon avis</a>
                @auth
                    <a href="{{ route('contrib.entretien.create', $entreprise) }}" data-modal-open="entretien"
                       class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Retour d’entretien</a>
                    <a href="{{ route('contrib.mission.create', $entreprise) }}" data-modal-open="mission"
                       class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Déclarer une mission</a>
                @endauth
            </div>
            @guest
                <p class="mt-2 text-xs text-slate-500">Pas besoin de compte pour commencer — il sera créé au moment d'envoyer votre avis.</p>
            @endguest
        </section>

        {{-- Avis --}}
        <section class="mt-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Avis des employés
                <span class="text-sm font-normal text-slate-400">({{ $entreprise->avis->count() }})</span>
            </h2>
            <div class="space-y-3">
                @forelse ($entreprise->avis as $avis)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-medium text-slate-800">{{ $avis->user->pseudo_public ?? 'Anonyme' }}</span>
                                @if ($avis->user?->linkedin_verifie)
                                    <span class="rounded bg-sky-50 px-1.5 py-0.5 text-xs font-medium text-sky-700" title="LinkedIn vérifié — avis au poids le plus fort">✓ LinkedIn</span>
                                @elseif ($avis->user?->email_verified_at)
                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700" title="Email vérifié — avis à poids renforcé">✓ Email vérifié</span>
                                @endif
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ $avis->statut_emploi->libelle() }}</span>
                            </div>
                            <x-note-etoiles :note="$avis->noteMoyenne()" />
                        </div>
                        @if ($avis->commentaire)
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $avis->commentaire }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-slate-400">{{ $avis->created_at->diffForHumans() }}</p>
                            <x-signaler type="avis" :model="$avis" />
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Aucun avis publié pour le moment.</p>
                @endforelse
            </div>
        </section>

        {{-- Retours d'entretien --}}
        <section class="mt-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Retours d’entretien
                <span class="text-sm font-normal text-slate-400">({{ $entreprise->retoursEntretiens->count() }})</span>
            </h2>
            <div class="space-y-3">
                @forelse ($entreprise->retoursEntretiens as $retour)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-slate-800">{{ $retour->poste_vise }}</span>
                            <span class="text-xs text-slate-400">{{ $retour->date_entretien_mois->translatedFormat('F Y') }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            @if ($retour->nb_etapes)<span class="rounded bg-slate-100 px-2 py-0.5 text-slate-600">{{ $retour->nb_etapes }} étape(s)</span>@endif
                            @if ($retour->duree_processus_jours)<span class="rounded bg-slate-100 px-2 py-0.5 text-slate-600">Process : {{ $retour->duree_processus_jours }} j</span>@endif
                            @if ($retour->a_recu_reponse)
                                <span class="rounded bg-emerald-50 px-2 py-0.5 text-emerald-700">Réponse reçue{{ $retour->delai_reponse_jours ? ' ('.$retour->delai_reponse_jours.' j)' : '' }}</span>
                            @else
                                <span class="rounded bg-rose-50 px-2 py-0.5 text-rose-700">Sans réponse</span>
                            @endif
                            @if ($retour->a_eu_offre)<span class="rounded bg-indigo-50 px-2 py-0.5 text-indigo-700">Offre reçue</span>@endif
                        </div>
                        @if (!empty($retour->questions_posees))
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($retour->questions_posees as $tag)
                                    <span class="rounded-full border border-slate-200 px-2 py-0.5 text-xs text-slate-500">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($retour->ressenti_general)
                            <p class="mt-2 text-sm text-slate-600">{{ $retour->ressenti_general }}</p>
                        @endif
                        <div class="mt-2 flex justify-end">
                            <x-signaler type="entretien" :model="$retour" />
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Aucun retour d’entretien publié.</p>
                @endforelse
            </div>
        </section>

        {{-- Missions --}}
        <section class="mt-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Missions
                <span class="text-sm font-normal text-slate-400">({{ $entreprise->missions->count() }})</span>
            </h2>
            <div class="space-y-3">
                @forelse ($entreprise->missions as $mission)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-slate-800">{{ $mission->type_mission->libelle() }}</span>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @if ($mission->duree_mois)<span class="rounded bg-slate-100 px-2 py-0.5 text-slate-600">{{ $mission->duree_mois }} mois</span>@endif
                                @if ($mission->fourchette_remuneration)<span class="rounded bg-slate-100 px-2 py-0.5 text-slate-600">{{ $mission->fourchette_remuneration }}</span>@endif
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            @if ($mission->paiement_a_temps !== null)
                                <span class="rounded px-2 py-0.5 {{ $mission->paiement_a_temps ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    Paiement {{ $mission->paiement_a_temps ? 'à temps' : 'en retard' }}
                                </span>
                            @endif
                            @if ($mission->respect_contrat !== null)
                                <span class="rounded px-2 py-0.5 {{ $mission->respect_contrat ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    Contrat {{ $mission->respect_contrat ? 'respecté' : 'non respecté' }}
                                </span>
                            @endif
                        </div>
                        @if ($mission->commentaire)
                            <p class="mt-2 text-sm text-slate-600">{{ $mission->commentaire }}</p>
                        @endif
                        <div class="mt-2 flex justify-end">
                            <x-signaler type="mission" :model="$mission" />
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Aucune mission publiée.</p>
                @endforelse
            </div>
        </section>

        {{-- Maillage interne : même secteur --}}
        @if ($similaires->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-3 text-lg font-bold text-slate-900">Autres entreprises — {{ $entreprise->secteur_activite->libelle() }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($similaires as $similaire)
                        <a href="{{ route('entreprises.show', $similaire) }}"
                           class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:shadow-sm">
                            <span class="truncate font-semibold text-slate-900">{{ $similaire->nom }}</span>
                            @if ($similaire->score_bayesien !== null)
                                <span class="shrink-0 text-sm font-medium text-slate-500 tabular-nums">{{ number_format((float) $similaire->score_bayesien, 1, ',', ' ') }}/5</span>
                            @else
                                <span class="shrink-0 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">Nouveau</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Avis : disponible aussi en invité (flux « avis d'abord ») --}}
    <x-modal id="avis" title="Donner mon avis">
        @include('contributions.partials.avis')
    </x-modal>

    @auth
        <x-modal id="entretien" title="Partager un retour d’entretien">
            @include('contributions.partials.entretien')
        </x-modal>
        <x-modal id="mission" title="Déclarer une mission">
            @include('contributions.partials.mission')
        </x-modal>
    @endauth

    @guest
        {{-- Dernière étape du flux « avis d'abord » : création de compte inline --}}
        <x-modal id="compte-avis" title="Dernière étape : créez votre compte">
            <p class="mb-4 text-sm text-slate-600">
                Votre avis est prêt ✅ Créez votre compte pour le publier — <strong>anonyme, 30 secondes.</strong>
            </p>
            <form method="POST" action="{{ route('register') }}" class="space-y-3">
                @csrf
                <div>
                    <input name="email" type="email" required autocomplete="email" placeholder="Votre email"
                           value="{{ old('email') }}"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-password-input name="password" :required="true" autocomplete="new-password" placeholder="Choisissez un mot de passe" />
                    @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Publier mon avis
                </button>
            </form>
            <p class="mt-3 text-center text-xs text-slate-400">
                Déjà un compte ? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Se connecter</a>
            </p>
        </x-modal>
    @endguest
</x-layout>
