<?php

require '_include.php';

// Config ----------------
$dataFile = DATA_DIR . 'bitcoinity-exchanges.json';
$dataFileCSV = DATA_DIR . 'bitcoinity-exchanges.csv';

$url = 'https://data.bitcoinity.org/markets/exchanges_data';
$header = [
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Origin' => 'https://data.bitcoinity.org',
    'Accept' => '*/*',
    'DNT' => '1',
    'X-Requested-With' => 'XMLHttpRequest',
    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1 Safari/605.1.15'
];
$data = [
    'currency' => 'all',
    'timespan' => '5y',
    'market_type' => 'exchange'
];

// Begin -------------
$existingExchanges = file_exists($dataFile)
    ? json_decode(file_get_contents($dataFile), true)
    : [];

// test for parsing failures
if (empty($existingExchanges)) {
    $existingExchanges = [];
}
echo 'Found following exchanges in JSON: ' . implode(', ', $existingExchanges) . PHP_EOL . PHP_EOL;

echo 'Fetching exchanges list from ' . $url . PHP_EOL;
$exchangeList = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => $header,
        'content' => http_build_query($data)
    ]
]));

if ($exchangeList === false) {
    die('Could not fetch data.');
}

$exchangeList = json_decode($exchangeList, true);
if ($exchangeList === null) {
    die('Could not read response.');
}

$foundExchanges = array_column($exchangeList['list'], 'nice_name', 'name');
ksort($foundExchanges);
echo 'Fetched following exchanges from bitcoinity: ' . implode(', ', $foundExchanges) . PHP_EOL . PHP_EOL;
echo 'New exchanges: ' . implode(', ', array_diff($foundExchanges, $existingExchanges)) . PHP_EOL;

echo 'Writing JSON...' . PHP_EOL;
$totalExchanges = array_merge($existingExchanges, $foundExchanges);
ksort($totalExchanges);
file_put_contents($dataFile, json_encode($totalExchanges));

echo 'Writing CSV...' . PHP_EOL;
$csv = fopen($dataFileCSV, 'w');
fputcsv($csv, ['Exchange', 'NiceName']);
foreach ($totalExchanges as $name => $niceName) {
    if ($name === 'coinbase') {
        $niceName = 'Coinbase'; // Kein GDAX, da unüblich
    }
    fputcsv($csv, [$name, $niceName]);
}
fclose($csv);

infoLog('Collected ' . count($totalExchanges) . ' exchanges.');
