@php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
<form method="POST" action="{{ route('contrib.mission.store', $entreprise) }}" class="space-y-3">
    @csrf
    <input type="hidden" name="entreprise_id" value="{{ $entreprise->id }}">
    <input type="hidden" name="_form" value="mission">

    <div class="grid gap-3 sm:grid-cols-2">
        <x-champ label="Type de mission" name="type_mission" :required="true">
            <select id="type_mission" name="type_mission" class="{{ $input }}" required>
                <option value="">—</option>
                @foreach (\App\Enums\TypeMission::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('type_mission') === $type->value)>{{ $type->libelle() }}</option>
                @endforeach
            </select>
        </x-champ>
        <x-champ label="Durée (mois)" name="duree_mois">
            <input id="duree_mois" name="duree_mois" type="number" min="0" value="{{ old('duree_mois') }}" class="{{ $input }}">
        </x-champ>
        <x-champ label="Fourchette de rémunération" name="fourchette_remuneration" hint="Ex : 300k-500k FCFA">
            <input id="fourchette_remuneration" name="fourchette_remuneration" type="text" value="{{ old('fourchette_remuneration') }}" class="{{ $input }}">
        </x-champ>
        <x-champ label="Paiement à temps ?" name="paiement_a_temps">
            <select id="paiement_a_temps" name="paiement_a_temps" class="{{ $input }}">
                <option value="">—</option>
                <option value="1" @selected(old('paiement_a_temps') === '1')>Oui</option>
                <option value="0" @selected(old('paiement_a_temps') === '0')>Non</option>
            </select>
        </x-champ>
        <x-champ label="Contrat respecté ?" name="respect_contrat">
            <select id="respect_contrat" name="respect_contrat" class="{{ $input }}">
                <option value="">—</option>
                <option value="1" @selected(old('respect_contrat') === '1')>Oui</option>
                <option value="0" @selected(old('respect_contrat') === '0')>Non</option>
            </select>
        </x-champ>
    </div>

    <x-champ label="Commentaire" name="commentaire">
        <textarea id="commentaire" name="commentaire" rows="2" class="{{ $input }}">{{ old('commentaire') }}</textarea>
    </x-champ>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        Publier ma mission
    </button>
</form>
