<?php

// Regelmäßig SQL-Rohdaten in simple CSV-Daten dumpen um SQL-Server zu entlasten
// Aufruf alle 15min, da mit einem Query maximal ca. 20-25min erfasst werden

require_once '_config.php';
require_once '_include.php';

define('CSV_SCHEME', DATA_DIR . '%s-orderbook-%s.csv.gz');

// Frage Daten bis max. vor zwei Tagen ab
// Nicht viel früher, da z.B. Aggregation der Daten den Datensatz benötigt
$queryUntil = new \DateTime();
$queryUntil->sub(new \DateInterval('P2D'));

// getPDO() defined in config.php
/** @var \PDO $pdo */
$pdo = getPDO();


// Alle verfügbaren Börsen laden
$exchanges = $pdo->query('SELECT exchange_name FROM order_books GROUP BY exchange_name');
$exchanges = $exchanges->fetchAll(\PDO::FETCH_COLUMN, 0);

// COUNT(*) dauert lange, nur Schätzung abrufen
$tableStatus = $pdo->query("SHOW TABLE STATUS LIKE 'order_books'");
$tableStatus = $tableStatus->fetch(\PDO::FETCH_OBJ);

echo '~' . number_format($tableStatus->Rows, 0, ',', '.') . ' datasets total in database.' . PHP_EOL;

foreach ($exchanges as $exchange) {
    
    list($exchange_name, $currency) = explode('_', $exchange);
    
    $csvFile = sprintf(CSV_SCHEME, $exchange_name, $currency);
    echo PHP_EOL;
    
    // Erzeuge Header
    if (!file_exists($csvFile) || filesize($csvFile) === 0) {
    
        echo 'Starting new CSV.' . PHP_EOL;
        file_put_contents($csvFile, gzencode('TimeUTC,Source,Type,Price,Amount' . PHP_EOL));
    
    } else {
    
        echo 'Existing CSV: ' . $csvFile . PHP_EOL;
        echo 'File size: ' . formatFileSize(filesize($csvFile)) . PHP_EOL;
        echo 'File last modified: ' . strftime('%Y-%m-%d %H:%M:%S', filemtime($csvFile)) . PHP_EOL;
    
    }

    // CSV nicht beschreibbar
    if (!is_writeable($csvFile)) {
        echo 'Could not open target CSV file for writing.' . PHP_EOL;
        continue;
    }

    
    // Verarbeite bis zu 15.000 Datensätze gleichzeitig
    // ORDER BY nicht notwendig, Daten sind chronologisch sortiert
    $stmt = $pdo->prepare('
        SELECT *
        FROM order_books
        WHERE book_time < ? AND exchange_name = ?
        LIMIT 0,15000
    ');
    $cleanupStmt = $pdo->prepare('
        DELETE
        FROM order_books
        WHERE book_time < ? AND exchange_name = ?
        LIMIT 15000
    ');

    if ($stmt->execute([$queryUntil->format('Y-m-d H:i:s.u'), $exchange]) === false) {
        infoLog('Error querying database: ' . $stmt->errorInfo()[2]);
        continue;
    }

    if ($stmt->rowCount() === 0) {
        echo 'Received dataset is empty.' . PHP_EOL;
        continue;
    }

    // Ergebnis zusammenstellen und gemeinsam komprimieren
    $result = '';
    while ($order = $stmt->fetch(\PDO::FETCH_NUM)) {

        // Datenquelle (Crawler) mit allgemeinverständlicher Bezeichnung ersetzen
        $order[2] = getCrawlerName($order[2]);
        
        // Börse aus Datensatz entfernen, ist im Dateinamen enthalten
        unset($order[1]);
    
        $result .= implode(',', $order) . PHP_EOL;
    }

    $stmt->closeCursor();

    if (!empty($result)) {
        $result = gzencode($result);
        infoLog(
            'Collected ' . number_format($stmt->rowCount(), 0, ',', '.') . ' datasets for ' . $exchange .
            ', limited to ' . $queryUntil->format('Y-m-d H:i:s.u') . '. ' . 
            'Writing ' . round(strlen($result)/1024) . ' kB gzip to target file.'
        );
        file_put_contents($csvFile, $result, FILE_APPEND);
    }
    
    // Aufräumen
    if ($cleanupStmt->execute([$queryUntil->format('Y-m-d H:i:s.u'), $exchange]) === false) {
        infoLog('Error cleaning up database: ' . $cleanupStmt->errorInfo()[2]);
        continue;
    }

    $cleanupStmt->closeCursor();

    infoLog('Cleaned up ' . number_format($cleanupStmt->rowCount(), 0, ',', '.') . ' datasets.');
}
