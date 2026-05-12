'use strict';

const I18n = (() => {
    let lang = localStorage.getItem('riskdash_lang') || 'en';

    const dict = {
        en: {
            // ── Tabs ──────────────────────────────────────────
            tab_market:            'Market Risk',
            tab_credit:            'Credit Risk',

            // ── Nav ───────────────────────────────────────────
            live_label:            'LIVE',
            synthetic_label:       'Synthetic Data',
            popup_close:           'Close',

            // ── Section titles ────────────────────────────────
            section_kri:           'Key Risk Indicators',
            section_greeks:        'Options Book Greeks',
            section_pnl_attr:      'P&L Attribution',
            section_top5_market:   'Top 5 VaR Drivers',
            section_top5_credit:   'Top 5 Counterparties by Exposure',
            section_credit_summary:'Credit Portfolio Summary',
            hint_click:            'Click any row to see the math',
            hint_pnl_sub:          'How much of P&L is explained by delta?',

            // ── KPI labels ────────────────────────────────────
            kpi_var_label:         'Portfolio VaR (99%, 1d)',
            kpi_var_sub:           '% of portfolio',
            kpi_svar_label:        'Stressed VaR (2008 Crisis)',
            kpi_svar_sub:          'Multiplier',
            kpi_sigma_label:       'Portfolio Daily σ',
            kpi_sigma_sub:         '1-day portfolio volatility',
            kpi_pnl_label:         'Daily P&L',
            kpi_pnl_sub:           'Total portfolio',
            kpi_ead_label:         'Total EAD',
            kpi_ead_sub:           'Exposure at Default',
            kpi_el_label:          'Total Expected Loss',
            kpi_el_sub:            'EL = PD × LGD × EAD',
            kpi_cva_label:         'Portfolio CVA',
            kpi_cva_sub:           'Credit Valuation Adj.',
            kpi_pd_label:          'Wtd Avg PD',
            kpi_pd_sub:            'EAD-weighted average',

            // ── Table column headers ───────────────────────────
            col_rank:              '#',
            col_factor:            'Risk Factor',
            col_var_contrib:       'VaR Contribution',
            col_var_pct:           '% of Total',
            col_beta:              'Beta',
            col_sigma:             'Daily σ',
            col_counterparty:      'Counterparty',
            col_ead:               'EAD',
            col_pd:                'PD',
            col_lgd:               'LGD',
            col_el:                'Expected Loss',
            col_cva:               'CVA',

            // ── Greek labels ──────────────────────────────────
            greek_delta:           'Net Delta',
            greek_gamma:           'Net Gamma',
            greek_vega:            'Net Vega',
            greek_theta:           'Net Theta',

            // ── P&L attribution row labels ────────────────────
            pnl_total:             'Total P&L',
            pnl_delta:             'Delta P&L',
            pnl_unexplained:       'Unexplained',

            // ── Drill-down labels ─────────────────────────────
            drill_symbolic:        'Formula (Symbolic)',
            drill_substituted:     'Formula (with Your Numbers)',
            drill_el_symbolic:     'Expected Loss Formula (Symbolic)',
            drill_el_substituted:  'EL with Your Numbers',
            drill_cva_label:       'CVA Formula (with Your Numbers)',
            drill_steps:           'Step-by-Step Calculation',
            drill_chart:           'Supporting Chart',

            // ── Error / status ────────────────────────────────
            err_refresh:           'Live data refresh failed. Retrying…',
            loading:               'Loading…',

            // ── Popup: KPI explanations ───────────────────────
            'popup.var':
                'VaR (Value at Risk) is the maximum loss not exceeded with 99% confidence over 1 trading day.\n\nFormula: VaR = Z₀.₉₉ × σ_P × √h\nwhere Z₀.₉₉ = 2.326, σ_P = portfolio daily volatility, h = holding period in days.',

            'popup.svar':
                'Stressed VaR (SVaR) is VaR recalculated using market data from the 2008 financial crisis, when volatilities were 2–3× normal.\n\nFormula: SVaR = VaR × m_stress\n\nRequired by Basel 2.5 for market risk capital.',

            'popup.sigma':
                'Portfolio Daily σ — the standard deviation of the portfolio\'s daily P&L, measured in USD.\n\nIt is the key input to parametric VaR. A higher σ directly increases VaR.',

            'popup.pnl':
                'Daily P&L (Profit & Loss) — the total mark-to-market gain or loss across all portfolio positions over today\'s trading session.',

            'popup.delta':
                'Delta (Δ) — the sensitivity of the portfolio\'s value to a $1 move in the underlying asset price.\n\nNet Delta is the sum of all option and position deltas, expressed in USD. Positive = long market.',

            'popup.gamma':
                'Gamma (Γ) — the rate of change of Delta with respect to the underlying price.\n\nHigh Gamma means Delta changes rapidly when the market moves, creating convex (non-linear) risk that Delta-hedging alone cannot capture.',

            'popup.vega':
                'Vega (ν) — the sensitivity to a 1% change in implied volatility.\n\nPositive Vega = long volatility (profits when vol rises). Negative = short volatility.',

            'popup.theta':
                'Theta (θ) — time decay of option value per calendar day.\n\nA negative Net Theta means the portfolio loses this amount per day purely due to the passage of time, even if markets don\'t move.',

            'popup.ead':
                'EAD (Exposure at Default) — the total amount owed by a counterparty at the moment of their default.\n\nFor loans: outstanding principal. For derivatives: positive mark-to-market value plus potential future exposure.',

            'popup.el':
                'Expected Loss (EL) — the average annual credit loss expected from this portfolio.\n\nFormula: EL = PD × LGD × EAD\nwhere PD = probability of default, LGD = loss given default, EAD = exposure at default.',

            'popup.cva':
                'CVA (Credit Valuation Adjustment) — the market value of counterparty default risk, i.e., what it costs to hedge against counterparty defaults.\n\nFormula: CVA ≈ (1−R) × PD × EAD × DF_T\nwhere R = recovery rate, DF = discount factor.',

            'popup.pd':
                'Weighted Average PD — the portfolio-level probability of default, where each counterparty\'s PD is weighted by its EAD.\n\nHigher PD = higher expected credit losses across the portfolio.',

            // ── Popup: column explanations ────────────────────
            'popup.col_var_pct':
                '% of Total VaR — the share of the portfolio\'s total VaR attributable to this single risk factor.\n\nHigher % = more concentrated risk. Top factors should be monitored closely for limit breaches.',

            'popup.col_beta':
                'Beta (β) — measures how much this position moves relative to the overall market.\n\nβ = 1.2 means a 1% market move causes a 1.2% move in this position. β > 1 amplifies market risk.',

            'popup.col_sigma':
                'Daily σ — the daily (1-day) volatility of this risk factor, expressed as a percentage.\n\nDerived from annualised volatility ÷ √252. Higher σ = larger potential daily price swings.',

            'popup.col_pd_cp':
                'PD (Probability of Default) — the annual probability that this counterparty will fail to meet its financial obligations.\n\nCalibrated to rating-agency historical default rates by bucket.',

            'popup.col_lgd':
                'LGD (Loss Given Default) — the fraction of EAD that the bank expects to lose if the counterparty defaults.\n\nLGD = 1 − Recovery Rate. Senior unsecured debt typically has LGD of 40–60%.',

            'popup.col_el_cp':
                'Expected Loss for this counterparty: EL = PD × LGD × EAD.\n\nThe statistical average annual credit loss from this single counterparty relationship.',

            'popup.col_cva_cp':
                'CVA for this counterparty — the cost of hedging its default risk over the trade\'s remaining life.\n\nFormula: CVA ≈ (1−R) × PD × EAD × e^(−r×T)\nwhere T = maturity, r = risk-free rate.',

            // ── P&L attribution popups ────────────────────────
            'popup.pnl_total':
                'Total Daily P&L — the mark-to-market gain or loss across all portfolio positions today.\n\nIncludes delta, gamma, vega, theta effects plus any unexplained residuals.',

            'popup.pnl_delta':
                'Delta P&L — the portion of total P&L explained by the portfolio\'s net Delta.\n\nDelta P&L ≈ Net Delta × today\'s spot move. This is the linear (first-order) component.',

            'popup.pnl_unexplained':
                'Unexplained P&L — total P&L minus delta P&L.\n\nIncludes Gamma (convexity), Vega (volatility change), Theta (time decay), and model/pricing errors. Large unexplained P&L is a risk management warning sign.',
        },

        fr: {
            // ── Tabs ──────────────────────────────────────────
            tab_market:            'Risque de Marché',
            tab_credit:            'Risque de Crédit',

            // ── Nav ───────────────────────────────────────────
            live_label:            'EN DIRECT',
            synthetic_label:       'Données Synthétiques',
            popup_close:           'Fermer',

            // ── Section titles ────────────────────────────────
            section_kri:           'Indicateurs Clés de Risque',
            section_greeks:        'Grecques du Livre d\'Options',
            section_pnl_attr:      'Attribution du P&L',
            section_top5_market:   'Top 5 Facteurs de VaR',
            section_top5_credit:   'Top 5 Contreparties par Exposition',
            section_credit_summary:'Résumé du Portefeuille Crédit',
            hint_click:            'Cliquez sur une ligne pour voir les calculs',
            hint_pnl_sub:          'Quelle part du P&L est expliquée par le delta ?',

            // ── KPI labels ────────────────────────────────────
            kpi_var_label:         'VaR Portefeuille (99%, 1j)',
            kpi_var_sub:           '% du portefeuille',
            kpi_svar_label:        'VaR Stressée (Crise 2008)',
            kpi_svar_sub:          'Multiplicateur',
            kpi_sigma_label:       'σ Quotidien du Portefeuille',
            kpi_sigma_sub:         'Volatilité 1 jour du portefeuille',
            kpi_pnl_label:         'P&L Quotidien',
            kpi_pnl_sub:           'Portefeuille total',
            kpi_ead_label:         'EAD Total',
            kpi_ead_sub:           'Exposition en Cas de Défaut',
            kpi_el_label:          'Perte Attendue Totale',
            kpi_el_sub:            'EL = PD × LGD × EAD',
            kpi_cva_label:         'CVA Portefeuille',
            kpi_cva_sub:           'Ajustement de Valorisation Crédit',
            kpi_pd_label:          'PD Moy. Pondérée',
            kpi_pd_sub:            'Moyenne pondérée par EAD',

            // ── Table column headers ───────────────────────────
            col_rank:              '#',
            col_factor:            'Facteur de Risque',
            col_var_contrib:       'Contribution VaR',
            col_var_pct:           '% du Total',
            col_beta:              'Bêta',
            col_sigma:             'σ Quotidien',
            col_counterparty:      'Contrepartie',
            col_ead:               'EAD',
            col_pd:                'PD',
            col_lgd:               'LGD',
            col_el:                'Perte Attendue',
            col_cva:               'CVA',

            // ── Greek labels ──────────────────────────────────
            greek_delta:           'Delta Net',
            greek_gamma:           'Gamma Net',
            greek_vega:            'Vega Net',
            greek_theta:           'Thêta Net',

            // ── P&L attribution row labels ────────────────────
            pnl_total:             'P&L Total',
            pnl_delta:             'P&L Delta',
            pnl_unexplained:       'Non Expliqué',

            // ── Drill-down labels ─────────────────────────────
            drill_symbolic:        'Formule (Symbolique)',
            drill_substituted:     'Formule (avec Vos Chiffres)',
            drill_el_symbolic:     'Formule Perte Attendue (Symbolique)',
            drill_el_substituted:  'PA avec Vos Chiffres',
            drill_cva_label:       'Formule CVA (avec Vos Chiffres)',
            drill_steps:           'Calcul Étape par Étape',
            drill_chart:           'Graphique Illustratif',

            // ── Error / status ────────────────────────────────
            err_refresh:           'Échec de la mise à jour des données. Nouvelle tentative…',
            loading:               'Chargement…',

            // ── Popup: KPI explanations ───────────────────────
            'popup.var':
                'La VaR (Value at Risk) est la perte maximale non dépassée avec 99% de confiance sur 1 journée de trading.\n\nFormule : VaR = Z₀.₉₉ × σ_P × √h\noù Z₀.₉₉ = 2,326, σ_P = volatilité quotidienne du portefeuille, h = période de détention en jours.',

            'popup.svar':
                'La VaR Stressée (SVaR) est la VaR recalculée avec les données de marché de la crise financière de 2008, lors de laquelle les volatilités étaient 2 à 3 fois supérieures à la normale.\n\nFormule : SVaR = VaR × m_stress\n\nExigée par Bâle 2.5 pour le capital au titre du risque de marché.',

            'popup.sigma':
                'σ Quotidien du Portefeuille — l\'écart-type du P&L quotidien du portefeuille, exprimé en USD.\n\nC\'est le principal paramètre de la VaR paramétrique. Un σ plus élevé augmente directement la VaR.',

            'popup.pnl':
                'P&L Quotidien (Profit & Perte) — le gain ou la perte mark-to-market total de tous les postes du portefeuille sur la session de trading du jour.',

            'popup.delta':
                'Delta (Δ) — la sensibilité de la valeur du portefeuille à un mouvement de 1 USD du prix de l\'actif sous-jacent.\n\nLe Delta Net est la somme de tous les deltas d\'options et de positions, en USD. Positif = exposition longue au marché.',

            'popup.gamma':
                'Gamma (Γ) — le taux de variation du Delta par rapport au prix sous-jacent.\n\nUn Gamma élevé signifie que le Delta change rapidement lors de mouvements du marché, créant un risque de convexité que la couverture delta seule ne peut pas capturer.',

            'popup.vega':
                'Vega (ν) — la sensibilité à une variation de 1% de la volatilité implicite.\n\nVega positif = long volatilité (bénéficie d\'une hausse de la vol). Négatif = short volatilité.',

            'popup.theta':
                'Thêta (θ) — dépréciation temporelle de la valeur de l\'option par jour calendaire.\n\nUn Thêta Net négatif signifie que le portefeuille perd ce montant chaque jour uniquement dû au passage du temps, même sans mouvement des marchés.',

            'popup.ead':
                'EAD (Exposition en Cas de Défaut) — le montant total dû par une contrepartie au moment de son défaut.\n\nPour les prêts : capital restant dû. Pour les dérivés : valeur mark-to-market positive plus exposition future potentielle.',

            'popup.el':
                'Perte Attendue (EL) — la perte crédit annuelle moyenne attendue pour ce portefeuille.\n\nFormule : EL = PD × LGD × EAD\noù PD = probabilité de défaut, LGD = perte en cas de défaut, EAD = exposition en cas de défaut.',

            'popup.cva':
                'CVA (Ajustement de Valorisation du Crédit) — la valeur de marché du risque de défaut de contrepartie, c\'est-à-dire le coût de couverture contre les défauts.\n\nFormule : CVA ≈ (1−R) × PD × EAD × DF_T\noù R = taux de recouvrement, DF = facteur d\'actualisation.',

            'popup.pd':
                'PD Moyenne Pondérée — la probabilité de défaut au niveau du portefeuille, où la PD de chaque contrepartie est pondérée par son EAD.\n\nUne PD plus élevée = des pertes attendues sur crédit plus importantes.',

            // ── Popup: column explanations ────────────────────
            'popup.col_var_pct':
                '% de la VaR Totale — la part de la VaR totale du portefeuille attribuable à ce facteur de risque.\n\nUn % élevé = risque plus concentré. Les facteurs principaux doivent être surveillés de près pour les dépassements de limites.',

            'popup.col_beta':
                'Bêta (β) — mesure l\'amplitude des mouvements de cette position par rapport au marché global.\n\nβ = 1,2 signifie qu\'un mouvement de 1% du marché entraîne un mouvement de 1,2% de cette position.',

            'popup.col_sigma':
                'σ Quotidien — la volatilité quotidienne (1 jour) de ce facteur de risque, exprimée en pourcentage.\n\nDérivée de la volatilité annualisée ÷ √252. Un σ plus élevé = des variations de prix quotidiennes potentiellement plus importantes.',

            'popup.col_pd_cp':
                'PD (Probabilité de Défaut) — la probabilité annuelle que cette contrepartie ne respecte pas ses obligations financières.\n\nCalibrée sur les taux de défaut historiques des agences de notation par classe de risque.',

            'popup.col_lgd':
                'LGD (Perte en Cas de Défaut) — la fraction de l\'EAD que la banque s\'attend à perdre si la contrepartie fait défaut.\n\nLGD = 1 − Taux de Recouvrement. La dette senior non sécurisée a typiquement un LGD de 40 à 60%.',

            'popup.col_el_cp':
                'Perte Attendue pour cette contrepartie : EL = PD × LGD × EAD.\n\nLa perte crédit annuelle statistique moyenne générée par cette relation de contrepartie.',

            'popup.col_cva_cp':
                'CVA pour cette contrepartie — le coût de couverture de son risque de défaut sur la durée résiduelle de la transaction.\n\nFormule : CVA ≈ (1−R) × PD × EAD × e^(−r×T)\noù T = maturité, r = taux sans risque.',

            // ── P&L attribution popups ────────────────────────
            'popup.pnl_total':
                'P&L Quotidien Total — le gain ou la perte mark-to-market de tous les postes du portefeuille aujourd\'hui.\n\nInclut les effets delta, gamma, vega, thêta et les résidus inexpliqués.',

            'popup.pnl_delta':
                'P&L Delta — la partie du P&L total expliquée par le Delta net du portefeuille.\n\nP&L Delta ≈ Delta Net × mouvement spot du jour. C\'est la composante linéaire (premier ordre).',

            'popup.pnl_unexplained':
                'P&L Non Expliqué — P&L total moins P&L delta.\n\nInclut Gamma (convexité), Vega (changement de volatilité), Thêta (valeur temps) et les erreurs de modèle. Un P&L inexpliqué important est un signal d\'alerte.',
        },
    };

    return {
        t(key) {
            return dict[lang][key] ?? dict['en'][key] ?? key;
        },
        setLang(l) {
            if (!dict[l]) return;
            lang = l;
            localStorage.setItem('riskdash_lang', l);
        },
        getLang() { return lang; },
        available() { return Object.keys(dict); },
    };
})();
