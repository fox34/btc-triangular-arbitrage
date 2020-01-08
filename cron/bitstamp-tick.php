<?php

require_once '_include.php';

$src = strtolower($_GET['src'] ?? '');
if ($src !== 'eur' && $src !== 'usd') {
    die('Invalid source.');
}

define('API_URL', 'https://www.bitstamp.net/api/v2/transactions/btc' . $src . '/?time=day');
define('CSV_FILE', DATA_DIR . 'bitstamp/bitstamp-tick-btc' . $src . '.csv.gz');

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    echo 'Starting new.' . PHP_EOL;
    file_put_contents(CSV_FILE, gzencode('ID,Time,Amount,Price,Type' . PHP_EOL));
    $lastDataset = [0, '2000-01-01T00:00:00+00:00'];
    
} else {
    
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // Lese letzten Datensatz
    // Typischerweise nicht mehr als 10 kb an komprimierten Daten, bis zu 1MB zur Sicherheit lesen
    $lastChunk = gzfile_get_last_chunk_of_concatenated_file(CSV_FILE);
    if (empty($lastChunk)) {
        infoLog('Last chunk is empty.');
        exit;
    }

    $lastChunk = explode(PHP_EOL, $lastChunk);
    
    // Leeres Ende entfernen
    if (end($lastChunk) === '') {
        array_pop($lastChunk);
    }
    
    $lastDataset = explode(',', array_pop($lastChunk));
    
    // Speicher wieder freigeben
    unset($lastChunk);
    
    echo PHP_EOL . 'Last dataset: ID ' . $lastDataset[0] . ' @ ' . $lastDataset[1] . PHP_EOL;
    
    $lastDatasetTime = \DateTime::createFromFormat(TIMEFORMAT_SECONDS, $lastDataset[1]);
    
    // Letzter Datensatz zuletzt vor weniger als einer Stunde eingelesen
    if ($lastDatasetTime > ( (new DateTime())->sub(new DateInterval('PT1H')))) {
        die('Last dataset is too recent. Stop.');
    }
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

echo 'Reading data for BTC/' . strtoupper($src) . PHP_EOL;
echo 'Querying ' . API_URL . PHP_EOL;

$json = file_get_contents(API_URL);

$data = json_decode($json);
if (!is_array($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    var_dump($json);
    exit;
}

echo 'Received data: ' . round(strlen($json)/1000) . ' kB / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}

// Neueste zuerst, daher muss Reihenfolge umgekehrt werden
$data = array_reverse($data);


// Ergebnis zusammenstellen
$result = '';
foreach ($data as $tick) {
    
    $time = new \DateTime();
    $time->setTimestamp($tick->date);
    $tick->date = $time->format(TIMEFORMAT_SECONDS);
    
    $tickLine = sprintf(
        '%s,%s,%s,%s,%s',
        $tick->tid,
        $tick->date,
        $tick->amount,
        $tick->price,
        $tick->type
    );
    
    if ($tick->tid <= $lastDataset[0]) {
        echo 'ID below last dataset, skipping: ' . $tickLine . PHP_EOL;
        continue;
    }
    
    echo 'Tick: ' . $tickLine . PHP_EOL;
    // ID,Time,Amount,Price,Type
    $result .= $tickLine . PHP_EOL;
}

if (empty($result)) {
    die('No new datasets.');
}

$result = gzencode($result);
infoLog(
    'BTC' . strtoupper($src) . ': ' .
    'Collected ' . number_format(count($data), 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1000) . ' kB gzip to target file.'
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);


