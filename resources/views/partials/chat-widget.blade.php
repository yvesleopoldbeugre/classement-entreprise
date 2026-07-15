{{-- Widget de chat visiteur (bulle + panneau), piloté par resources/js/chat.js. --}}
<div id="chat-widget"
     data-heartbeat-url="{{ route('presence') }}"
     data-ouvrir-url="{{ route('chat.ouvrir') }}"
     data-message-url="{{ route('chat.message') }}"
     data-messages-url="{{ route('chat.messages') }}"
     class="fixed bottom-4 right-4 z-40 print:hidden">

    {{-- Panneau --}}
    <div data-chat-panel
         class="mb-3 hidden h-[28rem] w-80 max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between bg-indigo-600 px-4 py-3 text-white">
            <div>
                <p class="text-sm font-semibold">Assistant Note ta boîte</p>
                <p class="text-xs text-indigo-200">Une question ? On vous répond ici.</p>
            </div>
            <button type="button" data-chat-close aria-label="Fermer" class="text-indigo-200 hover:text-white">✕</button>
        </div>

        <div data-chat-messages class="flex-1 space-y-2 overflow-y-auto bg-slate-50 p-3 text-sm"></div>

        <form data-chat-form class="flex items-center gap-2 border-t border-slate-100 p-2">
            <input data-chat-input type="text" required maxlength="2000" autocomplete="off" placeholder="Votre message…"
                   class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            <button type="submit" aria-label="Envoyer"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">➤</button>
        </form>
    </div>

    {{-- Bulle --}}
    <button type="button" data-chat-toggle aria-label="Ouvrir le chat"
            class="relative grid h-14 w-14 place-items-center rounded-full bg-indigo-600 text-2xl text-white shadow-lg transition hover:bg-indigo-700">
        💬
        <span data-chat-badge
              class="absolute -right-1 -top-1 hidden min-w-[1.25rem] rounded-full bg-rose-500 px-1 text-center text-xs font-bold leading-5">0</span>
    </button>
</div>

@vite('resources/js/chat.js')
