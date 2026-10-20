<?php

class Parser {
    // The parser will completely stop if the given columns are missing.
    private const RequiredColumns = ['sku', 'preis'];
    private array $articles = [];
    private int $rowsRead = 0;
    private array $errors = [];

    /**
     * @throws JsonException
     */
    public function parse(string $inputPath, string $outputPath): void
    {
        $this->resetState();    

        foreach ($this->readRows($inputPath) as $lineNumber => $row) {
            $this->rowsRead++;

            try {
                $article = $this->validateRow($row);
                $this->articles[$article['sku']] = $article;
            } catch (InvalidArgumentException $e) {
                $this->errors[] = ['zeile' => $lineNumber, 'sku' => $row['sku'] ?? null, 'grund' => $e->getMessage()];
            }
        }

        $articles = array_values($this->articles);
        usort($articles, static fn (array $a, array $b): int => strcmp($a['sku'], $b['sku']));

        $this->writeJson($articles, $outputPath);
        $this->writeErrorLog(preg_replace('/\.json$/i', '', $outputPath) . '.fehler.txt');

        echo "Gelesen:      {$this->rowsRead} Zeilen\n";
        echo 'Importiert:   ' . count($articles) . " Artikel\n";
        echo 'Übersprungen: ' . count($this->errors) . " Zeilen (siehe Fehlerprotokoll)\n";
    }

    private function readRows(string $path): Generator
    {
        $handle = is_file($path) ? @fopen($path, 'rb') : false;
        if ($handle === false) {
            throw new RuntimeException("Datei nicht lesbar: {$path}");
        }

        $header = fgetcsv($handle, null, ';', '"', '');
        if ($header === false || $header === [null]) {
            throw new RuntimeException('Die Datei hat keine Kopfzeile.');
        }

        $header = array_map(static fn (?string $name): string => strtolower(trim(ltrim((string) $name, "\xEF\xBB\xBF"))), $header);
        foreach (self::RequiredColumns as $column) {
            if (!in_array($column, $header, true)) {
                throw new RuntimeException("Die Spalte '{$column}' fehlt.");
            }
        }

        $lineNumber = 1;
        while (($values = fgetcsv($handle, null, ';', '"', '')) !== false) {
            $lineNumber++;
            if ($values === [null]) {
                continue;
            }
            $values = array_pad(array_slice($values, 0, count($header)), count($header), '');
            yield $lineNumber => array_combine($header, array_map('trim', $values));
        }

        fclose($handle);
    }

    private function validateRow(array $row): array
    {
        if (($row['sku'] ?? '') === '') {
            throw new InvalidArgumentException('SKU fehlt');
        }

        return [
            'sku' => $row['sku'],
            'name' => $row['name'] ?? '',
            'preis' => $this->parsePrice($row['preis'] ?? ''),
            'bestand' => $this->parseStock($row['bestand'] ?? ''),
            'kategorie' => $row['kategorie'] ?? '',
            'ean' => $row['ean'] ?? '',
        ];
    }

    private function parsePrice(string $rawPrice): string
    {
        $text = str_ireplace(['€', '$', 'EUR', ' ', "\u{00A0}"], '', $rawPrice);
        if ($text === '') {
            throw new InvalidArgumentException('Preis fehlt');
        }

        if (str_contains($text, ',')) {
            $text = str_replace(['.', ','], ['', '.'], $text);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $text)) {
            throw new InvalidArgumentException("Preis nicht auswertbar: '{$rawPrice}'");
        }

        return number_format((float) $text, 2, '.', '');
    }

    private function parseStock(string $rawStock): int
    {
        if ($rawStock === '') {
            return 0;
        }
        if (!preg_match('/^\d+$/', $rawStock)) {
            throw new InvalidArgumentException("Bestand ungültig: '{$rawStock}'");
        }

        return (int) $rawStock;
    }

    /**
     * @throws JsonException
     */
    private function writeJson(array $articles, string $path): void
    {
        $json = json_encode($articles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json . "\n") === false) {
            throw new RuntimeException("Ausgabedatei nicht schreibbar: {$path}");
        }
    }

    private function writeErrorLog(string $path): void
    {
        $lines = array_map(
            static fn (array $error): string => sprintf(
                'Zeile %d (SKU: %s): %s',
                $error['zeile'],
                $error['sku'] === null || $error['sku'] === '' ? '-' : $error['sku'],
                $error['grund'],
            ),
            $this->errors,
        );
 
        if (file_put_contents($path, implode("\n", $lines) . ($lines === [] ? '' : "\n")) === false) {
            throw new RuntimeException("Fehlerprotokoll nicht schreibbar: {$path}");
        }
    }

    private function resetState(): void
    {
        $this->articles = [];
        $this->errors = [];
        $this->rowsRead = 0;
    }
}