@props(['note' => null, 'taille' => 'md', 'tracer' => false])
@php
    // Moyenne /5 entourée au stylo, comme sur un bulletin : vert si bonne, rouge si faible.
    $valeur = $note !== null ? (float) $note : null;
    $stylo = match (true) {
        $valeur === null => 'text-regle',
        $valeur >= 3.5 => 'text-stylo-vert',
        $valeur >= 2.8 => 'text-encre-doux',
        default => 'text-stylo-rouge',
    };
    $chiffre = $taille === 'lg' ? 'text-5xl px-5 py-3' : 'text-2xl px-3 py-1.5';
@endphp
<span {{ $attributes->merge(['class' => 'relative inline-flex items-baseline']) }}
      @if ($valeur !== null) title="{{ number_format($valeur, 2, ',', ' ') }} sur 5" @endif>
    <span class="{{ $chiffre }} font-extrabold leading-none tabular-nums text-encre font-stretch-condensed">
        {{ $valeur !== null ? number_format($valeur, 1, ',', ' ') : '–' }}
    </span>
    @if ($valeur !== null)
        <svg viewBox="0 0 110 60" preserveAspectRatio="none" aria-hidden="true"
             class="trait-stylo pointer-events-none absolute inset-0 h-full w-full {{ $stylo }}" @if ($tracer) data-tracer @endif>
            <path d="M14 40C4 28 16 10 46 6c30-4 58 4 60 22 2 18-30 28-58 27C22 54 8 46 10 32 12 22 24 14 36 11"
                  fill="none" stroke="currentColor" stroke-width="{{ $taille === 'lg' ? 2.5 : 3 }}" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
        </svg>
    @endif
</span>
