<?php

require_once '_include.php';

$src = $_GET['src'] ?? '';
if ($src !== 'EUR' && $src !== 'USD') {
    die('Invalid source.');
}

define('API_URL', 'http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstamp' . $src);
define('CSV_FILE', DATA_DIR . 'bitcoincharts-bitstamp-' . strtolower($src) . '-tick.csv.gz');

echo 'Processing bitstamp' . $src . ' Ticks...' . PHP_EOL;

// CSV format:
// Timestamp (ms); Amount, Price
if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    die('Target CSV not found.');
    file_put_contents(CSV_FILE, gzencode('Time,Price,Amount', 9) . PHP_EOL);
    
} else {
    
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    
    // Lese letzten Datensatz
    // Typischerweise nicht mehr als 10 kb an komprimierten Daten, bis zu 1MB zur Sicherheit lesen
    $lastChunk = gzfile_get_last_chunk_of_concatenated_file(CSV_FILE);
    if (empty($lastChunk)) {
        die('Could not read last chunk from CSV.');
    }

    $lastLines = explode(PHP_EOL, $lastChunk);
    
    // Speicher wieder freigeben
    unset($lastChunk);
    
    
    $lastLine = end($lastLines);
    
    // last line is empty
    if ($lastLine === '') {
        array_pop($lastLines);
        $lastLine = end($lastLines);
    }
    
    $lastLines = array_slice($lastLines, -10);
    $lastLine = str_getcsv($lastLine);
    
    try {
        $lastDataset = readISODate($lastLine[0]);
    } catch (Exception $e) {
        die('Could not parse last dataset: ' . $lastLine[0]);
    }
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

echo 'Last dataset: ' . $lastDataset->format('Y-m-d H:i:s') . PHP_EOL;
echo 'Querying ' . API_URL . PHP_EOL;

$data = file(API_URL);
if (empty($data)) {
    die('Could not read response.');
}
echo 'Received '. count($data) . ' datasets.' . PHP_EOL . PHP_EOL;
$data = array_reverse($data);


// build new lines
$result = '';
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
    $result .= implode(',', [getISODate($time), $tick['Price'], $tick['Amount']]) . PHP_EOL;
}

$result = gzencode($result, 9);
echo 'Collected ' . number_format($newDatasets, 0, ',', '.') . ' datasets. Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.' . PHP_EOL;

file_put_contents(CSV_FILE, $result, FILE_APPEND);

