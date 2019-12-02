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

// JSON parsen
try {
    $dataset = json_decode($_POST['raw_data'], false, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException $e) {
    http_response_code(400);
    infoLog('Could not parse provided data as JSON.');
    exit;
}

infoLog(
    'Received ' . count($dataset) . ' datasets for ' . $exchange . ' ' .
    'from host ' . $sourceHost . ' (' . $_SERVER['REMOTE_ADDR'] . ')'
);

// Umweg über Datenbank: Bessere Suche und Aggregation, außerdem zeitlich korrekte Reihenfolge
// der Daten auch bei verschiedenen Datenquellen (Backup-Crawler)
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
        //echo 'Bid @ ' . $bid[0] . PHP_EOL;
        
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
        //echo 'Ask @ ' . $ask[0] . PHP_EOL;
        
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
    
    //echo 'Processed: ' . $time . PHP_EOL . PHP_EOL;
}

$stmt = $pdo->prepare($query);

try {
    $result = $stmt->execute($pdo_data);
    if ($result === false) {
        throw new \Exception($stmt->errorInfo()[2] ?? 'Unknown SQL error');
    }
} catch (\PDOException | \Exception $e) {
    http_response_code(500);
    infoLog($e->getMessage());
}
