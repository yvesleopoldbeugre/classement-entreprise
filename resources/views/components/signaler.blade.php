@props(['type' => 'avis', 'model' => null])
@auth
    @if ($model && auth()->id() !== $model->user_id)
        <form method="POST" action="{{ route('signaler', [$type, $model->id]) }}"
              data-confirm="Signaler ce contenu aux modérateurs ?"
              data-confirm-title="Signaler ?"
              data-confirm-button="Signaler"
              data-confirm-icon="warning">
            @csrf
            <button type="submit" class="text-xs text-slate-400 hover:text-rose-600">Signaler</button>
        </form>
    @endif
@endauth
