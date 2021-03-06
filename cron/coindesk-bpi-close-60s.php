<?php

require_once '_include.php';

// API-Quelle
define('API_URL', 'https://api.coindesk.com/charts/data');

// CSV-Ziel
define('CSV_FILE', DATA_DIR . 'coindesk/coindesk-bpi-close-60s.csv.gz');

echo 'Processing 60s close...' . PHP_EOL;

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // Noch keine Daten gesammelt. Datei erzeugen und von vorne beginnen.
    file_put_contents(CSV_FILE, gzencode('Time,Close') . PHP_EOL);
    $lastDataset = new \DateTime('2010-07-18');
    
} else {
    
    // Lese letzten Datensatz
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    
    $lastChunk = gzfile_get_last_chunk_of_concatenated_file(CSV_FILE);
    if (empty($lastChunk)) {
        die('Could not read last chunk from CSV.');
    }

    $chunkLines = explode(PHP_EOL, $lastChunk);
    $lastLine = array_pop($chunkLines);
    
    // last line is empty
    if ($lastLine === '') {
        $lastLine = array_pop($chunkLines);
    }
    
    // Speicher wieder freigeben
    unset($lastChunk, $chunkLines);
    
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

// API abfragen
echo 'Last dataset:          ' . $lastDataset->format('Y-m-d H:i') . PHP_EOL;

$requestedDay = clone $lastDataset;
$requestedDay->setTime(0, 0, 0);
$requestedDay->add(new \DateInterval('P1D'));

$requestedDayEnd = clone $requestedDay;
$requestedDayEnd->add(new \DateInterval('P1D'));

echo 'Querying for this day: ' . $requestedDay->format('Y-m-d') . PHP_EOL;

if ($requestedDay >= (new DateTime())->setTime(0, 0, 0)) {
    die('Next dataset is too recent. Stop.');
}


$url = API_URL . '?' . http_build_query([
    'data' => 'close',
    'startdate' => $requestedDay->format('Y-m-d'),
    'enddate' => $requestedDay->format('Y-m-d'),
    'exchanges' => 'bpi',
    'dev' => 1,
    'index' => 'USD',
]);

echo 'Querying ' . $url . PHP_EOL;

$result = file_get_contents($url);

// Da es eine interne API ist, ist das Rückgabeformat etwas speziell
// Rückgabeformat: cb({"bpi": [...] }); -> {"bpi": [...]}
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


// Ergebnis zusammenstellen
$result = '';
foreach ($data as $tick) {
    
    $tick = array_combine(['Time', 'Close'], $tick);
    $time = \DateTime::createFromFormat('U.u', sprintf('%f', $tick['Time'] / 1000));
    
    // skip datasets out of range
    if ($time < $requestedDay || $time >= $requestedDayEnd) {
        //echo '-- Skipping ' . $time->format('Y-m-d H:i') . PHP_EOL;
        continue;
    }
    
    echo 'Got tick: ' . $time->format('Y-m-d H:i') . ', ';
    echo 'Price: ' . asPrice($tick['CLOSE']) . ' USD' . PHP_EOL;
    $tick['Time'] = getISODate($time);
    
    $result .= implode(',', array_values($tick)) . PHP_EOL;
}

// Keine neuen Datensätze
if (empty($result)) {
    die('No new datasets.');
}

// Ergebnis in Datei speichern
$result = gzencode($result);
infoLog(
    'Collected ' . number_format(count($data), 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.'
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);
