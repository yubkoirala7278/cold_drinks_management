<!DOCTYPE html>
<html>

<head>
    <title>Generated Barcodes</title>
    <style>
        @page {
            size: A4;
            margin: 0.5cm;
        }

        body {
            font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .barcode-sheet {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            width: 100%;
            margin: 0 auto;
        }

        .barcode-label {
            border: 1px dotted #ccc;
            padding: 5mm;
            height: 25mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .barcode-image {
            height: 15mm;
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 1mm;
        }

        .barcode-text {
            font-size: 8pt;
            text-align: center;
            word-break: break-all;
            padding: 0 2mm;
        }

        .footer {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 5mm;
            padding-top: 2mm;
            border-top: 1px solid #eee;
        }

        .page-break {
            page-break-after: always;
        }

        /* Print-specific styles */
        @media print {
            .barcode-label {
                border: 1px solid transparent;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h2 style="margin-bottom: 2mm;">Generated Barcodes</h2>
        <p style="margin: 0; font-size: 9pt;">
            Generated on: {{ now()->format('Y-m-d H:i:s') }} |
            Total: {{ count($barcodes) }} barcodes
        </p>
    </div>

    @foreach (array_chunk($barcodes, 32) as $page => $barcodePage)
        <div class="barcode-sheet">
            @foreach ($barcodePage as $barcode)
                <div class="barcode-label">
                    <div class="barcode-image">
                        {!! $barcode['barcode'] !!}
                    </div>
                    <div class="barcode-text">
                        {{ $barcode['code'] }}
                    </div>
                </div>
            @endforeach
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="footer no-print">
        <p>Barcode System v1.0 | Cut along dotted lines</p>
    </div>
</body>

</html>
