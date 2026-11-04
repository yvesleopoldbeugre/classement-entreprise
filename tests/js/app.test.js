import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';

// SweetAlert est remplacé par un espion : on vérifie ce que l'application lui demande.
const swal = vi.hoisted(() => ({ fire: vi.fn(() => Promise.resolve({ isConfirmed: true, value: 'faux' })) }));
vi.mock('sweetalert2', () => ({ default: swal }));
vi.mock('sweetalert2/dist/sweetalert2.min.css', () => ({}));

// Gabarit reprenant les attributs data-* des vues Blade (layout, fiche, accueil, formulaires).
const GABARIT = `
    <div id="flash-message" data-type="success">Avis envoyé !</div>

    <button type="button" data-menu-toggle aria-expanded="false" aria-label="Ouvrir le menu">menu</button>
    <nav id="menu-mobile" class="hidden"><a href="#secteurs" id="lien-menu">Secteurs</a></nav>

    <a href="/entreprises/akwaba/avis" data-modal-open="avis" id="ouvrir-avis">Donner mon avis</a>
    <div id="modal-avis" data-modal class="hidden">
        <div data-modal-backdrop id="fond-avis"></div>
        <div><button type="button" data-modal-close id="fermer-avis">✕</button><input id="champ-avis"></div>
    </div>

    <div data-onglets>
        <button type="button" data-onglet="avis" id="onglet-avis">Avis</button>
        <button type="button" data-onglet="entretiens" id="onglet-entretiens">Entretiens</button>
        <section data-panneau="avis" id="panneau-avis"></section>
        <section data-panneau="entretiens" id="panneau-entretiens"></section>
    </div>

    <form id="filtre-form" action="/" method="GET"><input name="q" id="recherche"></form>
    <div id="liste-loader" class="hidden"></div>
    <div id="liste-classement"><a href="/?page=2" id="page-2">2</a><a href="/entreprises/akwaba" id="fiche">Fiche</a></div>

    <form id="form-confirme" action="/deconnexion" method="POST" data-confirm="Vous allez être déconnecté." data-confirm-title="Se déconnecter ?">
        <button type="submit" id="bouton-confirme">Déconnexion</button>
    </form>
    <form id="form-classique" action="/avis" method="POST"><button type="submit" id="bouton-classique">Publier</button></form>

    <div><form data-magic-form id="form-magique" action="/connexion/lien" method="POST">
        <input name="email" value="awa@example.com"><button type="submit" id="bouton-magique">Recevoir</button>
        <p data-magic-erreur class="hidden" id="erreur-magique"></p>
    </form><p data-magic-succes class="hidden" id="succes-magique">Lien envoyé</p></div>

    <button type="button" data-copier="https://notetaboite.ci/entreprises/akwaba" id="copier">Partager</button>

    <div data-champ><button type="button" data-aide-toggle id="aide">?</button><span data-aide-contenu class="hidden" id="bulle">Aide</span></div>

    <div data-banniere-mdp id="banniere"><button type="button" data-banniere-fermer id="fermer-banniere">✕</button></div>
`;

const $ = (id) => document.getElementById(id);
const clic = (el) => el.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
const touche = (key) => document.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
const soumettre = (form) => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
const attendre = () => new Promise((r) => setTimeout(r, 0));

let ecouteurGrandEcran;
let appelsAuChargement = [];
const fetchEspion = vi.fn();
const presseEspion = vi.fn(() => Promise.resolve());

beforeAll(async () => {
    document.body.innerHTML = GABARIT;
    window.matchMedia = () => ({ matches: false, addEventListener: (_t, f) => { ecouteurGrandEcran = f; } });
    globalThis.fetch = (...args) => fetchEspion(...args);
    Object.defineProperty(navigator, 'clipboard', { value: { writeText: (...a) => presseEspion(...a) }, configurable: true });
    HTMLFormElement.prototype.submit = vi.fn();

    await import('../../resources/js/app.js');
    appelsAuChargement = [...swal.fire.mock.calls];
});

beforeEach(() => {
    fetchEspion.mockReset();
    swal.fire.mockClear();
    presseEspion.mockClear();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('messages flash', () => {
    it('affiche le message de session en toast au chargement', () => {
        expect(appelsAuChargement).toContainEqual([expect.objectContaining({ toast: true, icon: 'success', title: 'Avis envoyé !' })]);
    });
});

describe('menu mobile', () => {
    it('s’ouvre, bloque le défilement et propose de se fermer', () => {
        clic($('menu-mobile').previousElementSibling);

        expect($('menu-mobile').classList.contains('hidden')).toBe(false);
        expect(document.querySelector('[data-menu-toggle]').getAttribute('aria-expanded')).toBe('true');
        expect(document.querySelector('[data-menu-toggle]').getAttribute('aria-label')).toBe('Fermer le menu');
        expect(document.body.classList.contains('overflow-hidden')).toBe(true);
    });

    it('se ferme avec Échap', () => {
        touche('Escape');

        expect($('menu-mobile').classList.contains('hidden')).toBe(true);
        expect(document.body.classList.contains('overflow-hidden')).toBe(false);
    });

    it('se ferme quand on touche un lien du menu', () => {
        clic(document.querySelector('[data-menu-toggle]'));
        clic($('lien-menu'));

        expect($('menu-mobile').classList.contains('hidden')).toBe(true);
    });

    it('se ferme quand l’écran passe en grand format', () => {
        clic(document.querySelector('[data-menu-toggle]'));
        ecouteurGrandEcran({ matches: true });

        expect($('menu-mobile').classList.contains('hidden')).toBe(true);
    });
});

describe('fenêtres (modals)', () => {
    it('le lien ouvre la fenêtre sans quitter la page et place le focus', () => {
        const evt = new MouseEvent('click', { bubbles: true, cancelable: true });
        $('ouvrir-avis').dispatchEvent(evt);

        expect(evt.defaultPrevented).toBe(true);
        expect($('modal-avis').classList.contains('hidden')).toBe(false);
        expect(document.activeElement?.id).toBe('fermer-avis');
    });

    it('se ferme avec la croix, le fond ou Échap', () => {
        clic($('fermer-avis'));
        expect($('modal-avis').classList.contains('hidden')).toBe(true);

        clic($('ouvrir-avis'));
        clic($('fond-avis'));
        expect($('modal-avis').classList.contains('hidden')).toBe(true);

        clic($('ouvrir-avis'));
        touche('Escape');
        expect($('modal-avis').classList.contains('hidden')).toBe(true);
        expect(document.body.classList.contains('overflow-hidden')).toBe(false);
    });
});

describe('onglets de la fiche', () => {
    it('affiche le premier panneau au chargement', () => {
        expect($('onglet-avis').getAttribute('aria-selected')).toBe('true');
        expect($('panneau-avis').classList.contains('hidden')).toBe(false);
        expect($('panneau-entretiens').classList.contains('hidden')).toBe(true);
    });

    it('bascule de panneau au clic', () => {
        clic($('onglet-entretiens'));

        expect($('onglet-entretiens').getAttribute('aria-selected')).toBe('true');
        expect($('onglet-avis').getAttribute('aria-selected')).toBe('false');
        expect($('panneau-entretiens').classList.contains('hidden')).toBe(false);
        expect($('panneau-avis').classList.contains('hidden')).toBe(true);
    });
});

describe('liste du classement (AJAX)', () => {
    const reponseHtml = (html) => Promise.resolve({ text: () => Promise.resolve(html) });

    it('la recherche attend la fin de la frappe puis recharge la liste seule', async () => {
        vi.useFakeTimers();
        fetchEspion.mockReturnValue(reponseHtml('<p id="resultat">Akwaba Tech</p>'));

        $('recherche').value = 'akw';
        $('recherche').dispatchEvent(new Event('input', { bubbles: true }));
        $('recherche').value = 'akwaba';
        $('recherche').dispatchEvent(new Event('input', { bubbles: true }));
        expect(fetchEspion).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(400);
        vi.useRealTimers();
        await attendre();

        expect(fetchEspion).toHaveBeenCalledTimes(1);
        const [url, options] = fetchEspion.mock.calls[0];
        expect(url).toMatch(/\?q=akwaba$/);
        expect(options.headers['X-Requested-With']).toBe('XMLHttpRequest');
        expect($('liste-classement').innerHTML).toContain('Akwaba Tech');
        expect(window.location.search).toBe('?q=akwaba');
        expect($('liste-loader').classList.contains('hidden')).toBe(true);
    });

    it('la soumission du formulaire ne recharge pas la page', async () => {
        fetchEspion.mockReturnValue(reponseHtml('<p>ok</p>'));

        const evt = new Event('submit', { bubbles: true, cancelable: true });
        $('filtre-form').dispatchEvent(evt);
        await attendre();

        expect(evt.defaultPrevented).toBe(true);
        expect(fetchEspion).toHaveBeenCalledTimes(1);
    });

    it('la pagination est chargée en AJAX, les autres liens restent normaux', async () => {
        document.getElementById('liste-classement').innerHTML = '<a href="/?page=2" id="page-2">2</a><a href="/entreprises/akwaba" id="fiche">Fiche</a>';
        fetchEspion.mockReturnValue(reponseHtml('<p>page 2</p>'));

        const pagination = new MouseEvent('click', { bubbles: true, cancelable: true });
        $('page-2').dispatchEvent(pagination);
        await attendre();
        expect(pagination.defaultPrevented).toBe(true);
        expect(fetchEspion.mock.calls[0][0]).toMatch(/page=2/);

        document.getElementById('liste-classement').innerHTML = '<a href="/entreprises/akwaba" id="fiche">Fiche</a>';
        const fiche = new MouseEvent('click', { bubbles: true, cancelable: true });
        $('fiche').dispatchEvent(fiche);
        expect(fiche.defaultPrevented).toBe(false);
    });

    it('un changement d’ancre (#secteurs) ne recharge pas la liste', async () => {
        window.location.hash = 'explorer';
        window.dispatchEvent(new PopStateEvent('popstate'));
        await attendre();

        expect(fetchEspion).not.toHaveBeenCalled();
    });

    it('le bouton retour du navigateur recharge la liste précédente', async () => {
        fetchEspion.mockReturnValue(reponseHtml('<p>liste précédente</p>'));
        window.history.pushState({}, '', '/?q=autre');
        window.dispatchEvent(new PopStateEvent('popstate'));
        await attendre();

        expect(fetchEspion.mock.calls[0][0]).toMatch(/\?q=autre$/);
        expect($('liste-classement').textContent).toContain('liste précédente');
    });

    it('une erreur réseau affiche un message à la place de la liste', async () => {
        fetchEspion.mockReturnValue(Promise.reject(new TypeError('réseau coupé')));

        soumettre($('filtre-form'));
        await attendre();

        expect($('liste-classement').textContent).toContain('Erreur de chargement');
        expect($('liste-loader').classList.contains('hidden')).toBe(true);
    });
});

describe('formulaires', () => {
    it('une action sensible demande confirmation avant l’envoi', async () => {
        soumettre($('form-confirme'));
        await attendre();

        expect(swal.fire).toHaveBeenCalledWith(expect.objectContaining({ title: 'Se déconnecter ?', text: 'Vous allez être déconnecté.' }));
        expect(HTMLFormElement.prototype.submit).toHaveBeenCalled();
        expect($('bouton-confirme').disabled).toBe(true);
    });

    it('sans confirmation, rien n’est envoyé', async () => {
        HTMLFormElement.prototype.submit.mockClear();
        swal.fire.mockReturnValueOnce(Promise.resolve({ isConfirmed: false }));
        const form = $('form-confirme');
        form.querySelector('button').disabled = false;
        delete form.querySelector('button').dataset.loading;

        soumettre(form);
        await attendre();

        expect(HTMLFormElement.prototype.submit).not.toHaveBeenCalled();
    });

    it('un envoi classique désactive le bouton et affiche « Envoi… »', () => {
        soumettre($('form-classique'));

        expect($('bouton-classique').disabled).toBe(true);
        expect($('bouton-classique').textContent).toContain('Envoi…');
    });
});

describe('lien magique', () => {
    it('envoie la demande en AJAX et affiche la confirmation', async () => {
        fetchEspion.mockReturnValue(Promise.resolve({ ok: true, json: () => Promise.resolve({ ok: true }) }));

        soumettre($('form-magique'));
        await attendre();
        await attendre();

        expect(fetchEspion.mock.calls[0][0]).toMatch(/\/connexion\/lien$/);
        expect(fetchEspion.mock.calls[0][1].method).toBe('POST');
        expect($('form-magique').classList.contains('hidden')).toBe(true);
        expect($('succes-magique').classList.contains('hidden')).toBe(false);
    });

    it('affiche l’erreur de validation renvoyée par le serveur', async () => {
        $('form-magique').classList.remove('hidden');
        $('succes-magique').classList.add('hidden');
        fetchEspion.mockReturnValue(Promise.resolve({ ok: false, json: () => Promise.resolve({ errors: { email: ['Email invalide.'] } }) }));

        soumettre($('form-magique'));
        await attendre();
        await attendre();

        expect($('erreur-magique').textContent).toBe('Email invalide.');
        expect($('erreur-magique').classList.contains('hidden')).toBe(false);
        expect($('bouton-magique').disabled).toBe(false);
    });
});

describe('petits services', () => {
    it('copie le lien de la fiche et confirme', async () => {
        clic($('copier'));
        await attendre();

        expect(presseEspion).toHaveBeenCalledWith('https://notetaboite.ci/entreprises/akwaba');
        expect(swal.fire).toHaveBeenCalledWith(expect.objectContaining({ title: 'Lien copié !' }));
    });

    it('la bulle d’aide s’ouvre au clic et se ferme en cliquant ailleurs', () => {
        clic($('aide'));
        expect($('bulle').classList.contains('hidden')).toBe(false);

        clic(document.body);
        expect($('bulle').classList.contains('hidden')).toBe(true);
    });

    it('la bannière se masque pour la session', () => {
        clic($('fermer-banniere'));

        expect($('banniere')).toBeNull();
        expect(sessionStorage.getItem('banniere_mdp_fermee')).toBe('1');
    });
});
