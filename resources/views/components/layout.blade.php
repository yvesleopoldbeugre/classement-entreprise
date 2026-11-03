@props([
    'title' => 'Classement des entreprises · Côte d’Ivoire',
    'description' => 'Note ta boîte — le classement des entreprises de Côte d’Ivoire par les avis de celles et ceux qui y travaillent. Avis vérifiés et notes fiables pour choisir où postuler en confiance.',
    'ogImage' => null,       // URL absolue d’une image de partage spécifique à la page (facultatif)
    'ogType' => 'website',   // « article » pour une fiche entreprise, par ex.
    'robots' => 'index, follow', // « noindex, nofollow » sur les pages privées / sans intérêt SEO
    'openModal' => null,
])
@php
    // Image de partage : celle passée par la page, sinon la carte de marque par défaut (1200×630).
    $shareImage = $ogImage ?: asset('og-image.png');
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#4f46e5">
    @if (config('services.google_site_verification'))
        <meta name="google-site-verification" content="{{ config('services.google_site_verification') }}">
    @endif

    {{-- Open Graph (Facebook, LinkedIn, WhatsApp, Slack, iMessage…) --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:secure_url" content="{{ $shareImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('app.name') }}">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <meta name="twitter:image:alt" content="{{ config('app.name') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-papier text-encre antialiased" @if ($openModal) data-open-modal="{{ $openModal }}" @endif>
    <header class="sticky top-0 z-20 border-b border-regle bg-papier/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('classement.index') }}" class="text-xl font-extrabold tracking-tight text-encre font-stretch-condensed">
                Note ta boîte
            </a>

            {{-- Navigation bureau --}}
            <nav class="hidden items-center gap-1 text-sm md:flex">
                @include('partials.nav-links')
            </nav>

            {{-- Bouton menu mobile --}}
            <button type="button" data-menu-toggle aria-label="Ouvrir le menu" aria-expanded="false"
                    class="rounded-md p-2 text-encre hover:bg-indigo-50 md:hidden">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
        </div>

        {{-- Menu mobile déroulant --}}
        <nav id="menu-mobile" class="hidden border-t border-regle bg-papier md:hidden">
            <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm">
                @include('partials.nav-links')
            </div>
        </nav>
    </header>

    {{-- Bannières compte : définir un mot de passe (comptes lien magique/SSO)
         OU vérifier son email (comptes mot de passe non vérifiés → poids des avis ↑). --}}
    @auth
        @if (is_null(auth()->user()->password))
            <div data-banniere-mdp class="border-b border-amber-200 bg-amber-50">
                <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                    <p class="text-amber-800">
                        🔑 <strong>Définissez un mot de passe</strong> pour vous reconnecter facilement la prochaine fois.
                    </p>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('compte.securite') }}"
                           class="rounded-md bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">Définir maintenant</a>
                        <button type="button" data-banniere-fermer aria-label="Masquer"
                                class="px-1 text-amber-500 hover:text-amber-700">✕</button>
                    </div>
                </div>
            </div>
        @elseif (! auth()->user()->hasVerifiedEmail())
            <div data-banniere-mdp class="border-b border-amber-200 bg-amber-50">
                <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                    <p class="text-amber-800">
                        ✉️ <strong>Vérifiez votre email</strong> pour que vos avis comptent davantage dans le classement.
                    </p>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-md bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">Renvoyer le lien</button>
                        </form>
                        <button type="button" data-banniere-fermer aria-label="Masquer"
                                class="px-1 text-amber-500 hover:text-amber-700">✕</button>
                    </div>
                </div>
            </div>
        @elseif (config('services.sso.enabled') && ! auth()->user()->linkedin_verifie)
            <div data-banniere-mdp class="border-b border-indigo-200 bg-indigo-50">
                <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                    <p class="text-indigo-800">
                        🔒 <strong>Renforcez la crédibilité de vos avis</strong> en connectant vos comptes (LinkedIn, Google…).
                    </p>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('compte.securite') }}"
                           class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">Connecter mes comptes</a>
                        <button type="button" data-banniere-fermer aria-label="Masquer"
                                class="px-1 text-indigo-400 hover:text-indigo-700">✕</button>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    {{-- Messages flash → toast SweetAlert (voir app.js) --}}
    @php $flashType = collect(['success', 'warning', 'error', 'info'])->first(fn ($k) => session()->has($k)); @endphp
    @if ($flashType)
        <div id="flash-message" data-type="{{ $flashType }}" hidden>{{ session($flashType) }}</div>
    @endif

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-20 border-t border-regle">
        <div class="mx-auto grid max-w-6xl gap-2 px-4 py-10 text-sm text-encre-doux sm:grid-cols-[1fr_auto]">
            <p class="max-w-xl">
                <span class="font-bold text-encre">Note ta boîte</span> classe les entreprises de Côte d’Ivoire
                d’après les avis de celles et ceux qui y ont travaillé. Chaque avis est relu avant publication.
            </p>
            <p class="max-w-xs text-xs sm:text-right">La moyenne tient compte du nombre d’avis : une entreprise ne monte pas sur un seul avis élogieux.</p>
        </div>
    </footer>

    {{-- Modal d'incitation à l'inscription (invités, hors pages d'authentification) --}}
    @guest
        @unless (request()->routeIs('login', 'register'))
            @include('partials.inscription-modal')
        @endunless
    @endguest

    {{-- Chat visiteur + présence temps réel (hors espace admin/modération) --}}
    @unless (request()->routeIs('admin.*') || request()->routeIs('moderation.*'))
        @include('partials.chat-widget')
    @endunless
</body>
</html>
