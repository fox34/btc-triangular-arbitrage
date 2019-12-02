# Auftragsbücher Bitstamp

Auftragsbücher mit den Top-10 Bids und Asks im Abstand von fünf Sekunden.
Mehrere Aufträge mit dem selben Preislimit werden automatisch (durch die Börsen) aggregiert.
Diese Rohdaten enthalten ggf. Duplikate, da zur Sicherheit mehrere unabhängige Crawler zum Einsatz kommen.
Eine Bereinigung der Daten vor Verwendung ist daher unerlässlich.
Die Quelldaten werden zunächst in einer Datenbank zwischengespeichert und nach zwei Tagen
in die .csv.gz geschrieben.

In seltenen Fällen werden die Daten erst nach mehr als einer Sekunde zurückgegeben.
Durch den parallelen Einsatz mehrerer Crawler werden Lücken zwar weitestgehend vermieden,
sind jedoch prinzipbedingt nicht vollständig auszuschließen. Dies ist bei einer
Analyse der Daten zu berücksichtigen, zu prüfen und gegebenenfalls zu erwähnen.

## API
- Referenz: https://www.bitstamp.net/api/
- Abfrage folgender URLs:
    - https://www.bitstamp.net/api/v2/order_book/btcusd
    - https://www.bitstamp.net/api/v2/order_book/btceur
- Aktualisierung alle volle fünf Sekunden


## Dateistruktur
Sortiert nach Datum + Uhrzeit.
Dann zunächst Bid (Geldkurs) nach Preis absteigend, danach Ask (Briefkurs) nach Preis aufsteigend.
Jeweils die besten zehn Geld- und Briefkurse je Abfrageintervall.

- Abfrage-Zeit mit einer Genauigkeit von 10 Millisekunden ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Datenquelle (MAIN_CRAWLER, BACKUP_CRAWLER_1, ..., BACKUP_CRAWLER_n)
- Art (Geld-/Briefkurs)
- Gesetztes Limit in USD bzw. EUR
- Auftragsgröße in BTC (bis auf 1 satoshi = 1/8 genau)

---
    TimeUTC,Source,Type,Price,Amount
    2019-11-10 10:12:50.05,MAIN_CRAWLER,bid,8830.2400,0.12000000
    2019-11-10 10:12:50.05,MAIN_CRAWLER,bid,8827.0000,5.65720000
    ...
    2019-11-10 10:14:40.25,BACKUP_CRAWLER_2,bid,8831.9600,0.04621000
    2019-11-10 10:14:40.25,BACKUP_CRAWLER_2,bid,8830.0900,0.12000000
    ...
---
