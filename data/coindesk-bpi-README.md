# Coindesk Bitcoin Price Index

> Launched in September 2013, the CoinDesk Bitcoin Price Index (XBP) represents an average
> of bitcoin prices across leading global exchanges that meet criteria specified by the XBP.
> It is intended to serve as a standard retail price reference for industry participants and
> accounting professionals.
>@ https://www.coindesk.com/price/bitcoin-price-index

## 60s Close
### API
- Abfrage folgender URL: https://api.coindesk.com/charts/data?data=close&exchanges=bpi&index=USD&dev=1
- Aktualisierung erfolgt täglich um 08:07
- Aufgrund der Nutzung einer internen API können bei kurzfristigen Änderungen vorübergehend Lücken entstehen.


### Dateistruktur
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Aktueller Wert des BPI in USD

---
    Time,Close
    2010-07-18T00:00:00.000000+00:00,0.05
    2010-07-18T00:01:00.000000+00:00,0.05
    [...]
    2019-10-27T23:58:00.000000+00:00,9545.54
    2019-10-27T23:59:00.000000+00:00,9562.93
---


## 15min OHLC

### API
- Abfrage folgender URL: https://api.coindesk.com/charts/data?data=ohlc&exchanges=bpi&index=USD&dev=1
- Aktualisierung erfolgt täglich um 08:05
- Aufgrund der Nutzung einer internen API können bei kurzfristigen Änderungen vorübergehend Lücken entstehen.


### Dateistruktur
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Eröffnungskurs in USD
- Höchstkurs in USD
- Tiefstkurs in USD
- Schlusskurs in USD

---
    Time,Open,High,Low,Close
    2010-07-19T00:00:00.000000+00:00,0.09,0.09,0.09,0.09
    2010-07-19T00:15:00.000000+00:00,0.09,0.09,0.09,0.09
    [...]
    2019-10-28T23:15:00.000000+00:00,9478.6,9497.29,9474.33,9452.45
    2019-10-28T23:30:00.000000+00:00,9452.45,9465.37,9219.17,9268.22
---