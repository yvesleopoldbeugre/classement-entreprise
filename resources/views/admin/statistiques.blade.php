<x-layout title="Statistiques · ClassementCI">
    @php
        // Cartes KPI : visiteurs uniques en tête, puis un total par type d'événement.
        $cartes = [['libelle' => 'Visiteurs uniques', 'valeur' => $visiteursUniques, 'accent' => true]];
        foreach (\App\Enums\TypeEvenement::cases() as $t) {
            $cartes[] = ['libelle' => $t->libelle(), 'valeur' => $totaux[$t->value] ?? 0, 'accent' => false];
        }
        $lienPeriode = 'rounded-lg px-3 py-1.5 text-sm font-medium';
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- En-tête + sélecteur de période --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Statistiques d’utilisation</h1>
                <p class="mt-1 text-sm text-slate-500">Visites et actions sur les {{ $jours }} derniers jours.</p>
            </div>
            <div class="inline-flex gap-1 rounded-xl border border-slate-200 bg-white p-1">
                @foreach ($periodes as $p)
                    <a href="{{ route('admin.stats.index', ['jours' => $p]) }}"
                       class="{{ $lienPeriode }} {{ $jours === $p ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $p }} jours
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Cartes KPI --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($cartes as $carte)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 {{ $carte['accent'] ? 'ring-1 ring-indigo-100' : '' }}">
                    <div class="text-2xl font-bold {{ $carte['accent'] ? 'text-indigo-600' : 'text-slate-900' }}">
                        {{ number_format($carte['valeur'], 0, ',', ' ') }}
                    </div>
                    <div class="mt-1 text-xs font-medium text-slate-500">{{ $carte['libelle'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Graphique d'évolution --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Évolution de l’utilisation</h2>
            <p class="mb-4 text-xs text-slate-400">Visites de pages et actions des utilisateurs, par jour.</p>
            <div class="relative h-72 w-full">
                <canvas id="graphe-usage"></canvas>
            </div>
        </div>
    </div>

    {{-- Données injectées pour Chart.js (lues par resources/js/stats.js) --}}
    <script type="application/json" id="stats-data">@json($graphe)</script>
    @vite('resources/js/stats.js')
</x-layout>
