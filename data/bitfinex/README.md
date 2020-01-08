# Bitfinex Tickdaten

## Datenbeschreibung

> The trades endpoint allows the retrieval of past public trades and includes
> details such as price, size, and time.
>@ https://docs.bitfinex.com/reference#rest-public-trades


## API

- Abfrage folgender URLs:
    - https://api-pub.bitfinex.com/v2/trades/tBTCUSD/hist
    - https://api-pub.bitfinex.com/v2/trades/tBTCEUR/hist
- Aktualisierung erfolgt stündlich

## Enthaltener Zeitraum

- BTCUSD enthält Daten von 01.01.2018, 00:00:00 (UTC) bis heute
- BTCEUR enthält Daten von 01.01.2018, 00:00:00 (UTC) bis heute

## Dateistruktur
- Vorgangs-ID
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC ("How much was bought (positive) or sold (negative).")
- Preis in USD/EUR

---
    ID,Time,Amount,Price
    25291508,2017-01-01T00:00:12.000000+00:00,-1.65,966.61
    25291510,2017-01-01T00:01:04.000000+00:00,-0.0125,966.62
    [...]
    27202171,2017-03-16T07:45:37.000000+00:00,0.84679416,1230
    27202176,2017-03-16T07:45:48.000000+00:00,-0.63372261,1229.9
---
