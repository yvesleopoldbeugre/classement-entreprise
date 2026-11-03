{{-- Maillage interne : liens vers les pages secteur et commune (découverte par Google). --}}
<section class="mt-10 grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 sm:grid-cols-2">
    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Par secteur</h2>
        <div class="flex flex-wrap gap-2">
            @foreach ($secteurs as $secteur)
                <a href="{{ route('classement.secteur', $secteur->slug()) }}"
                   class="rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-700 hover:border-indigo-300 hover:text-indigo-700">{{ $secteur->libelle() }}</a>
            @endforeach
        </div>
    </div>
    @if ($communes->isNotEmpty())
        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Par commune</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($communes as $commune)
                    <a href="{{ route('classement.commune', \Illuminate\Support\Str::slug($commune)) }}"
                       class="rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-700 hover:border-indigo-300 hover:text-indigo-700">{{ $commune }}</a>
                @endforeach
            </div>
        </div>
    @endif
</section>
