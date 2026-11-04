@php
    $ligne = 'flex items-center justify-between rounded-xl px-3 py-3.5 text-base font-semibold text-encre hover:bg-surface';
    $chevron = '<svg class="h-4 w-4 text-encre-doux" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>';
@endphp
{{-- Menu déroulant (mobile et tablette) : grandes zones tactiles, compte en bas. --}}
<nav id="menu-mobile" aria-label="Menu principal"
     class="absolute inset-x-0 top-full hidden max-h-[calc(100dvh-4rem)] overflow-y-auto border-b border-ligne bg-white shadow-xl lg:hidden">
    <div class="mx-auto max-w-6xl px-4 py-3">
        <a href="{{ route('classement.index') }}" class="{{ $ligne }}">Classement {!! $chevron !!}</a>
        <a href="{{ route('classement.index') }}#secteurs" class="{{ $ligne }}">Secteurs {!! $chevron !!}</a>
        <a href="{{ auth()->check() ? route('entreprises.create') : route('login') }}" class="{{ $ligne }}">Proposer une entreprise {!! $chevron !!}</a>

        <div class="mt-3 border-t border-ligne pt-4">
            @auth
                <div class="flex items-center gap-3 px-3 pb-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-doux text-sm font-bold text-encre" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr((string) auth()->user()->pseudo_public, 0, 2)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-semibold text-encre">{{ auth()->user()->pseudo_public }}</p>
                        <p class="truncate text-[13px] text-encre-doux">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                @can('moderer')
                    <a href="{{ route('moderation.index') }}" class="{{ $ligne }}">Administration {!! $chevron !!}</a>
                @endcan
                <a href="{{ route('compte.securite') }}" class="{{ $ligne }}">Mon compte {!! $chevron !!}</a>
                <form method="POST" action="{{ route('logout') }}"
                      data-confirm="Vous allez être déconnecté de votre compte."
                      data-confirm-title="Se déconnecter ?"
                      data-confirm-button="Se déconnecter"
                      data-confirm-icon="warning">
                    @csrf
                    <button type="submit" class="{{ $ligne }} w-full text-rouge">Déconnexion</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-2.5 pb-2">
                    <a href="{{ route('login') }}" class="rounded-xl border-[1.5px] border-ligne px-4 py-3 text-center text-[15px] font-semibold text-encre hover:border-encre">Connexion</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-orange px-4 py-3 text-center text-[15px] font-semibold text-encre hover:brightness-105">S’inscrire</a>
                </div>
            @endauth
        </div>
    </div>
</nav>
