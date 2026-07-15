@props(['url', 'texte' => ''])
@php
    $u = urlencode($url);
    $t = urlencode($texte);
    $tPlusUrl = urlencode(trim($texte.' '.$url));

    $reseaux = [
        'WhatsApp' => [
            'href' => "https://wa.me/?text={$tPlusUrl}",
            'classe' => 'bg-[#25D366] hover:brightness-95',
            'icone' => '<path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91C21.95 6.45 17.5 2 12.04 2Zm5.8 14.02c-.24.68-1.42 1.32-1.95 1.36-.5.05-.5.4-3.16-.66-2.66-1.06-4.32-3.8-4.45-3.98-.13-.18-1.06-1.41-1.06-2.69 0-1.28.67-1.9.9-2.17.24-.26.53-.33.7-.33.18 0 .35 0 .5.01.16.01.38-.06.59.45.24.55.8 1.9.87 2.04.07.13.12.29.02.47-.09.18-.14.29-.27.45-.13.16-.28.35-.4.47-.13.13-.27.28-.12.54.16.26.7 1.16 1.51 1.88 1.04.93 1.92 1.22 2.19 1.36.27.13.42.11.58-.07.16-.18.67-.78.85-1.05.18-.26.35-.22.59-.13.24.09 1.52.72 1.78.85.26.13.44.2.5.31.07.11.07.64-.17 1.32Z" fill="#fff"/>',
        ],
        'Facebook' => [
            'href' => "https://www.facebook.com/sharer/sharer.php?u={$u}",
            'classe' => 'bg-[#1877F2] hover:brightness-95',
            'icone' => '<path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.9h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94Z" fill="#fff"/>',
        ],
        'LinkedIn' => [
            'href' => "https://www.linkedin.com/sharing/share-offsite/?url={$u}",
            'classe' => 'bg-[#0A66C2] hover:brightness-95',
            'icone' => '<path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.55V9h3.57v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0Z" fill="#fff"/>',
        ],
        'X' => [
            'href' => "https://twitter.com/intent/tweet?text={$t}&url={$u}",
            'classe' => 'bg-slate-900 hover:brightness-125',
            'icone' => '<path d="M18.9 1.5h3.68l-8.04 9.19L24 22.5h-7.4l-5.8-7.58-6.63 7.58H.49l8.6-9.83L0 1.5h7.59l5.24 6.93L18.9 1.5Zm-1.29 18.79h2.04L6.48 3.6H4.29l13.32 16.69Z" fill="#fff"/>',
        ],
    ];
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <span class="text-sm font-medium text-slate-600">Inviter à donner un avis :</span>

    @foreach ($reseaux as $nom => $r)
        <a href="{{ $r['href'] }}" target="_blank" rel="noopener"
           aria-label="Partager sur {{ $nom }}" title="Partager sur {{ $nom }}"
           class="grid h-9 w-9 place-items-center rounded-full text-white transition {{ $r['classe'] }}">
            <svg viewBox="0 0 24 24" class="h-4 w-4">{!! $r['icone'] !!}</svg>
        </a>
    @endforeach

    <button type="button" data-copier="{{ $url }}"
            aria-label="Copier le lien" title="Copier le lien"
            class="grid h-9 w-9 place-items-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-100">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H15a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3v-1.5M9 18H7.5A3 3 0 0 1 4.5 15V6a3 3 0 0 1 3-3H12a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H9Z"/>
        </svg>
    </button>
</div>
