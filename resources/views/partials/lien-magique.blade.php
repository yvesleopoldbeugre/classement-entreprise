{{-- Formulaire « lien magique » (inscription/connexion sans mot de passe). Réutilisable. --}}
<form method="POST" action="{{ route('magic.send') }}" data-magic-form class="space-y-3">
    @csrf
    <input name="email" type="email" required autocomplete="email" placeholder="Votre email"
           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
    <p data-magic-erreur class="hidden text-sm text-rose-600"></p>
    <button type="submit"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-700">
        Recevoir mon lien de connexion
    </button>
</form>
<div data-magic-succes class="hidden rounded-lg bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-700">
    📩 Lien envoyé ! Ouvrez votre boîte mail pour vous connecter <span class="text-emerald-600/70">(pensez aux spams).</span>
</div>
