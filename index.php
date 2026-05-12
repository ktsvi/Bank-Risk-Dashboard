<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Risk Dashboard</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- KaTeX -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

  <!-- Dashboard CSS -->
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<!-- ── Top navigation ──────────────────────────────────── -->
<nav class="topnav">
  <div class="topnav__brand">
    <div class="topnav__brand-icon">&#9827;</div>
    Risk Dashboard
    <span style="font-size:11px;color:var(--text-muted);font-weight:400;margin-left:4px"
          data-i18n="synthetic_label">· Synthetic Data</span>
  </div>
  <div class="topnav__right">
    <!-- Language switcher -->
    <div class="lang-switcher">
      <button class="lang-btn active" data-lang="en">EN</button>
      <button class="lang-btn" data-lang="fr">FR</button>
    </div>
    <!-- Live badge -->
    <div class="badge-live">
      <div class="badge-live__dot" id="live-dot"></div>
      <span data-i18n="live_label">LIVE</span>&nbsp;
      <span class="badge-live__time" id="last-refresh">—</span>
    </div>
  </div>
</nav>

<!-- ── Error banner ────────────────────────────────────── -->
<div id="error-banner" style="display:none;background:var(--red);color:#fff;padding:8px 24px;font-size:13px;text-align:center"></div>

<!-- ── Layout ──────────────────────────────────────────── -->
<div class="layout">

  <!-- Main content -->
  <main class="main-content" id="main-content">

    <!-- Tabs -->
    <div class="risk-tabs">
      <button class="risk-tab active" data-tab="market" data-i18n="tab_market">Market Risk</button>
      <button class="risk-tab" data-tab="credit" data-i18n="tab_credit">Credit Risk</button>
    </div>

    <!-- ══ MARKET RISK PANEL ══════════════════════════════ -->
    <div class="tab-panel" data-panel="market">

      <p class="section-title" data-i18n="section_kri">Key Risk Indicators</p>
      <div class="kpi-grid">

        <div class="kpi-card kpi-card--red" id="m-var" data-popup="popup.var">
          <div class="kpi-card__label" data-i18n="kpi_var_label">Portfolio VaR (99%, 1d)</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub">Loading…</div>
        </div>

        <div class="kpi-card kpi-card--amber" id="m-svar" data-popup="popup.svar">
          <div class="kpi-card__label" data-i18n="kpi_svar_label">Stressed VaR (2008 Crisis)</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub">Loading…</div>
        </div>

        <div class="kpi-card kpi-card--cyan" id="m-sigma" data-popup="popup.sigma">
          <div class="kpi-card__label" data-i18n="kpi_sigma_label">Portfolio Daily σ</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub" data-i18n="kpi_sigma_sub">1-day portfolio volatility</div>
        </div>

        <div class="kpi-card kpi-card--green" id="m-pnl" data-popup="popup.pnl">
          <div class="kpi-card__label" data-i18n="kpi_pnl_label">Daily P&amp;L</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub" data-i18n="kpi_pnl_sub">Total portfolio</div>
        </div>

      </div>

      <!-- Greeks -->
      <p class="section-title" data-i18n="section_greeks">Options Book Greeks</p>
      <div class="greeks-row" id="greeks-row">
        <div class="greek-badge"><span>Δ Delta</span>—</div>
        <div class="greek-badge"><span>Γ Gamma</span>—</div>
        <div class="greek-badge"><span>ν Vega</span>—</div>
        <div class="greek-badge"><span>θ Theta</span>—</div>
      </div>

      <!-- P&L Attribution -->
      <p class="section-title" data-i18n="section_pnl_attr">P&amp;L Attribution</p>
      <div class="top5-wrap" style="margin-bottom:28px">
        <div class="top5-header">
          <h3 data-i18n="section_pnl_attr">P&amp;L Attribution</h3>
          <span class="top5-hint" data-i18n="hint_pnl_sub">How much of P&L is explained by delta?</span>
        </div>
        <div style="padding:16px 20px">
          <div class="pnl-attr" id="pnl-attr">
            <div class="skeleton" style="height:16px;margin-bottom:8px"></div>
            <div class="skeleton" style="height:16px;margin-bottom:8px"></div>
            <div class="skeleton" style="height:16px"></div>
          </div>
        </div>
      </div>

      <!-- Top-5 Risk Factors -->
      <div class="top5-wrap">
        <div class="top5-header">
          <h3 data-i18n="section_top5_market">Top 5 VaR Drivers</h3>
          <span class="top5-hint" data-i18n="hint_click">Click any row to see the math</span>
        </div>
        <table class="top5-table">
          <thead>
            <tr>
              <th style="width:40px" data-i18n="col_rank">#</th>
              <th data-i18n="col_factor">Risk Factor</th>
              <th class="right" data-i18n="col_var_contrib">VaR Contribution</th>
              <th class="right" data-popup="popup.col_var_pct" data-i18n="col_var_pct">% of Total</th>
              <th class="right" data-popup="popup.col_beta" data-i18n="col_beta">Beta</th>
              <th class="right" data-popup="popup.col_sigma" data-i18n="col_sigma">Daily σ</th>
            </tr>
          </thead>
          <tbody id="market-top5-body">
            <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted)" data-i18n="loading">Loading…</td></tr>
          </tbody>
        </table>
      </div>

    </div><!-- /market panel -->

    <!-- ══ CREDIT RISK PANEL ═══════════════════════════════ -->
    <div class="tab-panel panel-hidden" data-panel="credit">

      <p class="section-title" data-i18n="section_credit_summary">Credit Portfolio Summary</p>
      <div class="kpi-grid">

        <div class="kpi-card kpi-card--red" id="c-ead" data-popup="popup.ead">
          <div class="kpi-card__label" data-i18n="kpi_ead_label">Total EAD</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub" data-i18n="kpi_ead_sub">Exposure at Default</div>
        </div>

        <div class="kpi-card kpi-card--amber" id="c-el" data-popup="popup.el">
          <div class="kpi-card__label" data-i18n="kpi_el_label">Total Expected Loss</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub" data-i18n="kpi_el_sub">EL = PD × LGD × EAD</div>
        </div>

        <div class="kpi-card kpi-card--purple" id="c-cva" data-popup="popup.cva">
          <div class="kpi-card__label" data-i18n="kpi_cva_label">Portfolio CVA</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub" data-i18n="kpi_cva_sub">Credit Valuation Adj.</div>
        </div>

        <div class="kpi-card kpi-card--cyan" id="c-pd" data-popup="popup.pd">
          <div class="kpi-card__label" data-i18n="kpi_pd_label">Wtd Avg PD</div>
          <div class="kpi-card__value">—</div>
          <div class="kpi-card__sub" data-i18n="kpi_pd_sub">EAD-weighted average</div>
        </div>

      </div>

      <!-- Top-5 Counterparties -->
      <div class="top5-wrap">
        <div class="top5-header">
          <h3 data-i18n="section_top5_credit">Top 5 Counterparties by Exposure</h3>
          <span class="top5-hint" data-i18n="hint_click">Click any row to see the math</span>
        </div>
        <table class="top5-table">
          <thead>
            <tr>
              <th style="width:40px" data-i18n="col_rank">#</th>
              <th data-i18n="col_counterparty">Counterparty</th>
              <th class="right" data-i18n="col_ead">EAD</th>
              <th class="right" data-popup="popup.col_pd_cp" data-i18n="col_pd">PD</th>
              <th class="right" data-popup="popup.col_lgd" data-i18n="col_lgd">LGD</th>
              <th class="right" data-popup="popup.col_el_cp" data-i18n="col_el">Expected Loss</th>
              <th class="right" data-popup="popup.col_cva_cp" data-i18n="col_cva">CVA</th>
            </tr>
          </thead>
          <tbody id="credit-top5-body">
            <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted)" data-i18n="loading">Loading…</td></tr>
          </tbody>
        </table>
      </div>

    </div><!-- /credit panel -->

  </main><!-- /main-content -->

  <!-- ── Drill-down panel ─────────────────────────────── -->
  <div class="drill-panel" id="drill-panel">
    <div class="drill-header">
      <span class="drill-title" id="drill-title">Risk Detail</span>
      <button class="drill-close" onclick="DrillDown.close()" title="Close">&#x2715;</button>
    </div>
    <div class="drill-body">

      <!-- Symbolic formula -->
      <div class="formula-card" id="drill-formula-symbolic">
        <div class="formula-card__label" data-i18n="drill_symbolic">Formula (Symbolic)</div>
        <div class="formula-card__math"></div>
      </div>

      <!-- Substituted formula -->
      <div class="formula-card formula-card--substituted" id="drill-formula-substituted">
        <div class="formula-card__label" data-i18n="drill_substituted">Formula (with Your Numbers)</div>
        <div class="formula-card__math"></div>
      </div>

      <!-- Extra formula (CVA for credit) -->
      <div class="formula-card formula-card--substituted" id="drill-formula-extra" style="display:none">
        <div class="formula-card__label" data-i18n="drill_cva_label">CVA Formula (with Your Numbers)</div>
        <div class="formula-card__math"></div>
      </div>

      <!-- Step-by-step -->
      <div>
        <p class="step-section__title" data-i18n="drill_steps">Step-by-Step Calculation</p>
        <ol class="step-list" id="drill-steps"></ol>
      </div>

      <!-- Chart -->
      <div>
        <p class="chart-section__title" data-i18n="drill_chart">Supporting Chart</p>
        <div class="chart-wrap" style="height:240px">
          <canvas id="drill-chart"></canvas>
        </div>
      </div>

    </div><!-- /drill-body -->
  </div><!-- /drill-panel -->

</div><!-- /layout -->

<!-- ── Metric explanation popup ────────────────────────── -->
<div id="metric-popup" class="metric-popup" role="tooltip" aria-live="polite">
  <div class="metric-popup__header">
    <span class="metric-popup__title"></span>
    <button class="metric-popup__close" onclick="Popup.close()" aria-label="Close">×</button>
  </div>
  <p class="metric-popup__body"></p>
</div>

<!-- JS load order: i18n → popups → panels → dashboard (boot) -->
<script src="js/i18n.js"></script>
<script src="js/popups.js"></script>
<script src="js/drilldown.js"></script>
<script src="js/market_panel.js"></script>
<script src="js/credit_panel.js"></script>
<script src="js/dashboard.js"></script>

</body>
</html>
