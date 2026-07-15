// =============================================================
//  Panneau admin « Visiteurs en direct » : liste des présents (poll) +
//  conversation avec un visiteur (poll). Transport = polling (v1).
// =============================================================
const live = document.getElementById('live');

if (live) {
    const { visiteursUrl, demarrerUrl, convBase } = live.dataset;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const count = live.querySelector('[data-live-count]');
    const liste = live.querySelector('[data-live-liste]');
    const listeVide = live.querySelector('[data-live-liste-vide]');
    const entete = live.querySelector('[data-live-entete]');
    const nom = live.querySelector('[data-live-nom]');
    const page = live.querySelector('[data-live-page]');
    const zone = live.querySelector('[data-live-messages]');
    const vide = live.querySelector('[data-live-vide]');
    const form = live.querySelector('[data-live-form]');
    const input = live.querySelector('[data-live-input]');

    let convId = null;
    let dernierId = 0;
    let convTimer = null;

    const getJson = (url) => fetch(url, { headers: { Accept: 'application/json' } }).then((r) => (r.ok ? r.json() : null));
    const postJson = (url, corps) =>
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify(corps),
        }).then((r) => (r.ok ? r.json() : null));

    // --- Liste des visiteurs en ligne ---
    const rendreListe = (visiteurs) => {
        count.textContent = visiteurs.length;
        listeVide.classList.toggle('hidden', visiteurs.length > 0);
        liste.innerHTML = '';
        visiteurs.forEach((v) => {
            const li = document.createElement('li');
            li.className =
                'cursor-pointer rounded-xl border border-slate-100 p-3 hover:border-indigo-200 hover:bg-indigo-50/40';
            li.innerHTML =
                `<div class="flex items-center justify-between gap-2">
                    <span class="truncate font-medium text-slate-800">${escapeHtml(v.nom)}</span>
                    ${v.non_lus > 0 ? `<span class="rounded-full bg-rose-500 px-1.5 text-xs font-bold text-white">${v.non_lus}</span>` : ''}
                 </div>
                 <div class="mt-0.5 truncate text-xs text-slate-400">${v.connecte ? escapeHtml(v.email || '') + ' · ' : ''}${escapeHtml(v.appareil)}</div>
                 <div class="mt-0.5 truncate text-xs text-slate-400">📍 ${escapeHtml(v.url || '—')} · ${escapeHtml(v.activite || '')}</div>`;
            li.addEventListener('click', () => ouvrirConversation(v));
            liste.appendChild(li);
        });
    };

    const chargerVisiteurs = () =>
        getJson(visiteursUrl).then((d) => d && rendreListe(d.visiteurs || []));

    // --- Conversation ---
    const bulle = (m) => {
        const admin = m.expediteur === 'admin';
        const wrap = document.createElement('div');
        wrap.className = `flex ${admin ? 'justify-end' : 'justify-start'}`;
        const b = document.createElement('div');
        const style = {
            admin: 'bg-indigo-600 text-white rounded-br-sm',
            bot: 'bg-slate-200 text-slate-700 rounded-bl-sm',
            visiteur: 'bg-white text-slate-700 shadow-sm rounded-bl-sm',
        }[m.expediteur] || 'bg-white text-slate-700';
        b.className = `max-w-[80%] rounded-2xl px-3 py-2 ${style}`;
        b.textContent = m.corps;
        wrap.appendChild(b);
        return wrap;
    };

    const ajouter = (messages) => {
        messages.forEach((m) => {
            zone.appendChild(bulle(m));
            dernierId = Math.max(dernierId, m.id);
        });
        zone.scrollTop = zone.scrollHeight;
    };

    const pollConversation = () => {
        if (!convId) return;
        getJson(`${convBase}/${convId}/messages?depuis=${dernierId}`).then((d) => {
            if (d?.messages?.length) ajouter(d.messages.filter((m) => m.id > dernierId));
        });
    };

    const ouvrirConversation = async (v) => {
        // Crée la conversation si le visiteur n'en a pas encore.
        convId = v.conversation_id;
        if (!convId) {
            const d = await postJson(demarrerUrl, { visiteur_token: v.token });
            convId = d?.conversation_id;
        }
        if (!convId) return;

        dernierId = 0;
        zone.innerHTML = '';
        nom.textContent = v.nom;
        page.textContent = v.url || '';
        entete.classList.remove('hidden');
        vide.classList.add('hidden');
        form.classList.remove('hidden');
        form.classList.add('flex');

        const d = await getJson(`${convBase}/${convId}/messages`);
        if (d?.messages) ajouter(d.messages);

        clearInterval(convTimer);
        convTimer = setInterval(pollConversation, 4000);
        chargerVisiteurs();
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const corps = input.value.trim();
        if (!corps || !convId) return;
        input.value = '';
        postJson(`${convBase}/${convId}/messages`, { corps }).then((d) => {
            if (d?.messages) ajouter(d.messages.filter((m) => m.id > dernierId));
        });
    });

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Démarrage : liste immédiate puis toutes les 5 s.
    chargerVisiteurs();
    setInterval(chargerVisiteurs, 5000);
}
