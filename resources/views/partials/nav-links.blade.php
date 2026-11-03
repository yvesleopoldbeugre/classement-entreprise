@php
    $sombre = $sombre ?? false;
    $lien = $sombre
        ? 'rounded-xl px-3 py-2 font-medium text-white/85 hover:bg-white/10 hover:text-white'
        : 'rounded-xl px-3 py-2 font-medium text-encre-doux hover:bg-surface hover:text-encre';
@endphp
<a href="{{ route('classement.index') }}" class="{{ $lien }}">Classement</a>
<a href="{{ route('classement.index') }}#secteurs" class="{{ $lien }}">Secteurs</a>
<a href="{{ auth()->check() ? route('entreprises.create') : route('login') }}" class="{{ $lien }}">Proposer une entreprise</a>

@auth
    @can('moderer')
        <a href="{{ route('moderation.index') }}" class="{{ $lien }}">Administration</a>
    @endcan
    <a href="{{ route('compte.securite') }}" class="{{ $lien }}">Mon compte</a>
    <form method="POST" action="{{ route('logout') }}"
          data-confirm="Vous allez être déconnecté de votre compte."
          data-confirm-title="Se déconnecter ?"
          data-confirm-button="Se déconnecter"
          data-confirm-icon="warning">
        @csrf
        <button type="submit" class="{{ $lien }} w-full text-left">Déconnexion</button>
    </form>
    <span class="ml-1 hidden h-9 w-9 place-items-center rounded-full bg-orange-doux text-xs font-bold text-encre md:inline-grid" title="{{ auth()->user()->pseudo_public }}">
        {{ mb_strtoupper(mb_substr((string) auth()->user()->pseudo_public, 0, 2)) }}
    </span>
@else
    <a href="{{ route('login') }}" class="{{ $lien }} font-semibold">Connexion</a>
    <a href="{{ route('register') }}"
       class="rounded-xl px-4 py-2 text-center font-semibold {{ $sombre ? 'bg-orange text-encre hover:brightness-105' : 'bg-encre text-white hover:bg-indigo-800' }}">S’inscrire</a>
@endauth
