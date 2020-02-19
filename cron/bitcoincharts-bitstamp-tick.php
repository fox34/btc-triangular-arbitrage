<?php

require_once '_include.php';

// Skript kann für BTCUSD und BTCEUR verwendet werden
$src = $_GET['src'] ?? '';
if ($src !== 'EUR' && $src !== 'USD') {
    die('Invalid source.');
}

// API-Quelle
// https://bitcoincharts.com/about/markets-api/
// Trade data is available as CSV, delayed by approx. 15 minutes. It will return the 2000 most recent trades.
define('API_URL', 'http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstamp' . $src);

// CSV-Ziel
define('CSV_FILE', DATA_DIR . 'bitstamp/bitcoincharts-bitstamp-tick-btc' . strtolower($src) . '.csv.gz');

echo 'Processing bitstamp' . $src . ' Ticks...' . PHP_EOL;

// CSV format:
// Timestamp (ms); Amount, Price
if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // Noch keine Daten gesammelt. Datei erzeugen und neu beginnen.
    file_put_contents(CSV_FILE, gzencode('Time,Price,Amount') . PHP_EOL);
    
} else {
    
    // Datensatz existiert, fortsetzen
    echo 'Reading last dataset from CSV: ' . CSV_FILE . PHP_EOL;
    
    // Lese letzten Datensatz
    $lastChunk = gzfile_get_last_chunk_of_concatenated_file(CSV_FILE);
    if (empty($lastChunk)) {
        die('Could not read last chunk from CSV.');
    }

    $lastLines = explode(PHP_EOL, $lastChunk);
    
    // Speicher freigeben
    unset($lastChunk);
    
    
    $lastLine = end($lastLines);
    
    // Leeres Ende entfernen
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
    
    echo 'Last dataset: ' . $lastDataset->format('Y-m-d H:i:s') . PHP_EOL;
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

// API abfragen
echo 'Querying ' . API_URL . PHP_EOL;

$data = file(API_URL);
if (empty($data)) {
    infoLog('Decoding data failed.');
    die('Could not read response.');
}
echo 'Received '. count($data) . ' datasets.' . PHP_EOL . PHP_EOL;

// Reihenfolge umkehren, da neueste zuerst erscheinen
$data = array_reverse($data);


// Ergebnis zusammenstellen
$result = '';
$newDatasets = 0;
foreach ($data as $line) {
    
    $tick = str_getcsv($line);
    $tick = array_combine(['Time', 'Price', 'Amount'], $tick);
    
    // Datum formatieren: Unix-Timestamp
    $time = \DateTime::createFromFormat('U', $tick['Time']);
    
    // Datensatz bereits erfasst
    if (isset($lastDataset) && $time < $lastDataset) {
        /*
        echo '-- Skipping ' . $time->format('Y-m-d H:i:s') . ' = ' .
             asPrice($tick['Price']) . ' ' . $src . ', ' . $tick['Amount'] . ' BTC' . PHP_EOL;
        */
        continue;
    }
    
    // Selbe Sekunde wie letzter Datensatz, eventuell bereits erfasst: Weitere Prüfung
    if (isset($lastDataset) && $time == $lastDataset) {
        
        // Prüfe alle letzten Datensätze in dieser Sekunde
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
    
    // Datensatz hinzufügen
    echo 'Got tick: ' . $time->format('Y-m-d H:i:s') . ', ' . 
         'Price: ' . asPrice($tick['Price']) . ' ' . $src . '' . ', ' . $tick['Amount'] . ' BTC' . PHP_EOL;
    
    $newDatasets++;
    $result .= implode(',', [getISODate($time), $tick['Price'], $tick['Amount']]) . PHP_EOL;
}

// Keine neuen Datensätze: Ende
if (empty($result)) {
    die('No new datasets.');
}

// Ergebnis in Datei speichern
$result = gzencode($result);
infoLog(
    'Collected ' . number_format($newDatasets, 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.'
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);

