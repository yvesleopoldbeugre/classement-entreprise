<x-layout robots="noindex, nofollow" title="Connexion · Note ta boîte">
    @php $input = 'w-full rounded-xl border-[1.5px] border-ligne bg-white px-3.5 py-2.5 text-[15px] outline-none focus:border-encre focus:ring-4 focus:ring-orange/20'; @endphp
    @php
        $ssoProviders = config('services.sso.enabled')
            ? \App\Http\Controllers\Auth\SocialiteController::providersConfigures()
            : [];
    @endphp
    <x-cadre-auth titre="Connexion" sous-titre="Content de vous revoir.">
        @if ($ssoProviders)
            @include('auth.partials.sso', ['providers' => $ssoProviders])
            <div class="my-6 flex items-center gap-3 text-[13px] text-encre-doux">
                <span class="h-px flex-1 bg-ligne"></span>ou avec votre email<span class="h-px flex-1 bg-ligne"></span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-champ label="Email" name="email" :required="true">
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="{{ $input }}" placeholder="vous@exemple.ci" required autofocus>
            </x-champ>
            <x-champ label="Mot de passe" name="password" :required="true">
                <x-password-input name="password" :required="true" autocomplete="current-password" />
            </x-champ>

            <label class="flex items-center gap-2 text-sm text-encre-doux">
                <input type="checkbox" name="remember" value="1" class="rounded border-ligne text-encre focus:ring-orange">
                Se souvenir de moi
            </label>

            <button type="submit" class="w-full rounded-xl bg-encre px-4 py-3 text-[15px] font-semibold text-white hover:bg-indigo-800">
                Se connecter
            </button>
        </form>

        {{-- Repli : compte sans mot de passe (créé par lien magique) --}}
        <details class="group mt-4 rounded-xl border border-ligne px-4 py-3">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-encre [&::-webkit-details-marker]:hidden">
                Recevoir un lien de connexion par email
                <svg class="h-4 w-4 shrink-0 text-encre-doux transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="mt-3">
                @include('partials.lien-magique')
            </div>
        </details>

        <p class="mt-6 text-sm text-encre-doux">
            Pas encore de compte ? <a href="{{ route('register') }}" class="font-semibold text-teal hover:underline">S’inscrire</a>
        </p>
    </x-cadre-auth>
</x-layout>
