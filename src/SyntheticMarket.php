<?php
declare(strict_types=1);

require_once __DIR__ . '/MathHelper.php';

class SyntheticMarket
{
    private int   $seed;
    private int   $counter = 0;
    private array $positions = [];

    // Asset class mix: 8 equity, 5 FX, 4 rates, 3 credit
    private const ASSETS = [
        ['id'=>'EQ_SPX',  'name'=>'S&P 500',          'class'=>'equity',  'sigma'=>0.0145, 'notMin'=>60e6,  'notMax'=>120e6],
        ['id'=>'EQ_NDX',  'name'=>'Nasdaq 100',        'class'=>'equity',  'sigma'=>0.0180, 'notMin'=>40e6,  'notMax'=>80e6],
        ['id'=>'EQ_STOXX','name'=>'Euro Stoxx 50',     'class'=>'equity',  'sigma'=>0.0160, 'notMin'=>30e6,  'notMax'=>70e6],
        ['id'=>'EQ_FTSE', 'name'=>'FTSE 100',          'class'=>'equity',  'sigma'=>0.0130, 'notMin'=>20e6,  'notMax'=>50e6],
        ['id'=>'EQ_NIK',  'name'=>'Nikkei 225',        'class'=>'equity',  'sigma'=>0.0175, 'notMin'=>25e6,  'notMax'=>55e6],
        ['id'=>'EQ_HK',   'name'=>'Hang Seng',         'class'=>'equity',  'sigma'=>0.0195, 'notMin'=>15e6,  'notMax'=>40e6],
        ['id'=>'EQ_EM',   'name'=>'EM Equity',         'class'=>'equity',  'sigma'=>0.0220, 'notMin'=>10e6,  'notMax'=>35e6],
        ['id'=>'EQ_GOLD', 'name'=>'Gold',              'class'=>'equity',  'sigma'=>0.0095, 'notMin'=>20e6,  'notMax'=>45e6],
        ['id'=>'FX_EURUSD','name'=>'EUR/USD',          'class'=>'fx',      'sigma'=>0.0060, 'notMin'=>80e6,  'notMax'=>150e6],
        ['id'=>'FX_USDJPY','name'=>'USD/JPY',          'class'=>'fx',      'sigma'=>0.0065, 'notMin'=>60e6,  'notMax'=>120e6],
        ['id'=>'FX_GBPUSD','name'=>'GBP/USD',          'class'=>'fx',      'sigma'=>0.0075, 'notMin'=>40e6,  'notMax'=>90e6],
        ['id'=>'FX_USDCHF','name'=>'USD/CHF',          'class'=>'fx',      'sigma'=>0.0055, 'notMin'=>25e6,  'notMax'=>60e6],
        ['id'=>'FX_AUDUSD','name'=>'AUD/USD',          'class'=>'fx',      'sigma'=>0.0085, 'notMin'=>20e6,  'notMax'=>50e6],
        ['id'=>'IR_US10Y', 'name'=>'US 10Y Treasury',  'class'=>'rates',   'sigma'=>0.0045, 'notMin'=>100e6, 'notMax'=>200e6],
        ['id'=>'IR_EU10Y', 'name'=>'EUR 10Y Bund',     'class'=>'rates',   'sigma'=>0.0040, 'notMin'=>80e6,  'notMax'=>160e6],
        ['id'=>'IR_US2Y',  'name'=>'US 2Y Treasury',   'class'=>'rates',   'sigma'=>0.0025, 'notMin'=>50e6,  'notMax'=>120e6],
        ['id'=>'IR_LIBOR', 'name'=>'3M LIBOR Futures', 'class'=>'rates',   'sigma'=>0.0020, 'notMin'=>40e6,  'notMax'=>90e6],
        ['id'=>'CR_ITRAXX','name'=>'iTraxx Europe',    'class'=>'credit',  'sigma'=>0.0120, 'notMin'=>30e6,  'notMax'=>70e6],
        ['id'=>'CR_CDX',   'name'=>'CDX IG',           'class'=>'credit',  'sigma'=>0.0130, 'notMin'=>25e6,  'notMax'=>60e6],
        ['id'=>'CR_HY',    'name'=>'HY Credit Index',  'class'=>'credit',  'sigma'=>0.0200, 'notMin'=>15e6,  'notMax'=>40e6],
    ];

    public function __construct(int $seed = 0)
    {
        $this->seed = $seed;
        mt_srand($seed);
        $this->positions = $this->generatePositions();
    }

    public function snapshot(): array
    {
        $var       = $this->computePortfolioVaR();
        $svar      = $this->computeStressedVaR($var);
        $greeks    = $this->computeGreeks();
        $pnlAttr   = $this->computePnLAttribution($greeks);
        $topFactors= $this->computeTopRiskFactors($var);

        $totalNotional = array_sum(array_column($this->positions, 'notional'));

        return [
            'generated_at' => time(),
            'seed'         => $this->seed,
            'portfolio'    => [
                'total_notional_usd' => round($totalNotional),
                'num_positions'      => count($this->positions),
                'asset_classes'      => ['equity', 'fx', 'rates', 'credit'],
            ],
            'var'             => $var,
            'stressed_var'    => $svar,
            'greeks'          => $greeks,
            'pnl_attribution' => $pnlAttr,
            'top_risk_factors'=> $topFactors,
        ];
    }

    // ---- Private generators ----

    private function generatePositions(): array
    {
        $positions = [];
        foreach (self::ASSETS as $asset) {
            $notional  = $this->seededFloat($asset['notMin'], $asset['notMax']);
            $sigma     = $asset['sigma'] * (1 + $this->seededFloat(-0.2, 0.2));
            $pnlDaily  = $notional * $sigma * $this->seededFloat(-2.0, 2.0);
            $isOption  = in_array($asset['class'], ['equity', 'fx']) && $this->seededFloat(0, 1) > 0.5;
            $delta     = $isOption ? $this->seededFloat(0.3, 0.8) * ($this->seededFloat(0,1) > 0.5 ? 1 : -1) : 1.0;
            $gamma     = $isOption ? $this->seededFloat(50, 500) : 0;
            $vega      = $isOption ? $this->seededFloat(5000, 80000) * ($delta >= 0 ? 1 : -1) : 0;
            $theta     = $isOption ? -$this->seededFloat(1000, 20000) : 0;

            $positions[] = [
                'id'       => $asset['id'],
                'asset'    => $asset['name'],
                'class'    => $asset['class'],
                'notional' => $notional,
                'sigma'    => $sigma,
                'pnl_daily'=> $pnlDaily,
                'delta'    => $delta,
                'gamma'    => $gamma,
                'vega'     => $vega,
                'theta'    => $theta,
                'beta'     => $this->seededFloat(0.7, 1.4),
                'is_option'=> $isOption,
            ];
        }
        return $positions;
    }

    private function computePortfolioVaR(float $confLevel = 0.99, int $horizon = 1): array
    {
        // Approximate portfolio sigma: sqrt(sum of variance contributions)
        // Simple diagonal covariance (independence assumption with asset-class correlation bump)
        $classCorr = ['equity'=>0.70, 'fx'=>0.50, 'rates'=>0.40, 'credit'=>0.65];

        $variances = [];
        foreach ($this->positions as $p) {
            $posVar = pow($p['notional'] * $p['sigma'], 2);
            $variances[$p['class']][] = $posVar;
        }

        $portfolioVariance = 0;
        foreach ($variances as $class => $vars) {
            $n    = count($vars);
            $corr = $classCorr[$class];
            $sumVar = array_sum($vars);
            $sumStd = array_sum(array_map('sqrt', $vars));
            // Correlated variance: sum_i(var_i) + corr * sum_i!=j(sigma_i * sigma_j)
            $crossTerms = $corr * ($sumStd * $sumStd - $sumVar);
            $portfolioVariance += $sumVar + $crossTerms * 0.5;
        }

        $portfolioSigma = sqrt(max($portfolioVariance, 0));
        $z              = MathHelper::normalPPF($confLevel);
        $varUsd         = MathHelper::parametricVaR($portfolioSigma, $z, $horizon);

        // Build P&L distribution for histogram (20 buckets)
        $distrib = $this->buildPnLDistribution($portfolioSigma, $varUsd);

        return [
            'method'              => 'parametric',
            'confidence'          => $confLevel,
            'horizon_days'        => $horizon,
            'z_score'             => round($z, 3),
            'portfolio_sigma_usd' => round($portfolioSigma),
            'var_usd'             => round($varUsd),
            'var_pct_notional'    => round($varUsd / array_sum(array_column($this->positions, 'notional')), 4),
            'formula_card'        => MathHelper::varFormulaCard($z, $portfolioSigma, $horizon, $varUsd),
            'pnl_distribution'    => $distrib['buckets'],
            'pnl_frequencies'     => $distrib['freqs'],
        ];
    }

    private function buildPnLDistribution(float $sigma, float $varUsd): array
    {
        $numBuckets = 20;
        $range      = 3.5 * $sigma;
        $buckets    = [];
        $freqs      = [];

        for ($i = 0; $i < $numBuckets; $i++) {
            $bucketMin = -$range + ($i / $numBuckets) * 2 * $range;
            $bucketMid = $bucketMin + ($range / $numBuckets);
            $z         = $bucketMid / $sigma;
            // Approximate normal density * count
            $density   = exp(-0.5 * $z * $z) / (sqrt(2 * M_PI));
            $freq      = (int) round($density * 250 * ($range / ($numBuckets * 0.5)));
            $buckets[] = round($bucketMid);
            $freqs[]   = max(1, $freq);
        }

        return ['buckets' => $buckets, 'freqs' => $freqs];
    }

    private function computeStressedVaR(array $var): array
    {
        $multipliers   = MathHelper::stressMultipliers();
        $stressFactors = [];
        $totalNotional = 0;

        foreach ($this->positions as $p) {
            $notional       = $p['notional'];
            $totalNotional += $notional;
            $stressFactors[$p['class']] = ($stressFactors[$p['class']] ?? 0) + $notional;
        }

        $weightedMultiplier = 0;
        foreach ($stressFactors as $class => $notional) {
            $w = $notional / $totalNotional;
            $weightedMultiplier += $w * ($multipliers[$class] ?? 2.0);
        }

        $svarUsd = $var['var_usd'] * $weightedMultiplier;

        return [
            'svar_usd'     => round($svarUsd),
            'stress_period'=> '2008 Financial Crisis',
            'multiplier'   => round($weightedMultiplier, 2),
            'formula_card' => MathHelper::stressedVarFormulaCard(
                $var['var_usd'], $weightedMultiplier, $svarUsd, '2008 Financial Crisis'
            ),
        ];
    }

    private function computeGreeks(): array
    {
        $netDelta = 0; $netGamma = 0; $netVega = 0; $netTheta = 0;
        $perPos   = [];

        foreach ($this->positions as $p) {
            if (!$p['is_option']) continue;
            $deltaUsd  = $p['delta']  * $p['notional'];
            $netDelta += $deltaUsd;
            $netGamma += $p['gamma'];
            $netVega  += $p['vega'];
            $netTheta += $p['theta'];

            $perPos[] = [
                'asset'    => $p['asset'],
                'delta_usd'=> round($deltaUsd),
                'gamma'    => round($p['gamma']),
                'vega'     => round($p['vega']),
                'theta'    => round($p['theta']),
            ];
        }

        return [
            'net_delta'   => round($netDelta),
            'net_gamma'   => round($netGamma),
            'net_vega'    => round($netVega),
            'net_theta'   => round($netTheta),
            'per_position'=> $perPos,
            'formula_card'=> MathHelper::greeksFormulaCard($netDelta, $netGamma, $netVega, $netTheta),
        ];
    }

    private function computePnLAttribution(array $greeks): array
    {
        $spotMove  = $this->seededFloat(-0.015, 0.015);
        $deltaPnL  = $greeks['net_delta'] * $spotMove;
        $totalPnL  = array_sum(array_column($this->positions, 'pnl_daily'));
        $unexplained = $totalPnL - $deltaPnL;
        $ratio       = $totalPnL != 0 ? abs($deltaPnL / $totalPnL) : 0;

        return [
            'total_pnl'      => round($totalPnL),
            'delta_pnl'      => round($deltaPnL),
            'unexplained_pnl'=> round($unexplained),
            'ratio'          => round($ratio, 3),
            'spot_move_pct'  => round($spotMove * 100, 2),
        ];
    }

    private function computeTopRiskFactors(array $var): array
    {
        $z          = $var['z_score'];
        $factors    = [];

        foreach ($this->positions as $p) {
            $varContrib = $z * $p['beta'] * $p['sigma'] * $p['notional'];
            $factors[]  = [
                'id'                  => $p['id'],
                'factor'              => $p['asset'],
                'asset_class'         => $p['class'],
                'var_contribution_usd'=> round(abs($varContrib)),
                'var_pct'             => 0, // filled below
                'beta'                => round($p['beta'], 2),
                'sigma_daily'         => round($p['sigma'], 4),
                'notional_usd'        => round($p['notional']),
                'formula_card'        => MathHelper::riskFactorVaRCard(
                    $p['asset'], $p['beta'], $p['sigma'], $p['notional'], $z, abs($varContrib)
                ),
            ];
        }

        usort($factors, fn($a, $b) => $b['var_contribution_usd'] <=> $a['var_contribution_usd']);
        $totalVar = array_sum(array_column($factors, 'var_contribution_usd'));

        foreach ($factors as &$f) {
            $f['var_pct'] = $totalVar > 0 ? round($f['var_contribution_usd'] / $totalVar, 3) : 0;
        }

        $top = array_slice($factors, 0, 5);
        foreach ($top as $i => &$f) {
            $f['rank'] = $i + 1;
        }

        return $top;
    }

    private function seededFloat(float $min, float $max): float
    {
        $this->counter++;
        $raw = mt_rand() / mt_getrandmax();
        return $min + $raw * ($max - $min);
    }
}
