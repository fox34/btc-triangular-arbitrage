# Coinbase Pro Tickdaten

## Datenbeschreibung

> List the latest trades for a product.  
> The trade side indicates the maker order side. The maker order is the order that was open on the order book.  
> **buy** side (0) indicates a down-tick because the maker was a buy order and their order was removed.  
> Conversely, **sell** side (1) indicates an up-tick.
>@ https://docs.pro.coinbase.com/#get-trades


## API

- Abfrage folgender URLs:
    - https://api.pro.coinbase.com/products/BTC-USD/trades
    - https://api.pro.coinbase.com/products/BTC-EUR/trades
    - https://api.pro.coinbase.com/products/BTC-GBP/trades
- Aktualisierung erfolgt alle zwei Minuten

## Enthaltener Zeitraum

- BTCUSD enthält Daten von 31.12.2017, 23:59:55.819 (UTC) bis heute
- BTCEUR enthält Daten von 31.12.2017, 23:59:50.700 (UTC) bis heute
- BTCGBP enthält Daten von 31.12.2017, 23:59:50.373 (UTC) bis heute

## Dateistruktur
- Vorgangs-ID
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC
- Preis in USD/EUR/GBP
- Art (Kauf = 0 / Verkauf = 1)

---
    ID,Time,Amount,Price,Type
    12309900,2016-12-31T09:34:01.560000+00:00,0.08313000,964.09000000,1
    12309901,2016-12-31T09:34:31.840000+00:00,0.01225000,964.09000000,1
    ...
    12314352,2016-12-31T20:40:31.120000+00:00,0.02500000,966.03000000,0
    12314353,2016-12-31T20:40:31.340000+00:00,0.01000000,966.03000000,0
---
