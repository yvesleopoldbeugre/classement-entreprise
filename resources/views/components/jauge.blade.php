@props(['label' => '', 'valeur' => null, 'max' => 5])
@php
    $pct = $valeur !== null ? max(0, min(100, ($valeur / $max) * 100)) : 0;
@endphp
<div>
    <div class="mb-1 flex items-baseline justify-between text-sm">
        <span class="text-slate-600">{{ $label }}</span>
        <span class="font-semibold tabular-nums text-slate-900">
            {{ $valeur !== null ? number_format($valeur, 1) : '—' }}
        </span>
    </div>
    <div class="h-2 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $pct }}%"></div>
    </div>
</div>
