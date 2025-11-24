<?php

return [
    // System-wide barcodes used when client wants a single inbound/outbound barcode
    // Default values: inbound '000000', outbound '11111111'
    'inbound_barcode' => env('INBOUND_BARCODE', '000000'),
    'outbound_barcode' => env('OUTBOUND_BARCODE', '11111111'),
];
