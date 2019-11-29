# Kraken Tickdaten

> Get recent trades  
> Result: array of pair name and recent trade data
>@ https://www.kraken.com/features/api#get-recent-trades


## API

- Abfrage folgender URLs:
    - https://api.kraken.com/0/public/Trades?pair=xbtusd
    - https://api.kraken.com/0/public/Trades?pair=xbteur
- Aktualisierung erfolgt alle 11-12 Minuten

## Dateistruktur
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC
- Preis in USD/EUR
- Art (Kauf = b / Verkauf = s)
- Limit (l = Limitiert, m = Market = Unlimitiert)

---
    Time,Amount,Price,Type,Limit
    2013-10-06T21:34:15.551400+00:00,0.10000000,122.00000,s,l
    2013-10-07T20:50:30.481500+00:00,0.10000000,123.61000,s,l
    [...]
    2014-02-10T21:30:30.337300+00:00,0.02000000,669.79391,b,l
    2014-02-10T21:30:33.599700+00:00,0.10400000,669.79391,b,l
---
