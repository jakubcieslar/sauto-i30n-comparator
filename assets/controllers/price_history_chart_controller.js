import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-date-fns';

export default class extends Controller {
    static values = {
        history: Array, // [{t: ISO8601, price: int}, ...]
    };

    connect() {
        const ctx = this.element.getContext('2d');
        const points = this.historyValue.map((p) => ({
            x: new Date(p.t),
            y: p.price,
        }));

        new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Cena (Kč)',
                    data: points,
                    borderColor: 'rgba(99, 102, 241, 1)',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    stepped: 'before',
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                parsing: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => new Intl.NumberFormat('cs-CZ').format(ctx.parsed.y) + ' Kč',
                        },
                    },
                },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day', displayFormats: { day: 'd. M. yyyy' } },
                    },
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
