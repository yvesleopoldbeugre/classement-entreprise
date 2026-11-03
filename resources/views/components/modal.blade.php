@props(['id' => '', 'title' => ''])
{{-- Fenêtre : feuille qui monte du bas sur mobile, boîte centrée sur grand écran. --}}
<div id="modal-{{ $id }}" data-modal
     class="fixed inset-0 z-40 hidden">
    <div data-modal-backdrop class="absolute inset-0 bg-encre/60"></div>
    <div class="absolute inset-0 flex items-end justify-center overflow-y-auto sm:items-start sm:p-4 sm:py-10">
        <div class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white sm:max-h-none sm:rounded-2xl">
            <div class="mx-auto mt-2.5 h-1 w-10 rounded-full bg-ligne sm:hidden" aria-hidden="true"></div>
            <div class="flex items-center justify-between gap-4 px-5 pb-1 pt-4 sm:px-6 sm:pt-5">
                <h2 class="text-xl font-extrabold tracking-tight text-encre">{{ $title }}</h2>
                <button type="button" data-modal-close
                        class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-encre-doux hover:bg-surface hover:text-encre"
                        aria-label="Fermer">✕</button>
            </div>
            <div class="px-5 pb-6 pt-3 sm:px-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
