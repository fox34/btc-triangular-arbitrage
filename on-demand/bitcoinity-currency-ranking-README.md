# Bitcoinity Währungsranking

## Beschreibung

Abfrage des Währungsrankings von Bitcoinity.
Dieses Ranking kann nicht korrekt exportiert oder über eine API abgerufen werden.

Zugriff ist über folgende Vorgehensweise möglich:
- http://data.bitcoinity.org/markets/rank/2y?c=c&t=ae
- Auswahl des gewünschten Zeithorizontes + Auflösung (2y + monatlich scheint sinnvoll)
- Currency: "all currencies"
- Comparison: "compare currencies"

[Dieses Skript](bitcoinity-currency-ranking.csv) imitiert die Browser-Abfrage zur Darstellung des resultierenden Graphen.

## Dateistruktur
- Datum
- Währung nach ISO 4217
- Berechneter Rang gemäß http://bitcoinity.org/markets/rank_explanation

---
    Time,Currency,Rank
    2017-10-01,AUD,125.060663514
    2017-11-01,AUD,120.809281253
    [...]
    2019-08-01,AUD,107.973031171
    2019-09-01,AUD,99.9472519225
    2017-10-01,CAD,220.23305164
    2017-11-01,CAD,176.623719302
    [...]
    2019-08-01,others,284.919695314
    2019-09-01,others,283.254296442
---
