# Bank Risk Dashboard — Project Prompt

## What We Built

A real-time bank risk dashboard for **Risk Managers**, showing **Market Risk** and **Credit Risk** metrics generated from **synthetic (fake) data**. The dashboard refreshes automatically every 7 seconds and lets users drill into any top risk to see the full mathematical explanation.

---

## Functional Requirements

### Market Risk
- Display **Portfolio VaR** (Value at Risk) using the **parametric method** at 99% confidence, 1-day horizon
  - Formula: `VaR = Z_α × σ_P × √h` where Z₀.₉₉ = 2.326
- Display **Stressed VaR** (Basel 2.5): VaR scaled by a 2008-crisis multiplier (~2.0–2.5×)
- Display **Options Book Greeks**: net Delta, Gamma, Vega, Theta across all option positions
- Display **P&L Attribution**: split daily P&L into delta-explained vs unexplained components
- Show a **Top 5 VaR Drivers** table ranked by each factor's VaR contribution

### Credit Risk
- Display **Portfolio EAD** (total Exposure at Default)
- Display **Expected Loss** (EL = PD × LGD × EAD) aggregated across all counterparties
- Display **CVA** (Credit Valuation Adjustment): `CVA ≈ (1−R) × PD × EAD × DF_T`
- Display **EAD-weighted average PD** across the portfolio
- Show a **Top 5 Counterparties by Exposure** table with PD, LGD, EAD, EL, and CVA columns

### Real-Time Feel
- AJAX polling every **7 seconds** using `fetch()` to PHP API endpoints
- Seed strategy: `seed = Math.floor(Date.now() / 7000)` — consistent data within each 7-second window, evolving smoothly across windows
- Live indicator badge pulses on each refresh
- Drill-down panel is stable while open (polling only updates KPI cards)

### Drill-Down on Top Risks
When a user clicks any row in a top-5 table, a slide-in panel appears showing:
1. **Symbolic formula** rendered in KaTeX (e.g., `VaR = Z_α · σ_P · √h`)
2. **Substituted formula** with the actual synthetic numbers (e.g., `VaR = 2.326 × $3,200,000 × √1`)
3. **Step-by-step numbered breakdown** explaining each variable and the final result
4. **Chart.js chart** appropriate to the risk type:
   - Market risk factor → P&L distribution histogram with VaR threshold line
   - Credit counterparty → horizontal bar chart (EAD / EL / CVA)

---

## Technology Stack

| Layer       | Choice                    | Reason                                      |
|-------------|---------------------------|---------------------------------------------|
| Backend     | PHP 8.x (plain, no framework) | Runs on XAMPP with zero install steps    |
| Frontend    | HTML5 + vanilla JS (ES6)  | No build step, no npm                       |
| CSS         | Custom CSS + Bootstrap 5 CDN | Dark finance theme with design tokens    |
| Math render | KaTeX 0.16 (CDN)          | Synchronous rendering, faster than MathJax  |
| Charts      | Chart.js 4 (CDN)          | Lightweight, beautiful, no dependencies     |
| Data        | PHP seeded PRNG            | Reproducible synthetic data per time window |

---

## File Structure

```
Assignment5B/
├── index.php               ← Single-page HTML shell, loads all CDN assets
├── api/
│   ├── market_data.php     ← JSON endpoint: MarketRiskSnapshot
│   └── credit_data.php     ← JSON endpoint: CreditRiskSnapshot
├── src/
│   ├── MathHelper.php      ← Pure math: VaR, EL, CVA + KaTeX formula card generators
│   ├── SyntheticMarket.php ← Generates 20 synthetic market positions
│   └── SyntheticCredit.php ← Generates 15 synthetic counterparties
├── js/
│   ├── dashboard.js        ← Polling loop, tab switching
│   ├── market_panel.js     ← Market risk KPI + table rendering
│   ├── credit_panel.js     ← Credit risk KPI + table rendering
│   └── drilldown.js        ← Slide-in panel: KaTeX + Chart.js
└── css/
    └── dashboard.css       ← Dark finance theme (CSS custom properties)
```

---

## Deployment

1. Copy this folder to `C:\xampp\htdocs\aiprojects\Assignment5B\`
2. Start Apache in XAMPP Control Panel
3. Open: `http://localhost/aiprojects/Assignment5B/index.php`
4. No database, no Composer, no npm — nothing to install

---

## Math Covered

| Risk Type | Formula | Variables |
|-----------|---------|-----------|
| Parametric VaR | `VaR = Z_α × σ_P × √h` | Z_α = inverse normal CDF, σ_P = portfolio daily vol, h = holding period |
| Stressed VaR | `SVaR = VaR × m_stress` | m_stress = 2008-calibrated multiplier per asset class |
| Expected Loss | `EL = PD × LGD × EAD` | PD = probability of default, LGD = loss given default, EAD = exposure |
| CVA | `CVA ≈ (1−R) × PD × EAD × DF_T` | R = recovery rate, DF = discount factor = e^(−rT) |
| Delta P&L | `ΔP ≈ δ × ΔS + ½γ(ΔS)²` | δ = delta, γ = gamma, ΔS = spot move |
| Risk Factor VaR | `VaR_i = Z_α × β_i × σ_i × N_i` | β = beta, σ = volatility, N = notional |
