<?php

require_once '_include.php';

$src = strtoupper($_GET['src'] ?? '');
if ($src !== 'EUR' && $src !== 'USD') {
    die('Invalid source.');
}

define('API_URL', 'https://api.pro.coinbase.com/products/BTC-' . $src . '/trades');
define('CSV_FILE', DATA_DIR . 'coinbase-tick-' . $src . '.csv.gz');
define('STATE_FILE', DATA_DIR . '.coinbase-state-' . strtolower($src)); // Enthält letzte Seite der Pagination

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    echo 'Starting new.' . PHP_EOL;
    file_put_contents(CSV_FILE, gzencode('ID,Time,Amount,Price,Type' . PHP_EOL));
    $lastDataset = [0, '2000-01-01T00:00:00+00:00'];
    $startPage = $src === 'USD' ? 12310000 : 1370000; // ca. 2016-12-31
    file_put_contents(STATE_FILE, $startPage);
    
} else {
    
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // Letzte Seite lesen
    $startPage = file_get_contents(STATE_FILE);
    echo 'Reading start page from file: ' . STATE_FILE . ':' . PHP_EOL;
    echo 'Start page: ' . $startPage . PHP_EOL . PHP_EOL;
    
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

echo 'Reading data for BTC/' . $src . PHP_EOL;

$url = API_URL . '?' . http_build_query([
    'after' => $startPage
]);

echo 'Querying ' . $url . PHP_EOL;

$json = file_get_contents($url, false, stream_context_create([
    'http' => [
        'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15\r\n" . 
            "Accept-Language: de-de\r\n" .
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" . 
            "Accept-Encoding: gzip, deflate, br\r\n" . 
            "Connection: Close\r\n"
    ]
]));

// most likely gzip encoded
if (($data = gzdecode($json)) === false) {
    $data = $json;
}

$data = json_decode($data);
if (empty($json) || !is_array($data)) {
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
    
    // 2020-01-07T13:16:38.55Z
    $time = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $tick->time);
    if ($time === false) {
        $time = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $tick->time);
    }
    $tick->time = $time->format(TIMEFORMAT_SECONDS);
    
    $tickLine = sprintf(
        '%s,%s,%s,%s,%s',
        $tick->trade_id,
        $tick->time,
        $tick->size,
        $tick->price,
        (int)($tick->side === 'sell') // 0 =  buy / 1 = sell
    );
    
    if ($tick->trade_id <= $lastDataset[0]) {
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
file_put_contents(STATE_FILE, $startPage + 99); // Immer 100 Datensätze enthalten


