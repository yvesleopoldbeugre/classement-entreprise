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

    @php
        $tonNote = fn (?float $n) => match (true) {
            $n === null => 'text-encre-doux',
            $n >= 3.5 => 'text-vert',
            $n >= 2.8 => 'text-ambre',
            default => 'text-rouge',
        };
        $fondNote = fn (float $n) => $n >= 3.5 ? 'bg-vert-doux text-vert' : ($n >= 2.8 ? 'bg-ambre-doux text-ambre' : 'bg-rouge-doux text-rouge');
        $barreNote = fn (float $n) => $n >= 3.5 ? 'bg-vert' : ($n >= 2.8 ? 'bg-ambre' : 'bg-rouge');
        $nbVerifies = $entreprise->avis->filter(fn ($a) => $a->user?->linkedin_verifie || $a->user?->email_verified_at)->count();
        $scoreAffiche = $assezDAvis ? $score : null;
    @endphp

    {{-- En-tête : identité + carte de score --}}
    <section class="border-b border-ligne bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-6 sm:py-10">
            <nav class="text-[13px] text-encre-doux" aria-label="Fil d’Ariane">
                <a href="{{ route('classement.index') }}" class="hover:text-encre hover:underline">Classement</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('classement.secteur', $entreprise->secteur_activite->slug()) }}" class="hover:text-encre hover:underline">{{ $entreprise->secteur_activite->libelle() }}</a>
                <span class="mx-1.5">/</span>
                <span class="text-encre">{{ $entreprise->nom }}</span>
            </nav>

            <div class="mt-5 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4 sm:gap-5">
                    <x-monogramme :nom="$entreprise->nom" :taille="72" />
                    <div class="min-w-0">
                        <h1 class="text-3xl font-extrabold tracking-tight text-encre sm:text-[44px] sm:leading-tight">{{ $entreprise->nom }}</h1>
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-[13px] font-medium">
                            <a href="{{ route('classement.secteur', $entreprise->secteur_activite->slug()) }}" class="rounded-full bg-white px-3 py-1.5 text-encre-doux hover:text-encre">{{ $entreprise->secteur_activite->libelle() }}</a>
                            @if ($entreprise->commune)
                                <a href="{{ route('classement.commune', \Illuminate\Support\Str::slug($entreprise->commune)) }}" class="rounded-full bg-white px-3 py-1.5 text-encre-doux hover:text-encre">{{ $entreprise->commune }}</a>
                            @endif
                            @if ($entreprise->taille_estimee)<span class="rounded-full bg-white px-3 py-1.5 text-encre-doux">{{ $entreprise->taille_estimee }} personnes</span>@endif
                            @if ($entreprise->site_web)
                                <a href="{{ $entreprise->site_web }}" target="_blank" rel="noopener" class="rounded-full bg-white px-3 py-1.5 text-teal hover:underline">Site web</a>
                            @endif
                            @if ($entreprise->statut === \App\Enums\StatutEntreprise::AVerifier)
                                <span class="rounded-full bg-orange-doux px-3 py-1.5 text-encre" title="En attente de vérification par un modérateur">En cours de vérification</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-teal-doux px-3 py-1.5 font-semibold text-teal" title="Existence de l’entreprise vérifiée par un modérateur">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                    Vérifiée
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-5 rounded-2xl border border-ligne bg-white p-4 pr-7">
                    <x-anneau :note="$scoreAffiche" :taille="88" tracer />
                    <div>
                        @if ($scoreAffiche !== null)
                            <p class="text-xl font-extrabold {{ $tonNote($scoreAffiche) }}">{{ $entreprise->appreciation() }}</p>
                            @if ($rang)<p class="text-sm font-semibold text-encre">{{ \App\Models\Entreprise::rangOrdinal($rang) }} sur {{ $totalClassees }} entreprises</p>@endif
                            <p class="text-[13px] text-encre-doux">Fiabilité : {{ $entreprise->nb_avis_total }} avis{{ $nbVerifies ? ', dont '.$nbVerifies.' vérifiés' : '' }}</p>
                        @else
                            <p class="text-xl font-extrabold text-encre">Pas encore notée</p>
                            <p class="text-[13px] text-encre-doux">{{ $entreprise->nb_avis_total }} avis sur les {{ $minAvis }} nécessaires</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl items-start gap-8 px-4 py-8 pb-28 lg:grid-cols-[1fr_340px] lg:pb-12">
        <div class="min-w-0 space-y-6">
            @if ($entreprise->description)
                <p class="text-base leading-relaxed text-encre-doux">{{ $entreprise->description }}</p>
            @endif

            {{-- Notes par critère --}}
            @if ($entreprise->nb_avis_total > 0)
                <section class="rounded-2xl border border-ligne bg-white p-5">
                    <h2 class="text-lg font-extrabold text-encre">Notes par critère</h2>
                    <dl class="mt-4 space-y-3.5">
                        @foreach (['Ambiance' => $entreprise->moy_ambiance, 'Management' => $entreprise->moy_management, 'Salaire' => $entreprise->moy_salaire, 'Évolution' => $entreprise->moy_evolution] as $libelle => $valeur)
                            @php $v = $valeur !== null ? (float) $valeur : null; @endphp
                            <div class="grid grid-cols-[6rem_1fr_2.5rem] items-center gap-3 sm:grid-cols-[8rem_1fr_2.5rem]">
                                <dt class="text-sm font-medium text-encre">{{ $libelle }}</dt>
                                <dd class="h-2 rounded-full bg-surface" aria-hidden="true">
                                    @if ($v !== null)<div class="h-full rounded-full {{ $barreNote($v) }}" style="width: {{ $v / 5 * 100 }}%"></div>@endif
                                </dd>
                                <dd class="text-right text-[15px] font-bold tabular-nums text-encre">{{ $v !== null ? number_format($v, 1, ',', ' ') : '–' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs text-encre-doux">
                        Moyenne brute des avis : {{ number_format((float) $entreprise->note_globale, 2, ',', ' ') }} sur 5.
                        @unless ($assezDAvis)Note provisoire tant qu’il y a moins de {{ $minAvis }} avis.@endunless
                    </p>
                </section>
            @endif

            {{-- Réponse de l'entreprise (droit de réponse) --}}
            @if ($entreprise->reponse_entreprise)
                <section class="rounded-2xl border border-teal/30 bg-teal-doux p-5">
                    <h2 class="text-sm font-semibold text-encre">Réponse de l’entreprise</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-encre">{{ $entreprise->reponse_entreprise }}</p>
                    @if ($entreprise->reponse_entreprise_le)
                        <p class="mt-2 text-xs text-encre-doux">Publiée {{ $entreprise->reponse_entreprise_le->diffForHumans() }}</p>
                    @endif
                </section>
            @endif

            @can('moderer')
                <section class="rounded-2xl border border-ligne bg-white p-5">
                    <h2 class="text-sm font-semibold text-encre">Droit de réponse <span class="text-xs font-normal text-encre-doux">(admin)</span></h2>
                    <form method="POST" action="{{ route('entreprises.reponse', $entreprise) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PUT')
                        <textarea name="reponse_entreprise" rows="3" placeholder="Réponse officielle de l’entreprise (laisser vide pour supprimer)…"
                                  class="w-full rounded-xl border-[1.5px] border-ligne bg-white px-3.5 py-2.5 text-[15px] outline-none focus:border-encre focus:ring-4 focus:ring-orange/20">{{ old('reponse_entreprise', $entreprise->reponse_entreprise) }}</textarea>
                        @error('reponse_entreprise')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Enregistrer la réponse</button>
                    </form>
                </section>
            @endcan

            {{-- Avis / entretiens / missions : onglets (tout est visible sans JavaScript) --}}
            <div data-onglets>
                <div class="flex gap-1 overflow-x-auto border-b border-ligne" role="tablist">
                    @foreach (['avis' => 'Avis ('.$entreprise->avis->count().')', 'entretiens' => 'Entretiens ('.$entreprise->retoursEntretiens->count().')', 'missions' => 'Missions ('.$entreprise->missions->count().')'] as $cle => $libelle)
                        <button type="button" role="tab" data-onglet="{{ $cle }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                class="-mb-px shrink-0 border-b-[3px] border-transparent px-4 py-3 text-[15px] font-semibold text-encre-doux hover:text-encre aria-selected:border-orange aria-selected:text-encre">{{ $libelle }}</button>
                    @endforeach
                </div>

                <section data-panneau="avis" class="space-y-4 pt-5">
                    <h2 class="sr-only">Avis des employés</h2>
                    @forelse ($entreprise->avis as $avis)
                        @php $n = (float) $avis->noteMoyenne(); @endphp
                        <article class="rounded-2xl border border-ligne bg-white p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-surface text-[13px] font-bold text-encre" aria-hidden="true">{{ mb_strtoupper(mb_substr($avis->user->pseudo_public ?? 'A', 0, 2)) }}</span>
                                    <div>
                                        <p class="flex flex-wrap items-center gap-1.5 text-[15px] font-semibold text-encre">
                                            {{ $avis->user->pseudo_public ?? 'Anonyme' }}
                                            @if ($avis->user?->linkedin_verifie)
                                                <span class="rounded-full bg-teal-doux px-2 py-0.5 text-xs font-semibold text-teal" title="LinkedIn vérifié : avis au poids le plus fort">LinkedIn vérifié</span>
                                            @elseif ($avis->user?->email_verified_at)
                                                <span class="rounded-full bg-teal-doux px-2 py-0.5 text-xs font-semibold text-teal" title="Email vérifié : avis à poids renforcé">Email vérifié</span>
                                            @endif
                                        </p>
                                        <p class="text-[13px] text-encre-doux">{{ $avis->statut_emploi->libelle() }}, {{ $avis->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-sm font-bold tabular-nums {{ $fondNote($n) }}">{{ number_format($n, 1, ',', ' ') }} / 5</span>
                            </div>
                            @if ($avis->commentaire)
                                <p class="mt-3 text-[15px] leading-relaxed text-encre">{{ $avis->commentaire }}</p>
                            @endif
                            <div class="mt-3 flex justify-end">
                                <x-signaler type="avis" :model="$avis" />
                            </div>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-ligne bg-white p-6 text-center text-sm text-encre-doux">Aucun avis publié pour le moment. Le vôtre serait le premier.</p>
                    @endforelse
                </section>

                <section data-panneau="entretiens" class="space-y-4 pt-5">
                    <h2 class="sr-only">Retours d’entretien</h2>
                    @forelse ($entreprise->retoursEntretiens as $retour)
                        <article class="rounded-2xl border border-ligne bg-white p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[15px] font-semibold text-encre">{{ $retour->poste_vise }}</span>
                                <span class="text-[13px] text-encre-doux">{{ $retour->date_entretien_mois->translatedFormat('F Y') }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
                                @if ($retour->nb_etapes)<span class="rounded-full bg-surface px-2.5 py-1 text-encre-doux">{{ $retour->nb_etapes }} étape(s)</span>@endif
                                @if ($retour->duree_processus_jours)<span class="rounded-full bg-surface px-2.5 py-1 text-encre-doux">Process : {{ $retour->duree_processus_jours }} j</span>@endif
                                @if ($retour->a_recu_reponse)
                                    <span class="rounded-full bg-vert-doux px-2.5 py-1 text-vert">Réponse reçue{{ $retour->delai_reponse_jours ? ' ('.$retour->delai_reponse_jours.' j)' : '' }}</span>
                                @else
                                    <span class="rounded-full bg-rouge-doux px-2.5 py-1 text-rouge">Sans réponse</span>
                                @endif
                                @if ($retour->a_eu_offre)<span class="rounded-full bg-teal-doux px-2.5 py-1 text-teal">Offre reçue</span>@endif
                            </div>
                            @if (!empty($retour->questions_posees))
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($retour->questions_posees as $tag)
                                        <span class="rounded-full border border-ligne px-2.5 py-0.5 text-xs text-encre-doux">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($retour->ressenti_general)
                                <p class="mt-3 text-[15px] leading-relaxed text-encre">{{ $retour->ressenti_general }}</p>
                            @endif
                            <div class="mt-3 flex justify-end">
                                <x-signaler type="entretien" :model="$retour" />
                            </div>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-ligne bg-white p-6 text-center text-sm text-encre-doux">Aucun retour d’entretien publié.</p>
                    @endforelse
                </section>

                <section data-panneau="missions" class="space-y-4 pt-5">
                    <h2 class="sr-only">Missions</h2>
                    @forelse ($entreprise->missions as $mission)
                        <article class="rounded-2xl border border-ligne bg-white p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[15px] font-semibold text-encre">{{ $mission->type_mission->libelle() }}</span>
                                <div class="flex flex-wrap gap-2 text-xs font-medium">
                                    @if ($mission->duree_mois)<span class="rounded-full bg-surface px-2.5 py-1 text-encre-doux">{{ $mission->duree_mois }} mois</span>@endif
                                    @if ($mission->fourchette_remuneration)<span class="rounded-full bg-surface px-2.5 py-1 text-encre-doux">{{ $mission->fourchette_remuneration }}</span>@endif
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
                                @if ($mission->paiement_a_temps !== null)
                                    <span class="rounded-full px-2.5 py-1 {{ $mission->paiement_a_temps ? 'bg-vert-doux text-vert' : 'bg-rouge-doux text-rouge' }}">
                                        Paiement {{ $mission->paiement_a_temps ? 'à temps' : 'en retard' }}
                                    </span>
                                @endif
                                @if ($mission->respect_contrat !== null)
                                    <span class="rounded-full px-2.5 py-1 {{ $mission->respect_contrat ? 'bg-vert-doux text-vert' : 'bg-rouge-doux text-rouge' }}">
                                        Contrat {{ $mission->respect_contrat ? 'respecté' : 'non respecté' }}
                                    </span>
                                @endif
                            </div>
                            @if ($mission->commentaire)
                                <p class="mt-3 text-[15px] leading-relaxed text-encre">{{ $mission->commentaire }}</p>
                            @endif
                            <div class="mt-3 flex justify-end">
                                <x-signaler type="mission" :model="$mission" />
                            </div>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-ligne bg-white p-6 text-center text-sm text-encre-doux">Aucune mission publiée.</p>
                    @endforelse
                </section>
            </div>

            {{-- Maillage interne : même secteur --}}
            @if ($similaires->isNotEmpty())
                <section>
                    <h2 class="mb-3 text-lg font-extrabold text-encre">Dans le même secteur</h2>
                    <ul class="rounded-2xl border border-ligne bg-white px-5">
                        @foreach ($similaires as $similaire)
                            <li class="border-t border-ligne first:border-t-0">
                                <a href="{{ route('entreprises.show', $similaire) }}" class="flex items-center gap-3 py-3">
                                    <x-monogramme :nom="$similaire->nom" :taille="40" />
                                    <span class="min-w-0 flex-1 truncate font-semibold text-encre hover:underline">{{ $similaire->nom }}</span>
                                    <x-anneau :note="$similaire->score_bayesien" :taille="42" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        {{-- Action : colonne sur grand écran --}}
        <aside class="hidden rounded-2xl bg-encre p-6 text-white lg:sticky lg:top-24 lg:block">
            <h2 class="text-2xl font-extrabold leading-tight">Vous avez travaillé chez {{ $entreprise->nom }} ?</h2>
            <p class="mt-2 text-sm text-white/75">Votre avis reste anonyme. Pas besoin de compte pour commencer.</p>
            <div class="mt-5 space-y-2.5">
                <a href="{{ route('contrib.avis.create', $entreprise) }}" data-modal-open="avis"
                   class="block rounded-xl bg-orange px-5 py-3 text-center text-[15px] font-semibold text-encre hover:brightness-105">Donner mon avis</a>
                @auth
                    <a href="{{ route('contrib.entretien.create', $entreprise) }}" data-modal-open="entretien"
                       class="block rounded-xl bg-white/10 px-5 py-3 text-center text-[15px] font-semibold text-white hover:bg-white/15">Raconter un entretien</a>
                    <a href="{{ route('contrib.mission.create', $entreprise) }}" data-modal-open="mission"
                       class="block rounded-xl bg-white/10 px-5 py-3 text-center text-[15px] font-semibold text-white hover:bg-white/15">Déclarer une mission</a>
                @endauth
            </div>
            <x-partage class="mt-6 border-t border-white/15 pt-4" sombre
                       :url="route('entreprises.show', $entreprise)"
                       :texte="'Donnez votre avis sur '.$entreprise->nom.' 👉'" />
        </aside>
    </div>

    {{-- Action : barre collée en bas sur mobile --}}
    <div class="fixed inset-x-0 bottom-0 z-20 flex gap-2.5 border-t border-ligne bg-white px-4 py-3 lg:hidden">
        <a href="{{ route('contrib.avis.create', $entreprise) }}" data-modal-open="avis"
           class="flex-1 rounded-xl bg-orange px-5 py-3 text-center text-[15px] font-semibold text-encre">Donner mon avis</a>
        <button type="button" data-copier="{{ route('entreprises.show', $entreprise) }}"
                class="rounded-xl border border-ligne px-4 py-3 text-[15px] font-semibold text-encre">Partager</button>
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
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-encre focus:ring-4 focus:ring-orange/20">
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <x-password-input name="password" :required="true" autocomplete="new-password" placeholder="Choisissez un mot de passe" />
                    @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Publier mon avis
                </button>
            </form>
            <p class="mt-3 text-center text-xs text-encre-doux">
                Déjà un compte ? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Se connecter</a>
            </p>
        </x-modal>
    @endguest
</x-layout>
