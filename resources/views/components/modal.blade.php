@props(['id' => '', 'title' => ''])
<div id="modal-{{ $id }}" data-modal
     class="fixed inset-0 z-40 hidden">
    <div data-modal-backdrop class="absolute inset-0 bg-slate-900/50"></div>
    <div class="absolute inset-0 flex items-start justify-center overflow-y-auto p-4 sm:py-8">
        <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
                <button type="button" data-modal-close
                        class="grid h-7 w-7 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Fermer">✕</button>
            </div>
            <div class="px-4 py-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
