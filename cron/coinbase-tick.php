<?php

require_once '_include.php';

// Skript kann für BTCUSD und BTCEUR verwendet werden
$src = strtoupper($_GET['src'] ?? '');
if ($src !== 'EUR' && $src !== 'USD' && $src !== 'GBP') {
    die('Invalid source.');
}

// API-Quelle
define('API_URL', 'https://api.pro.coinbase.com/products/BTC-' . $src . '/trades');

// CSV-Ziel
define('CSV_FILE', DATA_DIR . 'coinbase/coinbase-tick-btc' . strtolower($src) . '.csv.gz');

// Status-File: Enthält zuletzt abgefragte Seite für lückenlose Abfrage
define('STATE_FILE', DATA_DIR . 'coinbase/.state-btc' . strtolower($src));


// Ab welcher Seite soll das Crawling beginnen, wenn noch keine Daten vorliegen?
define('INITIAL_START_PAGE_USD', 31503503); // 31.12.2017 23:59:55.819 = Letzter Datensatz vor 2018
define('INITIAL_START_PAGE_EUR', 8814390); // 31.12.2017 23:59:50.700 = Letzter Datensatz vor 2018
define('INITIAL_START_PAGE_GBP', 2544002); // 31.12.2017 23:59:50.373 = Letzter Datensatz vor 2018


if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // Noch keine Daten gesammelt. Datei erzeugen und von vorne beginnen.
    echo 'Starting new.' . PHP_EOL;
    file_put_contents(CSV_FILE, gzencode('ID,Time,Amount,Price,Type' . PHP_EOL));
    $lastDataset = [0, '2000-01-01T00:00:00+00:00'];
    if ($src === 'USD') {
        $startPage = INITIAL_START_PAGE_USD;
    } elseif ($src === 'EUR') {
        $startPage = INITIAL_START_PAGE_EUR;
    } elseif ($src === 'GBP') {
        $startPage = INITIAL_START_PAGE_GBP;
    } else {
        die("Unknown start page.");
    }
    file_put_contents(STATE_FILE, $startPage);
    
} else {
    
    // Datensatz existiert, fortsetzen
    printLog('CSV last modified:', strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)));
    
    // Letzte Seite lesen
    $startPage = file_get_contents(STATE_FILE);
    printLog('Stored state:', $startPage);
    
    // Lese letzten Datensatz, um Duplikate zu vermeiden
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
    
    // Speicher freigeben
    unset($lastChunk);
    
    printLog('Last dataset in CSV:', $lastDataset[0], '@', $lastDataset[1], PHP_EOL);
    
    $lastDatasetTime = \DateTime::createFromFormat(TIMEFORMAT_SECONDS, $lastDataset[1]);
    
    // Letzter Datensatz zuletzt vor weniger als einer Minute eingelesen
    if ($lastDatasetTime > ( (new DateTime())->sub(new DateInterval('PT1M')))) {
        die('Last dataset is too recent. Stop.');
    }
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

// API abfragen
printLog('Reading data for BTC/' . $src);

$url = API_URL . '?' . http_build_query([
    'after' => $startPage
]);

printLog('Querying ' . $url);

// Coinbase braucht einen User Agent und die passenden Kopfzeilen. Simuliere macOS 10.15.2 mit Safari
$json = file_get_contents($url, false, stream_context_create([
    'http' => [
        'header' =>
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15\r\n" . 
            "Accept-Language: de-de\r\n" .
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" . 
            "Accept-Encoding: gzip, deflate, br\r\n" . 
            "Connection: Close\r\n"
    ]
]));

// Daten mit hoher Wahrscheinlichkeit GZip-komprimiert
if (($data = gzdecode($json)) === false) {
    $data = $json;
}

$data = json_decode($data);
if (empty($json) || !is_array($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    infoLog('Decoding data failed.');
    var_dump($json);
    exit;
}

printLog('Received', strlen($json), 'bytes containing', count($data), 'datasets', PHP_EOL);

if (empty($data)) {
    die('Received dataset is empty.');
}

// Neueste zuerst, daher muss Reihenfolge umgekehrt werden
$data = array_reverse($data);

// Ergebnis zusammenstellen
$result = '';
echo '           ID       Time                        Volume     Price         is_sell' . PHP_EOL;
foreach ($data as $tick) {
    
    // Datum einlesen
    // Format: 2020-01-07T13:16:38.55Z
    //    oder 2020-01-07T13:16:38Z
    $time = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $tick->time);
    if ($time === false) {
        $time = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $tick->time);
    }
    $tick->time = getISODate($time);
    
    // Datensatz aufbereiten: ID, Zeitstempel, Volumen, Preis, Art
    $tickLine = sprintf(
        '%s,%s,%s,%s,%s',
        $tick->trade_id,
        $tick->time,
        $tick->size,
        $tick->price,
        (int)($tick->side === 'sell') // 0 =  buy / 1 = sell
    );
    
    // Dieser Datensatz ist bereits erfasst worden
    if ($tick->trade_id <= $lastDataset[0]) {
        echo 'Duplicate: ' . $tickLine . PHP_EOL;
        continue;
    }
    
    // Datensatz hinzufügen
    echo '     Tick: ' . $tickLine . PHP_EOL;
    $result .= $tickLine . PHP_EOL;
}

// Keine neuen Datensätze: Ende
if (empty($result)) {
    die('No new datasets.');
}

// Ergebnis in Datei speichern
$result = gzencode($result);
infoLog(
    'BTC' . strtoupper($src) . ': ' .
    'Collected ' . number_format(count($data), 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1000) . ' kB gzip to target file.'
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);
file_put_contents(STATE_FILE, $startPage + 99); // Immer 100 Datensätze enthalten


