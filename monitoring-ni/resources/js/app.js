// Bootstrap + Alpine.js
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Chart.js est volontairement **non exposé globalement**
// pour éviter des erreurs provenant de scripts tiers mal configurés.

// ✅ Si tu veux utiliser Chart.js DANS CE FICHIER, tu peux décommenter ci-dessous :
/*
import { Chart } from 'chart.js';
import 'chartjs-adapter-luxon';

// Exemple d'initialisation sécurisée :
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('pingChart');
    if (el) {
        const pingsRawData = JSON.parse(el.dataset.chartData);

        new Chart(el, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Temps de réponse (ms)',
                    data: pingsRawData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    pointRadius: 2
                }]
            },
            options: {
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            tooltipFormat: 'dd/LL/yyyy HH:mm',
                            displayFormats: {
                                minute: 'HH:mm',
                                hour: 'dd/LL HH:mm'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Date et Heure'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Temps de réponse (ms)'
                        }
                    }
                },
                responsive: true
            }
        });
    }
});
*/
