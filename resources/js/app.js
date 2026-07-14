import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// =============================================================
//  Messages flash → toast SweetAlert
// =============================================================
const flash = document.getElementById('flash-message');
if (flash && flash.textContent.trim() !== '') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: flash.dataset.type || 'success',
        title: flash.textContent.trim(),
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        customClass: { popup: 'swal-compact' },
    });
}

// =============================================================
//  Confirmation (SweetAlert) sur les formulaires [data-confirm]
// =============================================================
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

    e.preventDefault();

    // Optionnel : un select (ex. motif de signalement) dont la valeur est injectée
    // dans un champ caché du formulaire avant envoi.
    const options = form.dataset.confirmSelect ? JSON.parse(form.dataset.confirmSelect) : null;

    Swal.fire({
        title: form.dataset.confirmTitle || 'Confirmer',
        text: form.dataset.confirm,
        icon: form.dataset.confirmIcon || 'question',
        input: options ? 'select' : undefined,
        inputOptions: options || undefined,
        inputPlaceholder: form.dataset.confirmSelectPlaceholder || 'Choisir…',
        inputValidator: options ? (valeur) => (! valeur ? 'Veuillez choisir une option.' : undefined) : undefined,
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Confirmer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        customClass: { popup: 'swal-compact' },
    }).then((resultat) => {
        if (! resultat.isConfirmed) return;

        const nomChamp = form.dataset.confirmSelectName;
        if (options && nomChamp) {
            let champ = form.querySelector(`input[name="${nomChamp}"]`);
            if (! champ) {
                champ = document.createElement('input');
                champ.type = 'hidden';
                champ.name = nomChamp;
                form.appendChild(champ);
            }
            champ.value = resultat.value;
        }

        // form.submit() ne redéclenche pas l'événement 'submit' → pas de boucle.
        marquerEnvoi(form);
        form.submit();
    });
});

// =============================================================
//  État « envoi en cours » sur les boutons de soumission
// =============================================================
const spinnerSvg =
    '<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">' +
    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path></svg>';

const marquerEnvoi = (form) => {
    const bouton = form.querySelector('button[type="submit"], button:not([type])');
    if (! bouton || bouton.dataset.loading === '1') return;
    bouton.dataset.loading = '1';
    bouton.disabled = true;
    bouton.classList.add('cursor-not-allowed', 'opacity-70');
    bouton.innerHTML =
        `<span class="inline-flex items-center justify-center gap-2">${spinnerSvg}${bouton.dataset.loadingText || 'Envoi…'}</span>`;
};

// =============================================================
//  Menu de navigation mobile
// =============================================================
const menuToggle = document.querySelector('[data-menu-toggle]');
const menuMobile = document.getElementById('menu-mobile');
menuToggle?.addEventListener('click', () => {
    const ouvert = menuMobile?.classList.toggle('hidden') === false;
    menuToggle.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
});

// =============================================================
//  Modals (formulaires de contribution)
// =============================================================
const ouvrirModal = (id) => {
    const modal = document.getElementById('modal-' + id);
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    modal.querySelector('input, select, textarea, button')?.focus();
};

const fermerModal = (modal) => {
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

const fermerTous = () =>
    document.querySelectorAll('[data-modal]:not(.hidden)').forEach(fermerModal);

document.addEventListener('click', (e) => {
    const declencheur = e.target.closest('[data-modal-open]');
    if (declencheur) {
        e.preventDefault();
        ouvrirModal(declencheur.dataset.modalOpen);
        return;
    }
    if (e.target.closest('[data-modal-close]') || e.target.matches('[data-modal-backdrop]')) {
        const modal = e.target.closest('[data-modal]');
        if (modal) fermerModal(modal);
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') fermerTous();
});

// Réouverture automatique après une erreur de validation (redirigé vers la fiche).
const modalAuto = document.body.dataset.openModal;
if (modalAuto) ouvrirModal(modalAuto);

// =============================================================
//  Voir / masquer le mot de passe
// =============================================================
document.addEventListener('click', (e) => {
    const bouton = e.target.closest('[data-toggle-password]');
    if (!bouton) return;
    const champ = bouton.parentElement.querySelector('input');
    if (!champ) return;
    const afficher = champ.type === 'password';
    champ.type = afficher ? 'text' : 'password';
    bouton.querySelectorAll('[data-eye]').forEach((icone) => icone.classList.toggle('hidden'));
    bouton.setAttribute('aria-label', afficher ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
});

// =============================================================
//  Filtre du classement en AJAX (sans rechargement) + loader
// =============================================================
const form = document.getElementById('filtre-form');
const liste = document.getElementById('liste-classement');
const loader = document.getElementById('liste-loader');

if (form && liste) {
    let requeteEnCours = null;

    const afficherLoader = (visible) => {
        loader?.classList.toggle('hidden', !visible);
        loader?.classList.toggle('flex', visible);
        liste.classList.toggle('opacity-40', visible);
        liste.setAttribute('aria-busy', visible ? 'true' : 'false');
    };

    const charger = async (url) => {
        requeteEnCours?.abort();
        const controleur = new AbortController();
        requeteEnCours = controleur;
        afficherLoader(true);

        try {
            const reponse = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controleur.signal,
            });
            liste.innerHTML = await reponse.text();
            window.history.pushState({}, '', url);
        } catch (erreur) {
            if (erreur.name !== 'AbortError') {
                liste.innerHTML =
                    '<p class="rounded-xl border border-rose-200 bg-rose-50 p-6 text-center text-sm text-rose-700">Erreur de chargement. Réessayez.</p>';
            }
        } finally {
            if (requeteEnCours === controleur) {
                afficherLoader(false);
                requeteEnCours = null;
            }
        }
    };

    const soumettre = () => {
        const params = new URLSearchParams(new FormData(form)).toString();
        charger(form.action + (params ? '?' + params : ''));
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        soumettre();
    });

    form.querySelector('select[name="secteur"]')?.addEventListener('change', soumettre);
    form.querySelector('select[name="vue"]')?.addEventListener('change', soumettre);

    let minuteur;
    form.querySelector('input[name="q"]')?.addEventListener('input', () => {
        clearTimeout(minuteur);
        minuteur = setTimeout(soumettre, 400);
    });

    // Pagination : les liens injectés (?page=…) sont aussi chargés en AJAX.
    liste.addEventListener('click', (e) => {
        const lien = e.target.closest('a');
        if (lien && lien.pathname === window.location.pathname && lien.search.includes('page=')) {
            e.preventDefault();
            charger(lien.href);
        }
    });

    window.addEventListener('popstate', () => charger(window.location.href));
}

// Loader sur les formulaires « classiques » (ni AJAX, ni confirmation SweetAlert).
// Enregistré en dernier pour voir un éventuel preventDefault des handlers précédents.
document.addEventListener('submit', (e) => {
    const cible = e.target;
    if (! (cible instanceof HTMLFormElement)) return;
    if (cible.dataset.confirm || e.defaultPrevented) return; // gérés ailleurs
    marquerEnvoi(cible);
});

// =============================================================
//  Modal d'incitation à l'inscription (invités)
//  Déclenché après un délai OU à l'intention de sortie, une seule
//  fois par période, jamais au 1er paint (respect UX / SEO).
// =============================================================
const modalInscription = document.getElementById('modal-inscription');
if (modalInscription) {
    const CLE = 'inscription_modal_vue';
    const PERIODE = 7 * 24 * 3600 * 1000; // 7 jours

    const dejaVue = () => {
        const t = Number(localStorage.getItem(CLE) || 0);
        return t && Date.now() - t < PERIODE;
    };

    let affichee = false;
    const afficher = () => {
        if (affichee || dejaVue()) return;
        affichee = true;
        localStorage.setItem(CLE, String(Date.now()));
        ouvrirModal('inscription');
    };

    // Déclencheur 1 : après 25 s de présence.
    const minuteur = setTimeout(afficher, 25000);

    // Déclencheur 2 : intention de sortie (souris quittant par le haut, desktop).
    document.addEventListener('mouseout', (e) => {
        if (e.clientY <= 0 && ! e.relatedTarget) {
            clearTimeout(minuteur);
            afficher();
        }
    });

    // Soumission AJAX (reste sur la page, erreurs inline).
    const form = modalInscription.querySelector('[data-inscription-rapide]');
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const bouton = form.querySelector('button[type="submit"]');
        const erreur = form.querySelector('[data-erreur]');
        erreur.classList.add('hidden');
        bouton.disabled = true;
        bouton.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            const reponse = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: new FormData(form),
            });
            const data = await reponse.json().catch(() => ({}));

            if (reponse.ok && data.redirect) {
                window.location = data.redirect;
                return;
            }
            erreur.textContent = data.errors
                ? Object.values(data.errors)[0][0]
                : (data.message || 'Une erreur est survenue, réessayez.');
            erreur.classList.remove('hidden');
        } catch {
            erreur.textContent = 'Erreur réseau, réessayez.';
            erreur.classList.remove('hidden');
        }

        bouton.disabled = false;
        bouton.classList.remove('opacity-70', 'cursor-not-allowed');
    });
}

// =============================================================
//  Lien magique : envoi AJAX (modal + page connexion)
// =============================================================
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (! (form instanceof HTMLFormElement) || ! form.matches('[data-magic-form]')) return;
    e.preventDefault();

    const bouton = form.querySelector('button[type="submit"]');
    const erreur = form.querySelector('[data-magic-erreur]');
    const succes = form.parentElement.querySelector('[data-magic-succes]');
    erreur.classList.add('hidden');
    bouton.disabled = true;
    bouton.classList.add('opacity-70', 'cursor-not-allowed');

    try {
        const reponse = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: new FormData(form),
        });
        const data = await reponse.json().catch(() => ({}));

        if (reponse.ok && data.ok) {
            form.classList.add('hidden');
            succes?.classList.remove('hidden');
            return;
        }
        erreur.textContent = data.message
            || (data.errors ? Object.values(data.errors)[0][0] : 'Une erreur est survenue, réessayez.');
        erreur.classList.remove('hidden');
    } catch {
        erreur.textContent = 'Erreur réseau, réessayez.';
        erreur.classList.remove('hidden');
    }

    bouton.disabled = false;
    bouton.classList.remove('opacity-70', 'cursor-not-allowed');
});
