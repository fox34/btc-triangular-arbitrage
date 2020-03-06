<?php

require_once '_include.php';

// Skript kann für BTCUSD und BTCEUR verwendet werden
$src = strtoupper($_GET['src'] ?? '');
if ($src !== 'EUR' && $src !== 'USD' && $src !== 'JPY' && $src !== 'GBP') {
    die('Invalid source.');
}

// API-Quelle
// Docs: https://docs.bitfinex.com/reference#rest-public-trades
define('API_URL', 'https://api-pub.bitfinex.com/v2/trades/tBTC' . $src . '/hist');

// CSV-Ziel
define('CSV_FILE', DATA_DIR . 'bitfinex/bitfinex-tick-btc' . strtolower($src) . '.csv.gz');

// Ab welchem Datum soll das Crawling beginnen, wenn noch keine Daten vorliegen?
define('INITIAL_START_DATE', '2018-01-01');




if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // Noch keine Daten gesammelt. Datei erzeugen und von vorne beginnen.
    echo 'Starting new.' . PHP_EOL;
    file_put_contents(CSV_FILE, gzencode('ID,Time,Amount,Price' . PHP_EOL));
    $startQuery = new \DateTime(INITIAL_START_DATE);
    $lastDatasets = [];
    
} else {
    
    // Datensatz existiert, fortsetzen
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // Lese letzten Datensatz
    // Typischerweise nicht mehr als 10 kb an komprimierten Daten, bis zu 1MB zur Sicherheit lesen
    $lastChunk = gzfile_get_last_chunk_of_concatenated_file(CSV_FILE);
    if (empty($lastChunk)) {
        infoLog('Last chunk is empty.');
        exit;
    }

    $lastDatasetsRaw = explode(PHP_EOL, $lastChunk);
    
    // Leeres Ende entfernen
    if (end($lastDatasetsRaw) === '') {
        array_pop($lastDatasetsRaw);
    }
    
    $lastDatasetsRaw = array_slice($lastDatasetsRaw, -200);
    $lastDatasets = [];
    
    // Speicher wieder freigeben
    unset($lastChunk);
    
    
    // read last datasets to prevent duplicates with same timestamp/id
    // assume there will be not more than 200 ticks at the exact same timestamp
    foreach ($lastDatasetsRaw as $i => $lastDataset) {
        $lastDataset = explode(',', $lastDataset);
        $lastDatasets[$lastDataset[0]] = $lastDataset;
    }
    
    $lastDatasetTime = readISODate(end($lastDatasets)[1]);
    
    echo PHP_EOL . 'Last dataset: ' . $lastDatasetTime->format('Y-m-d H:i:s.u') . PHP_EOL;

    // Zuletzt erfasster Datensatz ist weniger als eine Minute alt: Verhindere zu häufige Abfragen
    if ($lastDatasetTime > ( (new DateTime())->sub(new \DateInterval('PT1M')))) {
        die('Last dataset is too recent. Stop.');
    }

    // Eine Sekunde in die Vergangenheit abfragen, um ggf. doppelte Aufträge in der selben Sekunde vollständig
    // zu erfassen
    $startQuery = clone $lastDatasetTime;
    $startQuery->sub(new DateInterval('PT1S'));
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

// API abfragen
echo 'Reading data for BTC/' . $src . PHP_EOL;
echo PHP_EOL . 'Querying from: ' . $startQuery->format('Y-m-d H:i:s.u') . PHP_EOL;

$url = API_URL . '?' . http_build_query([
    'start' => getUnixTimeWithMilliseconds($startQuery),
    'limit' => 5000,
    'sort' => 1,
]);

echo 'Querying ' . $url . PHP_EOL;

$json = file_get_contents($url);

// floats als string beibehalten, da die Werte für PHP zu groß werden können...
$json = preg_replace('/((?:-)?\d+(?:\.\d+)?(?:e-\d+)?)/', '"$1"', $json);

$data = json_decode($json);
if (!is_array($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    infoLog('Decoding data failed.');
    var_dump($json);
    exit;
}

echo 'Received data: ' . strlen($json) . ' bytes / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}

// Nach ID aufsteigend sortieren (= erste Spalte)
usort($data, function($a, $b) {
    return $a[0] - $b[0];
});


// Ergebnis zusammenstellen
$result = '';
foreach ($data as $tick) {
    
    // Datum einlesen: Unix-Timestamp mit Millisekunden
    $time = DateTime::createFromFormat('U.u', sprintf('%f', $tick[1] / 1000));
    $tick[1] = getISODate($time);
    
    $tickLine = implode(' / ', $tick);
    
    // Dieser Datensatz ist bereits erfasst worden
    if (isset($lastDatasets[$tick[0]])) {
        echo 'ID exists, skipping: ' . $tickLine . PHP_EOL;
        continue;
    }
    
    echo 'Tick: ' . $tickLine . PHP_EOL;
    
    // Datensatz ist bereits in richtigem Format: ID, Zeitstempel, Volumen (+/- mit Art), Preis
    $result .= implode(',', array_values($tick)) . PHP_EOL;
}

// Keine neuen Datensätze: Ende
if (empty($result)) {
    die('No new datasets.');
}

// Ergebnis in Datei speichern
$result = gzencode($result);
infoLog(
    'BTC' . $src . ': ' .
    'Collected ' . number_format(count($data), 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.'
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);


