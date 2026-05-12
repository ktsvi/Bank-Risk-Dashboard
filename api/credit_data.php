<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../src/SyntheticCredit.php';

$seed = isset($_GET['seed']) ? (int) $_GET['seed'] : (int) floor(time() / 7);
$credit = new SyntheticCredit($seed);
echo json_encode($credit->snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
