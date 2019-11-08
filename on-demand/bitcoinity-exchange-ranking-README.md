# Bitcoinity Börsenranking

## Beschreibung

Abfrage des Börsenrankings von Bitcoinity.
Dieses Ranking kann nicht korrekt exportiert oder über eine API abgerufen werden.

Zugriff ist über folgende Vorgehensweise möglich:
- http://data.bitcoinity.org/markets/rank/2y?c=e&t=ae
- Auswahl des gewünschten Zeithorizontes + Auflösung (2y + monatlich scheint sinnvoll)
- Currency: "all currencies"
- Comparison: "compare exchanges" (voreingestellt)

[Dieses Skript](bitcoinity-exchange-ranking.csv) imitiert die Browser-Abfrage zur Darstellung des resultierenden Graphen.

**Achtung!** Die Daten nicht mehr aktiver Börsen (z.B. hitbtc) werden für die dort dargestellten
Gesamtwerte ("Total in this period") interpoliert. Damit ist die Gesamtstatistik i.d.R. etwas verzerrt.

## Dateistruktur
- Datum
- Abkürzung gemäß [Abkürzungsliste](https://research.noecho.de/btc-triangular-arbitrage/data/bitcoinity-exchanges-README.md)
- Berechneter Rang gemäß http://bitcoinity.org/markets/rank_explanation

---
    Time,Exchange,Rank
    2017-10-01,bit-x,1775.50126616
    2017-11-01,bit-x,1731.47658036
    [...]
    2019-08-01,bit-x,1116.08243867
    2019-09-01,bit-x,953.651621688
    2017-10-01,bitfinex,7431.18756199
    2017-11-01,bitfinex,8820.90147429
    [...]
    2019-08-01,others,1974.07553052
    2019-09-01,others,1691.46550844
---
