@props(['label' => '', 'name' => '', 'required' => false, 'hint' => null, 'aide' => null])
<div data-champ>
    <div class="mb-0.5 flex items-center gap-1.5">
        <label for="{{ $name }}" class="block text-xs font-medium text-slate-600">
            {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
        </label>
        @if ($aide)
            <span class="relative inline-flex">
                <button type="button" data-aide-toggle aria-label="Aide sur ce champ"
                        class="grid h-4 w-4 place-items-center rounded-full bg-slate-200 text-[10px] font-bold leading-none text-slate-500 transition hover:bg-indigo-100 hover:text-indigo-600">?</button>
                {{-- Bulle d'info flottante (n'agrandit pas le formulaire) --}}
                <span data-aide-contenu role="tooltip"
                      class="absolute left-1/2 top-full z-30 mt-1.5 hidden w-56 max-w-[70vw] -translate-x-1/2 rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs leading-relaxed text-white shadow-lg">
                    {{ $aide }}
                </span>
            </span>
        @endif
    </div>
    {{ $slot }}
    @if ($hint)
        <p class="mt-0.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
