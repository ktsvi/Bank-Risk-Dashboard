'use strict';

function renderCreditPanel(data) {
    renderCreditKPIs(data);
    renderCreditTop5(data.top_counterparties, data);
}

// ── KPI Cards ────────────────────────────────────────────
function renderCreditKPIs(data) {
    const p = data.portfolio;
    setCKPI('c-ead', '$' + fmtUSD(p.total_ead_usd),  I18n.t('kpi_ead_sub'));
    setCKPI('c-el',  '$' + fmtUSD(p.total_el_usd),   I18n.t('kpi_el_sub'));
    setCKPI('c-cva', '$' + fmtUSD(p.total_cva_usd),  I18n.t('kpi_cva_sub'));
    setCKPI('c-pd',  fmtPct(p.weighted_avg_pd, 3),   I18n.t('kpi_pd_sub'));
}

function setCKPI(id, value, sub) {
    const card = document.getElementById(id);
    if (!card) return;
    const valEl = card.querySelector('.kpi-card__value');
    const subEl = card.querySelector('.kpi-card__sub');
    if (valEl) valEl.textContent = value;
    if (subEl) subEl.textContent = sub || '';
}

// ── Top-5 Counterparties table ───────────────────────────
function renderCreditTop5(cps, fullData) {
    const tbody = document.getElementById('credit-top5-body');
    if (!tbody) return;

    tbody.innerHTML = cps.map((cp, i) => {
        const rankClass   = i === 0 ? 'top1' : i === 1 ? 'top2' : i === 2 ? 'top3' : '';
        const ratingClass = ratingCssClass(cp.rating_bucket);

        const payload = JSON.stringify(Object.assign({}, cp, {
            pd_by_bucket:       fullData.pd_by_bucket,
            exposure_by_bucket: fullData.exposure_by_bucket,
        })).replace(/'/g, '&#39;');

        return `<tr onclick="DrillDown.open('credit','${cp.id}',JSON.parse(this.dataset.payload))" data-payload='${payload}'>
            <td><span class="rank-badge ${rankClass}">${cp.rank}</span></td>
            <td>
                <div style="font-weight:600">${escC(cp.name)}</div>
                <span class="rating-chip ${ratingClass}">${cp.rating_bucket}</span>
            </td>
            <td class="right">$${fmtUSD(cp.ead_usd)}</td>
            <td class="right">${fmtPct(cp.pd, 2)}</td>
            <td class="right text-muted">${fmtPct(cp.lgd, 0)}</td>
            <td class="right text-amber">$${fmtUSD(cp.el_usd)}</td>
            <td class="right text-red">$${fmtUSD(cp.cva_usd)}</td>
        </tr>`;
    }).join('');
}

function ratingCssClass(bucket) {
    const map = { 'AAA/AA': 'rating-aaa', 'A/BBB': 'rating-abbb', 'BB': 'rating-bb', 'B': 'rating-b', 'CCC': 'rating-ccc' };
    return map[bucket] || '';
}

function escC(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

window.renderCreditPanel = renderCreditPanel;
