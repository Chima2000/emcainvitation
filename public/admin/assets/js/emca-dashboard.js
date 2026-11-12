(() => {
    const palette = ['#940000', '#1f1f1f', '#d46a6a', '#8c8c8c', '#5c0000', '#c9c9c9', '#f0b3b3', '#4d4d4d'];

    const theme = () => {
        const styles = getComputedStyle(document.documentElement);
        const read = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

        return {
            isDark,
            body: read('--bs-body-color', '#000000'),
            heading: read('--bs-heading-color', '#000000'),
            muted: read('--bs-secondary-color', '#737373'),
            border: read('--bs-border-color', '#e6e6e6'),
            surface: read('--bs-body-bg', '#ffffff'),
            font: read('--bs-body-font-family', 'sans-serif'),
            ink: isDark ? '#f2f2f2' : '#1f1f1f',
        };
    };

    const colorAt = (index, ui) => {
        const color = palette[index % palette.length];

        return color === '#1f1f1f' ? ui.ink : color;
    };

    const colorsFor = (count, ui) => Array.from({ length: Math.max(count, 1) }, (_, index) => colorAt(index, ui));

    const niceMax = (values) => {
        const peak = Math.max(0, ...values);

        if (peak <= 4) {
            return 4;
        }

        const magnitude = 10 ** Math.floor(Math.log10(peak));
        const step = magnitude / 2;

        return Math.ceil((peak * 1.1) / step) * step;
    };

    const formatNumber = (value) => Math.round(value).toLocaleString();

    const formatCompact = (value) => {
        if (value >= 1000000) {
            return `${(value / 1000000).toFixed(value >= 10000000 ? 0 : 1)}M`;
        }

        if (value >= 1000) {
            return `${Math.round(value / 1000).toLocaleString()}k`;
        }

        return formatNumber(value);
    };

    const renderSpark = (element, config, ui) => {
        const prefix = config.prefix || '';

        new ApexCharts(element, {
            series: [{ name: config.name, data: config.data }],
            chart: { type: 'bar', height: 95, fontFamily: ui.font, sparkline: { enabled: true } },
            colors: ['#940000'],
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, distributed: false } },
            states: { hover: { filter: { type: 'darken', value: 0.85 } } },
            xaxis: { categories: config.categories },
            tooltip: {
                theme: ui.isDark ? 'dark' : 'light',
                x: { show: true },
                y: { formatter: (value) => `${prefix}${formatNumber(value)}` },
            },
        }).render();
    };

    const renderBar = (element, config, ui) => {
        const values = config.series.flatMap((series) => series.data);
        const prefix = config.prefix || '';

        new ApexCharts(element, {
            series: config.series,
            colors: colorsFor(config.series.length, ui),
            chart: { type: 'bar', height: 320, fontFamily: ui.font, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 3 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: config.categories,
                axisBorder: { color: ui.border },
                axisTicks: { show: false },
                labels: { style: { colors: ui.body, fontSize: '13px' } },
            },
            yaxis: {
                min: 0,
                max: niceMax(values),
                tickAmount: 4,
                labels: { formatter: formatCompact, style: { colors: ui.body, fontSize: '13px' } },
            },
            grid: { borderColor: ui.border, strokeDashArray: 5, xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } } },
            fill: { opacity: 1 },
            tooltip: { theme: ui.isDark ? 'dark' : 'light', y: { formatter: (value) => `${prefix}${formatNumber(value)}` } },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center',
                markers: { size: 5, shape: 'circle' },
                labels: { colors: ui.heading },
                fontSize: '13px',
            },
        }).render();
    };

    const renderArea = (element, config, ui) => {
        const prefix = config.prefix || '';

        new ApexCharts(element, {
            series: [{ name: config.name, data: config.data }],
            chart: { height: 280, type: 'area', fontFamily: ui.font, toolbar: { show: false } },
            colors: ['#940000'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            legend: { show: false },
            grid: { show: true, strokeDashArray: 3, borderColor: ui.border },
            yaxis: {
                min: 0,
                max: niceMax(config.data),
                tickAmount: 4,
                labels: {
                    style: { colors: ui.body, fontSize: '12px' },
                    formatter: (value) => (value >= 1000 ? `${Math.round(value / 1000).toLocaleString()}k` : formatNumber(value)),
                },
            },
            xaxis: {
                categories: config.categories,
                labels: { style: { colors: ui.body, fontSize: '12px' } },
                axisTicks: { show: false },
                axisBorder: { show: false },
            },
            fill: {
                type: 'gradient',
                gradient: {
                    colorStops: [[
                        { offset: 0, color: '#940000', opacity: 0.25 },
                        { offset: 50, color: '#940000', opacity: 0.1 },
                        { offset: 85, color: '#940000', opacity: 0 },
                    ]],
                },
            },
            tooltip: { theme: ui.isDark ? 'dark' : 'light', y: { formatter: (value) => `${prefix}${formatNumber(value)}` } },
        }).render();
    };

    const renderGauge = (element, config, ui) => {
        new ApexCharts(element, {
            series: [config.percent],
            chart: { type: 'radialBar', offsetY: -15, height: 300, fontFamily: ui.font, sparkline: { enabled: true } },
            plotOptions: {
                radialBar: {
                    startAngle: -90,
                    endAngle: 90,
                    track: {
                        background: ui.isDark ? 'rgba(255, 255, 255, 0.08)' : '#f6eaea',
                        strokeWidth: '100%',
                        margin: 25,
                        dropShadow: { enabled: true, top: 10, left: 0, blur: 15, color: 'rgba(148, 0, 0, 1)', opacity: 0.12 },
                    },
                    dataLabels: {
                        name: { show: false },
                        value: { show: true, offsetY: -30, fontSize: '30px', fontWeight: 800, color: ui.heading, formatter: (value) => `${Math.round(value)}%` },
                    },
                },
            },
            grid: { padding: { top: 0, bottom: 0, left: 0, right: 0 } },
            fill: { colors: ['#940000'] },
            stroke: { lineCap: 'round' },
        }).render();
    };

    const renderDoughnut = (canvas, config, ui, style) => {
        const values = config.data.map(Number);
        const total = values.reduce((sum, value) => sum + value, 0);
        const isEmpty = total === 0;
        const thin = style === 'thin';

        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: isEmpty ? [config.centerLabel] : config.labels,
                datasets: [{
                    data: isEmpty ? [1] : values,
                    backgroundColor: isEmpty ? [ui.isDark ? 'rgba(255, 255, 255, 0.08)' : '#f2f2f2'] : colorsFor(values.length, ui),
                    borderRadius: thin ? 30 : 10,
                    spacing: 0,
                    hoverOffset: isEmpty ? 0 : 5,
                    borderWidth: thin ? 2 : 5,
                    borderColor: ui.surface,
                    hoverBorderColor: ui.surface,
                }],
            },
            options: {
                cutout: thin ? '85%' : '65%',
                devicePixelRatio: 2,
                layout: { padding: thin ? 10 : 20 },
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
            },
            plugins: [{
                id: 'emcaCenterText',
                afterDraw(chart) {
                    const { ctx, chartArea: { left, right, top, bottom } } = chart;
                    const active = chart.getActiveElements();
                    let value = total;
                    let label = config.centerLabel;

                    if (!isEmpty && active.length > 0) {
                        value = values[active[0].index];
                        label = config.labels[active[0].index];
                    }

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = `700 26px ${ui.font}`;
                    ctx.fillStyle = ui.heading;
                    ctx.fillText(formatNumber(value), (left + right) / 2, (top + bottom) / 2 - 8);
                    ctx.font = `14px ${ui.font}`;
                    ctx.fillStyle = ui.muted;
                    ctx.fillText(label, (left + right) / 2, (top + bottom) / 2 + 16);
                    ctx.restore();
                },
            }],
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const source = document.getElementById('emca-dashboard-data');

        if (!source) {
            return;
        }

        const charts = JSON.parse(source.textContent);
        const ui = theme();

        document.querySelectorAll('[data-emca-chart]').forEach((element) => {
            const config = charts[element.dataset.emcaChart];

            if (!config) {
                return;
            }

            const kind = element.dataset.emcaKind;

            if (kind === 'doughnut' && window.Chart) {
                renderDoughnut(element, config, ui, element.dataset.emcaStyle);
            } else if (window.ApexCharts && kind === 'spark') {
                renderSpark(element, config, ui);
            } else if (window.ApexCharts && kind === 'bar') {
                renderBar(element, config, ui);
            } else if (window.ApexCharts && kind === 'area') {
                renderArea(element, config, ui);
            } else if (window.ApexCharts && kind === 'gauge') {
                renderGauge(element, config, ui);
            }
        });

        document.querySelectorAll('[data-emca-swatch]').forEach((element) => {
            element.style.color = colorAt(Number(element.dataset.emcaSwatch), ui);
        });
    });
})();
