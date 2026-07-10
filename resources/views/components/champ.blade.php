@props(['label' => '', 'name' => '', 'required' => false, 'hint' => null])
<div>
    <label for="{{ $name }}" class="mb-0.5 block text-xs font-medium text-slate-600">
        {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p class="mt-0.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
