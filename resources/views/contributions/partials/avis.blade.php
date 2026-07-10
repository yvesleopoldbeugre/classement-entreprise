@php
    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100';
    $dimensions = ['note_ambiance' => 'Ambiance', 'note_management' => 'Management', 'note_salaire' => 'Salaire', 'note_evolution' => 'Évolution'];
@endphp
<form method="POST" action="{{ route('contrib.avis.store', $entreprise) }}" class="space-y-3">
    @csrf
    <input type="hidden" name="entreprise_id" value="{{ $entreprise->id }}">
    <input type="hidden" name="_form" value="avis">

    @error('entreprise_id')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($dimensions as $name => $label)
            <x-champ :label="$label" :name="$name" :required="true">
                <select id="{{ $name }}" name="{{ $name }}" class="{{ $input }}" required>
                    <option value="">—</option>
                    @for ($n = 1; $n <= 5; $n++)
                        <option value="{{ $n }}" @selected((int) old($name) === $n)>{{ $n }} / 5</option>
                    @endfor
                </select>
            </x-champ>
        @endforeach
    </div>

    <x-champ label="Votre situation" name="statut_emploi" :required="true">
        <select id="statut_emploi" name="statut_emploi" class="{{ $input }}" required>
            <option value="">—</option>
            @foreach (\App\Enums\StatutEmploi::cases() as $statut)
                <option value="{{ $statut->value }}" @selected(old('statut_emploi') === $statut->value)>{{ $statut->libelle() }}</option>
            @endforeach
        </select>
    </x-champ>

    <x-champ label="Commentaire" name="commentaire" hint="Optionnel, mais très utile aux autres.">
        <textarea id="commentaire" name="commentaire" rows="3" class="{{ $input }}">{{ old('commentaire') }}</textarea>
    </x-champ>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        Publier mon avis
    </button>
</form>
