<?php

/*
 * This script reads CLV data from sample.clv file and outputs parsed rows to STDOUT in JSON format
 */
use gugglegum\ClvRw\Column;
use gugglegum\ClvRw\ColumnsSet;
use gugglegum\ClvRw\Reader;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $clv = (new Reader())->open(__DIR__ . '/sample.clv', new ColumnsSet([
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

    foreach ($clv as $row) {
        echo json_encode($row), "\n";
    }
} catch (\gugglegum\ClvRw\Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(-1);
}
