# Bitcoincharts API - Tickdaten von Bitstamp

## Datenbeschreibung
https://bitcoincharts.com/about/markets-api/

## API
- http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstampUSD
- http://api.bitcoincharts.com/v1/trades.csv?symbol=bitstampEUR
- Abfrage alle 15-16min


## Dateistruktur
- Datum
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
