{{-- Maillage interne : liens vers les pages secteur et commune (découverte par Google). --}}
<nav aria-label="Explorer le classement" class="mt-14 grid gap-8 border-t border-regle pt-8 sm:grid-cols-2">
    <div>
        <h2 class="mb-3 text-lg font-bold text-encre">Classement par secteur</h2>
        <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
            @foreach ($secteurs as $secteur)
                <li><a href="{{ route('classement.secteur', $secteur->slug()) }}"
                       class="text-encre underline decoration-regle decoration-2 underline-offset-4 hover:decoration-encre">{{ $secteur->libelle() }}</a></li>
            @endforeach
        </ul>
    </div>
    @if ($communes->isNotEmpty())
        <div>
            <h2 class="mb-3 text-lg font-bold text-encre">Classement par commune</h2>
            <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                @foreach ($communes as $commune)
                    <li><a href="{{ route('classement.commune', \Illuminate\Support\Str::slug($commune)) }}"
                           class="text-encre underline decoration-regle decoration-2 underline-offset-4 hover:decoration-encre">{{ $commune }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
</nav>
