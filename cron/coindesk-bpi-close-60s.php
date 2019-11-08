<?php

require_once '_include.php';

define('TICK_API', 'https://api.coindesk.com/charts/data');
define('TICK_CSV', DATA_DIR . 'coindesk-bpi-close-60s.csv');

echo 'Processing 60s close...' . PHP_EOL;

// CSV format:
// Timestamp (ms); Amount, Price
if (!file_exists(TICK_CSV) || filesize(TICK_CSV) === 0) {
    
    die('Target CSV not found.');
	// fresh start
	$lastDataset = new DateTime('2010-07-18');
	
} else {
	
	echo 'Reading last dataset from CSV: ' . TICK_CSV . PHP_EOL;
    
	// read last dataset
	$lastLine = tailCustom(TICK_CSV);
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

echo 'Last dataset:          ' . $lastDataset->format('Y-m-d H:i') . PHP_EOL;

$requestedDay = clone $lastDataset;
$requestedDay->setTime(0, 0, 0);
$requestedDay->add(new DateInterval('P1D'));

$requestedDayEnd = clone $requestedDay;
$requestedDayEnd->add(new DateInterval('P1D'));

echo 'Querying for this day: ' . $requestedDay->format('Y-m-d') . PHP_EOL;

if ($requestedDay >= (new DateTime())->setTime(0, 0, 0)) {
	die('Next dataset is too recent. Stop.');
}


$url = TICK_API . '?' . http_build_query([
    'data' => 'close',
	'startdate' => $requestedDay->format('Y-m-d'),
	'enddate' => $requestedDay->format('Y-m-d'),
	'exchanges' => 'bpi',
	'dev' => 1,
	'index' => 'USD',
]);

echo 'Querying ' . $url . PHP_EOL;

$result = file_get_contents($url);

// result format: cb({"bpi": [...] }); -> {"bpi": [...]}
if (!empty($result)) {
    $result = substr($result, 3, -2);
}

$data = json_decode($result);
if ($data === null || !isset($data->bpi) || !is_array($data->bpi)) {
	echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
	echo 'Received data: ' . PHP_EOL;
	print_r($result);
	exit;
}

$data = $data->bpi;

echo 'Received data: ' . strlen($result) . ' bytes / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
	die('Received dataset is empty.');
}


// open target file
$csv = fopen(TICK_CSV, 'a');
if ($csv === false) {
	die('Could not open target CSV file for writing.');
}


// build new lines
foreach ($data as $tick) {
	
	$tick = array_combine(['Time', 'Close'], $tick);
	
	$time = DateTime::createFromFormat('U.u', sprintf('%f', $tick['Time'] / 1000));
	
	// skip datasets out of range
	if ($time < $requestedDay || $time >= $requestedDayEnd) {
	    //echo '-- Skipping ' . $time->format('Y-m-d H:i') . PHP_EOL;
	    continue;
	}
	
	echo 'Got tick: ' . $time->format('Y-m-d H:i') . ', ';
	echo 'Price: ' . asPrice($tick['CLOSE']) . ' USD' . PHP_EOL;
	$tick['Time'] = getISODate($time);
	
	fputcsv($csv, array_values($tick));
}

fclose($csv);
