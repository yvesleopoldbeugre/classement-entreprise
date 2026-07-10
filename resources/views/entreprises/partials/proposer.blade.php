@php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
<form method="POST" action="{{ route('entreprises.proposer') }}" class="space-y-3">
    @csrf
    <input type="hidden" name="_form" value="proposer">

    <x-champ label="Nom de l’entreprise" name="nom" :required="true">
        <input id="nom" name="nom" type="text" value="{{ old('nom') }}" class="{{ $input }}" required>
    </x-champ>

    <div class="grid gap-3 sm:grid-cols-2">
        <x-champ label="Secteur d’activité" name="secteur_activite" :required="true">
            <select id="secteur_activite" name="secteur_activite" class="{{ $input }}" required>
                <option value="">—</option>
                @foreach (\App\Enums\SecteurActivite::cases() as $secteur)
                    <option value="{{ $secteur->value }}" @selected(old('secteur_activite') === $secteur->value)>{{ $secteur->libelle() }}</option>
                @endforeach
            </select>
        </x-champ>
        <x-champ label="Commune" name="commune">
            <input id="commune" name="commune" type="text" value="{{ old('commune') }}" class="{{ $input }}" placeholder="Cocody, Plateau…">
        </x-champ>
        <x-champ label="Site web" name="site_web">
            <input id="site_web" name="site_web" type="url" value="{{ old('site_web') }}" class="{{ $input }}" placeholder="https://…">
        </x-champ>
        <x-champ label="Page LinkedIn" name="linkedin_url">
            <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url') }}" class="{{ $input }}" placeholder="https://www.linkedin.com/company/…">
        </x-champ>
    </div>

    <x-champ label="Pourquoi proposez-vous cette entreprise ?" name="commentaire_proposition" :required="true"
             hint="Expliquez brièvement votre expérience ou pourquoi elle mérite d’être suivie.">
        <textarea id="commentaire_proposition" name="commentaire_proposition" rows="3" class="{{ $input }}" required>{{ old('commentaire_proposition') }}</textarea>
    </x-champ>

    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        @can('moderer') Ajouter l’entreprise @else Proposer l’entreprise @endcan
    </button>
</form>
