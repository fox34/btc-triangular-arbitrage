<?php

require_once '_include.php';

define('API_URL', 'https://api-pub.bitfinex.com/v2/trades/tBTCUSD/hist');
define('CSV_FILE', DATA_DIR . 'bitfinex-tick.csv');

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // safety net
    die('Target CSV not found.');
    
    echo 'Starting new.' . PHP_EOL;
    file_put_contents(CSV_FILE, 'ID,Time,Amount,Price' . PHP_EOL);
    $startQuery = new DateTime('2017-01-01');
    $lastDatasets = [];
    
} else {
    
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified:      ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // read last datasets to prevent duplicates with same timestamp/id
    // assume there will be not more than 200 ticks at the exact same timestamp
    $lastDatasetsRaw = explode(PHP_EOL, tailCustom(CSV_FILE, 200));
    $lastDatasets = [];
    foreach ($lastDatasetsRaw as $i => $lastDataset) {
        $lastDataset = explode(',', $lastDataset);
        $lastDatasets[$lastDataset[0]] = $lastDataset;
    }
    
    $lastDatasetTime = readISODate(end($lastDatasets)[1]);
    
    echo PHP_EOL . 'Last dataset:  ' . $lastDatasetTime->format('Y-m-d H:i:s.u') . PHP_EOL;

    // last received dataset was less than a minute ago
    if ($lastDatasetTime > ( (new DateTime())->sub(new DateInterval('PT1M')))) {
        die('Last dataset is too recent. Stop.');
    }

    // query 1s in the past to avoid truncated data with same timestamp
    $startQuery = clone $lastDatasetTime;
    $startQuery->sub(new DateInterval('PT1S'));
}

echo PHP_EOL . 'Querying from: ' . $startQuery->format('Y-m-d H:i:s.u') . PHP_EOL;

// Docs: https://docs.bitfinex.com/v2/reference#rest-public-candles
$url = API_URL . '?' . http_build_query([
    'start' => getUnixTimeWithMilliseconds($startQuery),
    'limit' => 5000,
    'sort' => 1,
]);

echo 'Querying ' . $url . PHP_EOL;

$json = file_get_contents($url);

// preserve floats as string, too large for php to handle...
$json = preg_replace('/((?:-)?\d+\.\d+(?:e-?\d+))/', '"$1"', $json);

$data = json_decode($json);
if (!is_array($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    var_dump($json);
    exit;
}

echo 'Received data: ' . strlen($json) . ' bytes / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}

// sort by id = first column
usort($data, function($a, $b) {
    return $a[0] - $b[0];
});

// open target file
$csv = fopen(CSV_FILE, 'a');
if ($csv === false) {
    die('Could not open target CSV file for writing.');
}

// build result
foreach ($data as $tick) {
    
    // DO NOT PERFORM ANY CALCULATIONS! Number too large for PHP float
    $time = DateTime::createFromFormat('U.u', sprintf('%f', $tick[1] / 1000));
    $tick[1] = getISODate($time);
    
    $tickLine = implode(' / ', $tick);
    
    if (isset($lastDatasets[$tick[0]])) {
        echo 'ID exists, skipping: ' . $tickLine . PHP_EOL;
        continue;
    }
    
    echo 'Tick: ' . $tickLine . PHP_EOL;
    fputcsv($csv, array_values($tick));
}

fclose($csv);
