<x-layout title="Inscription · ClassementCI">
    @php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Créer un compte</h1>
            <p class="mt-1 text-sm text-slate-500">Pour partager vos avis et retours d’expérience.</p>

            {{-- Connexion sociale (masquée si SSO désactivé) --}}
            @if (config('services.sso.enabled'))
                <div class="mt-6">
                    @include('auth.partials.sso')
                </div>

                <div class="my-6 flex items-center gap-3 text-xs text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    ou avec un email
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <x-champ label="Nom complet" name="name" :required="true">
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="{{ $input }}" required>
                </x-champ>
                <x-champ label="Pseudo public" name="pseudo_public" :required="true" hint="Affiché sur vos avis. Lettres, chiffres, tirets.">
                    <input id="pseudo_public" name="pseudo_public" type="text" value="{{ old('pseudo_public') }}" class="{{ $input }}" required>
                </x-champ>
                <x-champ label="Email" name="email" :required="true">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="{{ $input }}" required>
                </x-champ>
                <x-champ label="Poste actuel" name="poste_actuel">
                    <input id="poste_actuel" name="poste_actuel" type="text" value="{{ old('poste_actuel') }}" class="{{ $input }}">
                </x-champ>
                <x-champ label="Mot de passe" name="password" :required="true">
                    <x-password-input name="password" :required="true" autocomplete="new-password" />
                </x-champ>
                <x-champ label="Confirmer le mot de passe" name="password_confirmation" :required="true">
                    <x-password-input name="password_confirmation" :required="true" autocomplete="new-password" />
                </x-champ>

                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Créer mon compte
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-500">
                Déjà inscrit ? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Se connecter</a>
            </p>
        </div>
    </div>
</x-layout>
