import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default class extends Controller {
    static values = {
        labels: Array,
        prices: Array,
    };

    connect() {
        const ctx = this.element.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: this.labelsValue,
                datasets: [{
                    label: 'Cena (Kč)',
                    data: this.pricesValue,
                    backgroundColor: 'rgba(99, 102, 241, 0.6)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => new Intl.NumberFormat('cs-CZ').format(ctx.parsed.y) + ' Kč',
                        },
                    },
                },
                scales: {
                    x: { ticks: { autoSkip: false, maxRotation: 60, minRotation: 30, font: { size: 10 } } },
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: (v) => new Intl.NumberFormat('cs-CZ').format(v),
                        },
                    },
                },
            },
        });
    }
}
