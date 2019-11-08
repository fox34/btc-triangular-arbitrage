<?php

require_once '_include.php';

define('API_URL', 'https://api-pub.bitfinex.com/v2/candles/trade:1m:tBTCUSD/hist');
define('CSV_FILE', DATA_DIR . 'bitfinex-ohlc-60s.csv');

// CSV format:
// Timestamp (ms); Open; Close; High; Low; Volume

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    die('Target CSV not found.');
    
    // fresh start
    $lastDataset = new DateTime('2016-11-01');
    
} else {
    
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified:              ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // read last dataset
    $lastLine = tailCustom(CSV_FILE);
    if ($lastLine === false || empty($lastLine)) {
        die('Could not read CSV.');
    }
    $lastLine = str_getcsv($lastLine);
    
    try {
        $lastDataset = readISODate($lastLine[0]);
    } catch (Exception $e) {
        die('Could not parse last dataset: ' . $lastLine[0]);
    }
    
    $lastDataset->add(new DateInterval('PT1S'));
}

echo 'Starting with last dataset + 1s: ' . $lastDataset->format('Y-m-d H:i:s.u') . PHP_EOL;

// Requested data contains mostly a few days of candles
if ($lastDataset > ( (new DateTime())->sub(new DateInterval('PT10M')))) {
    die('Last dataset is too recent. Stop.');
}

$endQuery = clone $lastDataset;
$endQuery->add(new DateInterval('P7D'));

// query max until now.
if ($endQuery > new DateTime()) {
    $endQuery = new DateTime();
}

echo 'Querying until.                  ' . $endQuery->format('Y-m-d H:i:s.u') . PHP_EOL;

// Docs: https://docs.bitfinex.com/v2/reference#rest-public-candles
$url = API_URL . '?' . http_build_query([
    'start' => getUnixTimeWithMilliseconds($lastDataset),
    'end' => getUnixTimeWithMilliseconds($endQuery),
    'limit' => 1000,
    'sort' => 1,
]);

echo 'Querying ' . $url . PHP_EOL;

$result = file_get_contents($url);
$data = json_decode($result);
if (!is_array($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    print_r($result);
    exit;
}

echo 'Received data: ' . strlen($result) . ' bytes / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}

// open target file
$csv = fopen(CSV_FILE, 'a');
if ($csv === false) {
    die('Could not open target CSV file for writing.');
}

// build new lines
foreach ($data as $tick) {
    
    $tick = array_combine(['Time', 'Open', 'Close', 'High', 'Low', 'Volume'], $tick);
    
    $time = DateTime::createFromFormat('U.u', sprintf('%f', $tick['Time'] / 1000));
    echo 'Got tick: ' . $time->format('Y-m-d H:i:s.u') . ', ';
    echo 'Mean: ' . number_format(($tick['Open'] + $tick['Close']) / 2, 2) . PHP_EOL;
    
    $tick['Time'] = getISODate($time);
    
    fputcsv($csv, array_values($tick));
    
}

fclose($csv);
