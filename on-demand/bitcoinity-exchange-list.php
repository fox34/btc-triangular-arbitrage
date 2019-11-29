<?php

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

header('Content-Type: text/csv; charset=UTF-8');
echo 'Exchange,NiceName' . PHP_EOL;
foreach ($foundExchanges as $name => $niceName) {
    if ($name === 'coinbase') {
        $niceName = 'Coinbase'; // Kein GDAX, da unüblich
    }
    echo $name . ',' . $niceName . PHP_EOL;
}
