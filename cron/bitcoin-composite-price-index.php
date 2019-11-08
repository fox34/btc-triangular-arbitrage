<?php

require_once '_include.php';

$api = 'https://index-api.bitcoin.com/api/v0/history?unix=1';
$dataFile = DATA_DIR . 'bitcoin-composite-price-index-history.csv';

echo 'Processing 1d close...' . PHP_EOL;

// CSV format:
// Timestamp (ms); Amount, Price
if (!file_exists($dataFile) || filesize($dataFile) === 0) {
    
    die('Target CSV not found.');
    
    // fresh start
    $lastDataset = new DateTime('2010-01-01');
    file_put_contents($dataFile, 'Time,Close' . PHP_EOL);
    $api .= '&span=all';
    
} else {
    
    echo 'Reading last dataset from CSV: ' . $dataFile . PHP_EOL;
    
    // read last dataset
    $lastLine = tailCustom($dataFile);
    if ($lastLine === false || empty($lastLine)) {
        die('Could not read CSV.');
    }
    $lastLine = str_getcsv($lastLine);
    
    try {
        $lastDataset = readISODate($lastLine[0]);
    } catch (Exception $e) {
        die('Could not parse last dataset: ' . $lastLine[0]);
    }
}

echo 'Last dataset:          ' . $lastDataset->format('Y-m-d') . PHP_EOL;

if ($lastDataset >= (new DateTime())->setTime(0, 0, 0)) {
    die('Last dataset is too recent. Stop.');
}


$url = $api;
echo 'Querying ' . $url . PHP_EOL;

$result = file_get_contents($url);

// result format: json
$data = json_decode($result);
if ($data === null) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    print_r($result);
    exit;
}

echo 'Received data: ' . strlen($result) . ' bytes / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}
$data = array_reverse($data);

// open target file
$csv = fopen($dataFile, 'a');
if ($csv === false) {
    die('Could not open target CSV file for writing.');
}


// build new lines
foreach ($data as $tick) {
    
    $tick = array_combine(['TIME', 'CLOSE'], $tick);
    
    // Close in us cents
    $tick['CLOSE'] /= 100;
    
    // Date format is unix time (as selected)
    $time = DateTime::createFromFormat('U', $tick['TIME']);
    
    // skip datasets out of range
    if ($time <= $lastDataset) {
        //echo '-- Skipping ' . $time->format('Y-m-d') . PHP_EOL;
        continue;
    }
    if ($time >= (new DateTime())->setTime(0, 0, 0)) {
        echo '-- Skipping for today: ' . $time->format('Y-m-d') . PHP_EOL;
        continue;
    }
    
    echo 'Got tick: ' . $time->format('Y-m-d') . ', ';
    echo 'Price: ' . asPrice($tick['CLOSE']) . ' USD' . PHP_EOL;
    
    fputcsv($csv, [getISODate($time), $tick['CLOSE']]);
}

fclose($csv);
