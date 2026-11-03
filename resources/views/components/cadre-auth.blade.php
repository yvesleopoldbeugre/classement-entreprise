@props(['titre', 'sousTitre' => null])
@php
    $nbAvis = \App\Models\AvisEntreprise::publie()->count();
    $nbEntreprises = \App\Models\Entreprise::count();
@endphp
{{-- Écran partagé des pages de compte : promesse à gauche (grand écran), formulaire à droite. --}}
<div class="grid lg:min-h-[calc(100vh-65px)] lg:grid-cols-2">
    <section class="hidden flex-col justify-between bg-encre px-16 py-14 text-white lg:flex">
        <x-logo />
        <div>
            <p class="max-w-md text-3xl font-bold leading-tight">Les avis qui comptent, avant de signer.</p>
            <ul class="mt-8 space-y-4 text-base text-white/80">
                @foreach (['Notes sur l’ambiance, le management, le salaire et l’évolution.', 'Avis anonymes, relus avant publication.', 'Retours d’entretien et de mission pour savoir à quoi vous attendre.'] as $point)
                    <li class="flex gap-3">
                        <svg class="mt-1 h-4 w-4 shrink-0 text-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>
        <p class="text-sm text-white/60">{{ $nbAvis }} avis publiés, {{ $nbEntreprises }} entreprises suivies.</p>
    </section>

    <section class="flex items-start justify-center px-4 py-10 sm:py-16 lg:items-center">
        <div class="w-full max-w-md">
            <h1 class="text-3xl font-extrabold tracking-tight text-encre sm:text-4xl">{{ $titre }}</h1>
            @if ($sousTitre)<p class="mt-2 text-base text-encre-doux">{{ $sousTitre }}</p>@endif
            <div class="mt-7">
                {{ $slot }}
            </div>
        </div>
    </section>
</div>
