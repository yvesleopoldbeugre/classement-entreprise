// =============================================================
//  Widget de chat visiteur : présence (heartbeat) + chat avec le bot / l'équipe.
//  Transport = polling (v1). Identité = visiteur_token persistant (localStorage).
// =============================================================
const widget = document.getElementById('chat-widget');

if (widget) {
    const urls = widget.dataset;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // --- Identité visiteur persistante ---
    let token = localStorage.getItem('visiteur_token');
    if (!token) {
        token = (crypto.randomUUID?.() || String(Date.now()) + Math.random().toString(16).slice(2));
        localStorage.setItem('visiteur_token', token);
    }

    const panel = widget.querySelector('[data-chat-panel]');
    const badge = widget.querySelector('[data-chat-badge]');
    const zone = widget.querySelector('[data-chat-messages]');
    const form = widget.querySelector('[data-chat-form]');
    const input = widget.querySelector('[data-chat-input]');

    let ouvert = false;
    let dernierId = 0;
    let pollTimer = null;

    const poster = (url, corps) =>
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify({ visiteur_token: token, ...corps }),
        }).then((r) => (r.ok ? r.json() : null));

    // --- Présence (heartbeat) ---
    const heartbeat = () => {
        poster(urls.heartbeatUrl, { url: location.pathname })
            .then((d) => {
                if (!d || ouvert) return;
                if (d.nouveaux > 0) {
                    badge.textContent = d.nouveaux;
                    badge.classList.remove('hidden');
                }
            })
            .catch(() => {});
    };

    // --- Rendu d'un message ---
    const bulleMessage = (m) => {
        const visiteur = m.expediteur === 'visiteur';
        const wrap = document.createElement('div');
        wrap.className = `flex ${visiteur ? 'justify-end' : 'justify-start'}`;
        const b = document.createElement('div');
        b.className = visiteur
            ? 'max-w-[80%] rounded-2xl rounded-br-sm bg-indigo-600 px-3 py-2 text-white'
            : 'max-w-[80%] rounded-2xl rounded-bl-sm bg-white px-3 py-2 text-slate-700 shadow-sm';
        b.textContent = m.corps;
        wrap.appendChild(b);
        return wrap;
    };

    const ajouter = (messages) => {
        messages.forEach((m) => {
            zone.appendChild(bulleMessage(m));
            dernierId = Math.max(dernierId, m.id);
        });
        zone.scrollTop = zone.scrollHeight;
    };

    // --- Poll des nouveaux messages (panneau ouvert) ---
    const poll = () => {
        fetch(`${urls.messagesUrl}?visiteur_token=${encodeURIComponent(token)}&depuis=${dernierId}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => (r.ok ? r.json() : null))
            .then((d) => d && d.messages?.length && ajouter(d.messages))
            .catch(() => {});
    };

    // --- Ouverture / fermeture ---
    const ouvrir = () => {
        ouvert = true;
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        badge.classList.add('hidden');
        input.focus();
        poster(urls.ouvrirUrl, {}).then((d) => {
            if (d?.messages) { zone.innerHTML = ''; dernierId = 0; ajouter(d.messages); }
        });
        clearInterval(pollTimer);
        pollTimer = setInterval(poll, 4000);
    };

    const fermer = () => {
        ouvert = false;
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        clearInterval(pollTimer);
    };

    widget.querySelector('[data-chat-toggle]').addEventListener('click', () => (ouvert ? fermer() : ouvrir()));
    widget.querySelector('[data-chat-close]').addEventListener('click', fermer);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const corps = input.value.trim();
        if (!corps) return;
        input.value = '';
        poster(urls.messageUrl, { corps }).then((d) => {
            if (d?.messages) ajouter(d.messages);
        });
    });

    // Démarrage : heartbeat immédiat puis toutes les 25 s.
    heartbeat();
    setInterval(heartbeat, 25000);
}
