@php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
<form method="POST" action="{{ route('entreprises.proposer') }}" class="space-y-3"
      data-import-url="{{ route('entreprises.importer-site') }}">
    @csrf
    <input type="hidden" name="_form" value="proposer">

    {{-- Remplissage automatique depuis le site web (masqué par défaut, dépliable) --}}
    <details class="rounded-xl border border-indigo-100 bg-indigo-50/60">
        <summary class="cursor-pointer select-none px-3 py-2 text-xs font-semibold text-indigo-800 hover:text-indigo-900">
            ⚡ Remplir automatiquement depuis le site web
        </summary>
        <div class="px-3 pb-3">
            <div class="flex gap-2">
                <input id="site_web_import" type="url" placeholder="https://site-de-l-entreprise.com"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <button type="button" data-import-site
                        class="grid min-w-[6rem] shrink-0 place-items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">Récupérer</button>
            </div>
            <p class="mt-1 text-xs text-indigo-700/70">On tente de pré-remplir le nom et la description depuis le site (tout reste modifiable).</p>
            <p data-import-erreur class="mt-1 hidden text-xs text-rose-600"></p>
        </div>
    </details>

    <x-champ label="Nom de l’entreprise" name="nom" :required="true"
             aide="Le nom officiel/commercial de l’entreprise, tel qu’on le connaît en Côte d’Ivoire.">
        <input id="nom" name="nom" type="text" value="{{ old('nom') }}" class="{{ $input }}" required>
    </x-champ>

    <div class="grid gap-3 sm:grid-cols-2">
        <x-champ label="Secteur d’activité" name="secteur_activite" :required="true"
                 aide="Le domaine principal de l’entreprise (SSII, banque, télécom, startup…). Choisissez le plus proche.">
            <select id="secteur_activite" name="secteur_activite" class="{{ $input }}" required>
                <option value="">—</option>
                @foreach (\App\Enums\SecteurActivite::cases() as $secteur)
                    <option value="{{ $secteur->value }}" @selected(old('secteur_activite') === $secteur->value)>{{ $secteur->libelle() }}</option>
                @endforeach
            </select>
        </x-champ>
        <x-champ label="Commune" name="commune" aide="La commune/quartier du siège ou du bureau principal (ex. Cocody, Plateau, Marcory).">
            <input id="commune" name="commune" type="text" value="{{ old('commune') }}" class="{{ $input }}" placeholder="Cocody, Plateau…">
        </x-champ>
        <x-champ label="Site web" name="site_web" aide="L’adresse du site officiel de l’entreprise. Elle sert aussi au remplissage automatique ci-dessus.">
            <input id="site_web" name="site_web" type="url" value="{{ old('site_web') }}" class="{{ $input }}" placeholder="https://…">
        </x-champ>
        <x-champ label="Page LinkedIn" name="linkedin_url">
            <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url') }}" class="{{ $input }}" placeholder="https://www.linkedin.com/company/…">
        </x-champ>
    </div>

    <x-champ label="Pourquoi proposez-vous cette entreprise ?" name="commentaire_proposition" :required="true"
             hint="Expliquez brièvement votre expérience ou pourquoi elle mérite d’être suivie."
             aide="Un mot pour le modérateur : votre lien avec l’entreprise ou pourquoi elle mérite d’être suivie. Peut être pré-rempli depuis le site (modifiable).">
        <textarea id="commentaire_proposition" name="commentaire_proposition" rows="3" class="{{ $input }}" required>{{ old('commentaire_proposition') }}</textarea>
    </x-champ>

    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        @can('moderer') Ajouter l’entreprise @else Proposer l’entreprise @endcan
    </button>
</form>
