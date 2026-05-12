'use strict';

const DrillDown = (() => {
    let chartInstance = null;

    // ── Public entry point ───────────────────────────────
    function open(domain, id, data) {
        document.querySelectorAll('.top5-table tbody tr').forEach(r => r.classList.remove('selected'));
        // Mark the clicked row (event is available from the inline onclick)
        if (typeof event !== 'undefined' && event.currentTarget) {
            event.currentTarget.classList.add('selected');
        }

        renderPanel(domain, id, data);
        document.getElementById('drill-panel').classList.add('open');
        document.querySelector('.main-content')?.classList.add('panel-open');
        if (typeof drillOpen !== 'undefined') drillOpen = true;

        // Re-apply i18n labels inside the panel
        applyDrillI18n();
    }

    function close() {
        document.getElementById('drill-panel').classList.remove('open');
        document.querySelector('.main-content')?.classList.remove('panel-open');
        document.querySelectorAll('.top5-table tbody tr').forEach(r => r.classList.remove('selected'));
        destroyChart();
        if (typeof drillOpen !== 'undefined') drillOpen = false;
    }

    function applyDrillI18n() {
        document.querySelectorAll('#drill-panel [data-i18n]').forEach(el => {
            el.textContent = I18n.t(el.dataset.i18n);
        });
    }

    // ── Main renderer ────────────────────────────────────
    function renderPanel(domain, id, data) {
        const titleEl = document.getElementById('drill-title');

        if (domain === 'market') {
            titleEl.textContent = data.factor + ' — Risk Drill-Down';
            renderMarketDrillDown(id, data);
        } else {
            titleEl.textContent = data.name + ' — Credit Drill-Down';
            renderCreditDrillDown(id, data);
        }
    }

    // ── Market drill-down ────────────────────────────────
    function renderMarketDrillDown(id, data) {
        const fc = data.formula_card;

        renderFormulaBlock(
            'drill-formula-symbolic',
            I18n.t('drill_symbolic'),
            fc.latex_symbolic
        );
        renderFormulaBlock(
            'drill-formula-substituted',
            I18n.t('drill_substituted'),
            fc.latex_substituted
        );
        document.getElementById('drill-formula-extra').style.display = 'none';
        renderSteps(fc.steps);

        destroyChart();
        if (data.pnl_distribution && data.pnl_frequencies) {
            buildVaRHistogram(data);
        } else {
            buildRiskFactorBarChart(data);
        }
    }

    // ── Credit drill-down ────────────────────────────────
    function renderCreditDrillDown(id, data) {
        const fc = data.formula_card;

        renderFormulaBlock(
            'drill-formula-symbolic',
            I18n.t('drill_el_symbolic'),
            fc.el.latex_symbolic
        );
        renderFormulaBlock(
            'drill-formula-substituted',
            I18n.t('drill_el_substituted'),
            fc.el.latex_substituted
        );

        const extraEl = document.getElementById('drill-formula-extra');
        if (extraEl) {
            extraEl.style.display = 'block';
            extraEl.querySelector('.formula-card__label').textContent = I18n.t('drill_cva_label');
            safeKatex(extraEl.querySelector('.formula-card__math'), fc.cva.latex_substituted);
        }

        renderSteps(fc.el.steps);

        destroyChart();
        buildExposureBar(data);
    }

    // ── KaTeX rendering ──────────────────────────────────
    function renderFormulaBlock(containerId, label, latex) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.querySelector('.formula-card__label').textContent = label;
        safeKatex(container.querySelector('.formula-card__math'), latex);
    }

    function safeKatex(el, latex) {
        if (!el) return;
        try {
            el.innerHTML = katex.renderToString(latex, {
                displayMode:  true,
                throwOnError: false,
                output:       'html',
                trust:        false,
            });
        } catch (e) {
            el.textContent = latex;
        }
    }

    function renderSteps(steps) {
        const ol = document.getElementById('drill-steps');
        if (!ol) return;
        ol.innerHTML = steps.map(step => {
            let rendered = step;
            try {
                rendered = katex.renderToString(step, { displayMode: false, throwOnError: false, output: 'html' });
            } catch (e) { /* keep raw */ }
            return `<li><span>${rendered}</span></li>`;
        }).join('');
    }

    // ── Chart builders ────────────────────────────────────
    function buildVaRHistogram(data) {
        const ctx     = document.getElementById('drill-chart').getContext('2d');
        const varUsd  = data.var_usd;
        const buckets = data.pnl_distribution;
        const freqs   = data.pnl_frequencies;

        const varBucket = buckets.reduce((best, b, i) =>
            Math.abs(b + varUsd) < Math.abs(buckets[best] + varUsd) ? i : best, 0);

        const colors = buckets.map((b, i) =>
            i <= varBucket ? 'rgba(232,69,69,0.7)' : 'rgba(30,111,194,0.6)');

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   buckets.map(b => '$' + fmtUSD(b)),
                datasets: [{ label: 'Frequency', data: freqs, backgroundColor: colors, borderWidth: 0, borderRadius: 3 }],
            },
            options: chartDefaults('P&L Distribution (1-Day)'),
        });
    }

    function buildRiskFactorBarChart(data) {
        const ctx = document.getElementById('drill-chart').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['VaR Contribution', 'Position σ × Notional'],
                datasets: [{
                    label: data.factor,
                    data:  [data.var_contribution_usd, data.beta * data.sigma_daily * data.notional_usd],
                    backgroundColor: ['rgba(232,69,69,0.7)', 'rgba(30,111,194,0.6)'],
                    borderWidth: 0, borderRadius: 4,
                }],
            },
            options: chartDefaults('Risk Factor Decomposition'),
        });
    }

    function buildExposureBar(data) {
        const ctx = document.getElementById('drill-chart').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.exposure_chart_data.labels,
                datasets: [{
                    label: 'USD',
                    data:  data.exposure_chart_data.values,
                    backgroundColor: ['rgba(30,111,194,0.7)', 'rgba(245,166,35,0.7)', 'rgba(232,69,69,0.7)'],
                    borderWidth: 0, borderRadius: 4,
                }],
            },
            options: chartDefaults('Exposure Breakdown (USD)', { indexAxis: 'y' }),
        });
    }

    function chartDefaults(title, extra = {}) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: Object.assign({
                legend: { display: false },
                title: { display: true, text: title, color: '#7d99bb', font: { size: 12, weight: '600' }, padding: { bottom: 10 } },
                tooltip: {
                    backgroundColor: '#112038', borderColor: '#1e3a5f', borderWidth: 1,
                    titleColor: '#e4eaf4', bodyColor: '#7d99bb',
                    callbacks: { label: ctx => '$' + fmtUSD(ctx.parsed.y ?? ctx.parsed.x) },
                },
            }, extra.plugins || {}),
            scales: {
                x: { grid: { color: '#1a2e4a' }, ticks: { color: '#7d99bb', font: { size: 10 } } },
                y: { grid: { color: '#1a2e4a' }, ticks: { color: '#7d99bb', font: { size: 10 }, callback: v => '$' + fmtUSD(v) } },
                ...(extra.scales || {}),
            },
            ...Object.fromEntries(Object.entries(extra).filter(([k]) => !['plugins', 'scales'].includes(k))),
        };
    }

    function destroyChart() {
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
    }

    return { open, close, applyDrillI18n };
})();

window.DrillDown = DrillDown;
