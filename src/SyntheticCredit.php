<?php
declare(strict_types=1);

require_once __DIR__ . '/MathHelper.php';

class SyntheticCredit
{
    private int   $seed;
    private int   $counter = 0;
    private array $counterparties = [];

    private const COUNTERPARTIES = [
        ['id'=>'cp_001', 'name'=>'GlobalBank Corp',       'bucket'=>'A/BBB'],
        ['id'=>'cp_002', 'name'=>'NorthStar Industries',  'bucket'=>'BB'],
        ['id'=>'cp_003', 'name'=>'Apex Financial Group',  'bucket'=>'AAA/AA'],
        ['id'=>'cp_004', 'name'=>'Pacific Rim Holdings',  'bucket'=>'B'],
        ['id'=>'cp_005', 'name'=>'Euro Dynamics SA',      'bucket'=>'A/BBB'],
        ['id'=>'cp_006', 'name'=>'Meridian Energy Ltd',   'bucket'=>'BB'],
        ['id'=>'cp_007', 'name'=>'Continental Trust',     'bucket'=>'AAA/AA'],
        ['id'=>'cp_008', 'name'=>'RedRock Capital',       'bucket'=>'CCC'],
        ['id'=>'cp_009', 'name'=>'Atlantic Partners',     'bucket'=>'A/BBB'],
        ['id'=>'cp_010', 'name'=>'Sunrise Technologies',  'bucket'=>'BB'],
        ['id'=>'cp_011', 'name'=>'Heritage Bank plc',     'bucket'=>'A/BBB'],
        ['id'=>'cp_012', 'name'=>'Delta Commodities',     'bucket'=>'B'],
        ['id'=>'cp_013', 'name'=>'Nova Insurance Group',  'bucket'=>'AAA/AA'],
        ['id'=>'cp_014', 'name'=>'Quantum Logistics',     'bucket'=>'CCC'],
        ['id'=>'cp_015', 'name'=>'Stellar Resources Co',  'bucket'=>'BB'],
    ];

    private const BUCKET_PD = [
        'AAA/AA' => [0.0001, 0.0005],
        'A/BBB'  => [0.0040, 0.0120],
        'BB'     => [0.0200, 0.0450],
        'B'      => [0.0600, 0.1200],
        'CCC'    => [0.1500, 0.2500],
    ];

    private const BUCKET_LGD = [
        'AAA/AA' => [0.20, 0.35],
        'A/BBB'  => [0.35, 0.50],
        'BB'     => [0.40, 0.60],
        'B'      => [0.50, 0.70],
        'CCC'    => [0.60, 0.80],
    ];

    private const BUCKET_EAD_RANGE = [
        'AAA/AA' => [50e6,  120e6],
        'A/BBB'  => [30e6,  80e6],
        'BB'     => [15e6,  50e6],
        'B'      => [8e6,   30e6],
        'CCC'    => [3e6,   15e6],
    ];

    public function __construct(int $seed = 0)
    {
        $this->seed = $seed;
        mt_srand($seed + 9999); // offset so credit PRNG differs from market
        $this->counterparties = $this->generateCounterparties();
    }

    public function snapshot(): array
    {
        $totalEAD = array_sum(array_column($this->counterparties, 'ead_usd'));
        $totalEL  = array_sum(array_column($this->counterparties, 'el_usd'));
        $totalCVA = array_sum(array_column($this->counterparties, 'cva_usd'));
        $totalPD  = array_sum(array_map(fn($cp) => $cp['pd'] * $cp['ead_usd'], $this->counterparties));
        $wtdAvgPD = $totalEAD > 0 ? $totalPD / $totalEAD : 0;

        $top5       = $this->computeTopCounterparties();
        $pdBucket   = $this->getPdByBucket();
        $eadBucket  = $this->getExposureByBucket();

        return [
            'generated_at' => time(),
            'seed'         => $this->seed,
            'portfolio'    => [
                'num_counterparties' => count($this->counterparties),
                'total_ead_usd'      => round($totalEAD),
                'total_el_usd'       => round($totalEL),
                'total_cva_usd'      => round($totalCVA),
                'weighted_avg_pd'    => round($wtdAvgPD, 5),
            ],
            'top_counterparties' => $top5,
            'pd_by_bucket'       => $pdBucket,
            'exposure_by_bucket' => $eadBucket,
        ];
    }

    // ---- Private generators ----

    private function generateCounterparties(): array
    {
        $cps = [];
        foreach (self::COUNTERPARTIES as $cp) {
            $bucket     = $cp['bucket'];
            $pdRange    = self::BUCKET_PD[$bucket];
            $lgdRange   = self::BUCKET_LGD[$bucket];
            $eadRange   = self::BUCKET_EAD_RANGE[$bucket];

            $pd         = $this->seededFloat($pdRange[0], $pdRange[1]);
            $lgd        = $this->seededFloat($lgdRange[0], $lgdRange[1]);
            $ead        = $this->seededFloat($eadRange[0], $eadRange[1]);
            $matYears   = $this->seededFloat(1.0, 7.0);
            $el         = MathHelper::expectedLoss($pd, $lgd, $ead);
            $cva        = MathHelper::cvaSimplified($pd, $lgd, $ead, $matYears);
            $df         = MathHelper::discountFactor($matYears);

            $cps[] = [
                'id'              => $cp['id'],
                'name'            => $cp['name'],
                'rating_bucket'   => $bucket,
                'pd'              => round($pd, 6),
                'lgd'             => round($lgd, 4),
                'ead_usd'         => round($ead),
                'el_usd'          => round($el),
                'maturity_years'  => round($matYears, 1),
                'discount_factor' => round($df, 4),
                'cva_usd'         => round($cva),
                'formula_card' => [
                    'el'  => MathHelper::elFormulaCard($pd, $lgd, $ead),
                    'cva' => MathHelper::cvaFormulaCard($pd, $lgd, $ead, $matYears),
                ],
                'exposure_chart_data' => [
                    'labels' => ['EAD', 'Expected Loss', 'CVA'],
                    'values' => [round($ead), round($el), round($cva)],
                ],
            ];
        }
        return $cps;
    }

    private function computeTopCounterparties(): array
    {
        $sorted = $this->counterparties;
        usort($sorted, fn($a, $b) => $b['ead_usd'] <=> $a['ead_usd']);
        $top = array_slice($sorted, 0, 5);
        foreach ($top as $i => &$cp) {
            $cp['rank'] = $i + 1;
        }
        return $top;
    }

    private function getPdByBucket(): array
    {
        $groups = [];
        foreach ($this->counterparties as $cp) {
            $groups[$cp['rating_bucket']][] = $cp['pd'];
        }
        $result = [];
        foreach ($groups as $bucket => $pds) {
            $result[$bucket] = round(array_sum($pds) / count($pds), 6);
        }
        // Ensure canonical ordering
        $order = ['AAA/AA', 'A/BBB', 'BB', 'B', 'CCC'];
        $out   = [];
        foreach ($order as $b) {
            $out[$b] = $result[$b] ?? 0;
        }
        return $out;
    }

    private function getExposureByBucket(): array
    {
        $groups = [];
        foreach ($this->counterparties as $cp) {
            $groups[$cp['rating_bucket']] = ($groups[$cp['rating_bucket']] ?? 0) + $cp['ead_usd'];
        }
        $order = ['AAA/AA', 'A/BBB', 'BB', 'B', 'CCC'];
        $out   = [];
        foreach ($order as $b) {
            $out[$b] = $groups[$b] ?? 0;
        }
        return $out;
    }

    private function seededFloat(float $min, float $max): float
    {
        $this->counter++;
        $raw = mt_rand() / mt_getrandmax();
        return $min + $raw * ($max - $min);
    }
}
