@props(['label' => '', 'name' => '', 'required' => false, 'hint' => null, 'aide' => null])
<div data-champ>
    <div class="mb-1.5 flex items-center gap-1.5">
        <label for="{{ $name }}" class="block text-[13px] font-semibold text-encre">
            {{ $label }}@if ($required)<span class="text-rouge"> *</span>@endif
        </label>
        @if ($aide)
            <span class="relative inline-flex">
                <button type="button" data-aide-toggle aria-label="Aide sur ce champ"
                        class="grid h-4 w-4 place-items-center rounded-full bg-surface text-[10px] font-bold leading-none text-encre-doux ring-1 ring-ligne hover:text-encre">?</button>
                {{-- Bulle d'info flottante (n'agrandit pas le formulaire) --}}
                <span data-aide-contenu role="tooltip"
                      class="absolute left-1/2 top-full z-30 mt-1.5 hidden w-56 max-w-[70vw] -translate-x-1/2 rounded-xl bg-encre px-2.5 py-1.5 text-xs leading-relaxed text-white shadow-lg">
                    {{ $aide }}
                </span>
            </span>
        @endif
    </div>
    {{ $slot }}
    @if ($hint)
        <p class="mt-1 text-xs text-encre-doux">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs font-medium text-rouge">{{ $message }}</p>
    @enderror
</div>
