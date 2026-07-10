@props(['note' => 0, 'max' => 5])
@php
    $valeur = (float) $note;
    $pct = max(0, min(100, ($valeur / $max) * 100));
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}
      title="{{ number_format($valeur, 1) }} / {{ $max }}">
    <span class="relative inline-block whitespace-nowrap tracking-[0.15em] leading-none">
        <span class="text-slate-300">★★★★★</span>
        <span class="absolute inset-0 overflow-hidden text-amber-400" style="width: {{ $pct }}%">★★★★★</span>
    </span>
</span>
