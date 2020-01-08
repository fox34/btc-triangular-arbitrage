<?php

require_once '_include.php';

$src = strtolower($_GET['src'] ?? '');
if ($src !== 'eur' && $src !== 'usd') {
    die('Invalid source.');
}
$datasetName = 'XXBTZ' . strtoupper($src);

define('API_URL', 'https://api.kraken.com/0/public/Trades?pair=xbt' . $src);
define('CSV_FILE', DATA_DIR . 'kraken/kraken-tick-btc' . $src . '.csv.gz');
define('STATE_FILE', DATA_DIR . 'kraken/.state-btc' . $src); // Enthält "last"-ID der letzten Abfrage

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    echo 'Starting new.' . PHP_EOL;
    $since = 0;
    file_put_contents(CSV_FILE, gzencode('Time,Amount,Price,Type,Limit' . PHP_EOL));
    file_put_contents(STATE_FILE, $since);
    
} else {
    
    if (!file_exists(STATE_FILE) || filesize(STATE_FILE) === 0) {
        infoLog('Could not read state from ' . STATE_FILE);
        exit;
    }
    
    $since = file_get_contents(STATE_FILE);
    
    echo 'Last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    echo 'Reading state from file: ' . STATE_FILE . ':' . PHP_EOL;
    echo 'State: ' . $since . PHP_EOL . PHP_EOL;
    
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

echo 'Reading data for BTC/' . strtoupper($src) . PHP_EOL;

// Docs: https://www.kraken.com/features/api#get-recent-trades
// Abfrage liefert immer maximal 1.000 Datensätze zurück
$url = API_URL . '&' . http_build_query([
    'since' => $since
]);

echo 'Querying ' . $url . PHP_EOL;

$json = file_get_contents($url);

// preserve time stamps as string, too large for php to handle...
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
    
    // DO NOT PERFORM ANY CALCULATIONS! Number too large for PHP float
    // sprintf mit %f damit auf jeden Fall das Format U.u herauskommt, auch bei exakt 0ms
    $time = \DateTime::createFromFormat('U.u', sprintf('%f', $tick[2]));
    $tick[2] = getISODate($time);
    
    // Time,Amount,Price,Type,Limit
    $tickInOrder = [
        $tick[2],
        $tick[1],
        $tick[0],
        $tick[3],
        $tick[4]
    ];
    
    echo 'Got tick: ' . implode(' / ', $tickInOrder) . PHP_EOL;
    $result .= implode(',', $tickInOrder) . PHP_EOL;
}

if (empty($result)) {
    die('No new datasets.');
}

$result = gzencode($result);
infoLog(
    'BTC' . strtoupper($src) . ': ' .
    'Collected ' . number_format(count($data->result->{$datasetName}), 0, ',', '.') . ' datasets. ' . 
    'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file. ' .
    'Last dataset: ' . $data->result->last
);

file_put_contents(CSV_FILE, $result, FILE_APPEND);
file_put_contents(STATE_FILE, $data->result->last);

