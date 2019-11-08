<?php

require_once '_include.php';

$src = $_GET['src'] ?? 'USD';
if ($src !== 'EUR' && $src !== 'USD') die("Invalid source.");

$apiURL = 'http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstamp' . $src;
$dataFile = DATA_DIR . 'bitcoincharts-bitstamp-' . strtolower($src) . '-tick.csv';

echo 'Processing bitstamp' . $src . ' Ticks...' . PHP_EOL;

// CSV format:
// Timestamp (ms); Amount, Price
if (!file_exists($dataFile) || filesize($dataFile) === 0) {
    
    die('Target CSV not found.');
	
} else {
	
	echo 'Reading last dataset from CSV: ' . $dataFile . PHP_EOL;
    
	// read last 10 datasets for comparison with latest tick data in the same second,
	// since we don't get any microseconds, just ticks with same time
	$lastLines = tailCustom($dataFile, 10);
	if ($lastLines === false || empty($lastLines)) {
		die('Could not read CSV.');
	}
	$lastLines = explode("\n", $lastLines);
	
	$lastLine = str_getcsv(end($lastLines));
	
	try {
	    $lastDataset = readISODate($lastLine[0]);
	} catch (Exception $e) {
	    die('Could not parse last dataset: ' . $lastLine[0]);
    }
}

echo 'Last dataset: ' . $lastDataset->format('Y-m-d H:i:s') . PHP_EOL;
echo 'Querying ' . $apiURL . PHP_EOL;

$data = file($apiURL);
if (empty($data)) {
    die('Could not read response.');
}
echo 'Received '. count($data) . ' datasets.' . PHP_EOL . PHP_EOL;
$data = array_reverse($data);

// open target file
$csv = fopen($dataFile, 'a');
if ($csv === false) {
	die('Could not open target CSV file for writing.');
}


// build new lines
$newDatasets = 0;
foreach ($data as $line) {
	
	$tick = str_getcsv($line);
	$tick = array_combine(['Time', 'Price', 'Amount'], $tick);
	
	// Date format is unix time (as selected)
	$time = DateTime::createFromFormat('U', $tick['Time']);
	
	// skip datasets out of range
	if ($time < $lastDataset) {
	    /*
	    echo '-- Skipping ' . $time->format('Y-m-d H:i:s') . ' = ' .
	         asPrice($tick['Price']) . ' ' . $src . ', ' . $tick['Amount'] . ' BTC' . PHP_EOL;
	    */
	    continue;
	}
	
	// same second, check dataset
	if ($time == $lastDataset) {
	    
	    // check last datasets
	    foreach ($lastLines as $existingData) {
            $existingData = str_getcsv($existingData);
            $existingData = array_combine(['Time', 'Price', 'Amount'], $existingData);
	        $existingData['Time'] = readISODate($existingData['Time']);
            
	        if (
	            $time == $existingData['Time'] &&
	            $tick['Price'] == $existingData['Price'] &&
	            $tick['Amount'] == $existingData['Amount']
	        ) {
                echo '-- Skipping dataset for same second: ' . $time->format('Y-m-d H:i:s') . ' = ' .
                     asPrice($tick['Price']) . ' ' . $src . ', ' . $tick['Amount'] . ' BTC' . PHP_EOL;
	            continue 2;
	        }
	    }
	    
	}
	
	echo 'Got tick: ' . $time->format('Y-m-d H:i:s') . ', ' . 
	     'Price: ' . asPrice($tick['Price']) . ' ' . $src . '' . ', ' . $tick['Amount'] . ' BTC' . PHP_EOL;
	
	$newDatasets++;
	fputcsv($csv, [getISODate($time), $tick['Price'], $tick['Amount']]);
}

fclose($csv);

echo 'Finished. ' . $newDatasets . ' new datasets.' . PHP_EOL;
