<x-layout :title="($utilisateur->pseudo_public ?? $utilisateur->name).' · Utilisateur'">
    @php
        $cartes = [];
        foreach (\App\Enums\TypeEvenement::cases() as $t) {
            $cartes[] = ['libelle' => $t->libelle(), 'valeur' => $totaux[$t->value] ?? 0];
        }
        $lienPeriode = 'rounded-lg px-3 py-1.5 text-sm font-medium';
    @endphp

    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('admin.users.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
            ← Retour aux utilisateurs
        </a>

        {{-- En-tête --}}
        <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $utilisateur->pseudo_public ?? $utilisateur->name }}</h1>
                    @if ($utilisateur->is_admin)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">admin</span>
                    @endif
                </div>
                <div class="mt-1 text-sm text-slate-500">{{ $utilisateur->email }}</div>
                <div class="mt-1 text-xs text-slate-400">Inscrit le {{ $utilisateur->created_at->translatedFormat('d F Y') }}</div>
            </div>
            <div class="inline-flex gap-1 rounded-xl border border-slate-200 bg-white p-1">
                @foreach ($periodes as $p)
                    <a href="{{ route('admin.users.show', ['user' => $utilisateur, 'jours' => $p]) }}"
                       class="{{ $lienPeriode }} {{ $jours === $p ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $p }} jours
                    </a>
                @endforeach
            </div>
        </div>

        {{-- KPI par type (tous temps) --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($cartes as $carte)
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-2xl font-bold text-slate-900">{{ number_format($carte['valeur'], 0, ',', ' ') }}</div>
                    <div class="mt-1 text-xs font-medium text-slate-500">{{ $carte['libelle'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Graphique d'évolution --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Activité sur {{ $jours }} jours</h2>
            <p class="mb-4 text-xs text-slate-400">Visites et actions de cet utilisateur, par jour.</p>
            <div class="relative h-64 w-full">
                <canvas id="graphe-utilisateur"></canvas>
            </div>
        </div>

        {{-- Dernières actions --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold text-slate-700">Dernières actions</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentes as $action)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <span class="font-medium text-slate-800">{{ $action['type'] }}</span>
                            @if ($action['sujet']['libelle'])
                                <span class="text-slate-400"> · </span>
                                @if ($action['sujet']['url'])
                                    <a href="{{ $action['sujet']['url'] }}" class="text-indigo-600 hover:underline">{{ $action['sujet']['libelle'] }}</a>
                                @else
                                    <span class="text-slate-500">{{ $action['sujet']['libelle'] }}</span>
                                @endif
                            @endif
                        </div>
                        <time class="shrink-0 text-xs text-slate-400">{{ $action['date']->diffForHumans() }}</time>
                    </li>
                @empty
                    <li class="py-6 text-center text-slate-400">Aucune action enregistrée.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <script type="application/json" id="user-stats-data">@json($graphe)</script>
    @vite('resources/js/users.js')
</x-layout>
