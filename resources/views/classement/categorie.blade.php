@php
    $pageDescription = $intro.' Avis vérifiés et notes fiables pour choisir où postuler en confiance.';
@endphp
<x-layout :title="$titre.' · Avis salariés'" :description="$pageDescription"
          :robots="$entreprises->total() > 0 ? 'index, follow' : 'noindex, follow'">
    <x-schema :data="[
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Classement', 'item' => route('classement.index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $ariane, 'item' => url()->current()],
        ],
    ]" />

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-10">
            <a href="{{ route('classement.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
                ← Tout le classement
            </a>
            <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900">{{ $titre }}</h1>
            <p class="mt-3 max-w-2xl text-slate-600">{{ $intro }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $entreprises->total() }} {{ $entreprises->total() > 1 ? 'entreprises suivies' : 'entreprise suivie' }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-8">
        @include('classement.partials.liste', ['titre' => 'Classement · '.$ariane, 'intro' => 'Les entreprises notées d’abord ; les nouvelles attendent leurs premiers avis.'])

        @include('classement.partials.explorer')
    </div>
</x-layout>
