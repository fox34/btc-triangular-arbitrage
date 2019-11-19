# Bitcoin Composite Price Index (BCX)

> The Bitcoin.com Composite Price Index (BCX) is a daily historical price index that
> tracks the value of Bitcoin in United States Dollars.  
> [...]  
> The BCX is a composite of multiple Bitcoin indices, providing a robust measurement
> of Bitcoin's value. Downtime or API changes on any one exchange or constituent index
> will not drastically alter the quality of the BCX.
>@ https://index.bitcoin.com

## API
- Nutzung folgender Adresse: https://index-api.bitcoin.com/api/v0/history?unix=1
- Aktualisierung erfolgt täglich um 08:10

## Dateistruktur

- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Tagesschlusskurs in USD

---
    Time,Close
    2010-07-18T00:00:00.000000+00:00,0.05
    2010-07-19T00:00:00.000000+00:00,0.08
    [...]
    2019-10-27T00:00:00.000000+00:00,9264.74
    2019-10-28T00:00:00.000000+00:00,9564.27
---
