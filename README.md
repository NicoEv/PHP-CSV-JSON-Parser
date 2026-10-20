# PHP CSV TO JSON PARSER
Liest eine Artikel-CSV, prüft und bereinigt die Zeilen und schreibt sie als JSON.

## Requirements
- PHP 8.1 oder neuer

## Nutzung
Das Programm wird ausschließlich innerhalb der Kommandozeile (CLI) verwendet.
Dazu wird folgender Befehl verwendet:
```bash
php import.php (CSV Dateipfad) (Ausgabepfad)
```
### Ergebnis
- `ausgabe.json` gültige Artikel, sortiert nach SKU
- `ausgabe.fehler.txt` Fehlerprotokoll als Textdatei, eine Zeile pro fehlerhaftem Datensatz mit Zeilennummer, SKU und Grund
- Konsole: Zusammenfassung (gelesen / importiert / übersprungen)

## Aufbau
 
Eine Klasse, `Parser`. `parse()` steuert nur, die Schritte stehen in eigenen Methoden:
`readRows()` (einlesen), `validateRow()`, `parsePrice()`, `parseStock()` (prüfen) und `writeJson()`, `writeErrorLog()` (schreiben).
Eine fehlerhafte Zeile wird protokolliert und übersprungen, das Programm läuft dabei gezielt weiter.

## Bewusste Entscheidungen
- Die CSV wird zeilenweise mithilfe eines Generators eingelesen damit nicht die gesamte Datei im speicher landet. Dies ist für besonders große Importe nützlich um das Programm weiterhin performant zu halten.
- Im Price parser werden neben Euro Angaben auch Dollar Angaben gehandhabt. Weitere Währungen würden den zeitlichen Rahmen sprengen.
- Die gesamte Anwendung funktioniert über eine einzelne Klasse damit es übersichtlich und weiterhin gut wartbar bleibt.
  - Ausschließlich die verschiedenen Funktionen wurden in einzelne Methoden aufgeteilt.

## Mögliche Erweiterungen bei verbleibender Zeit
 
- Tests (PHPUnit / PHPStan), vor allem für die Preisauswertung mit Grenzfällen.
- Preisvalidierung weiter ausbauen besonders für hohe Zahlen sowie andere Währungen.
- Interface/UI für eine einfachere Bedienung sowie UX.
    - Simple Weboberfläche, in welcher man die Datei hochladen kann und dann entsprechend ausgewertet wird. In der aktuellen Anwendung sind gewisse technische Kenntnisse vonnöten um es nutzen zu können.

## KI Hilfsmittel
- Für Abfragen mithilfe von Regex Ausdrücken wurde hier KI verwendet.
    - z.B. 
        ```
        if (!preg_match('/^\d+(\.\d+)?$/', $text)) ...
        ```