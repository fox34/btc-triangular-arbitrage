# Auftragsbücher für verschiedene Börsen

Auftragsbücher mit den Top-10 Bids und Asks wichtiger Bitcoin-Börsen im Abstand von fünf Sekunden.
Mehrere Aufträge mit dem selben Preislimit werden automatisch (durch die Börsen) aggregiert.
Diese Rohdaten enthalten ggf. Duplikate, da zur Sicherheit mehrere unabhängige Crawler zum Einsatz kommen.
Eine Bereinigung der Daten vor Verwendung ist daher unerlässlich.
Die Quelldaten werden zunächst in einer Datenbank zwischengespeichert und nach zwei Tagen
in die .csv.gz geschrieben.

Manche Börsen liefern die Daten erst nach mehr als einer Sekunde zurück.
Durch den parallelen Einsatz mehrerer Crawler werden Lücken zwar weitestgehend vermieden,
sind jedoch prinzipbedingt nicht vollständig auszuschließen. Dies ist bei einer
Analyse der Daten zu berücksichtigen, zu prüfen und gegebenenfalls zu erwähnen.

### Bitstamp
- Referenz: https://www.bitstamp.net/api/
- Abfrage folgender URLs:
    - https://www.bitstamp.net/api/v2/order_book/btcusd
    - https://www.bitstamp.net/api/v2/order_book/btceur
- Aktualisierung alle volle fünf Sekunden

### Bitfinex
- Referenz: https://docs.bitfinex.com/reference#rest-public-books
- Abfrage folgender URLs:
    - https://api-pub.bitfinex.com/v2/book/tBTCUSD/P0?len=25
    - https://api-pub.bitfinex.com/v2/book/tBTCEUR/P0?len=25
- Aktualisierung alle volle fünf Sekunden

### Coinbase
- Referenz: https://docs.pro.coinbase.com/#get-product-order-book
- Abfrage folgender URLs:
    - https://api.pro.coinbase.com/products/BTC-USD/book?level=2
    - https://api.pro.coinbase.com/products/BTC-EUR/book?level=2
- Aktualisierung alle volle fünf Sekunden


## Dateistruktur
Sortiert nach Datum + Uhrzeit.
Dann zunächst Bid (Geldkurs) nach Preis absteigend, danach Ask (Briefkurs) nach Preis aufsteigend.
Jeweils die besten zehn Geld- und Briefkurse je Abfrageintervall.

- Abfrage-Zeit mit einer Genauigkeit von 10 Millisekunden ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Börse (bitstamp_usd/eur, bitfinex_usd/eur, coinbase_usd/eur)
- Datenquelle (MAIN_CRAWLER, BACKUP_CRAWLER_1, ..., BACKUP_CRAWLER_n)
- Art (Geld-/Briefkurs)
- Gesetztes Limit in USD bzw. EUR
- Auftragsgröße in BTC (bis auf 1 satoshi = 1/8 genau)

---
    TimeUTC,Exchange,Source,Type,Price,Amount
    2019-11-10 10:12:50.05,bitstamp_usd,MAIN_CRAWLER,bid,8830.2400,0.12000000
    2019-11-10 10:12:50.05,bitstamp_usd,MAIN_CRAWLER,bid,8827.0000,5.65720000
    ...
    2019-11-10 10:12:50.05,bitstamp_usd,MAIN_CRAWLER,ask,8841.0500,6.00000000
    2019-11-10 10:12:50.05,bitstamp_usd,MAIN_CRAWLER,ask,8841.0600,1.00000000
    2019-11-10 10:12:50.05,bitstamp_eur,MAIN_CRAWLER,bid,8010.6000,2.00000000
    2019-11-10 10:12:50.05,bitstamp_eur,MAIN_CRAWLER,bid,8010.5900,0.35000000
    ...
    2019-11-10 10:14:40.20,coinbase_usd,MAIN_CRAWLER,ask,8832.9300,2.00000000
    2019-11-10 10:14:40.20,coinbase_usd,MAIN_CRAWLER,ask,8833.3900,0.06000000
    2019-11-10 10:14:40.25,bitstamp_usd,BACKUP_CRAWLER_2,bid,8831.9600,0.04621000
    2019-11-10 10:14:40.25,bitstamp_usd,BACKUP_CRAWLER_2,bid,8830.0900,0.12000000
    ...
---
