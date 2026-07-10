<div class="mt-3 flex gap-2">
    <form method="POST" action="{{ route('moderation.publier', [$type, $id]) }}">
        @csrf
        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Publier</button>
    </form>
    <form method="POST" action="{{ route('moderation.retirer', [$type, $id]) }}">
        @csrf
        <button type="submit" class="rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">Retirer</button>
    </form>
</div>
