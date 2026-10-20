<?php

declare(strict_types= 1);

require __DIR__ . '/src/parser.php';
 
if ($argc !== 3) {
    fwrite(STDERR, "Aufruf: php import.php <eingabe.csv> <ausgabe.json>\n");
    exit(2);
}

try {
    (new Parser())->parse($argv[1], $argv[2]);
} catch (RuntimeException|JsonException $e) {
    fwrite(STDERR, 'Abbruch: ' . $e->getMessage() . "\n");
    exit(1);
}