<x-layout robots="noindex, nofollow" title="Proposer une entreprise · Note ta boîte" :open-modal="old('_form')">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('classement.index') }}" class="mb-4 inline-block text-sm text-encre-doux hover:text-encre">← Retour au classement</a>
        <div class="rounded-xl border border-ligne bg-white p-6">
            <h1 class="text-xl font-bold text-encre">Ajouter une entreprise</h1>
            <p class="mb-6 mt-1 text-sm text-encre-doux">
                @can('moderer')
                    Elle sera publiée directement (vérifiée).
                @else
                    Elle sera vérifiée par un modérateur avant d’obtenir le badge « vérifiée ».
                @endcan
            </p>
            @include('entreprises.partials.proposer')
        </div>
    </div>
</x-layout>
