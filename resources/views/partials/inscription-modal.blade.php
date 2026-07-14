{{-- Modal d'incitation à l'inscription (invités uniquement, déclenché par app.js). --}}
<x-modal id="inscription" title="Rejoignez la communauté">
    <div class="text-center">
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-indigo-50 text-2xl">⭐</div>
        <h3 class="text-lg font-bold text-slate-900">Notez votre entreprise en 30 secondes</h3>
        <p class="mt-1 text-sm text-slate-600">
            Aidez les futurs candidats à savoir où ils mettent les pieds.
            <strong>Anonyme et gratuit.</strong>
        </p>
    </div>

    {{-- Option principale : lien magique (juste l'email) --}}
    <div class="mt-4">
        @include('partials.lien-magique')
    </div>

    {{-- Option secondaire : mot de passe --}}
    <details class="mt-4">
        <summary class="cursor-pointer text-center text-xs text-slate-400 hover:text-slate-600">
            ou créer un compte avec un mot de passe
        </summary>
        <form method="POST" action="{{ route('register') }}" data-inscription-rapide class="mt-3 space-y-3">
            @csrf
            <input type="hidden" name="_source" value="modal">
            <input name="email" type="email" required autocomplete="email" placeholder="Votre email"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            <x-password-input name="password" :required="true" autocomplete="new-password" placeholder="Choisissez un mot de passe" />
            <p data-erreur class="hidden text-sm text-rose-600"></p>
            <button type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-800">
                Créer mon compte
            </button>
        </form>
    </details>

    <p class="mt-4 text-center text-xs text-slate-400">
        Déjà inscrit ? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Se connecter</a>
    </p>
</x-modal>
