@props(['title' => 'Classement des entreprises · Côte d’Ivoire', 'openModal' => null])
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="Le classement des entreprises de Côte d’Ivoire basé sur les retours de ceux qui y ont travaillé.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" @if ($openModal) data-open-modal="{{ $openModal }}" @endif>
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('classement.index') }}" class="flex items-center gap-2 font-semibold text-slate-900">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-white">★</span>
                <span>Classement<span class="text-indigo-600">CI</span></span>
            </a>

            {{-- Navigation bureau --}}
            <nav class="hidden items-center gap-1 text-sm md:flex">
                @include('partials.nav-links')
            </nav>

            {{-- Bouton menu mobile --}}
            <button type="button" data-menu-toggle aria-label="Ouvrir le menu" aria-expanded="false"
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
        </div>

        {{-- Menu mobile déroulant --}}
        <nav id="menu-mobile" class="hidden border-t border-slate-200 bg-white md:hidden">
            <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm">
                @include('partials.nav-links')
            </div>
        </nav>
    </header>

    @if (session('success'))
        <div class="mx-auto mt-4 max-w-6xl px-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-slate-500">
            <p>Classement<span class="text-indigo-600">CI</span> — les avis sont laissés par la communauté et modérés avant publication.</p>
            <p class="mt-1 text-xs text-slate-400">Score calculé par moyenne bayésienne pour rester fiable même avec peu d’avis.</p>
        </div>
    </footer>
</body>
</html>
