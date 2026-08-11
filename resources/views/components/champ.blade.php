@props(['label' => '', 'name' => '', 'required' => false, 'hint' => null, 'aide' => null])
<div data-champ>
    <div class="mb-0.5 flex items-center gap-1.5">
        <label for="{{ $name }}" class="block text-xs font-medium text-slate-600">
            {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
        </label>
        @if ($aide)
            <button type="button" data-aide-toggle aria-label="Aide sur ce champ"
                    class="grid h-4 w-4 place-items-center rounded-full bg-slate-200 text-[10px] font-bold leading-none text-slate-500 transition hover:bg-indigo-100 hover:text-indigo-600">?</button>
        @endif
    </div>
    @if ($aide)
        <div data-aide-contenu class="mb-1 hidden rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs leading-relaxed text-indigo-800">{{ $aide }}</div>
    @endif
    {{ $slot }}
    @if ($hint)
        <p class="mt-0.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
