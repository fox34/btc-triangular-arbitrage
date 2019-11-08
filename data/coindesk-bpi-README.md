# Coindesk Bitcoin Price Index

Referenz: https://www.coindesk.com/price/bitcoin-price-index

Launched in September 2013, the CoinDesk Bitcoin Price Index (XBP) represents an average
of bitcoin prices across leading global exchanges that meet criteria specified by the XBP.
It is intended to serve as a standard retail price reference for industry participants and
accounting professionals.

## 60s Close
### Beschreibung
- Abfrage folgender URL: https://api.coindesk.com/charts/data?data=close&exchanges=bpi&index=USD&dev=1
- Abfrage täglich 08:07

### Dateistruktur
- Datum
- BPI-Wert

---
    Time,Close
    2010-07-18T00:00:00.000000+00:00,0.05
    2010-07-18T00:01:00.000000+00:00,0.05
    [...]
    2019-10-27T23:58:00.000000+00:00,9545.54
    2019-10-27T23:59:00.000000+00:00,9562.93
---


## 15min OHLC
### Beschreibung
- Abfrage folgender URL: https://api.coindesk.com/charts/data?data=ohlc&exchanges=bpi&index=USD&dev=1
- Abfrage täglich 08:05

### Dateistruktur
- Datum
- Open
- Höchstkurs
- Tiefstkurs
- Schlusskurs

---
    Time,Open,High,Low,Close
    2010-07-19T00:00:00.000000+00:00,0.09,0.09,0.09,0.09
    2010-07-19T00:15:00.000000+00:00,0.09,0.09,0.09,0.09
    [...]
    2019-10-28T23:15:00.000000+00:00,9478.6,9497.29,9474.33,9452.45
    2019-10-28T23:30:00.000000+00:00,9452.45,9465.37,9219.17,9268.22
---