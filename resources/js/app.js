import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// =============================================================
//  Confirmation (SweetAlert) sur les formulaires [data-confirm]
// =============================================================
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

    e.preventDefault();
    Swal.fire({
        title: form.dataset.confirmTitle || 'Confirmer',
        text: form.dataset.confirm,
        icon: form.dataset.confirmIcon || 'question',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Confirmer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        customClass: { popup: 'swal-compact' },
    }).then((resultat) => {
        // form.submit() ne redéclenche pas l'événement 'submit' → pas de boucle.
        if (resultat.isConfirmed) form.submit();
    });
});

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
    form.querySelector('input[name="pires"][type="checkbox"]')?.addEventListener('change', soumettre);

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
