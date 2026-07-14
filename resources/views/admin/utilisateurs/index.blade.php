<x-layout robots="noindex, nofollow" title="Utilisateurs · ClassementCI">
    @php $lienPeriode = 'rounded-lg px-3 py-1.5 text-sm font-medium'; @endphp

    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- En-tête + période (pour le comparatif) --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Utilisateurs</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} compte(s) — classés par engagement.</p>
            </div>
            <div class="inline-flex gap-1 rounded-xl border border-slate-200 bg-white p-1">
                @foreach ($periodes as $p)
                    <a href="{{ route('admin.users.index', ['jours' => $p]) }}"
                       class="{{ $lienPeriode }} {{ $jours === $p ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $p }} jours
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Comparatif d'utilisation --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Utilisation comparée (actions sur {{ $jours }} jours)</h2>
            <p class="mb-4 text-xs text-slate-400">Top 12 des utilisateurs les plus actifs sur la période.</p>
            @if ($comparatif['labels']->isEmpty())
                <p class="py-8 text-center text-sm text-slate-400">Aucune action enregistrée sur cette période.</p>
            @else
                <div class="relative h-72 w-full">
                    <canvas id="graphe-comparatif"></canvas>
                </div>
            @endif
        </div>

        {{-- Table des utilisateurs --}}
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Utilisateur</th>
                        <th class="px-4 py-3 text-right">Contributions</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                        <th class="px-4 py-3">Dernière activité</th>
                        <th class="px-4 py-3">Inscrit</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        @php $contributions = $u->avis_total + $u->entretiens_total + $u->missions_total; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-900">{{ $u->pseudo_public ?? $u->name }}</span>
                                    @if ($u->is_admin)
                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">admin</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-400">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $contributions }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $u->actions_total }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $u->evenements_max_created_at ? \Illuminate\Support\Carbon::parse($u->evenements_max_created_at)->diffForHumans() : '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $u->created_at->translatedFormat('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.show', $u) }}" class="font-medium text-indigo-600 hover:underline">Voir →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Aucun utilisateur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    <script type="application/json" id="comparatif-data">@json($comparatif)</script>
    @vite('resources/js/users.js')
</x-layout>
