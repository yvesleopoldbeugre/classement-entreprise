<x-layout robots="noindex, nofollow" :title="'Donner mon avis · '.$entreprise->nom">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('entreprises.show', $entreprise) }}" class="mb-4 inline-block text-sm text-encre-doux hover:text-encre">← {{ $entreprise->nom }}</a>
        <div class="rounded-md border border-regle bg-white p-6">
            <h1 class="text-xl font-bold text-encre">Donner mon avis</h1>
            <p class="mb-6 mt-1 text-sm text-encre-doux">Sur <span class="font-medium text-encre">{{ $entreprise->nom }}</span>. Publié après modération.</p>
            @include('contributions.partials.avis')
        </div>
    </div>
</x-layout>
