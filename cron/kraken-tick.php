<?php

require_once '_include.php';

// Skript kann für BTCUSD und BTCEUR verwendet werden
$src = strtolower($_GET['src'] ?? '');
if ($src !== 'eur' && $src !== 'usd') {
    die('Invalid source.');
}

// Bezeichnung des Datensatzes bei Kraken setzt sich aus XAAAZBBB zusammen (Mengennotierung)
// AAA = Quotierte Währung = XBT = BTC
// BBB = Gegenwährung = USD oder EUR
$datasetName = 'XXBTZ' . strtoupper($src);

// API-Quelle
// Docs: https://www.kraken.com/features/api#get-recent-trades
define('API_URL', 'https://api.kraken.com/0/public/Trades?pair=xbt' . $src);

// CSV-Ziel
define('CSV_FILE', DATA_DIR . 'kraken/kraken-tick-btc' . $src . '.csv.gz');

// Status-File: Enthält zuletzt abgefragte ID für lückenlose Abfrage
define('STATE_FILE', DATA_DIR . 'kraken/.state-btc' . $src);

// Ab welcher ID soll das Crawling beginnen, wenn noch keine Daten vorliegen?
// 0 = Alle
define('INITIAL_ID_USD', 0); // 06.10.2013, 21:34:15
define('INITIAL_ID_EUR', 0); // 10.09.2013, 23:47:11

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    // Noch keine Daten gesammelt. Datei erzeugen und von vorne beginnen.
    echo 'Starting new.' . PHP_EOL;
    $since = $src === 'USD' ? INITIAL_ID_USD : INITIAL_ID_EUR;
    file_put_contents(CSV_FILE, gzencode('Time,Amount,Price,Type,Limit' . PHP_EOL));
    file_put_contents(STATE_FILE, $since);
    
} else {
    
    // Datensatz existiert, fortsetzen
    if (!file_exists(STATE_FILE) || filesize(STATE_FILE) === 0) {
        infoLog('Could not read state from ' . STATE_FILE);
        exit;
    }
    
    // Letzte ID lesen
    $since = file_get_contents(STATE_FILE);
    
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    echo 'Reading state from file: ' . STATE_FILE . ':' . PHP_EOL;
    echo 'State: ' . $since . PHP_EOL . PHP_EOL;
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

// API abfragen
echo 'Reading data for BTC/' . strtoupper($src) . PHP_EOL;

// Abfrage liefert immer maximal 1.000 Datensätze zurück
$url = API_URL . '&' . http_build_query([
    'since' => $since
]);

echo 'Querying ' . $url . PHP_EOL;

$json = file_get_contents($url);

// floats als string beibehalten, da die Werte für PHP zu groß werden können...
$json = preg_replace('/,(\d+\.\d+),/', ',"$1",', $json);

$data = json_decode($json);
if (!is_object($data)) {
    echo 'Could not decode response: ' . json_last_error_msg() . PHP_EOL;
    echo 'Received data: ' . PHP_EOL;
    var_dump($json);
    exit;
}

echo 'Received data: ' . strlen($json) . ' bytes / '. count($data->result->{$datasetName}) . ' datasets' . PHP_EOL . PHP_EOL;

if (empty($data)) {
    die('Received dataset is empty.');
}

// Ergebnis zusammenstellen
$result = '';
foreach ($data->result->{$datasetName} as $tick) {
    
    // Datensatz:
    // [ "123.91000", "1.00000000", 1381201115.641, "s"       , "l"           , ""              ]
    //   <price>    , <volume>    , <time>        , <buy/sell>, <market/limit>, <miscellaneous>
    
    // sprintf mit %f damit auf jeden Fall das Format U.u herauskommt, auch bei exakt 0ms
    $time = \DateTime::createFromFormat('U.u', sprintf('%f', $tick[2]));
    $tick[2] = getISODate($time);
    
    // Datensatz aufbereiten: Zeitstempel, Volumen, Preis, Art, Markt/Limit
    $tickInOrder = [
        $tick[2],
        $tick[1],
        $tick[0],
        $tick[3],
        $tick[4]
    ];
    
    // Keine Prüfung auf Duplikate, da exakt die ID der letzten Abfrage angegeben wird
    
    // Datensatz hinzufügen
    echo 'Got tick: ' . implode(' / ', $tickInOrder) . PHP_EOL;
    $result .= implode(',', $tickInOrder) . PHP_EOL;
}

// Keine neuen Datensätze: Ende
if (empty($result)) {
    die('No new datasets.');
}

// Ergebnis in Datei speichern
$result = gzencode($result);
infoLog(
    'BTC' . strtoupper($src) . ': ' .
    'Collected ' . number_format(count($data->result->{$datasetName}), 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file. ' .
    'Last dataset: ' . $data->result->last
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);
file_put_contents(STATE_FILE, $data->result->last);

