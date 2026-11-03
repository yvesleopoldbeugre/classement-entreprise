@props(['nom' => '', 'taille' => 44])
@php
    // Pavé d'initiales : donne une identité visuelle à chaque entreprise sans logo.
    $tons = ['bg-[#e3ecf7]', 'bg-vert-doux', 'bg-orange-doux', 'bg-[#efe6f3]', 'bg-[#e6eef0]', 'bg-ambre-doux'];
    $ton = $tons[array_sum(array_map('ord', str_split($nom))) % count($tons)];
    $initiales = collect(preg_split('/\s+/u', trim($nom)))->filter()->take(2)
        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->implode('');
@endphp
<span {{ $attributes->merge(['class' => "inline-grid shrink-0 place-items-center font-bold text-encre {$ton}"]) }}
      style="width: {{ $taille }}px; height: {{ $taille }}px; border-radius: {{ round($taille * 0.28) }}px; font-size: {{ round($taille * 0.36) }}px"
      aria-hidden="true">{{ $initiales }}</span>
