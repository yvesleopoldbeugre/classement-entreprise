<x-layout robots="noindex, nofollow" :title="'Retour d’entretien · '.$entreprise->nom">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('entreprises.show', $entreprise) }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-800">← {{ $entreprise->nom }}</a>
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h1 class="text-xl font-bold text-slate-900">Partager un retour d’entretien</h1>
            <p class="mb-6 mt-1 text-sm text-slate-500">Chez <span class="font-medium text-slate-700">{{ $entreprise->nom }}</span>. Publié après modération.</p>
            @include('contributions.partials.entretien')
        </div>
    </div>
</x-layout>
