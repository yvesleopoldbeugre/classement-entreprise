{{-- Maillage interne : liens vers les pages secteur et commune (découverte par Google). --}}
<nav id="secteurs" aria-label="Explorer le classement" class="grid scroll-mt-24 gap-8 sm:grid-cols-2">
    <div>
        <h2 class="mb-3 text-base font-bold text-encre">Par secteur</h2>
        <ul class="flex flex-wrap gap-2 text-[13px]">
            @foreach ($secteurs as $secteur)
                <li><a href="{{ route('classement.secteur', $secteur->slug()) }}"
                       class="inline-block rounded-full bg-white px-3 py-1.5 font-medium text-encre-doux ring-1 ring-ligne hover:text-encre hover:ring-encre">{{ $secteur->libelle() }}</a></li>
            @endforeach
        </ul>
    </div>
    @if ($communes->isNotEmpty())
        <div>
            <h2 class="mb-3 text-base font-bold text-encre">Par commune</h2>
            <ul class="flex flex-wrap gap-2 text-[13px]">
                @foreach ($communes as $commune)
                    <li><a href="{{ route('classement.commune', \Illuminate\Support\Str::slug($commune)) }}"
                           class="inline-block rounded-full bg-white px-3 py-1.5 font-medium text-encre-doux ring-1 ring-ligne hover:text-encre hover:ring-encre">{{ $commune }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
</nav>
