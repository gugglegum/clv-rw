<?php

/*
 * This script reads JSON rows from STDIN and outputs to STDOUT rows in CLV format. It should be executed so:
 * ```
 * php reader.php | php writer.php
 * ```
 * You can redirect it to any file, for example:
 * ```
 * php reader.php | php writer.php > sample2.clv
 * ```
 * It will create sample2.clv which should be equal to sample.clv
 */

use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use gugglegum\ClvRw\Writer;

require_once __DIR__ . '/../vendor/autoload.php';

$clv = (new Writer())->assign(STDOUT, new ColumnsSet([
    Column::create('Style Number', 12),
    Column::create('Style Description', 25),
    Column::create('Color Code', 3),
    Column::create('Color Description', 25),
    Column::create('Piece', 6),
    Column::create('Width', 3),
    Column::create('Size', 4),
    Column::create('Sold Out Reason', 2),
    Column::create('Sold Out Date', 8),
    Column::create('UPC', 12),
    Column::create('Inventory', 7),
    Column::create('Next Delivery Availability', 7),
    Column::create('Next Delivery Date', 8),
    Column::create('USD List Price', 9),
    Column::create('HSCode', 10),
    Column::create('HSCountry', 3),
]));

while (($s = fgets(STDIN)) !== false) {
    $row = json_decode($s, true);
    try {
        $clv->writeRow($row);
    } catch (\gugglegum\ClvRw\Exception $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(-1);
    }
}
