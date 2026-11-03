@php
    $menu = [
        ['Modération', 'moderation.index', 'moderation.*', 'M12 3l8 3v6c0 4.5-3.5 8-8 9-4.5-1-8-4.5-8-9V6z'],
        ['Statistiques', 'admin.stats.index', 'admin.stats.*', 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
        ['Utilisateurs', 'admin.users.index', 'admin.users.*', 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7.5M22 21a7 7 0 0 0-4-6.3'],
        ['En direct', 'admin.live.index', 'admin.live.*', 'M13 2 4 14h7l-1 8 9-12h-7z'],
        ['Ajouter une entreprise', 'entreprises.create', 'entreprises.create', 'M12 5v14M5 12h14'],
        ['Voir le site', 'classement.index', 'classement.index', 'M4 11l8-7 8 7v9h-5v-6H9v6H4z'],
    ];
@endphp
{{-- Barre latérale de l'espace admin (en haut, défilante, sur mobile). --}}
<aside class="bg-encre text-white lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0">
    <div class="flex items-center justify-between gap-3 px-4 py-4 lg:px-6 lg:py-6">
        <a href="{{ route('moderation.index') }}" class="flex items-center gap-2.5">
            <x-logo />
            <span class="text-base font-extrabold tracking-tight">Administration</span>
        </a>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3 text-sm lg:flex-col lg:px-3">
        @foreach ($menu as [$libelle, $route, $actif, $icone])
            <a href="{{ route($route) }}"
               class="flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs($actif) ? 'bg-white/10 text-white' : 'text-white/75 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                {{ $libelle }}
            </a>
        @endforeach
        <form method="POST" action="{{ route('logout') }}" class="shrink-0 lg:mt-6">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 font-medium text-white/60 hover:bg-white/5 hover:text-white">Déconnexion</button>
        </form>
    </nav>
</aside>
