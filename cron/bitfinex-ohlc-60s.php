<?php

require_once '_include.php';

define('API_URL', 'https://api-pub.bitfinex.com/v2/candles/trade:1m:tBTCUSD/hist');
define('CSV_FILE', DATA_DIR . 'bitfinex-ohlc-60s.csv.gz');

// CSV format:
// Timestamp (ms); Open; Close; High; Low; Volume

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    die('Target CSV not found.');
    
    // fresh start
    file_put_contents(CSV_FILE, gzencode('Time,Open,Close,High,Low,Volume', 6) . PHP_EOL);
    $lastDataset = new DateTime('2016-11-01');
    
} else {
    
    // Lese letzten Datensatz
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // Lese letzten Datensatz
    // Typischerweise nicht mehr als 10 kb an komprimierten Daten, bis zu 1MB zur Sicherheit lesen
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
    
    $lastDataset->add(new DateInterval('PT1S'));
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
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

// Ergebnis zusammenstellen
$result = '';
foreach ($data as $tick) {
    
    $tick = array_combine(['Time', 'Open', 'Close', 'High', 'Low', 'Volume'], $tick);
    
    $time = DateTime::createFromFormat('U.u', sprintf('%f', $tick['Time'] / 1000));
    echo 'Got tick: ' . $time->format('Y-m-d H:i:s.u') . ', ';
    echo 'Mean: ' . number_format(($tick['Open'] + $tick['Close']) / 2, 2) . PHP_EOL;
    
    $tick['Time'] = getISODate($time);
    
    $result .= implode(',', array_values($tick)) . PHP_EOL;
}

$result = gzencode($result, 6);
echo 'Collected ' . number_format(count($data), 0, ',', '.') . ' datasets. Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.' . PHP_EOL;

file_put_contents(CSV_FILE, $result, FILE_APPEND);


