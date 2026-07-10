@php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
<form method="POST" action="{{ route('contrib.entretien.store', $entreprise) }}" class="space-y-3">
    @csrf
    <input type="hidden" name="entreprise_id" value="{{ $entreprise->id }}">
    <input type="hidden" name="_form" value="entretien">

    <div class="grid gap-3 sm:grid-cols-2">
        <x-champ label="Poste visé" name="poste_vise" :required="true">
            <input id="poste_vise" name="poste_vise" type="text" value="{{ old('poste_vise') }}" class="{{ $input }}" required>
        </x-champ>
        <x-champ label="Mois de l’entretien" name="date_entretien_mois" :required="true">
            <input id="date_entretien_mois" name="date_entretien_mois" type="month" value="{{ old('date_entretien_mois') }}" class="{{ $input }}" required>
        </x-champ>
        <x-champ label="Nombre d’étapes" name="nb_etapes">
            <input id="nb_etapes" name="nb_etapes" type="number" min="1" max="255" value="{{ old('nb_etapes') }}" class="{{ $input }}">
        </x-champ>
        <x-champ label="Durée du process (jours)" name="duree_processus_jours">
            <input id="duree_processus_jours" name="duree_processus_jours" type="number" min="0" value="{{ old('duree_processus_jours') }}" class="{{ $input }}">
        </x-champ>
    </div>

    <x-champ label="Questions posées" name="questions_posees" hint="Séparées par des virgules (ex : sql, algo, comportemental).">
        <input id="questions_posees" name="questions_posees" type="text" value="{{ old('questions_posees') }}" class="{{ $input }}">
    </x-champ>

    <div class="grid gap-3 sm:grid-cols-2">
        <x-champ label="Délai de réponse (jours)" name="delai_reponse_jours">
            <input id="delai_reponse_jours" name="delai_reponse_jours" type="number" min="0" value="{{ old('delai_reponse_jours') }}" class="{{ $input }}">
        </x-champ>
        <div class="flex flex-col justify-center gap-2 pt-2">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="a_recu_reponse" value="0">
                <input type="checkbox" name="a_recu_reponse" value="1" @checked(old('a_recu_reponse')) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                J’ai reçu une réponse
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="a_eu_offre" value="0">
                <input type="checkbox" name="a_eu_offre" value="1" @checked(old('a_eu_offre')) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                J’ai eu une offre
            </label>
        </div>
    </div>

    <x-champ label="Ressenti général" name="ressenti_general">
        <textarea id="ressenti_general" name="ressenti_general" rows="2" class="{{ $input }}">{{ old('ressenti_general') }}</textarea>
    </x-champ>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        Publier mon retour
    </button>
</form>
