<?php

require_once '_config.php';

if (!in_array($_SERVER['REMOTE_ADDR'], VALID_REQUEST_IPS)) {
    http_response_code(401);
    die("Access denied.");
}

// Empfange Werte von Python-Skript
// Werte in $_POST['raw_data']
if (empty($_POST['raw_data']) || empty($_POST['source_host']) || empty($_POST['exchange'])) {
    http_response_code(400);
    die("No values provided.");
}

require_once '_include.php';

$sourceHost = strtolower(basename($_POST['source_host']));
$exchange = strtolower(basename($_POST['exchange']));
$dot = ($sourceHost === 'drive.noecho.de') ? '' : '.'; // hide backup hosts

// csv: backwards-compatibility and additional backup
define('CSV_FILE', DATA_DIR . $dot . 'orderbook-' . $exchange . '-crawler-' . $sourceHost . '.csv');

echo 'Received data for ' . $exchange .
     ' from host ' . $sourceHost . ' (' . $_SERVER['REMOTE_ADDR'] . ')' . PHP_EOL;

if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    echo 'Starting new CSV.' . PHP_EOL;
    file_put_contents(CSV_FILE, 'Unixtime,Type,Price,Amount' . PHP_EOL);
}

// open target file
$csv = fopen(CSV_FILE, 'a');
if ($csv === false) {
    http_response_code(500);
    die('Could not open target CSV file for writing.');
}

// Struct:
try {
    $dataset = json_decode($_POST['raw_data'], false, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException $e) {
    http_response_code(400);
    die('Could not parse provided data as JSON.');
}

// build result
foreach ($dataset as $time_fragment) {
    
    /*
    $timestamp = new \DateTime();
    $timestamp->setTimestamp($time_fragment->timestamp);
    $time = getISODate($timestamp);
    */
    
    // using unixtime saves *a lot* of disk space
    // for consistency remove fragments of a second
    $time = $time_fragment->timestamp;
    if (($pos = strpos($time, '.')) !== false) {
        $time = substr($time, 0, $pos);
    }
    
    foreach ($time_fragment->bids as $bid) {
        echo 'Bid @ ' . $bid[0] . PHP_EOL;
        fputcsv($csv, [$time, 'Bid', $bid[0], $bid[1]]);
    }
    foreach ($time_fragment->asks as $ask) {
        echo 'Ask @ ' . $ask[0] . PHP_EOL;
        fputcsv($csv, [$time, 'Ask', $ask[0], $ask[1]]);
    }
    
    echo 'Processed: ' . $time . PHP_EOL . PHP_EOL;
}

fclose($csv);

// New: Insert to Database
// getPDO() defined in config.php
/** @var \PDO $pdo */
$pdo = getPDO();

$query = 'INSERT INTO order_books (book_time, exchange_name, source_host, type, price, amount) VALUES ';
$pdo_data = [];

// build query
$first = true;
foreach ($dataset as $time_fragment) {
    
    if (strpos($time_fragment->timestamp, '.') !== false) {
        $timestamp = \DateTime::createFromFormat('U.u', $time_fragment->timestamp);
    } else {
        $timestamp = \DateTime::createFromFormat('U', $time_fragment->timestamp);
    }
    
    $time = $timestamp->format('Y-m-d H:i:s.u');
    
    foreach ($time_fragment->bids as $bid) {
        echo 'Bid @ ' . $bid[0] . PHP_EOL;
        
        if (!$first) {
            $query .= ',';
        } else {
            $first = false;
        }
        
        $query .= '(?, ?, ?, ?, ?, ?)';
        $pdo_data = array_merge($pdo_data, [
            $time,
            $exchange,
            $sourceHost,
            'bid',
            $bid[0],
            $bid[1]
        ]);
    }
    foreach ($time_fragment->asks as $ask) {
        echo 'Ask @ ' . $ask[0] . PHP_EOL;
        
        if (!$first) {
            $query .= ',';
        } else {
            $first = false;
        }
        
        $query .= '(?, ?, ?, ?, ?, ?)';
        $pdo_data = array_merge($pdo_data, [
            $time,
            $exchange,
            $sourceHost,
            'ask',
            $ask[0],
            $ask[1]
        ]);
    }
    
    echo 'Processed: ' . $time . PHP_EOL . PHP_EOL;
}

$stmt = $pdo->prepare($query);

try {
    $stmt->execute($pdo_data);
} catch (\PDOException $e) {
    http_response_code(500);
    echo $e->getMessage();
}
