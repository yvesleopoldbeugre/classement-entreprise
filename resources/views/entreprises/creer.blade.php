<x-layout title="Proposer une entreprise · ClassementCI" :open-modal="old('_form')">
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
            @include('entreprises.partials.proposer')
        </div>
    </div>
</x-layout>
