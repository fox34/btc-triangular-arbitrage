<?php

$url = 'http://data.bitcoinity.org/chart_data';
$header = [
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Origin' => 'https://data.bitcoinity.org',
    'Accept' => '*/*',
    'X-Requested-With' => 'XMLHttpRequest',
    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Safari/605.1.15'
];
$data = [
    'data_type' => 'rank',
    'currency' => 'all',
    'exchange' => 'all',
    'function' => 'none',
    'groups_count' => '10',
    'timespan' => '2y',
    'resolution' => 'auto',
    'compare' => 'currency',
    'chart_type' => 'area_expanded',
    'smoothing' => 'linear',
    'scale_type' => 'lin'
];

// Begin -------------
//echo 'Fetching ranking from ' . $url . PHP_EOL;
$rankingData = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => $header,
        'content' => http_build_query($data)
    ]
]));

if ($rankingData === false) {
    die('Could not fetch data.');
}

$rankingData = json_decode($rankingData, true);
if ($rankingData === null) {
    die('Could not read response.');
}

// Melt structure
$rankingDataMelted = [['Time', 'Currency', 'Rank']];
foreach ($rankingData['data'] as $currencyData) {
    $currency = $currencyData['key'];
    foreach ($currencyData['values'] as $rankValue) {
        $date = new DateTime();
        $date->setTimestamp($rankValue[0]/1000);
        $rankingDataMelted[] = [
            $date->format('Y-m-d'),
            $currency,
            empty($rankValue[1]) ? 0 : $rankValue[1]
        ];
    }
}

//echo 'Fetched following exchanges from bitcoinity: ' . implode(', ', $foundExchanges) . PHP_EOL . PHP_EOL;
//echo 'New exchanges: ' . implode(', ', array_diff($foundExchanges, $existingExchanges)) . PHP_EOL;

header('Content-Type: text/csv; charset=UTF-8');
$csv = fopen('php://output', 'w');
foreach ($rankingDataMelted as $csvLine) {
    fputcsv($csv, $csvLine);
}
fclose($csv);
