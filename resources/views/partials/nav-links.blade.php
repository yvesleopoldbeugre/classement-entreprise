@php $lien = 'rounded-md px-3 py-2 font-medium text-encre-doux hover:bg-indigo-50 hover:text-encre'; @endphp
<a href="{{ route('classement.index') }}" class="{{ $lien }}">Classement</a>

@auth
    @can('moderer')
        <a href="{{ route('entreprises.create') }}" class="{{ $lien }}">+ Ajouter</a>
        <a href="{{ route('admin.live.index') }}" class="{{ $lien }}">En direct</a>
        <a href="{{ route('admin.stats.index') }}" class="{{ $lien }}">Statistiques</a>
        <a href="{{ route('admin.users.index') }}" class="{{ $lien }}">Utilisateurs</a>
        <a href="{{ route('moderation.index') }}" class="{{ $lien }}">Modération</a>
    @endcan
    <a href="{{ route('compte.securite') }}" class="{{ $lien }}">Mon compte</a>
    <span class="px-3 py-2 text-sm text-encre-doux/70">{{ auth()->user()->pseudo_public }}</span>
    <form method="POST" action="{{ route('logout') }}"
          data-confirm="Vous allez être déconnecté de votre compte."
          data-confirm-title="Se déconnecter ?"
          data-confirm-button="Se déconnecter"
          data-confirm-icon="warning">
        @csrf
        <button type="submit" class="{{ $lien }} w-full text-left">Déconnexion</button>
    </form>
@else
    <a href="{{ route('login') }}" class="{{ $lien }}">Connexion</a>
    <a href="{{ route('register') }}"
       class="rounded-md border border-encre px-3 py-2 text-center font-semibold text-encre hover:bg-encre hover:text-papier">S’inscrire</a>
@endauth
