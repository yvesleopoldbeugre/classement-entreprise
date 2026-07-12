// =============================================================
//  Graphiques de l'espace « Utilisateurs » (admin)
//   - #graphe-comparatif  : barres, top utilisateurs (liste)
//   - #graphe-utilisateur : courbe visites/actions (fiche)
//  Chargé via @vite('resources/js/users.js') sur ces pages.
// =============================================================
import {
    Chart,
    BarController, BarElement,
    LineController, LineElement, PointElement,
    LinearScale, CategoryScale, Filler, Tooltip, Legend,
} from 'chart.js';

Chart.register(
    BarController, BarElement,
    LineController, LineElement, PointElement,
    LinearScale, CategoryScale, Filler, Tooltip, Legend,
);

const lire = (id) => {
    const el = document.getElementById(id);
    return el ? JSON.parse(el.textContent) : null;
};

// --- Comparatif (barres) ---
const comparatif = lire('comparatif-data');
const canvasComparatif = document.getElementById('graphe-comparatif');
if (comparatif && canvasComparatif) {
    new Chart(canvasComparatif, {
        type: 'bar',
        data: {
            labels: comparatif.labels,
            datasets: [{
                label: 'Actions',
                data: comparatif.valeurs,
                backgroundColor: '#4f46e5',
                borderRadius: 6,
                maxBarThickness: 40,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(100, 116, 139, 0.1)' } },
                x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 0 } },
            },
        },
    });
}

// --- Fiche utilisateur (courbe) ---
const userData = lire('user-stats-data');
const canvasUser = document.getElementById('graphe-utilisateur');
if (userData && canvasUser) {
    new Chart(canvasUser, {
        type: 'line',
        data: {
            labels: userData.labels,
            datasets: [
                {
                    label: 'Visites',
                    data: userData.visites,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                    fill: true, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2,
                },
                {
                    label: 'Actions',
                    data: userData.actions,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    fill: true, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(100, 116, 139, 0.1)' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
            },
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } } },
        },
    });
}
