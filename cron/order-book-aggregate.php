<?php

require_once '_config.php';
require_once '_include.php';

function errorLog(string $msg) : void
{
    echo $msg . PHP_EOL;
    file_put_contents(__DIR__ . '/logs/order-book-aggregate.log', date('r ') . $msg . PHP_EOL, FILE_APPEND);
}

// getPDO() defined in config.php
/** @var \PDO $pdo */
$pdo = getPDO();

$lastInfoStmt = $pdo->query('SELECT book_time FROM order_books_aggregate ORDER BY book_time DESC LIMIT 1');
if ($lastInfoStmt->rowCount() > 0) {
    
    $lastBook = $lastInfoStmt->fetchColumn(0);
    $lastBook = \DateTime::createFromFormat('Y-m-d H:i:s', $lastBook);
    echo 'Last dataset: ' . $lastBook->format('Y-m-d H:i:s') . PHP_EOL;
    
    if ($lastBook > (new \DateTime())->sub(new DateInterval('PT59M'))) {
        die('Last dataset was less than an hour ago, skipping.');
    }
    
    $lastBook->add(new \DateInterval('PT5S'));
    
} else {
    
    // Leere Datenbank: Starte mit erstem Datensatz
    $lastInfoStmt = $pdo->query('SELECT book_time FROM order_books ORDER BY book_time ASC LIMIT 1');
    $lastBook = $lastInfoStmt->fetchColumn(0);
    $lastBook = \DateTime::createFromFormat('Y-m-d H:i:s.u', $lastBook);
    echo 'Starting new with dataset: ' . $lastBook->format('Y-m-d H:i:s') . PHP_EOL;
    $lastBook->sub(new \DateInterval('PT1S'));
    
}

// Lade neue Bids/Asks
$stmt = $pdo->prepare('
    SELECT CAST(FLOOR(book_time) AS datetime(0)) AS book_time_s, exchange_name, type, price, amount
    FROM order_books
    WHERE book_time >= ? AND book_time < ?
    GROUP BY book_time_s, exchange_name, type
    ORDER BY book_time_s ASC, exchange_name ASC, type ASC
');

// 1h30min je Cron-Lauf (stündlich) abdecken
$bookTimeLimit = clone $lastBook;
$bookTimeLimit->add(new \DateInterval('PT1H30M'));

echo 'Processing until ' . $bookTimeLimit->format('Y-m-d H:i:s') . PHP_EOL;

try {
    
    $stmt->execute([$lastBook->format('Y-m-d H:i:s'), $bookTimeLimit->format('Y-m-d H:i:s')]);
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    
} catch (\PDOException $e) {
    http_response_code(500);
    errorLog($e->getMessage());
    exit;
}

$stmt = $pdo->prepare('
    INSERT INTO order_books_aggregate
        (book_time, exchange_name, bid, bid_amount, ask, ask_amount)
    VALUES
        (?, ?, ?, ?, ?, ?)
');

//print_r($result);

$data = [];
foreach ($result as $order) {
    if (empty($data)) {
        $data = ['book_time' => $order->book_time_s, 'exchange_name' => $order->exchange_name];
    }
    
    if ($order->type === 'bid') {
        
        if (isset($data['bid'])) {
            http_response_code(500);
            errorLog('Duplicate data for BID!');
            exit;
        }
        
        $data['bid'] = $order->price;
        $data['bid_amount'] = $order->amount;
        
    } elseif ($order->type === 'ask') {
    
        if (isset($data['ask'])) {
            http_response_code(500);
            errorLog('Duplicate data for ASK!');
            exit;
        }
        
        $data['ask'] = $order->price;
        $data['ask_amount'] = $order->amount;
        
    } else {
        http_response_code(500);
        errorLog('Unknown order type: ' . $order->type);
        exit;
    }
    
    if (count($data) === 6) {
    
        //echo 'Inserting: ' . implode(array_values($data), ',') . PHP_EOL;
        try {
            $stmt->execute([
                $data['book_time'],
                $data['exchange_name'],
                $data['bid'],
                $data['bid_amount'],
                $data['ask'],
                $data['ask_amount']
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            errorLog('Error inserting: ' . $e->getMessage());
            exit;
        }
        
        $data = [];
    }
}



// Überprüfe neu eingesetzte Daten auf Lücken > 5 Sekunden
$stmt = $pdo->prepare('
    SELECT book_time, exchange_name
    FROM order_books_aggregate
    WHERE book_time >= ? AND book_time < ?
    ORDER BY exchange_name ASC, book_time ASC
');

try {
    
    $stmt->execute([$lastBook->format('Y-m-d H:i:s'), $bookTimeLimit->format('Y-m-d H:i:s')]);
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    
} catch (\PDOException $e) {
    http_response_code(500);
    errorLog($e->getMessage());
    exit;
}

$lastDatasetTime = null;
$lastExchange = null;
foreach ($result as $order) {
    $orderTime = \DateTime::createFromFormat('Y-m-d H:i:s', $order->book_time);
    
    if ($lastExchange !== $order->exchange_name) {
        
        $lastExchange = $order->exchange_name;
        
    } else {
        
        // Nächster Datensatz ist maximal 5s von letztem entfernt
        // Das bedeutet, dass auch kleinere Differenzen möglich sind
        // Diese sind ggf. in R zu bereinigen!
        if ($orderTime->diff($lastDatasetTime)->s <= 5) {
            //echo 'Verified ' . $lastExchange . ' @ ' . $orderTime->format('Y-m-d H:i:s') . PHP_EOL;
        } else {
            errorLog('*** WARNING *** Missing dataset for ' . $lastExchange . ' between ' . 
                $lastDatasetTime->format('Y-m-d H:i:s') . ' and ' .
                $orderTime->format('Y-m-d H:i:s'));
        }
        
    }
    
    $lastDatasetTime = $orderTime;
}
