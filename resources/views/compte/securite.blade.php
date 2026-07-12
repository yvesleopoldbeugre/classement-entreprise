<x-layout title="Sécurité du compte · ClassementCI">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Sécurité du compte</h1>
            <p class="mt-1 text-sm text-slate-500">Gérez les appareils actuellement connectés à votre compte.</p>
        </div>

        {{-- Sessions actives --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Appareils connectés</h2>

            @unless ($driverSupporte)
                <p class="mt-4 text-sm text-slate-500">La liste des sessions n’est disponible qu’avec le stockage de session en base de données.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($sessions as $s)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-slate-800">{{ $s->appareil }}</span>
                                    @if ($s->actuelle)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">appareil actuel</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs text-slate-400">
                                    IP {{ $s->ip ?? '—' }} · dernière activité {{ $s->derniere_activite->diffForHumans() }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-slate-400">Aucune session active.</li>
                    @endforelse
                </ul>
            @endunless
        </div>

        {{-- Déconnexion des autres appareils --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Se déconnecter des autres appareils</h2>
            <p class="mt-1 text-sm text-slate-500">
                Déconnecte toutes les sessions sauf celle-ci. Saisissez votre mot de passe pour confirmer.
            </p>

            <form method="POST" action="{{ route('compte.securite.deconnecter-autres') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                @csrf
                <div class="flex-1">
                    <x-password-input name="password" placeholder="Votre mot de passe" autocomplete="current-password" />
                    @error('password')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-800">
                    Déconnecter les autres
                </button>
            </form>
        </div>
    </div>
</x-layout>
