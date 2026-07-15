<x-layout robots="noindex, nofollow" title="Visiteurs en direct · ClassementCI">
    <div id="live"
         data-visiteurs-url="{{ route('admin.live.visiteurs') }}"
         data-demarrer-url="{{ route('admin.live.demarrer') }}"
         data-conv-base="{{ url('/admin/live/conversations') }}"
         class="mx-auto max-w-6xl px-4 py-8">

        <div class="mb-6 flex items-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
            </span>
            <h1 class="text-2xl font-bold text-slate-900">Visiteurs en direct</h1>
            <span data-live-count class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-sm font-semibold text-emerald-700">0</span>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_1.3fr]">
            {{-- Liste des visiteurs en ligne --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                <ul data-live-liste class="space-y-1"></ul>
                <p data-live-liste-vide class="py-10 text-center text-sm text-slate-400">Aucun visiteur en ligne pour le moment.</p>
            </div>

            {{-- Conversation --}}
            <div class="flex h-[32rem] flex-col rounded-2xl border border-slate-200 bg-white">
                <div data-live-entete class="hidden border-b border-slate-100 px-4 py-3">
                    <p data-live-nom class="text-sm font-semibold text-slate-900"></p>
                    <p data-live-page class="truncate text-xs text-slate-400"></p>
                </div>
                <div data-live-messages class="flex-1 space-y-2 overflow-y-auto bg-slate-50 p-3 text-sm"></div>
                <div data-live-vide class="flex flex-1 items-center justify-center p-6 text-center text-sm text-slate-400">
                    Sélectionnez un visiteur à gauche pour lui écrire.
                </div>
                <form data-live-form class="hidden items-center gap-2 border-t border-slate-100 p-2">
                    <input data-live-input type="text" required maxlength="2000" autocomplete="off" placeholder="Votre réponse…"
                           class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Envoyer</button>
                </form>
            </div>
        </div>
    </div>

    @vite('resources/js/live.js')
</x-layout>
