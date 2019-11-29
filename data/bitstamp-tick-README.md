# Bitstamp Tickdaten

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

## Dateistruktur
- Vorgangs-ID
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC
- Preis in USD bzw. EUR
- Art (Kauf = 0 / Verkauf = 1)

---
    ID,Time,Amount,Price,Type
    101164279,2019-11-28T14:25:40+00:00,5.05122665,7463.00,0
    101164280,2019-11-28T14:25:41+00:00,3.10274958,7463.00,0
    [...]
    101212130,2019-11-29T14:25:24+00:00,0.09000000,7754.09,0
    101212131,2019-11-29T14:25:24+00:00,0.00646000,7754.09,0
---
