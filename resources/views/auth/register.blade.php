<x-layout robots="noindex, nofollow" title="Inscription · Note ta boîte">
    @php $input = 'w-full rounded-xl border-[1.5px] border-ligne bg-white px-3.5 py-2.5 text-[15px] outline-none focus:border-encre focus:ring-4 focus:ring-orange/20'; @endphp
    <x-cadre-auth titre="Créer un compte" sous-titre="Pour noter vos entreprises et partager vos entretiens. Votre pseudo est le seul nom affiché.">
        {{-- Connexion sociale : uniquement les fournisseurs réellement configurés --}}
        @php
            $ssoProviders = config('services.sso.enabled')
                ? \App\Http\Controllers\Auth\SocialiteController::providersConfigures()
                : [];
        @endphp
        @if ($ssoProviders)
            <div>
                @include('auth.partials.sso', ['providers' => $ssoProviders])
            </div>

            <div class="my-6 flex items-center gap-3 text-[13px] text-encre-doux">
                <span class="h-px flex-1 bg-ligne"></span>
                ou avec un email
                <span class="h-px flex-1 bg-ligne"></span>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <x-champ label="Email" name="email" :required="true">
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="{{ $input }}" required autocomplete="email">
            </x-champ>
            <x-champ label="Mot de passe" name="password" :required="true">
                <x-password-input name="password" :required="true" autocomplete="new-password" />
            </x-champ>

            {{-- Détails facultatifs : pseudo/nom auto-générés sinon --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-encre-doux hover:text-encre">
                    Personnaliser mon profil <span class="font-normal text-encre-doux">(facultatif)</span>
                </summary>
                <div class="mt-3 space-y-4">
                    <x-champ label="Pseudo public" name="pseudo_public" hint="Affiché sur vos avis. Laissez vide pour un pseudo automatique.">
                        <input id="pseudo_public" name="pseudo_public" type="text" value="{{ old('pseudo_public') }}" class="{{ $input }}">
                    </x-champ>
                    <x-champ label="Nom complet" name="name">
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="{{ $input }}">
                    </x-champ>
                    <x-champ label="Poste actuel" name="poste_actuel">
                        <input id="poste_actuel" name="poste_actuel" type="text" value="{{ old('poste_actuel') }}" class="{{ $input }}">
                    </x-champ>
                </div>
            </details>

            <button type="submit" class="w-full rounded-xl bg-orange px-4 py-3 text-[15px] font-semibold text-encre hover:brightness-105">
                Créer mon compte
            </button>
        </form>

        <p class="mt-6 text-sm text-encre-doux">
            Déjà inscrit ? <a href="{{ route('login') }}" class="font-semibold text-teal hover:underline">Se connecter</a>
        </p>
    </x-cadre-auth>
</x-layout>
