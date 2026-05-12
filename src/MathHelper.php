<?php
declare(strict_types=1);

class MathHelper
{
    // Beasley–Springer–Moro approximation of the inverse normal CDF
    public static function normalPPF(float $p): float
    {
        $a = [0, -3.969683028665376e+01, 2.209460984245205e+02,
              -2.759285104469687e+02, 1.383577518672690e+02,
              -3.066479806614716e+01, 2.506628277459239e+00];
        $b = [0, -5.447609879822406e+01, 1.615858368580409e+02,
              -1.556989798598866e+02, 6.680131188771972e+01,
              -1.328068155288572e+01];
        $c = [0, -7.784894002430293e-03, -3.223964580411365e-01,
              -2.400758277161838e+00, -2.549732539343734e+00,
               4.374664141464968e+00,  2.938163982698783e+00];
        $d = [0, 7.784695709041462e-03, 3.224671290700398e-01,
              2.445134137142996e+00, 3.754408661907416e+00];

        $p_low  = 0.02425;
        $p_high = 1 - $p_low;

        if ($p < $p_low) {
            $q = sqrt(-2 * log($p));
            return (((((($c[1]*$q+$c[2])*$q+$c[3])*$q+$c[4])*$q+$c[5])*$q+$c[6]) /
                    ((((($d[1]*$q+$d[2])*$q+$d[3])*$q+$d[4])*$q+1)));
        } elseif ($p <= $p_high) {
            $q = $p - 0.5;
            $r = $q * $q;
            return (((((($a[1]*$r+$a[2])*$r+$a[3])*$r+$a[4])*$r+$a[5])*$r+$a[6])*$q /
                    (((((($b[1]*$r+$b[2])*$r+$b[3])*$r+$b[4])*$r+$b[5])*$r+1)));
        } else {
            $q = sqrt(-2 * log(1 - $p));
            return -(((((($c[1]*$q+$c[2])*$q+$c[3])*$q+$c[4])*$q+$c[5])*$q+$c[6]) /
                     ((((($d[1]*$q+$d[2])*$q+$d[3])*$q+$d[4])*$q+1)));
        }
    }

    // Parametric VaR: VaR = Z_alpha * sigma_portfolio * sqrt(horizon)
    public static function parametricVaR(float $sigma, float $z, int $h): float
    {
        return $z * $sigma * sqrt($h);
    }

    // Expected Loss: EL = PD * LGD * EAD
    public static function expectedLoss(float $pd, float $lgd, float $ead): float
    {
        return $pd * $lgd * $ead;
    }

    // Simplified CVA: CVA ≈ (1-R) * PD * EAD * DiscountFactor
    public static function cvaSimplified(
        float $pd,
        float $lgd,
        float $ead,
        float $maturityYears,
        float $discountRate = 0.05
    ): float {
        $df = exp(-$discountRate * $maturityYears);
        return $lgd * $pd * $ead * $df;
    }

    public static function discountFactor(float $maturityYears, float $rate = 0.05): float
    {
        return exp(-$rate * $maturityYears);
    }

    // Stressed VaR multipliers by asset class (2008 crisis calibration)
    public static function stressMultipliers(): array
    {
        return ['equity' => 2.30, 'fx' => 1.80, 'rates' => 2.10, 'credit' => 2.50];
    }

    // --- Formula card generators ---
    // Returns ['latex_symbolic', 'latex_substituted', 'steps'[]]

    public static function varFormulaCard(
        float $z,
        float $sigmaPf,
        int   $h,
        float $varUsd
    ): array {
        $sigmaFmt = number_format($sigmaPf, 0, '.', '{,}');
        $varFmt   = number_format($varUsd,  0, '.', '{,}');
        $zFmt     = number_format($z, 3);

        return [
            'latex_symbolic'    => '\text{VaR} = Z_{\alpha} \cdot \sigma_{P} \cdot \sqrt{h}',
            'latex_substituted' => '\text{VaR} = ' . $zFmt . ' \times \$' . $sigmaFmt . ' \times \sqrt{' . $h . '}',
            'steps' => [
                '\text{Confidence level } \alpha = 99\% \Rightarrow Z_{0.99} = ' . $zFmt,
                '\text{Portfolio daily } \sigma_P = \$' . $sigmaFmt,
                '\text{Holding period } h = ' . $h . '\ \text{day(s)}',
                '\text{VaR} = ' . $zFmt . ' \times \$' . $sigmaFmt . ' \times \sqrt{' . $h . '} = \$' . $varFmt,
                '\text{Interpretation: there is a 1\% chance of losing more than \$}' . $varFmt . '\ \text{in a single day}',
            ],
        ];
    }

    public static function stressedVarFormulaCard(
        float $varUsd,
        float $multiplier,
        float $svarUsd,
        string $stressPeriod
    ): array {
        $varFmt  = number_format($varUsd,  0, '.', '{,}');
        $svarFmt = number_format($svarUsd, 0, '.', '{,}');
        $mFmt    = number_format($multiplier, 2);

        return [
            'latex_symbolic'    => '\text{SVaR} = \text{VaR} \times m_{\text{stress}}',
            'latex_substituted' => '\text{SVaR} = \$' . $varFmt . ' \times ' . $mFmt,
            'steps' => [
                '\text{Base VaR (99\%, 1-day)} = \$' . $varFmt,
                '\text{Stress period: ' . $stressPeriod . '}',
                '\text{Asset-class-weighted stress multiplier } m = ' . $mFmt,
                '\text{SVaR} = \$' . $varFmt . ' \times ' . $mFmt . ' = \$' . $svarFmt,
                '\text{SVaR represents the VaR during a significant financial crisis period}',
            ],
        ];
    }

    public static function greeksFormulaCard(
        float $netDelta,
        float $netGamma,
        float $netVega,
        float $netTheta
    ): array {
        $dFmt = number_format(abs($netDelta), 0, '.', '{,}');
        $gFmt = number_format(abs($netGamma), 0, '.', '{,}');
        $vFmt = number_format(abs($netVega),  0, '.', '{,}');
        $tFmt = number_format(abs($netTheta), 0, '.', '{,}');
        $dSign = $netDelta >= 0 ? '+' : '-';
        $vSign = $netVega  >= 0 ? '+' : '-';

        return [
            'latex_symbolic'    => '\Delta P \approx \delta \cdot \Delta S + \tfrac{1}{2}\gamma \cdot (\Delta S)^2 + \nu \cdot \Delta\sigma + \theta \cdot \Delta t',
            'latex_substituted' => '\Delta P \approx (' . $dSign . '\$' . $dFmt . ') \cdot \Delta S + \tfrac{1}{2}(' . $gFmt . ') \cdot (\Delta S)^2',
            'steps' => [
                '\delta\ (\text{Net Delta}) = ' . $dSign . number_format($netDelta, 0, '.', '{,}') . '\ \text{USD/unit}',
                '\gamma\ (\text{Net Gamma}) = ' . number_format($netGamma, 0, '.', '{,}') . '\ \text{USD/unit}^2',
                '\nu\ (\text{Net Vega})  = ' . $vSign . '\$' . $vFmt . '\ \text{per 1\% vol move}',
                '\theta\ (\text{Net Theta}) = -\$' . $tFmt . '\ \text{per day (time decay)}',
                '\text{A 1\% adverse spot move costs approx. } \delta \times 0.01\ \text{of notional}',
            ],
        ];
    }

    public static function elFormulaCard(float $pd, float $lgd, float $ead): array
    {
        $el    = self::expectedLoss($pd, $lgd, $ead);
        $pdPct = number_format($pd  * 100, 2);
        $lgdPct= number_format($lgd * 100, 0);
        $eadFmt= number_format($ead, 0, '.', '{,}');
        $elFmt = number_format($el,  0, '.', '{,}');

        return [
            'latex_symbolic'    => 'EL = PD \times LGD \times EAD',
            'latex_substituted' => 'EL = ' . $pdPct . '\% \times ' . $lgdPct . '\% \times \$' . $eadFmt,
            'steps' => [
                'PD\ (\text{Probability of Default}) = ' . $pdPct . '\%',
                'LGD\ (\text{Loss Given Default}) = ' . $lgdPct . '\%\ \text{(1 - Recovery Rate)}',
                'EAD\ (\text{Exposure at Default}) = \$' . $eadFmt,
                'EL = ' . number_format($pd, 4) . ' \times ' . number_format($lgd, 2) . ' \times ' . $eadFmt . ' = \$' . $elFmt,
                '\text{EL represents the average expected annual loss from this counterparty}',
            ],
        ];
    }

    public static function cvaFormulaCard(float $pd, float $lgd, float $ead, float $matYears): array
    {
        $df  = self::discountFactor($matYears);
        $cva = self::cvaSimplified($pd, $lgd, $ead, $matYears);

        $pdPct  = number_format($pd  * 100, 2);
        $lgdPct = number_format($lgd * 100, 0);
        $eadFmt = number_format($ead, 0, '.', '{,}');
        $dfFmt  = number_format($df, 4);
        $cvaFmt = number_format($cva, 0, '.', '{,}');
        $matFmt = number_format($matYears, 1);

        return [
            'latex_symbolic'    => 'CVA \approx (1-R) \cdot PD \cdot EAD \cdot DF_{T}',
            'latex_substituted' => 'CVA \approx ' . number_format($lgd,2) . ' \times ' . number_format($pd,4) . ' \times \$' . $eadFmt . ' \times ' . $dfFmt,
            'steps' => [
                'LGD = (1 - R) = ' . $lgdPct . '\%\ \text{where R is the recovery rate}',
                'PD = ' . $pdPct . '\%\ \text{(1-year probability of default)}',
                'EAD = \$' . $eadFmt,
                'DF_T = e^{-r \cdot T} = e^{-0.05 \times ' . $matFmt . '} = ' . $dfFmt,
                'CVA = ' . number_format($lgd,2) . ' \times ' . number_format($pd,4) . ' \times ' . $eadFmt . ' \times ' . $dfFmt . ' = \$' . $cvaFmt,
                '\text{CVA = cost of hedging counterparty default risk over the life of the trade}',
            ],
        ];
    }

    public static function riskFactorVaRCard(
        string $factor,
        float  $beta,
        float  $sigmaDaily,
        float  $notional,
        float  $z,
        float  $varContrib
    ): array {
        $betaFmt    = number_format($beta, 2);
        $sigmaPct   = number_format($sigmaDaily * 100, 2);
        $notFmt     = number_format($notional, 0, '.', '{,}');
        $varFmt     = number_format($varContrib, 0, '.', '{,}');
        $posSigmaUsd= $notional * $beta * $sigmaDaily;
        $psFmt      = number_format($posSigmaUsd, 0, '.', '{,}');

        return [
            'latex_symbolic'    => '\text{VaR}_i = Z_{\alpha} \cdot \beta_i \cdot \sigma_i \cdot N_i',
            'latex_substituted' => '\text{VaR}_i = ' . number_format($z,3) . ' \times ' . $betaFmt . ' \times ' . $sigmaPct . '\% \times \$' . $notFmt,
            'steps' => [
                '\text{Factor: } ' . $factor,
                '\beta_i\ (\text{exposure sensitivity}) = ' . $betaFmt,
                '\sigma_i\ (\text{daily vol}) = ' . $sigmaPct . '\%',
                'N_i\ (\text{notional}) = \$' . $notFmt,
                '\text{Position } \sigma = \beta \times \sigma \times N = \$' . $psFmt,
                '\text{VaR contribution} = ' . number_format($z,3) . ' \times \$' . $psFmt . ' = \$' . $varFmt,
            ],
        ];
    }
}
