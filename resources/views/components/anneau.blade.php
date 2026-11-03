@props(['note' => null, 'taille' => 48, 'tracer' => false])
@php
    // Anneau de score /5 : se remplit selon la note, vert / ambre / rouge (maquette « Refonte moderne »).
    $valeur = $note !== null ? (float) $note : null;
    $couleur = match (true) {
        $valeur === null => 'text-ligne',
        $valeur >= 3.5 => 'text-vert',
        $valeur >= 2.8 => 'text-ambre',
        default => 'text-rouge',
    };
    $pct = $valeur !== null ? round(max(0, min(5, $valeur)) / 5 * 100, 1) : 0;
@endphp
<span {{ $attributes->merge(['class' => 'anneau relative inline-grid shrink-0 place-items-center']) }}
      style="width: {{ $taille }}px; height: {{ $taille }}px"
      @if ($tracer) data-tracer @endif
      @if ($valeur !== null) title="{{ number_format($valeur, 2, ',', ' ') }} sur 5" @endif>
    <svg viewBox="0 0 36 36" class="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
        <circle cx="18" cy="18" r="15.9155" fill="none" class="stroke-ligne" stroke-width="3.4"/>
        @if ($valeur !== null)
            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round"
                    stroke-dasharray="{{ $pct }} 100" class="anneau-valeur {{ $couleur }}"/>
        @endif
    </svg>
    <span class="relative font-extrabold tabular-nums {{ $valeur !== null ? 'text-encre' : 'text-encre-doux' }}"
          style="font-size: {{ round($taille * 0.3) }}px">{{ $valeur !== null ? number_format($valeur, 1, ',', ' ') : '–' }}</span>
</span>
