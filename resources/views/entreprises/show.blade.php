@php
    // Description de partage (OpenGraph) spécifique à l’entreprise.
    $metaDescription = $entreprise->score_bayesien !== null
        ? $entreprise->nom.' : note de '.number_format((float) $entreprise->score_bayesien, 1, ',', ' ').'/5'
            .($rang ? ' (#'.$rang.' au classement)' : '')
            .' d’après les avis d’anciens salariés en Côte d’Ivoire. Secteur : '.$entreprise->secteur_activite->libelle().'.'
        : 'Découvrez les avis sur '.$entreprise->nom.' ('.$entreprise->secteur_activite->libelle().') en Côte d’Ivoire et partagez votre propre expérience.';

    // Fiche proposée mais pas encore vérifiée : consultable, mais hors index (comme le sitemap).
    $estPublique = $entreprise->rang_a_eviter !== null || $entreprise->statut === \App\Enums\StatutEntreprise::Verifiee;

    // Données structurées : entreprise + note agrégée (extraits enrichis) + fil d'Ariane.
    $schemaOrg = array_filter([
        '@context' => 'https://schema.org',
        '@type' => $entreprise->commune ? 'LocalBusiness' : 'Organization',
        'name' => $entreprise->nom,
        'description' => $entreprise->description,
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
            ['@type' => 'ListItem', 'position' => 2, 'name' => $entreprise->secteur_activite->libelle(), 'item' => route('classement.secteur', $entreprise->secteur_activite->slug())],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $entreprise->nom, 'item' => route('entreprises.show', $entreprise)],
        ],
    ];
@endphp
<x-layout :title="$entreprise->nom.' — avis & note des salariés · Côte d’Ivoire'" :description="$metaDescription" og-type="article" :robots="$estPublique ? 'index, follow' : 'noindex, follow'" :open-modal="session('avis_en_attente') ? 'compte-avis' : old('_form')">
    <x-schema :data="$schemaOrg" />
    <x-schema :data="$schemaBreadcrumb" />
    @php
        $score = $entreprise->score_bayesien !== null ? (float) $entreprise->score_bayesien : null;
        $minAvis = (int) config('classement.min_avis_classement');
        $assezDAvis = $entreprise->nb_avis_total >= $minAvis;
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-8">
        <a href="{{ route('classement.secteur', $entreprise->secteur_activite->slug()) }}"
           class="text-sm font-medium text-encre-doux underline underline-offset-4 hover:text-encre">Classement {{ $entreprise->secteur_activite->libelle() }}</a>

        {{-- En-tête du bulletin individuel --}}
        <header class="mt-4 grid gap-6 border-b-2 border-encre pb-6 sm:grid-cols-[1fr_auto] sm:items-end">
            <div class="min-w-0">
                <h1 class="text-4xl font-extrabold leading-none tracking-tight text-encre font-stretch-condensed sm:text-5xl">{{ $entreprise->nom }}</h1>
                <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-encre-doux">
                    <a href="{{ route('classement.secteur', $entreprise->secteur_activite->slug()) }}" class="hover:text-encre hover:underline">{{ $entreprise->secteur_activite->libelle() }}</a>
                    @if ($entreprise->commune)
                        <a href="{{ route('classement.commune', \Illuminate\Support\Str::slug($entreprise->commune)) }}" class="hover:text-encre hover:underline">{{ $entreprise->commune }}</a>
                    @endif
                    @if ($entreprise->taille_estimee)<span>{{ $entreprise->taille_estimee }} personnes</span>@endif
                    @if ($entreprise->site_web)
                        <a href="{{ $entreprise->site_web }}" target="_blank" rel="noopener" class="text-encre underline underline-offset-4">Site web</a>
                    @endif
                    @if ($entreprise->statut === \App\Enums\StatutEntreprise::AVerifier)
                        <span class="font-medium text-orange-ci" title="En attente de vérification par un modérateur">En cours de vérification</span>
                    @else
                        <span class="font-medium text-stylo-vert" title="Existence de l’entreprise vérifiée par un modérateur">Entreprise vérifiée</span>
                    @endif
                </p>
                @if ($entreprise->description)
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-encre-doux">{{ $entreprise->description }}</p>
                @endif
            </div>

            {{-- Moyenne générale : l'élément marquant de la page --}}
            <div class="flex items-center gap-5 sm:flex-col sm:items-end sm:gap-1 sm:text-right">
                @if ($assezDAvis && $score !== null)
                    <x-moyenne :note="$score" taille="lg" tracer />
                    <div>
                        <p class="text-lg font-bold text-encre">{{ $entreprise->appreciation() }}</p>
                        <p class="text-sm text-encre-doux">
                            @if ($rang){{ \App\Models\Entreprise::rangOrdinal($rang) }} au classement, @endif{{ $entreprise->nb_avis_total }} avis
                        </p>
                    </div>
                @else
                    <x-moyenne :note="null" taille="lg" />
                    <div>
                        <p class="text-lg font-bold text-encre">Pas encore notée</p>
                        <p class="text-sm text-encre-doux">{{ $entreprise->nb_avis_total }} avis sur les {{ $minAvis }} nécessaires</p>
                    </div>
                @endif
            </div>
        </header>

        {{-- Notes par critère, comme les matières d'un bulletin --}}
        @if ($entreprise->nb_avis_total > 0)
            <section class="mt-12">
                <h2 class="text-xl font-extrabold text-encre font-stretch-condensed">Notes par critère</h2>
                <dl class="mt-3 border-t border-regle">
                    @php $v = $entreprise->moy_ambiance !== null ? (float) $entreprise->moy_ambiance : null; @endphp
                    <div class="grid grid-cols-[7rem_1fr_3rem] items-center gap-4 border-b border-regle py-3">
                        <dt class="text-sm font-semibold text-encre">Ambiance</dt>
                        <dd class="h-1.5 bg-regle" aria-hidden="true"><div class="h-full bg-encre" style="width: {{ $v !== null ? $v / 5 * 100 : 0 }}%"></div></dd>
                        <dd class="text-right text-lg font-extrabold tabular-nums text-encre font-stretch-condensed">{{ $v !== null ? number_format($v, 1, ',', ' ') : '–' }}</dd>
                    </div>
                    @php $v = $entreprise->moy_management !== null ? (float) $entreprise->moy_management : null; @endphp
                    <div class="grid grid-cols-[7rem_1fr_3rem] items-center gap-4 border-b border-regle py-3">
                        <dt class="text-sm font-semibold text-encre">Management</dt>
                        <dd class="h-1.5 bg-regle" aria-hidden="true"><div class="h-full bg-encre" style="width: {{ $v !== null ? $v / 5 * 100 : 0 }}%"></div></dd>
                        <dd class="text-right text-lg font-extrabold tabular-nums text-encre font-stretch-condensed">{{ $v !== null ? number_format($v, 1, ',', ' ') : '–' }}</dd>
                    </div>
                    @php $v = $entreprise->moy_salaire !== null ? (float) $entreprise->moy_salaire : null; @endphp
                    <div class="grid grid-cols-[7rem_1fr_3rem] items-center gap-4 border-b border-regle py-3">
                        <dt class="text-sm font-semibold text-encre">Salaire</dt>
                        <dd class="h-1.5 bg-regle" aria-hidden="true"><div class="h-full bg-encre" style="width: {{ $v !== null ? $v / 5 * 100 : 0 }}%"></div></dd>
                        <dd class="text-right text-lg font-extrabold tabular-nums text-encre font-stretch-condensed">{{ $v !== null ? number_format($v, 1, ',', ' ') : '–' }}</dd>
                    </div>
                    @php $v = $entreprise->moy_evolution !== null ? (float) $entreprise->moy_evolution : null; @endphp
                    <div class="grid grid-cols-[7rem_1fr_3rem] items-center gap-4 border-b border-regle py-3">
                        <dt class="text-sm font-semibold text-encre">Évolution</dt>
                        <dd class="h-1.5 bg-regle" aria-hidden="true"><div class="h-full bg-encre" style="width: {{ $v !== null ? $v / 5 * 100 : 0 }}%"></div></dd>
                        <dd class="text-right text-lg font-extrabold tabular-nums text-encre font-stretch-condensed">{{ $v !== null ? number_format($v, 1, ',', ' ') : '–' }}</dd>
                    </div>
                </dl>
                <p class="mt-2 text-xs text-encre-doux">
                    Moyenne brute des avis : {{ number_format((float) $entreprise->note_globale, 2, ',', ' ') }} sur 5.
                    @unless ($assezDAvis)Note provisoire tant qu’il y a moins de {{ $minAvis }} avis.@endunless
                </p>
            </section>
        @endif

        {{-- Contribuer : la seule action orange de la page --}}
        <section class="mt-8 grid gap-4 rounded-md bg-encre p-6 text-papier sm:grid-cols-[1fr_auto] sm:items-center">
            <div>
                <h2 class="text-xl font-extrabold font-stretch-condensed">Vous avez travaillé chez {{ $entreprise->nom }} ?</h2>
                <p class="mt-1 text-sm text-papier/75">
                    Votre avis reste anonyme.
                    @guest Pas besoin de compte pour commencer : il sera créé au moment d’envoyer votre avis. @endguest
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                {{-- Avis : ouvrable même en invité (le compte est créé à l'envoi) --}}
                <a href="{{ route('contrib.avis.create', $entreprise) }}" data-modal-open="avis"
                   class="rounded-md bg-orange-ci px-5 py-3 text-base font-bold text-encre hover:brightness-105">Donner mon avis</a>
                @auth
                    <a href="{{ route('contrib.entretien.create', $entreprise) }}" data-modal-open="entretien"
                       class="rounded-md border border-papier/40 px-4 py-3 text-sm font-semibold text-papier hover:bg-papier/10">Retour d’entretien</a>
                    <a href="{{ route('contrib.mission.create', $entreprise) }}" data-modal-open="mission"
                       class="rounded-md border border-papier/40 px-4 py-3 text-sm font-semibold text-papier hover:bg-papier/10">Déclarer une mission</a>
                @endauth
            </div>
        </section>

        {{-- Partage : inviter d'anciens collègues à donner leur avis --}}
        <x-partage class="mt-4"
                   :url="route('entreprises.show', $entreprise)"
                   :texte="'Donnez votre avis sur '.$entreprise->nom.' 👉'" />

        {{-- Réponse de l'entreprise (droit de réponse) --}}
        @if ($entreprise->reponse_entreprise)
            <section class="mt-8 border-l-2 border-encre pl-4">
                <h2 class="text-sm font-semibold text-encre">Réponse de l’entreprise</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-encre">{{ $entreprise->reponse_entreprise }}</p>
                @if ($entreprise->reponse_entreprise_le)
                    <p class="mt-2 text-xs text-encre-doux">Publiée {{ $entreprise->reponse_entreprise_le->diffForHumans() }}</p>
                @endif
            </section>
        @endif

        @can('moderer')
            <section class="mt-8 rounded-md border border-regle bg-white p-5">
                <h2 class="text-sm font-semibold text-encre">Droit de réponse <span class="text-xs font-normal text-encre-doux">(admin)</span></h2>
                <form method="POST" action="{{ route('entreprises.reponse', $entreprise) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PUT')
                    <textarea name="reponse_entreprise" rows="3" placeholder="Réponse officielle de l’entreprise (laisser vide pour supprimer)…"
                              class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">{{ old('reponse_entreprise', $entreprise->reponse_entreprise) }}</textarea>
                    @error('reponse_entreprise')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Enregistrer la réponse</button>
                </form>
            </section>
        @endcan

        {{-- Avis --}}
        <section class="mt-12">
            <h2 class="mb-3 text-xl font-extrabold text-encre font-stretch-condensed">Avis des employés
                <span class="text-base font-normal text-encre-doux">({{ $entreprise->avis->count() }})</span>
            </h2>
            <div class="border-t border-regle">
                @forelse ($entreprise->avis as $avis)
                    <article class="border-b border-regle py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-medium text-encre">{{ $avis->user->pseudo_public ?? 'Anonyme' }}</span>
                                @if ($avis->user?->linkedin_verifie)
                                    <span class="rounded bg-sky-50 px-1.5 py-0.5 text-xs font-medium text-encre-doux" title="LinkedIn vérifié — avis au poids le plus fort">✓ LinkedIn</span>
                                @elseif ($avis->user?->email_verified_at)
                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700" title="Email vérifié — avis à poids renforcé">✓ Email vérifié</span>
                                @endif
                                <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-encre-doux">{{ $avis->statut_emploi->libelle() }}</span>
                            </div>
                            <x-note-etoiles :note="$avis->noteMoyenne()" />
                        </div>
                        @if ($avis->commentaire)
                            <p class="mt-2 text-sm leading-relaxed text-encre-doux">{{ $avis->commentaire }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-encre-doux">{{ $avis->created_at->diffForHumans() }}</p>
                            <x-signaler type="avis" :model="$avis" />
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-sm text-encre-doux">Aucun avis publié pour le moment. Le vôtre serait le premier.</p>
                @endforelse
            </div>
        </section>

        {{-- Retours d'entretien --}}
        <section class="mt-12">
            <h2 class="mb-3 text-xl font-extrabold text-encre font-stretch-condensed">Retours d’entretien
                <span class="text-base font-normal text-encre-doux">({{ $entreprise->retoursEntretiens->count() }})</span>
            </h2>
            <div class="border-t border-regle">
                @forelse ($entreprise->retoursEntretiens as $retour)
                    <article class="border-b border-regle py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-encre">{{ $retour->poste_vise }}</span>
                            <span class="text-xs text-encre-doux">{{ $retour->date_entretien_mois->translatedFormat('F Y') }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            @if ($retour->nb_etapes)<span class="rounded bg-indigo-50 px-2 py-0.5 text-encre-doux">{{ $retour->nb_etapes }} étape(s)</span>@endif
                            @if ($retour->duree_processus_jours)<span class="rounded bg-indigo-50 px-2 py-0.5 text-encre-doux">Process : {{ $retour->duree_processus_jours }} j</span>@endif
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
                                    <span class="rounded-full border border-regle px-2 py-0.5 text-xs text-encre-doux">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($retour->ressenti_general)
                            <p class="mt-2 text-sm text-encre-doux">{{ $retour->ressenti_general }}</p>
                        @endif
                        <div class="mt-2 flex justify-end">
                            <x-signaler type="entretien" :model="$retour" />
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-sm text-encre-doux">Aucun retour d’entretien publié.</p>
                @endforelse
            </div>
        </section>

        {{-- Missions --}}
        <section class="mt-12">
            <h2 class="mb-3 text-xl font-extrabold text-encre font-stretch-condensed">Missions
                <span class="text-base font-normal text-encre-doux">({{ $entreprise->missions->count() }})</span>
            </h2>
            <div class="border-t border-regle">
                @forelse ($entreprise->missions as $mission)
                    <article class="border-b border-regle py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-encre">{{ $mission->type_mission->libelle() }}</span>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @if ($mission->duree_mois)<span class="rounded bg-indigo-50 px-2 py-0.5 text-encre-doux">{{ $mission->duree_mois }} mois</span>@endif
                                @if ($mission->fourchette_remuneration)<span class="rounded bg-indigo-50 px-2 py-0.5 text-encre-doux">{{ $mission->fourchette_remuneration }}</span>@endif
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
                            <p class="mt-2 text-sm text-encre-doux">{{ $mission->commentaire }}</p>
                        @endif
                        <div class="mt-2 flex justify-end">
                            <x-signaler type="mission" :model="$mission" />
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-sm text-encre-doux">Aucune mission publiée.</p>
                @endforelse
            </div>
        </section>

        {{-- Maillage interne : même secteur --}}
        @if ($similaires->isNotEmpty())
            <section class="mt-14">
                <h2 class="mb-3 text-xl font-extrabold text-encre font-stretch-condensed">Dans le même secteur</h2>
                <ul class="border-t border-regle">
                    @foreach ($similaires as $similaire)
                        <li class="border-b border-regle">
                            <a href="{{ route('entreprises.show', $similaire) }}" class="flex items-center justify-between gap-3 py-3 hover:bg-white">
                                <span class="truncate font-semibold text-encre">{{ $similaire->nom }}</span>
                                <span class="shrink-0 text-sm tabular-nums text-encre-doux">
                                    {{ $similaire->score_bayesien !== null ? number_format((float) $similaire->score_bayesien, 1, ',', ' ').' sur 5' : 'Pas encore notée' }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
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
            <p class="mb-4 text-sm text-encre-doux">
                Votre avis est prêt ✅ Créez votre compte pour le publier — <strong>anonyme, 30 secondes.</strong>
            </p>
            <form method="POST" action="{{ route('register') }}" class="space-y-3">
                @csrf
                <div>
                    <input name="email" type="email" required autocomplete="email" placeholder="Votre email"
                           value="{{ old('email') }}"
                           class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-password-input name="password" :required="true" autocomplete="new-password" placeholder="Choisissez un mot de passe" />
                    @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Publier mon avis
                </button>
            </form>
            <p class="mt-3 text-center text-xs text-encre-doux">
                Déjà un compte ? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Se connecter</a>
            </p>
        </x-modal>
    @endguest
</x-layout>
