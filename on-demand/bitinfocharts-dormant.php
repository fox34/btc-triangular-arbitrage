<?php

$baseURL = 'https://bitinfocharts.com/top-100-dormant_1y-bitcoin-addresses.html';
$content = file_get_contents($baseURL);

if (!preg_match('~var dydata = \[(.+)\];~Us', $content, $matches)) {
    exit;
}

$dates = $matches[1];

if (!preg_match_all("~new Date\('(\d{4}/\d{2}/\d{2})'\),(\d+)~", $dates, $matches, PREG_SET_ORDER)) {
    exit;
}

header("Content-Type: text/plain");
foreach ($matches as $dataset) {
    
    $date = DateTime::createFromFormat('Y/m/d', $dataset[1]);
    
    echo $date->format('Y-m-d') . "," . $dataset[2] . PHP_EOL;
}
