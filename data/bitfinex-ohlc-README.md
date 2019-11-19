# Bitfinex 60s-OHLC-Daten

> The Candles endpoint provides chart candles for a specified time frame and period. 
>@ https://docs.bitfinex.com/reference#rest-public-candles

## API

- Abfrage folgender URL: https://api-pub.bitfinex.com/v2/candles/trade:1m:tBTCUSD/hist
- Aktualisierung erfolgt alle 6 Stunden

## Dateistruktur
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
