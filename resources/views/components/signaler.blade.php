@props(['type' => 'avis', 'model' => null])
@php
    $motifs = [
        'Faux ou trompeur' => 'Faux ou trompeur',
        'Diffamatoire ou injurieux' => 'Diffamatoire ou injurieux',
        'Hors sujet' => 'Hors sujet',
        'Spam ou publicité' => 'Spam ou publicité',
        'Autre' => 'Autre',
    ];
@endphp
@auth
    @if ($model && auth()->id() !== $model->user_id)
        <form method="POST" action="{{ route('signaler', [$type, $model->id]) }}"
              data-confirm="Indiquez le motif ; cela aide les modérateurs."
              data-confirm-title="Signaler ce contenu ?"
              data-confirm-button="Signaler"
              data-confirm-icon="warning"
              data-confirm-select-name="motif"
              data-confirm-select-placeholder="Choisir un motif…"
              data-confirm-select="{{ json_encode($motifs) }}">
            @csrf
            <input type="hidden" name="motif">
            <button type="submit" class="text-xs text-slate-400 hover:text-rose-600">Signaler</button>
        </form>
    @endif
@endauth
