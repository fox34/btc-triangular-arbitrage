<?php

// Regelmäßig SQL-Rohdaten in simple CSV-Daten dumpen um SQL-Server zu entlasten
// Aufruf alle 15min, da mit einem Query maximal ca. 20-25min erfasst werden

require_once '_config.php';
require_once '_include.php';

define('CSV_FILE', DATA_DIR . 'orderbook-all-raw.csv.gz');

// Erzeuge Header
if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
    
    echo 'Starting new CSV.' . PHP_EOL;
    file_put_contents(CSV_FILE, gzencode('TimeUTC,Exchange,Source,Type,Price,Amount' . PHP_EOL));
    
} else {
    
    echo 'Existing CSV: ' . CSV_FILE . PHP_EOL;
    echo 'File size: ' . formatFileSize(filesize(CSV_FILE)) . PHP_EOL;
    echo 'File last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime(CSV_FILE)) . PHP_EOL;
    
    // Letzter Datensatz kann aus gzip-Datei nicht ohne größeren Aufwand eingelesen werden
    // Ist allerdings auch nicht nötig
}

// CSV nicht beschreibbar
if (!is_writeable(CSV_FILE)) {
    die('Could not open target CSV file for writing.');
}

// Frage Daten bis max. vor zwei Tagen ab
// Nicht viel früher, da z.B. Aggregation der Daten den Datensatz benötigt
$queryUntil = new \DateTime();
$queryUntil->sub(new \DateInterval('P2D'));

// getPDO() defined in config.php
/** @var \PDO $pdo */
$pdo = getPDO();


// COUNT(*) dauert lange, nur Schätzung abrufen
$tableStatus = $pdo->query("SHOW TABLE STATUS LIKE 'order_books'");
$tableStatus = $tableStatus->fetch(\PDO::FETCH_OBJ);

echo '~' . number_format($tableStatus->Rows, 0, ',', '.') . ' datasets total in database.' . PHP_EOL;


// Verarbeite bis zu 100.000 Datensätze gleichzeitig
$stmt = $pdo->prepare('
    SELECT *
    FROM order_books
    WHERE book_time < ?
    ORDER BY book_time ASC
    LIMIT 0,100000
');
$cleanupStmt = $pdo->prepare('
    DELETE
    FROM order_books
    WHERE book_time < ?
    ORDER BY book_time ASC
    LIMIT 100000
');

if ($stmt->execute([$queryUntil->format('Y-m-d H:i:s.u')]) === false) {
    infoLog('Error querying database: ' . $stmt->errorInfo()[2]);
    exit;
}

if ($stmt->rowCount() === 0) {
    die('Received dataset is empty.');
}

// Ergebnis zusammenstellen und gemeinsam komprimieren
$result = '';
while ($order = $stmt->fetch(\PDO::FETCH_NUM)) {

    // Datenquelle (Crawler) mit allgemeinverständlicher Bezeichnung ersetzen
    $order[2] = getCrawlerName($order[2]);
    
    $result .= implode(',', $order) . PHP_EOL;
}

$stmt->closeCursor();

if (!empty($result)) {
    $result = gzencode($result);
    infoLog(
        'Collected ' . number_format($stmt->rowCount(), 0, ',', '.') . ' datasets ' .
        'up to ' . $queryUntil->format('Y-m-d H:i:s.u') . '. ' . 
        'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.'
    );
    file_put_contents(CSV_FILE, $result, FILE_APPEND);
}

// Aufräumen
if ($cleanupStmt->execute([$queryUntil->format('Y-m-d H:i:s.u')]) === false) {
    infoLog('Error cleaning up database: ' . $cleanupStmt->errorInfo()[2]);
    exit;
}

$cleanupStmt->closeCursor();
unset($pdo);

infoLog('Cleaned up ' . number_format($cleanupStmt->rowCount(), 0, ',', '.') . ' datasets.');
