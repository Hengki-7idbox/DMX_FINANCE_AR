<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class DebugImport implements \Maatwebsite\Excel\Concerns\ToCollection
{
    public function collection(Collection $rows): void
    {
        echo "rows count: " . $rows->count() . "\n";
        echo "first row class: " . get_class($rows[0] ?? new stdClass) . "\n";

        for ($i = 0; $i < min(8, $rows->count()); $i++) {
            $row = $rows[$i];
            echo "Row {$i} type: " . gettype($row) . " class: " . (is_object($row) ? get_class($row) : 'n/a') . "\n";
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }
            if (is_object($row) && method_exists($row, 'toArray')) {
                $row = $row->toArray();
            }
            if (!is_array($row)) {
                echo "  NOT ARRAY\n";
                continue;
            }
            echo "  count: " . count($row) . "\n";
            foreach (array_slice($row, 0, 12, true) as $col => $cell) {
                $type = is_object($cell) ? get_class($cell) : gettype($cell);
                $asStr = is_object($cell) && method_exists($cell, 'getPlainText')
                    ? $cell->getPlainText()
                    : (string) json_encode($cell);
                if (strlen($asStr) > 80) $asStr = substr($asStr, 0, 80) . '...';
                echo "  [{$col}] {$type}: {$asStr}\n";
            }
        }
    }
}

try {
    Excel::import(new DebugImport(), __DIR__ . '/Sample/ExportFile.xls');
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
