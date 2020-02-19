<?php

require_once '_include.php';

// Skript kann für BTCUSD und BTCEUR verwendet werden
$src = strtolower($_GET['src'] ?? '');
if ($src !== 'eur' && $src !== 'usd') {
    die('Invalid source.');
}

// API-Quelle
define('API_URL', 'https://www.bitstamp.net/api/v2/transactions/btc' . $src . '/?time=day');

// CSV-Ziel
define('CSV_FILE', DATA_DIR . 'bitstamp/bitstamp-tick-btc' . $src . '.csv.gz');


if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // Noch keine Daten gesammelt. Datei erzeugen und neu beginnen.
    // Bitstamp erlaubt keine Abfrage historischer Daten -> Beginn ab heute
    echo 'Starting new.' . PHP_EOL;
    file_put_contents(CSV_FILE, gzencode('ID,Time,Amount,Price,Type' . PHP_EOL));
    
    // Letzte ID = 0 = Anfang
    $lastDataset = [0];
    
} else {
    
    // Datensatz existiert, fortsetzen
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // Lese letzten Datensatz
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
    
    echo PHP_EOL . 'Last dataset: ID ' . $lastDataset[0] . ' @ ' . $lastDataset[1] . PHP_EOL;
    
    $lastDatasetTime = \DateTime::createFromFormat(TIMEFORMAT_SECONDS, $lastDataset[1]);
    
    // Letzter Datensatz zuletzt vor weniger als einer Stunde eingelesen
    if ($lastDatasetTime > ( (new \DateTime())->sub(new \DateInterval('PT1H')))) {
        die('Last dataset is too recent. Stop.');
    }
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

// API abfragen
echo 'Reading data for BTC/' . strtoupper($src) . PHP_EOL;
echo 'Querying ' . API_URL . PHP_EOL;

$json = file_get_contents(API_URL);

$data = json_decode($json);
if (!is_array($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    infoLog('Decoding data failed.');
    var_dump($json);
    exit;
}

echo 'Received data: ' . round(strlen($json)/1000) . ' kB / '. count($data) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}

// Neueste zuerst, daher Reihenfolge umkehren
$data = array_reverse($data);

// Ergebnis zusammenstellen
$result = '';
foreach ($data as $tick) {
    
    // Zeit einlesen: Unix-Timestamp
    $time = new \DateTime();
    $time->setTimestamp($tick->date);
    $tick->date = getISODateSeconds($time);
    
    // Datensatz aufbereiten: ID, Zeitstempel, Volumen, Preis, Art
    $tickLine = sprintf(
        '%s,%s,%s,%s,%s',
        $tick->tid,
        $tick->date,
        $tick->amount,
        $tick->price,
        $tick->type
    );
    
    // Dieser Datensatz ist bereits erfasst worden
    if ($tick->tid <= $lastDataset[0]) {
        echo 'ID below last dataset, skipping: ' . $tickLine . PHP_EOL;
        continue;
    }
    
    // Datensatz hinzufügen
    echo 'Tick: ' . $tickLine . PHP_EOL;
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


