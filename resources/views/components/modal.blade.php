@props(['id' => '', 'title' => ''])
<div id="modal-{{ $id }}" data-modal
     class="fixed inset-0 z-40 hidden">
    <div data-modal-backdrop class="absolute inset-0 bg-encre/60"></div>
    <div class="absolute inset-0 flex items-start justify-center overflow-y-auto p-4 sm:py-8">
        <div class="relative w-full max-w-md rounded-md border-2 border-encre bg-papier">
            <div class="flex items-center justify-between border-b border-regle px-5 py-3">
                <h2 class="text-xl font-extrabold text-encre font-stretch-condensed">{{ $title }}</h2>
                <button type="button" data-modal-close
                        class="grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-encre"
                        aria-label="Fermer">✕</button>
            </div>
            <div class="px-5 py-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
