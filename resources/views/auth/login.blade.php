<x-layout robots="noindex, nofollow" title="Connexion · ClassementCI">
    @php $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100'; @endphp
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Connexion</h1>
            <p class="mt-1 text-sm text-slate-500">Content de vous revoir.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <x-champ label="Email" name="email" :required="true">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="{{ $input }}" required autofocus>
                </x-champ>
                <x-champ label="Mot de passe" name="password" :required="true">
                    <x-password-input name="password" :required="true" autocomplete="current-password" />
                </x-champ>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Se souvenir de moi
                </label>

                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Se connecter
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-500">
                Pas encore de compte ? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:underline">S’inscrire</a>
            </p>

            {{-- Repli discret : compte sans mot de passe (créé par lien magique) --}}
            <details class="mt-3">
                <summary class="cursor-pointer text-center text-xs text-slate-400 hover:text-slate-600">
                    Je n’ai pas de mot de passe
                </summary>
                <div class="mt-3">
                    <p class="mb-2 text-xs text-slate-500">Recevez un lien de connexion par email :</p>
                    @include('partials.lien-magique')
                </div>
            </details>
        </div>
    </div>
</x-layout>
