@props([
    'title' => 'Classement des entreprises · Côte d’Ivoire',
    'description' => 'Note ta boîte — le classement des entreprises de Côte d’Ivoire par les avis de celles et ceux qui y travaillent. Avis vérifiés et notes fiables pour choisir où postuler en confiance.',
    'ogImage' => null,       // URL absolue d’une image de partage spécifique à la page (facultatif)
    'ogType' => 'website',   // « article » pour une fiche entreprise, par ex.
    'robots' => 'index, follow', // « noindex, nofollow » sur les pages privées / sans intérêt SEO
    'openModal' => null,
    'sombre' => false,       // en-tête sur fond encre (pages ouvertes par un bandeau sombre)
])
@php
    // Image de partage : celle passée par la page, sinon la carte de marque par défaut (1200×630).
    $shareImage = $ogImage ?: asset('og-image.png');
    $canonical = url()->current();
    // Espace admin : barre latérale commune au lieu de l'en-tête public.
    $espaceAdmin = request()->routeIs('admin.*', 'moderation.*');
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
<body class="min-h-screen bg-white text-encre antialiased" @if ($openModal) data-open-modal="{{ $openModal }}" @endif>
    @if ($espaceAdmin)
        <div class="lg:flex">
        @include('partials.admin-nav')
        <div class="min-w-0 flex-1 bg-surface lg:min-h-screen">
    @else
    <header class="sticky top-0 z-20 {{ $sombre ? 'bg-encre' : 'border-b border-ligne bg-white/95 backdrop-blur' }}">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
            <a href="{{ route('classement.index') }}" class="flex items-center gap-2.5">
                <x-logo />
                <span class="text-lg font-extrabold tracking-tight {{ $sombre ? 'text-white' : 'text-encre' }}">Note ta boîte</span>
            </a>

            {{-- Navigation bureau --}}
            <nav class="hidden items-center gap-1 text-[15px] lg:flex">
                @include('partials.nav-links', ['sombre' => $sombre])
            </nav>

            {{-- Bouton menu (mobile et tablette) : hamburger ↔ croix --}}
            <button type="button" data-menu-toggle aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="menu-mobile"
                    class="group grid h-10 w-10 place-items-center rounded-xl lg:hidden {{ $sombre ? 'text-white hover:bg-white/10' : 'text-encre hover:bg-surface' }}">
                <svg class="h-6 w-6 group-aria-expanded:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
                <svg class="hidden h-6 w-6 group-aria-expanded:block" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        @include('partials.nav-mobile')
    </header>
    @endif

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
                           class="rounded-xl bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">Définir maintenant</a>
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
                                    class="rounded-xl bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">Renvoyer le lien</button>
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
                           class="rounded-xl bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">Connecter mes comptes</a>
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
    @if ($espaceAdmin)
        </div>
        </div>
    @endif

    @unless ($espaceAdmin)
    <footer class="bg-encre">
        <div class="mx-auto grid max-w-6xl gap-3 px-4 py-9 text-sm sm:grid-cols-[1fr_auto]">
            <p class="max-w-xl text-white/80">
                <span class="font-bold text-white">Note ta boîte</span> : les entreprises de Côte d’Ivoire notées par celles et ceux qui y ont travaillé.
            </p>
            <p class="max-w-sm text-xs text-white/60 sm:text-right">Avis relus avant publication. Moyenne pondérée par le nombre d’avis.</p>
        </div>
    </footer>
    @endunless

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
