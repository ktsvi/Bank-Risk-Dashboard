# Project Context Log

## Project: Bank Risk Real-Time Dashboard
**Date:** 2026-05-12  
**Directory:** `C:\xampp\htdocs\aiprojects\Assignment5B`

---

## 1. Requirements Gathering

**Questions asked and answers received:**

| Question | Answer |
|----------|--------|
| Risk categories | Market Risk + Credit Risk |
| Primary audience | Risk Managers |
| Technology stack | PHP + HTML/CSS/JS (XAMPP, zero install) |
| Drill-down behavior | Both: formula card with substituted values AND supporting charts |

---

## 2. Architecture Decisions

### Why parametric VaR (not Monte Carlo)?
Parametric VaR produces an exact, human-readable formula (`VaR = Z × σ × √h`) that can be substituted directly into KaTeX. Monte Carlo requires generating 10,000 paths with no pedagogical advantage in this context.

### Why KaTeX (not MathJax)?
KaTeX renders **synchronously** via `katex.renderToString()`, so the drill-down panel appears instantly. MathJax requires an async typesetting queue which introduces visible latency. KaTeX is also ~10× smaller on the CDN.

### Why seeded PRNG?
Using `mt_srand($seed)` with seed = `floor(unixtime / 7)` ensures:
- Both market and credit endpoints return a **consistent synthetic world** within a given 7-second window
- Numbers **evolve smoothly** across poll cycles (adjacent seeds give plausible variations)
- No flickering when polling fires near a drill-down open event

### Why separate API endpoints?
Independent failure isolation: a crash in credit data generation doesn't blank the market panel. Each endpoint can be tested directly in a browser.

### Why Bootstrap 5 + custom CSS?
Bootstrap handles responsive layout and grid. All visual identity (dark navy, risk color coding) uses custom CSS tokens (`--navy`, `--red`, `--amber`, `--green`, etc.) that override Bootstrap's defaults.

---

## 3. File Creation Log

| # | File | Status | Notes |
|---|------|--------|-------|
| 1 | `src/MathHelper.php` | ✅ Created | Pure math + KaTeX formula card generators |
| 2 | `src/SyntheticMarket.php` | ✅ Created | 20 synthetic positions, VaR + Greeks + SVaR |
| 3 | `src/SyntheticCredit.php` | ✅ Created | 15 counterparties, EL + CVA per entity |
| 4 | `api/market_data.php` | ✅ Created | Thin JSON wrapper, accepts `?seed=` |
| 5 | `api/credit_data.php` | ✅ Created | Thin JSON wrapper, accepts `?seed=` |
| 6 | `css/dashboard.css` | ✅ Created | Dark finance theme, drill panel, animations |
| 7 | `index.php` | ✅ Created | Full HTML skeleton with all slots |
| 8 | `js/dashboard.js` | ✅ Created | 7s polling loop, tab switching |
| 9 | `js/market_panel.js` | ✅ Created | KPI cards, Greeks row, PnL attr, top-5 |
| 10 | `js/credit_panel.js` | ✅ Created | KPI cards, top-5 counterparties |
| 11 | `js/drilldown.js` | ✅ Created | KaTeX rendering + Chart.js charts |
| 12 | `prompt.md` | ✅ Updated | Full project description + math table |
| 13 | `context.md` | ✅ Created | This file |

---

## 4. Synthetic Data Schema Summary

### Market Risk (`api/market_data.php`)
- **20 positions** across equity (8), FX (5), rates (4), credit (3)
- Each position: id, asset name, class, notional, σ, daily P&L, delta/gamma/vega/theta
- Aggregated: portfolio VaR, stressed VaR, net Greeks, P&L attribution, top-5 VaR drivers
- Each top risk carries a `formula_card` with `latex_symbolic`, `latex_substituted`, `steps[]`

### Credit Risk (`api/credit_data.php`)
- **15 counterparties** across 5 rating buckets (AAA/AA → CCC)
- Each: PD, LGD, EAD, EL, maturity, discount factor, CVA
- Each counterparty carries `formula_card.el` and `formula_card.cva` with full KaTeX strings
- Portfolio-level: total EAD, total EL, total CVA, EAD-weighted avg PD

---

## 5. Verification Steps

1. Open `http://localhost/aiprojects/Assignment5B/index.php` → dashboard loads
2. Wait 7 seconds → numbers change + timestamp pulses
3. Click **Credit Risk** tab → credit KPIs and counterparty table appear
4. Click any top-5 row → drill-down panel slides in from the right with:
   - Symbolic formula (KaTeX)
   - Substituted formula with real numbers (KaTeX)
   - Numbered step-by-step breakdown
   - Chart.js visualization
5. Close panel → slides out, chart destroyed
6. Direct API test: `http://localhost/aiprojects/Assignment5B/api/market_data.php` → JSON

---

## 6. Known Limitations / Future Improvements

- **Correlation matrix**: Portfolio VaR uses simplified within-class correlation (diagonal covariance) rather than a full cross-asset correlation matrix. For production, use historical covariance estimation.
- **Monte Carlo**: Not implemented; parametric VaR is used for clarity. Adding MC would improve tail-risk accuracy.
- **Historical VaR**: Not implemented; would require a time-series store.
- **CVA simplification**: The CVA formula uses a one-factor approximation. Production CVA requires Monte Carlo exposure simulation.
- **No authentication**: This is a demonstration dashboard with no login. Add authentication before deploying to any shared environment.
