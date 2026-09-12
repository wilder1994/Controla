import Chart from 'chart.js/auto';

const SLATE = '#94a3b8';
const GRID = '#1e293b';
const MUTED = '#64748b';

function chartFont() {
    return { family: 'Figtree, ui-sans-serif, system-ui', size: 11 };
}

function lineOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                borderColor: '#334155',
                borderWidth: 1,
                titleColor: '#e2e8f0',
                bodyColor: '#cbd5e1',
                padding: 10,
            },
        },
        scales: {
            x: {
                ticks: { color: MUTED, font: chartFont(), maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                grid: { color: GRID, drawBorder: false },
            },
            y: {
                beginAtZero: true,
                suggestedMax: 4,
                ticks: { color: MUTED, precision: 0, font: chartFont() },
                grid: { color: GRID, drawBorder: false },
            },
        },
    };
}

function doughnutOptions(empty) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: SLATE, boxWidth: 10, font: { ...chartFont(), size: 10 }, padding: 10 },
            },
            tooltip: {
                callbacks: {
                    label(ctx) {
                        if (empty) {
                            return ` ${ctx.label}: 0`;
                        }
                        return ` ${ctx.label}: ${ctx.parsed}`;
                    },
                },
            },
        },
    };
}

const gaugeNeedle = {
    id: 'obsGaugeNeedle',
    afterDatasetDraw(chart, _args, opts) {
        const meta = chart.getDatasetMeta(0);
        const arc = meta?.data?.[0];
        if (!arc) {
            return;
        }
        const { x, y, outerRadius, innerRadius } = arc.getProps(['x', 'y', 'outerRadius', 'innerRadius'], true);
        const value = Math.max(0, Math.min(100, Number(opts.value ?? 0)));
        const angle = Math.PI + (Math.PI * value) / 100;
        const r = innerRadius + (outerRadius - innerRadius) * 0.55;
        const ctx = chart.ctx;
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.beginPath();
        ctx.moveTo(-5, 4);
        ctx.lineTo(r, 0);
        ctx.lineTo(-5, -4);
        ctx.closePath();
        ctx.fillStyle = '#e2e8f0';
        ctx.fill();
        ctx.beginPath();
        ctx.arc(0, 0, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#cbd5e1';
        ctx.fill();
        ctx.beginPath();
        ctx.arc(0, 0, 3, 0, Math.PI * 2);
        ctx.fillStyle = '#0f172a';
        ctx.fill();
        ctx.restore();
    },
};

function gaugeColor(value) {
    if (value >= 70) {
        return '#34d399';
    }
    if (value >= 35) {
        return '#14b8a6';
    }
    if (value > 0) {
        return '#f59e0b';
    }

    return '#475569';
}

export function observatoryBoard(payload) {
    return {
        payload,
        init() {
            this.draw();
        },
        draw() {
            const data = this.payload ?? {};
            const trend = data.trend ?? { labels: ['—'], values: [0] };
            const kinds = data.kinds ?? { labels: [], values: [] };
            const sources = data.sources ?? { labels: [], values: [] };
            const rate = Number(data.closed_rate ?? 0);
            const accent = data.accent === 'indigo' ? '#818cf8' : '#2dd4bf';

            this.line(this.$refs.trend, trend, accent);
            this.bars(this.$refs.kinds, kinds);
            this.pie(this.$refs.sources, sources);
            this.gauge(this.$refs.gauge, rate);
        },
        line(el, series, accent) {
            if (!el) {
                return;
            }
            const labels = series.labels?.length ? series.labels : ['—'];
            const values = series.values?.length ? series.values : [0];
            new Chart(el, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        borderColor: accent,
                        backgroundColor: accent + '33',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: accent,
                        borderWidth: 2,
                    }],
                },
                options: lineOptions(),
            });
        },
        bars(el, series) {
            if (!el) {
                return;
            }
            const labels = series.labels?.length ? series.labels : ['Amenaza', 'Riña', 'Hurto', 'Otro'];
            const values = series.values?.length ? series.values : [0, 0, 0, 0];
            new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#f59e0b', '#f43f5e', '#818cf8', '#64748b'],
                        borderRadius: 6,
                        maxBarThickness: 36,
                    }],
                },
                options: {
                    ...lineOptions(),
                    plugins: { ...lineOptions().plugins, legend: { display: false } },
                },
            });
        },
        pie(el, series) {
            if (!el) {
                return;
            }
            const labels = series.labels?.length ? series.labels : ['Comunidad', 'Panel', 'App de patrulla', 'Portería', 'Integración'];
            const values = series.values?.length ? series.values : labels.map(() => 0);
            const colors = ['#14b8a6', '#818cf8', '#f59e0b', '#64748b', '#a78bfa'];
            const empty = values.every((n) => Number(n) === 0);
            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: empty ? values.map(() => 1) : values,
                        backgroundColor: empty ? values.map(() => '#1e293b') : colors.slice(0, values.length),
                        borderColor: '#0f172a',
                        borderWidth: 2,
                    }],
                },
                options: {
                    ...doughnutOptions(empty),
                    cutout: '62%',
                },
            });
        },
        gauge(el, value) {
            if (!el) {
                return;
            }
            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: ['Cerrados', 'Abiertos'],
                    datasets: [{
                        data: [value, 100 - value],
                        backgroundColor: [gaugeColor(value), '#1e293b'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    rotation: -90,
                    circumference: 180,
                    cutout: '78%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                        obsGaugeNeedle: { value },
                    },
                },
                plugins: [gaugeNeedle],
            });
        },
    };
}
