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

    <section class="mx-auto max-w-6xl px-4 pt-10 sm:pt-14">
        <a href="{{ route('classement.index') }}" class="text-sm font-medium text-encre-doux underline underline-offset-4 hover:text-encre">Tout le classement</a>
        <h1 class="mt-4 max-w-4xl text-4xl font-extrabold leading-[0.95] tracking-tight text-encre font-stretch-condensed sm:text-6xl">{{ $titre }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-encre-doux">
            {{ $intro }}
            {{ $entreprises->total() }} {{ $entreprises->total() > 1 ? 'entreprises suivies' : 'entreprise suivie' }}.
        </p>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-8">
        @include('classement.partials.liste', ['titre' => 'Classement · '.$ariane, 'intro' => 'Les entreprises notées d’abord ; les nouvelles attendent leurs premiers avis.'])

        @include('classement.partials.explorer')
    </div>
</x-layout>
