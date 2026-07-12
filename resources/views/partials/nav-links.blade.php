@php $lien = 'rounded-lg px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900'; @endphp
<a href="{{ route('classement.index') }}" class="{{ $lien }}">Classement</a>

@auth
    @can('moderer')
        <a href="{{ route('admin.stats.index') }}" class="{{ $lien }}">Statistiques</a>
        <a href="{{ route('admin.users.index') }}" class="{{ $lien }}">Utilisateurs</a>
        <a href="{{ route('moderation.index') }}" class="{{ $lien }}">Modération</a>
    @endcan
    <a href="{{ route('compte.securite') }}" class="{{ $lien }}">Sécurité</a>
    <span class="px-3 py-2 text-sm text-slate-400">{{ auth()->user()->pseudo_public }}</span>
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
       class="rounded-lg bg-indigo-600 px-3 py-2 text-center font-semibold text-white hover:bg-indigo-700">S’inscrire</a>
@endauth
