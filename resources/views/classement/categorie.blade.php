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

    <section class="border-b border-ligne bg-white">
        <div class="mx-auto max-w-6xl px-4 py-10 sm:py-14">
            <a href="{{ route('classement.index') }}" class="text-sm font-semibold text-teal hover:underline">Tout le classement</a>
            <h1 class="mt-3 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight text-encre sm:text-5xl">{{ $titre }}</h1>
            <p class="mt-3 max-w-2xl text-base text-encre-doux sm:text-lg">
                {{ $intro }}
                {{ $entreprises->total() }} {{ $entreprises->total() > 1 ? 'entreprises suivies' : 'entreprise suivie' }}.
            </p>
        </div>
    </section>

    <div class="bg-surface">
        <div class="mx-auto max-w-6xl space-y-10 px-4 py-8 sm:py-12">
            <section class="rounded-2xl border border-ligne bg-white p-5 sm:p-6">
                @include('classement.partials.liste', ['titre' => 'Classement · '.$ariane, 'intro' => 'Les entreprises notées d’abord ; les nouvelles attendent leurs premiers avis.'])
            </section>

            @include('classement.partials.explorer')
        </div>
    </div>
</x-layout>
