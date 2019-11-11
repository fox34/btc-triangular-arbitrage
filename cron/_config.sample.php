<?php

define('VALID_REQUEST_IPS', ['127.0.0.1']);

function getPDO() : \PDO
{
    $dsn = 'mysql:host=localhost;dbname=DATABASE_NAME;charset=utf8mb4';
    $username = 'DATABASE_USER';
    $password = 'DATABASE_PASS';

    return new \PDO($dsn, $username, $password);
}

/*

Datenbank-Tabellenstrukturen:


-- order_books
-- ENUM für exchange_name und source_name spart erheblich Speicherplatz
CREATE TABLE `order_books` (
  `book_time` datetime(2) NOT NULL COMMENT 'Zeitstempel der Abfrage, Zeitzone UTC (!)',
  `exchange_name` enum('bitfinex_usd','bitfinex_eur','bitstamp_usd','bitstamp_eur','coinbase_usd','coinbase_eur') NOT NULL,
  `source_host` enum('drive.noecho.de') NOT NULL,
  `type` enum('bid','ask') NOT NULL,
  `price` decimal(10,4) NOT NULL COMMENT 'Preis typischerweise in xxxx.yy, aber zur Sicherheit etwas Puffer: xxxxxx.yyyy',
  `amount` decimal(16,8) NOT NULL COMMENT 'Bitcoins bis zu 1 sat (1/8)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPRESSED COMMENT='Rohdaten der abgefragten Orderbuecher';

ALTER TABLE `order_books`
  ADD KEY `book_time` (`book_time`),
  ADD KEY `exchange_name` (`exchange_name`);


CREATE TABLE `order_books_aggregate` (
  `book_time` datetime NOT NULL COMMENT 'Zeitstempel der Abfrage, Zeitzone UTC (!)',
  `exchange_name` enum('bitfinex_usd','bitfinex_eur','bitstamp_usd','bitstamp_eur','coinbase_usd','coinbase_eur') NOT NULL,
  `bid` decimal(10,4) NOT NULL COMMENT 'Preis typischerweise in xxxx.yy, aber zur Sicherheit etwas Puffer: xxxxxx.yyyy',
  `bid_amount` decimal(16,8) NOT NULL COMMENT 'Bitcoins bis zu 1 sat (1/8)',
  `ask` decimal(10,4) NOT NULL COMMENT 'Preis typischerweise in xxxx.yy, aber zur Sicherheit etwas Puffer: xxxxxx.yyyy',
  `ask_amount` decimal(16,8) NOT NULL COMMENT 'Bitcoins bis zu 1 sat (1/8)',
  KEY `exchange_name` (`exchange_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPRESSED COMMENT='Jeweils beste Bid- und Ask-Orders und deren Mengen';

*/
