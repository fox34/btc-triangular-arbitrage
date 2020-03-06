# Kraken Tickdaten

## Datenbeschreibung

> Get recent trades  
> Result: array of pair name and recent trade data
>@ https://www.kraken.com/features/api#get-recent-trades


## API

- Abfrage folgender URLs:
    - https://api.kraken.com/0/public/Trades?pair=xbtusd
    - https://api.kraken.com/0/public/Trades?pair=xbteur
    - https://api.kraken.com/0/public/Trades?pair=xbtgbp
    - https://api.kraken.com/0/public/Trades?pair=xbtjpy
    - https://api.kraken.com/0/public/Trades?pair=xbtcad
    - https://api.kraken.com/0/public/Trades?pair=xbtchf
- EUR wird alle 20 Minuten aktualisiert
- Alle anderen Handelspaare werden alle 30 Minuten aktualisiert

## Enthaltener Zeitraum

- BTCUSD enthält Daten von 06.10.2013, 21:34:15 (UTC) bis heute
- BTCEUR enthält Daten von 10.09.2013, 23:47:11 (UTC) bis heute
- BTCGBP enthält Daten von 06.11.2014, 16:13:43 (UTC) bis heute
- BTCJPY enthält Daten von 05.11.2014, 22:21:30 (UTC) bis heute
- BTCCAD enthält Daten von 29.06.2015, 03:27:41 (UTC) bis heute
- BTCCHF enthält Daten von 06.12.2019, 16:33:17 (UTC) bis heute

## Dateistruktur
- Datum ([UTC](https://de.wikipedia.org/wiki/Koordinierte_Weltzeit))
- Gehandelte Menge in BTC
- Preis in USD/EUR/GBP/JPY/CAD/CHF
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
