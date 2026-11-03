@php
    $input = 'w-full rounded-xl border-[1.5px] border-ligne bg-white px-3.5 py-2.5 text-[15px] outline-none focus:border-encre focus:ring-4 focus:ring-orange/20';
    $dimensions = ['note_ambiance' => 'Ambiance', 'note_management' => 'Management', 'note_salaire' => 'Salaire', 'note_evolution' => 'Évolution'];
    $aides = [
        'note_ambiance' => 'L’ambiance de travail : relations entre collègues, esprit d’équipe, bien-être au quotidien (1 = très mauvaise, 5 = excellente).',
        'note_management' => 'La qualité de l’encadrement : écoute, équité, reconnaissance, communication des managers (1 à 5).',
        'note_salaire' => 'Le niveau de rémunération et des avantages par rapport au poste et au marché (1 = faible, 5 = très bon).',
        'note_evolution' => 'Les perspectives d’évolution : formation, promotions, montée en compétences (1 à 5).',
    ];
@endphp
<form method="POST" action="{{ route('contrib.avis.store', $entreprise) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="entreprise_id" value="{{ $entreprise->id }}">
    <input type="hidden" name="_form" value="avis">

    @error('entreprise_id')
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <p class="text-sm text-encre-doux">Touchez une note de 1 (très mauvais) à 5 (excellent).</p>

    @foreach ($dimensions as $name => $label)
        <x-champ :label="$label" :name="$name" :required="true" :aide="$aides[$name] ?? null">
            <div class="grid grid-cols-5 gap-2" role="radiogroup" aria-label="{{ $label }}">
                @for ($n = 1; $n <= 5; $n++)
                    <label class="cursor-pointer">
                        <input type="radio" name="{{ $name }}" value="{{ $n }}" class="peer sr-only" required @checked((int) old($name) === $n) @if ($n === 1) id="{{ $name }}" @endif>
                        <span class="block rounded-xl bg-surface py-2.5 text-center text-[15px] font-bold text-encre transition peer-checked:bg-encre peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-orange hover:bg-indigo-50">{{ $n }}</span>
                    </label>
                @endfor
            </div>
        </x-champ>
    @endforeach

    <x-champ label="Votre situation" name="statut_emploi" :required="true"
             aide="Votre lien avec l’entreprise : salarié actuel, ancien salarié, stagiaire, freelance… Cela aide à pondérer et contextualiser votre avis.">
        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Votre situation">
            @foreach (\App\Enums\StatutEmploi::cases() as $statut)
                <label class="cursor-pointer">
                    <input type="radio" name="statut_emploi" value="{{ $statut->value }}" class="peer sr-only" required @checked(old('statut_emploi') === $statut->value) @if ($loop->first) id="statut_emploi" @endif>
                    <span class="block rounded-full bg-surface px-3.5 py-2 text-sm font-semibold text-encre transition peer-checked:bg-encre peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-orange hover:bg-indigo-50">{{ $statut->libelle() }}</span>
                </label>
            @endforeach
        </div>
    </x-champ>

    <x-champ label="Commentaire" name="commentaire" hint="Optionnel, mais très utile aux autres."
             aide="Décrivez concrètement votre expérience (points forts, points faibles, conseils). Restez factuel et respectueux : les avis sont modérés avant publication.">
        <textarea id="commentaire" name="commentaire" rows="3" placeholder="Ce qui vous a plu, ce qui pourrait changer…" class="{{ $input }}">{{ old('commentaire') }}</textarea>
    </x-champ>

    <button type="submit" class="w-full rounded-xl bg-orange px-4 py-3 text-[15px] font-semibold text-encre hover:brightness-105">
        Publier mon avis
    </button>
    <p class="text-center text-xs text-encre-doux">Anonyme. Chaque avis est relu avant publication.</p>
</form>
