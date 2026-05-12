# Bank Risk Dashboard

A real-time bank risk dashboard for **Risk Managers**, displaying **Market Risk** and **Credit Risk** metrics from synthetic data. Numbers refresh automatically every 7 seconds; clicking any top-risk row opens a slide-in drill-down panel with the full mathematical derivation.

## Features

### Market Risk
- **Portfolio VaR** — parametric method at 99% confidence, 1-day horizon (`VaR = Z_α × σ_P × √h`)
- **Stressed VaR** — Basel 2.5 VaR scaled by a 2008-crisis multiplier (~2.0–2.5×)
- **Options Greeks** — net Delta, Gamma, Vega, Theta across all option positions
- **P&L Attribution** — daily P&L split into delta-explained vs unexplained components
- **Top 5 VaR Drivers** table, clickable for drill-down

### Credit Risk
- **Total EAD** — portfolio Exposure at Default
- **Expected Loss** — `EL = PD × LGD × EAD` aggregated across all counterparties
- **CVA** — `CVA ≈ (1−R) × PD × EAD × DF_T`
- **EAD-weighted Average PD**
- **Top 5 Counterparties by Exposure** table, clickable for drill-down

### Drill-Down Panel
Clicking any row in a top-5 table slides in a panel showing:
1. Symbolic formula rendered with **KaTeX**
2. Substituted formula with the actual synthetic values
3. Step-by-step numbered breakdown
4. **Chart.js** chart (P&L distribution histogram for market risk; EAD/EL/CVA bar chart for credit risk)

### Real-Time Feel
- AJAX polling every **7 seconds** via `fetch()` to PHP API endpoints
- Seed strategy: `seed = floor(unixtime / 7)` — data is consistent within each 7-second window and evolves smoothly across windows
- Live indicator badge pulses on each refresh
- Language switcher: **English / French**

## Tech Stack

| Layer | Choice | Reason |
|---|---|---|
| Backend | PHP 8.x (no framework) | Runs on XAMPP with zero install |
| Frontend | HTML5 + Vanilla JS (ES6) | No build step, no npm |
| CSS | Bootstrap 5 CDN + custom CSS | Dark finance theme with design tokens |
| Math | KaTeX 0.16 CDN | Synchronous rendering, faster than MathJax |
| Charts | Chart.js 4 CDN | Lightweight, no dependencies |
| Data | PHP seeded PRNG | Reproducible synthetic data per time window |

## Project Structure

```
Bank-Risk-Dashboard/
├── index.php               # Single-page HTML shell, loads all CDN assets
├── api/
│   ├── market_data.php     # JSON endpoint: MarketRiskSnapshot
│   └── credit_data.php     # JSON endpoint: CreditRiskSnapshot
├── src/
│   ├── MathHelper.php      # VaR, EL, CVA formulas + KaTeX card generators
│   ├── SyntheticMarket.php # Generates 20 synthetic market positions
│   └── SyntheticCredit.php # Generates 15 synthetic counterparties
├── js/
│   ├── dashboard.js        # 7-second polling loop, tab switching
│   ├── market_panel.js     # Market risk KPI cards + table rendering
│   ├── credit_panel.js     # Credit risk KPI cards + table rendering
│   ├── drilldown.js        # Slide-in panel: KaTeX + Chart.js
│   ├── i18n.js             # Internationalization (EN/FR)
│   └── popups.js           # Metric explanation tooltips
└── css/
    └── dashboard.css       # Dark finance theme (CSS custom properties)
```

## Installation

**Requirements:** XAMPP (Apache + PHP 8.x). No database, no Composer, no npm.

1. Clone or copy this folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\aiprojects\Assignment5 B\Bank-Risk-Dashboard\
   ```
2. Start Apache in the XAMPP Control Panel.
3. Open in your browser:
   ```
   http://localhost/aiprojects/Assignment5%20B/Bank-Risk-Dashboard/index.php
   ```

You can also test the API endpoints directly:
- `http://localhost/aiprojects/Assignment5%20B/Bank-Risk-Dashboard/api/market_data.php`
- `http://localhost/aiprojects/Assignment5%20B/Bank-Risk-Dashboard/api/credit_data.php`

## Math Reference

| Formula | Expression | Key Variables |
|---|---|---|
| Parametric VaR | `VaR = Z_α × σ_P × √h` | Z_α = 2.326 (99%), σ_P = portfolio vol, h = holding period |
| Stressed VaR | `SVaR = VaR × m_stress` | m_stress = 2008-calibrated multiplier per asset class |
| Expected Loss | `EL = PD × LGD × EAD` | PD = prob. of default, LGD = loss given default |
| CVA | `CVA ≈ (1−R) × PD × EAD × DF_T` | R = recovery rate, DF = e^(−rT) |
| Delta P&L | `ΔP ≈ δ×ΔS + ½γ(ΔS)²` | δ = delta, γ = gamma, ΔS = spot move |
| Risk Factor VaR | `VaR_i = Z_α × β_i × σ_i × N_i` | β = beta, σ = volatility, N = notional |

## Known Limitations

- **Correlation**: Portfolio VaR uses simplified within-class correlation, not a full cross-asset covariance matrix.
- **Monte Carlo / Historical VaR**: Not implemented; parametric VaR is used for pedagogical clarity.
- **CVA approximation**: One-factor model only; production CVA requires Monte Carlo exposure simulation.
- **No authentication**: This is a demonstration dashboard — add login before any shared deployment.
