<?php

define('VALID_REQUEST_IPS', ['127.0.0.1']);

function getPDO() : \PDO
{
    $dsn = 'mysql:host=localhost;dbname=DATABASE_NAME;charset=utf8mb4';
    $username = 'DATABASE_USER';
    $password = 'DATABASE_PASS';

    return new \PDO($dsn, $username, $password);
}

function getCrawlerName(string $rawCrawlerIdentifier) : string
{
    return str_replace(
        ['RAW_DESCRIPTION_1', 'RAW_DESCRIPTION_2'],
        ['PUBLIC_NAME_1', 'PUBLIC_NAME_2'], 
        $rawCrawlerIdentifier
    );
}

/*

Datenbank-Tabellenstrukturen:

-- order_books
-- book_time auf zwei Nachkommastellen genau, genauer kommen sowieso keine Daten rein
-- ENUM für exchange_name und source_name spart erheblich Speicherplatz
CREATE TABLE `order_books` (
  `book_time` datetime(2) NOT NULL COMMENT 'Zeitstempel der Abfrage, Zeitzone UTC (!)',
  `exchange_name` enum('bitfinex_usd','bitfinex_eur','bitstamp_usd','bitstamp_eur','coinbase_usd','coinbase_eur','kraken_usd','kraken_eur') NOT NULL,
  `source_host` enum('HOST_1','HOST_2') NOT NULL,
  `type` enum('bid','ask') NOT NULL,
  `price` decimal(10,4) NOT NULL COMMENT 'Preis typischerweise in xxxx.yy, aber zur Sicherheit etwas Puffer: xxxxxx.yyyy',
  `amount` decimal(16,8) NOT NULL COMMENT 'Bitcoins bis zu 1 sat (1/8)',
  KEY `exchange_name` (`exchange_name`),
  KEY `book_time` (`book_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPRESSED COMMENT='Rohdaten der abgefragten Orderbücher';

-- order_books_aggregate
-- Auf eine Sekunde aggregierte Bestwerte für Geld- und Briefkurse jeder Börse für EUR+USD
CREATE TABLE `order_books_aggregate` (
  `book_time` datetime NOT NULL COMMENT 'Zeitstempel der Abfrage, Zeitzone UTC (!)',
  `exchange_name` enum('bitfinex_usd','bitfinex_eur','bitstamp_usd','bitstamp_eur','coinbase_usd','coinbase_eur','kraken_usd','kraken_eur') NOT NULL,
  `bid` decimal(10,4) NOT NULL COMMENT 'Preis typischerweise in xxxx.yy, aber zur Sicherheit etwas Puffer: xxxxxx.yyyy',
  `bid_amount` decimal(16,8) NOT NULL COMMENT 'Bitcoins bis zu 1 sat (1/8)',
  `ask` decimal(10,4) NOT NULL COMMENT 'Preis typischerweise in xxxx.yy, aber zur Sicherheit etwas Puffer: xxxxxx.yyyy',
  `ask_amount` decimal(16,8) NOT NULL COMMENT 'Bitcoins bis zu 1 sat (1/8)',
  KEY `exchange_name` (`exchange_name`),
  KEY `book_time` (`book_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPRESSED COMMENT='Jeweils beste Bid- und Ask-Orders und deren Mengen';

*/
