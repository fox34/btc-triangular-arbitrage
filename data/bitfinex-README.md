# Bitfinex Tickdaten und 60s-OHLC-Daten

## Tickdaten

> The trades endpoint allows the retrieval of past public trades and includes
> details such as price, size, and time.
>@ https://docs.bitfinex.com/reference#rest-public-trades


### API

- Abfrage folgender URL: https://api-pub.bitfinex.com/v2/trades/tBTCUSD/hist
- Aktualisierung erfolgt alle 2 Minuten

### Dateistruktur
- Vorgangs-ID
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC ("How much was bought (positive) or sold (negative).")
- Preis in USD

---
    ID,Time,Amount,Price
    25291508,2017-01-01T00:00:12.000000+00:00,-1.65,966.61
    25291510,2017-01-01T00:01:04.000000+00:00,-0.0125,966.62
    [...]
    27202171,2017-03-16T07:45:37.000000+00:00,0.84679416,1230
    27202176,2017-03-16T07:45:48.000000+00:00,-0.63372261,1229.9
---




## 60s OHLC (Candles)

> The Candles endpoint provides chart candles for a specified time frame and period. 
>@ https://docs.bitfinex.com/reference#rest-public-candles

Höchste Auflösung ist 1min.

### API

- Abfrage folgender URL: https://api-pub.bitfinex.com/v2/candles/trade:1m:tBTCUSD/hist
- Aktualisierung erfolgt alle 6 Stunden

### Dateistruktur
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Eröffnungskurs in USD
- Schlusskurs in USD
- Höchstkurs in USD
- Tiefstkurs in USD
- Gehandeltes Volumen in BTC

---
    Time,Open,Close,High,Low,Volume
    2016-10-31T23:00:00.000000+00:00,701.98,701.98,701.98,701.98,6.95835475
    2016-10-31T23:03:00.000000+00:00,702.13,702.67,702.67,702.13,0.26859286
    [...]
    2019-10-29T13:08:00.000000+00:00,9437.9,9441.2,9441.2,9437.9,0.36935408
    2019-10-29T13:09:00.000000+00:00,9441.2,9441.11196704,9441.2,9441.10955635,0.05011973
---
