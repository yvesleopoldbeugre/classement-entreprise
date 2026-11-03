<x-layout robots="noindex, nofollow" :title="'Déclarer une mission · '.$entreprise->nom">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('entreprises.show', $entreprise) }}" class="mb-4 inline-block text-sm text-encre-doux hover:text-encre">← {{ $entreprise->nom }}</a>
        <div class="rounded-xl border border-ligne bg-white p-6">
            <h1 class="text-xl font-bold text-encre">Déclarer une mission</h1>
            <p class="mb-6 mt-1 text-sm text-encre-doux">Avec <span class="font-medium text-encre">{{ $entreprise->nom }}</span>. Publié après modération.</p>
            @include('contributions.partials.mission')
        </div>
    </div>
</x-layout>
