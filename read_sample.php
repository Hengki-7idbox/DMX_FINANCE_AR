<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/Sample/ExportFile.xls';

try {
    $reader = IOFactory::createReaderForFile($file);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file);

    echo "Sheets: " . implode(', ', $spreadsheet->getSheetNames()) . "\n\n";

    foreach ($spreadsheet->getSheetNames() as $name) {
        $sheet = $spreadsheet->getSheetByName($name);
        echo "=== Sheet: {$name} ===\n";
        echo "Rows: {$sheet->getHighestDataRow()}, Cols: {$sheet->getHighestDataColumn()}\n";

        // Print first 8 rows
        $highestRow = min(8, $sheet->getHighestDataRow());
        $highestCol = $sheet->getHighestDataColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestCol; $col++) {
                $cellValue = $sheet->getCell($col . $row)->getValue();
                $rowData[] = is_null($cellValue) ? '' : (string)$cellValue;
            }
            echo "Row {$row}: " . implode(' | ', $rowData) . "\n";
        }
        echo "\n";
    }

    $spreadsheet->disconnectWorksheets();
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
