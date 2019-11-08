# Auftragsbücher für verschiedene Börsen

## Datenbeschreibung

Abfrage erfolgt in kurzen Abständen durch einen in python geschriebenen Crawler.
Nach je fünf Minuten werden die Daten aggregiert in der zugehörigen .csv gespeichert.
Mehrere Aufträge mit dem selben Preislimit werden automatisch (durch die Börsen) aggregiert.


### Bitstamp

- Referenz: https://www.bitstamp.net/api/
- Abfrage folgender URLs:
    - https://www.bitstamp.net/api/v2/order_book/btcusd
    - https://www.bitstamp.net/api/v2/order_book/btceur
- Abfrageintervall: 5 Sekunden

### Bitfinex

- Referenz: https://docs.bitfinex.com/reference#rest-public-books
- Abfrage folgender URLs:
    - https://api-pub.bitfinex.com/v2/book/tBTCUSD/P0?len=25
    - https://api-pub.bitfinex.com/v2/book/tBTCEUR/P0?len=25
- Abfrageintervall: 5 Sekunden

### Coinbase

- Referenz: https://docs.pro.coinbase.com/#get-product-order-book
- Abfrage folgender URLs:
    - https://api.pro.coinbase.com/products/BTC-USD/book?level=2
    - https://api.pro.coinbase.com/products/BTC-EUR/book?level=2
- Abfrageintervall: 5 Sekunden


## Dateistruktur (für alle Börsen identisch)

Reihenfolge: Zunächst Bid (Geldkurs) nach Preis absteigend, danach Ask (Briefkurs) nach Preis aufsteigend.
Jeweils die besten zehn Geld- und Briefkurse je Abfrageintervall.

- Unix-Timestamp in Sekunden
- Ordertyp (Bid oder Ask)
- Preislimit
- Menge zu diesem Preis

---
    Unixtime,Type,Price,Amount
    1573138289,Bid,9222.00,1.75760247
    1573138289,Bid,9220.59,0.20000000
    [...]
    1573138289,Bid,9214.17,2.00000000
    1573138289,Bid,9213.50,3.52543370
    1573138289,Ask,9228.31,0.88753094
    1573138289,Ask,9228.66,0.01200000
    [...]
    1573138289,Ask,9231.46,0.00436768
    1573138289,Ask,9231.47,0.03000000
---
