<x-layout title="Proposer une entreprise · ClassementCI">
    @php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
    <div class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('classement.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-800">← Retour au classement</a>
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h1 class="text-xl font-bold text-slate-900">Ajouter une entreprise</h1>
            <p class="mb-6 mt-1 text-sm text-slate-500">
                @can('moderer')
                    Elle sera publiée directement (vérifiée).
                @else
                    Elle sera vérifiée par un modérateur avant d’obtenir le badge « vérifiée ».
                @endcan
            </p>

            <form method="POST" action="{{ route('entreprises.proposer') }}" class="space-y-3">
                @csrf
                <x-champ label="Nom de l’entreprise" name="nom" :required="true">
                    <input id="nom" name="nom" type="text" value="{{ old('nom') }}" class="{{ $input }}" required>
                </x-champ>

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-champ label="Secteur d’activité" name="secteur_activite" :required="true">
                        <select id="secteur_activite" name="secteur_activite" class="{{ $input }}" required>
                            <option value="">—</option>
                            @foreach ($secteurs as $secteur)
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
                    <x-champ label="Taille estimée" name="taille_estimee" hint="Ex : 10-50">
                        <input id="taille_estimee" name="taille_estimee" type="text" value="{{ old('taille_estimee') }}" class="{{ $input }}">
                    </x-champ>
                </div>

                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    @can('moderer') Ajouter l’entreprise @else Proposer l’entreprise @endcan
                </button>
            </form>
        </div>
    </div>
</x-layout>
