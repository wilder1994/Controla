import Chart from 'chart.js/auto';

export function obsDateRange(config) {
    const fmt = (value) => {
        if (!value) {
            return '';
        }
        const [y, m, d] = String(value).split('-');
        return `${d}/${m}/${y}`;
    };

    return {
        open: false,
        from: config.from || '',
        to: config.to || '',
        draftFrom: config.from || '',
        draftTo: config.to || '',
        get label() {
            if (this.from && this.to) {
                return `${fmt(this.from)} – ${fmt(this.to)}`;
            }
            if (this.from) {
                return `Desde ${fmt(this.from)}`;
            }
            if (this.to) {
                return `Hasta ${fmt(this.to)}`;
            }

            return 'Fechas';
        },
        show() {
            this.draftFrom = this.from;
            this.draftTo = this.to;
            this.open = true;
        },
        apply() {
            this.from = this.draftFrom;
            this.to = this.draftTo;
            this.open = false;
            this.$nextTick(() => {
                if (this.$el instanceof HTMLFormElement) {
                    this.$el.submit();
                }
            });
        },
        close() {
            this.open = false;
        },
    };
}

const SLATE = '#94a3b8';
const GRID = '#1e293b';
const MUTED = '#64748b';

function chartFont() {
    return { family: 'Figtree, ui-sans-serif, system-ui', size: 10 };
}

function lineOptions(showLegend = false) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: showLegend,
                position: 'bottom',
                labels: { color: SLATE, boxWidth: 8, font: { ...chartFont(), size: 10 }, padding: 8 },
            },
            tooltip: {
                backgroundColor: '#0f172a',
                borderColor: '#334155',
                borderWidth: 1,
                titleColor: '#e2e8f0',
                bodyColor: '#cbd5e1',
                padding: 8,
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
                labels: { color: SLATE, boxWidth: 8, font: { ...chartFont(), size: 10 }, padding: 8 },
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

function mixRgb(from, to, t) {
    return from.map((c, i) => Math.round(c + (to[i] - c) * t));
}

function loadColor(t) {
    const clamped = Math.max(0, Math.min(1, t));
    const green = [16, 185, 129];
    const amber = [245, 158, 11];
    const red = [239, 68, 68];
    const rgb = clamped < 0.5
        ? mixRgb(green, amber, clamped / 0.5)
        : mixRgb(amber, red, (clamped - 0.5) / 0.5);

    return `rgb(${rgb[0]}, ${rgb[1]}, ${rgb[2]})`;
}

function drawLoadGauge(canvas, value) {
    const parent = canvas.parentElement;
    const width = Math.max(1, parent?.clientWidth ?? canvas.clientWidth);
    const height = Math.max(1, parent?.clientHeight ?? canvas.clientHeight);
    const dpr = window.devicePixelRatio || 1;
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, width, height);

    const cx = width / 2;
    const cy = height * 0.82;
    const radius = Math.min(width * 0.42, height * 0.78);
    const thickness = Math.max(10, radius * 0.18);
    const start = Math.PI;
    const sweep = Math.PI;
    const steps = 96;

    ctx.lineCap = 'butt';
    ctx.lineWidth = thickness;
    for (let i = 0; i < steps; i += 1) {
        const t0 = i / steps;
        const a0 = start + sweep * t0;
        const a1 = start + sweep * ((i + 1) / steps);
        ctx.beginPath();
        ctx.strokeStyle = loadColor(t0);
        ctx.arc(cx, cy, radius, a0, a1);
        ctx.stroke();
    }

    ctx.beginPath();
    ctx.lineWidth = 3;
    ctx.strokeStyle = '#0f172a';
    ctx.arc(cx, cy, radius - thickness / 2 - 1.5, start, start + sweep);
    ctx.stroke();

    const load = Math.max(0, Math.min(100, Number(value) || 0)) / 100;
    const angle = start + sweep * load;
    const needle = radius - thickness * 0.15;
    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate(angle);
    ctx.beginPath();
    ctx.moveTo(-7, 5);
    ctx.lineTo(needle, 0);
    ctx.lineTo(-7, -5);
    ctx.closePath();
    ctx.fillStyle = loadColor(load);
    ctx.fill();
    ctx.beginPath();
    ctx.arc(0, 0, 7, 0, Math.PI * 2);
    ctx.fillStyle = load >= 0.7 ? '#fecaca' : '#e2e8f0';
    ctx.fill();
    ctx.beginPath();
    ctx.arc(0, 0, 3.5, 0, Math.PI * 2);
    ctx.fillStyle = loadColor(load);
    ctx.fill();
    ctx.restore();
}

export function observatoryBoard(payload) {
    return {
        payload,
        init() {
            this.draw();
        },
        draw() {
            const data = this.payload ?? {};
            const trend = data.trend ?? { labels: ['—'], series: [] };
            const peaks = data.peaks ?? { labels: ['—'], values: [0] };
            const sources = data.sources ?? { labels: [], values: [] };
            const load = Number(data.load_rate ?? 0);

            this.lines(this.$refs.trend, trend);
            this.bars(this.$refs.peaks, peaks);
            this.pie(this.$refs.sources, sources);
            this.gauge(this.$refs.gauge, load);
        },
        lines(el, series) {
            if (!el) {
                return;
            }
            const labels = series.labels?.length ? series.labels : ['—'];
            const rows = (series.series || []).length
                ? series.series
                : [{ label: 'Reportes', color: '#94a3b8', values: [0] }];
            new Chart(el, {
                type: 'line',
                data: {
                    labels,
                    datasets: rows.map((row) => ({
                        label: row.label,
                        data: row.values?.length ? row.values : labels.map(() => 0),
                        borderColor: row.color,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        pointBackgroundColor: row.color,
                        borderWidth: 2,
                    })),
                },
                options: lineOptions(rows.length > 1),
            });
        },
        bars(el, series) {
            if (!el) {
                return;
            }
            const labels = series.labels?.length ? series.labels : ['—'];
            const values = series.values?.length ? series.values : [0];
            new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: '#f59e0b',
                        borderRadius: 5,
                        maxBarThickness: 22,
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
            const paint = () => drawLoadGauge(el, value);
            paint();
            if (typeof ResizeObserver !== 'undefined') {
                const observer = new ResizeObserver(paint);
                observer.observe(el.parentElement ?? el);
            }
        },
    };
}
