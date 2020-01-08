# Bitstamp Tickdaten

# Offizielle Bitstamp-API

> Returns data for the requested currency pair.  
> [...]  
> Supported values for currency_pair: btcusd, btceur, [...]
>
> Response (JSON) - descending list of transactions. Every transaction dictionary contains:  
> - date: Unix timestamp date and time.
> - tid: Transaction ID.
> - price: BTC price.
> - amount: BTC amount.
> - type: 0 (buy) or 1 (sell).
>
>@ https://www.bitstamp.net/api/#transactions


## API

- Abfrage folgender URLs:
    - https://www.bitstamp.net/api/v2/transactions/btcusd/?time=day
    - https://www.bitstamp.net/api/v2/transactions/btceur/?time=day
- Aktualisierung erfolgt alle 7 Stunden

## Enthaltener Zeitraum

- BTCUSD enthält Daten von 01.09.2019, 00:00:00 (UTC) bis heute
- BTCEUR enthält Daten von 01.09.2019, 00:00:00 (UTC) bis heute

## Dateistruktur
- Vorgangs-ID
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC
- Preis in USD bzw. EUR
- Art (Kauf = 0 / Verkauf = 1)

---
    ID,Time,Amount,Price,Type
    96551989,2019-09-01T00:00:07+00:00,0.00927907,8755.71,1
    96551990,2019-09-01T00:00:09+00:00,0.01970993,8750.82,1
    [...]
    101212130,2019-11-29T14:25:24+00:00,0.09000000,7754.09,0
    101212131,2019-11-29T14:25:24+00:00,0.00646000,7754.09,0
---


# Bitstamp-Tickdaten via Bitcoincharts

> Bitcoincharts provides a simple API to most of its data.  
> [...]  
> **Historic Trade Data**  
> Trade data is available as CSV, delayed by approx. 15 minutes. It will return the 2000 most recent trades.  
>@ https://bitcoincharts.com/about/markets-api/

Diese Schnittstelle wird für länger zurückliegende Tickdaten genutzt. Sie bietet im Unterschied zur offiziellen API
keine Unterscheidung von Kauf- und Verkaufsgeschäften.


## API
- Nutzung folgender Adressen:
    - http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstampUSD
    - http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstampEUR
- Aktualisierung erfolgt einmal pro Stunde

## Enthaltener Zeitraum

- BTCUSD enthält Daten von 13.09.2011, 13:53:36 (UTC) bis heute
- BTCEUR enthält Daten von 05.12.2017, 11:43:49 (UTC) bis heute

## Dateistruktur

- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Preis in USD bzw. EUR
- Menge in BTC

---
    Time,Price,Amount
    2017-12-05T11:43:49.000000+00:00,9803.92,0.137166
    2017-12-05T11:44:01.000000+00:00,9842.66,0.13538904
    [...]
    2019-10-29T14:15:34.000000+00:00,8413.770000000000,0.000006100000
    2019-10-29T14:15:36.000000+00:00,8406.150000000000,0.012340930000
---
