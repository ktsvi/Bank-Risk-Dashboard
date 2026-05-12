'use strict';

function renderMarketPanel(data) {
    renderMarketKPIs(data);
    renderGreeksRow(data.greeks);
    renderPnLAttribution(data.pnl_attribution);
    renderMarketTop5(data.top_risk_factors, data.var);
}

// ── KPI Cards ────────────────────────────────────────────
function renderMarketKPIs(data) {
    setKPI('m-var',
        '$' + fmtUSD(data.var.var_usd),
        fmtPct(data.var.var_pct_notional) + ' ' + I18n.t('kpi_var_sub'));

    setKPI('m-svar',
        '$' + fmtUSD(data.stressed_var.svar_usd),
        I18n.t('kpi_svar_sub') + ': ' + data.stressed_var.multiplier + 'x');

    setKPI('m-sigma',
        '$' + fmtUSD(data.var.portfolio_sigma_usd),
        I18n.t('kpi_sigma_sub'));

    setKPI('m-pnl',
        (data.pnl_attribution.total_pnl >= 0 ? '+' : '') + '$' + fmtUSD(data.pnl_attribution.total_pnl),
        I18n.t('kpi_pnl_sub'),
        data.pnl_attribution.total_pnl >= 0 ? 'text-green' : 'text-red');
}

function setKPI(id, value, sub, colorClass) {
    const card = document.getElementById(id);
    if (!card) return;
    const valEl = card.querySelector('.kpi-card__value');
    const subEl = card.querySelector('.kpi-card__sub');
    if (valEl) {
        valEl.textContent = value;
        valEl.className = 'kpi-card__value' + (colorClass ? ' ' + colorClass : '');
    }
    if (subEl) subEl.textContent = sub || '';
}

// ── Greeks row ───────────────────────────────────────────
function renderGreeksRow(greeks) {
    const row = document.getElementById('greeks-row');
    if (!row) return;
    const items = [
        { label: 'Δ ' + I18n.t('greek_delta'), val: '$' + fmtUSD(greeks.net_delta),  color: greeks.net_delta >= 0 ? 'text-green' : 'text-red', popup: 'popup.delta' },
        { label: 'Γ ' + I18n.t('greek_gamma'), val: fmtUSD(greeks.net_gamma, 0),      color: '',                                                  popup: 'popup.gamma' },
        { label: 'ν ' + I18n.t('greek_vega'),  val: '$' + fmtUSD(greeks.net_vega),    color: greeks.net_vega  >= 0 ? 'text-green' : 'text-red',  popup: 'popup.vega' },
        { label: 'θ ' + I18n.t('greek_theta'), val: '$' + fmtUSD(greeks.net_theta),   color: 'text-red',                                          popup: 'popup.theta' },
    ];

    row.innerHTML = items.map(it =>
        `<div class="greek-badge" data-popup="${it.popup}" data-popup-title="${esc(it.label)}">` +
        `<span>${esc(it.label)}</span><strong class="${it.color}">${it.val}</strong></div>`
    ).join('');
}

// ── P&L Attribution mini-bars ────────────────────────────
function renderPnLAttribution(attr) {
    const wrap = document.getElementById('pnl-attr');
    if (!wrap) return;
    const total = Math.abs(attr.total_pnl) || 1;

    const rows = [
        { key: 'pnl_total',       val: attr.total_pnl,       color: attr.total_pnl >= 0 ? '#1ebd7a' : '#e84545', popup: 'popup.pnl_total' },
        { key: 'pnl_delta',       val: attr.delta_pnl,       color: '#1e6fc2',                                    popup: 'popup.pnl_delta' },
        { key: 'pnl_unexplained', val: attr.unexplained_pnl, color: '#f5a623',                                    popup: 'popup.pnl_unexplained' },
    ];

    wrap.innerHTML = rows.map(r => {
        const label = I18n.t(r.key);
        const pct   = Math.min(100, (Math.abs(r.val) / total) * 100).toFixed(1);
        const sign  = r.val >= 0 ? '+' : '';
        const cls   = r.val >= 0 ? 'pnl-pos' : 'pnl-neg';
        return `<div class="pnl-attr__row" data-popup="${r.popup}" data-popup-title="${esc(label)}">` +
            `<span class="pnl-attr__label">${esc(label)}</span>` +
            `<div class="pnl-attr__bar"><div class="pnl-attr__fill" style="width:${pct}%;background:${r.color}"></div></div>` +
            `<span class="pnl-attr__val ${cls}">${sign}$${fmtUSD(r.val)}</span>` +
            `</div>`;
    }).join('');
}

// ── Top-5 Risk Factors table ─────────────────────────────
function renderMarketTop5(factors, varData) {
    const tbody = document.getElementById('market-top5-body');
    if (!tbody) return;

    const maxVar = factors.length ? factors[0].var_contribution_usd : 1;

    tbody.innerHTML = factors.map((f, i) => {
        const rankClass  = i === 0 ? 'top1' : i === 1 ? 'top2' : i === 2 ? 'top3' : '';
        const pct        = (f.var_pct * 100).toFixed(1);
        const barPct     = ((f.var_contribution_usd / maxVar) * 100).toFixed(1);
        const classLabel = f.asset_class;

        const payload = JSON.stringify(Object.assign({}, f, {
            pnl_distribution: varData.pnl_distribution,
            pnl_frequencies:  varData.pnl_frequencies,
            var_usd:          varData.var_usd,
            portfolio_sigma:  varData.portfolio_sigma_usd,
            z_score:          varData.z_score,
        })).replace(/'/g, '&#39;');

        return `<tr onclick="DrillDown.open('market','${f.id}',JSON.parse(this.dataset.payload))" data-payload='${payload}'>
            <td><span class="rank-badge ${rankClass}">${f.rank}</span></td>
            <td>
                <div style="font-weight:600">${esc(f.factor)}</div>
                <span class="class-chip class-${classLabel}">${classLabel}</span>
            </td>
            <td class="right">
                <div class="var-bar-wrap">
                    <div class="var-bar"><div class="var-bar__fill" style="width:${barPct}%"></div></div>
                    <span>$${fmtUSD(f.var_contribution_usd)}</span>
                </div>
            </td>
            <td class="right text-muted">${pct}%</td>
            <td class="right text-muted">${f.beta}</td>
            <td class="right text-muted">${(f.sigma_daily * 100).toFixed(2)}%</td>
        </tr>`;
    }).join('');
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

window.renderMarketPanel = renderMarketPanel;
