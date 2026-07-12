// =============================================================
//  Graphique d'évolution de l'utilisation (page admin/statistiques)
//  Chargé uniquement sur cette page via @vite('resources/js/stats.js').
// =============================================================
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    LinearScale, CategoryScale, Filler, Tooltip, Legend,
);

const source = document.getElementById('stats-data');
const canvas = document.getElementById('graphe-usage');

if (source && canvas) {
    const data = JSON.parse(source.textContent);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Visites',
                    data: data.visites,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                },
                {
                    label: 'Actions',
                    data: data.actions,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(100, 116, 139, 0.1)' },
                },
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
                },
            },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
            },
        },
    });
}
