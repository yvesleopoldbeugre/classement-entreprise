<x-layout robots="noindex, nofollow" title="Sécurité du compte · ClassementCI">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Sécurité du compte</h1>
            <p class="mt-1 text-sm text-slate-500">Gérez votre email, votre mot de passe et les appareils connectés à votre compte.</p>
        </div>

        {{-- Statut de vérification de l'email — persistant tant que non vérifié --}}
        @php $emailVerifie = auth()->user()->hasVerifiedEmail(); @endphp
        <div class="mb-6 rounded-2xl border p-6 {{ $emailVerifie ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold {{ $emailVerifie ? 'text-emerald-800' : 'text-amber-800' }}">
                        {{ $emailVerifie ? '✓ Email vérifié' : '✉️ Email non vérifié' }}
                    </h2>
                    <p class="mt-1 text-sm {{ $emailVerifie ? 'text-emerald-700' : 'text-amber-700' }}">
                        @if ($emailVerifie)
                            <span class="font-medium">{{ auth()->user()->email }}</span> est vérifié — vos avis comptent au maximum.
                        @else
                            Vérifiez <span class="font-medium">{{ auth()->user()->email }}</span> pour que vos avis pèsent davantage dans le classement.
                        @endif
                    </p>
                </div>
                @unless ($emailVerifie)
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="shrink-0 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            Renvoyer le lien
                        </button>
                    </form>
                @endunless
            </div>
        </div>

        {{-- Mot de passe : définir (compte lien magique / SSO) ou changer --}}
        @php $aMotDePasse = ! is_null(auth()->user()->password); @endphp
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">{{ $aMotDePasse ? 'Changer mon mot de passe' : 'Définir un mot de passe' }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $aMotDePasse
                    ? 'Choisissez un nouveau mot de passe pour votre compte.'
                    : 'Votre compte se connecte par lien magique. Définissez un mot de passe pour pouvoir aussi vous connecter classiquement.' }}
            </p>

            <form method="POST" action="{{ route('compte.securite.mot-de-passe') }}" class="mt-4 space-y-3">
                @csrf
                @if ($aMotDePasse)
                    <div>
                        <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Mot de passe actuel</label>
                        <x-password-input name="current_password" autocomplete="current-password" />
                        @error('current_password', 'motDePasse')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Nouveau mot de passe</label>
                    <x-password-input name="password" autocomplete="new-password" />
                    @error('password', 'motDePasse')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirmer le nouveau mot de passe</label>
                    <x-password-input name="password_confirmation" autocomplete="new-password" />
                </div>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-700">
                    {{ $aMotDePasse ? 'Mettre à jour' : 'Définir le mot de passe' }}
                </button>
            </form>
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
