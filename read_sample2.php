<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/Sample/ExportFile.xls';

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);
$sheet = $spreadsheet->getSheetByName($spreadsheet->getSheetNames()[0]);

$highestRow = $sheet->getHighestDataRow();

echo "=== Stats ===\n";
$codes = 0;
$withEmail = 0;
$withPhone = 0;
$withCity = 0;
$maxCodeLen = 0;
$maxNameLen = 0;
$sampleRows = [];
for ($row = 7; $row <= $highestRow; $row++) {
    $code = $sheet->getCell('B' . $row)->getValue();
    if ($code === null || $code === '') continue;
    $codes++;
    $maxCodeLen = max($maxCodeLen, mb_strlen((string)$code));
    $name = $sheet->getCell('D' . $row)->getValue();
    if ($name) $maxNameLen = max($maxNameLen, mb_strlen((string)$name));
    if ($sheet->getCell('V' . $row)->getValue()) $withEmail++;
    if ($sheet->getCell('H' . $row)->getValue()) $withPhone++;
    if ($sheet->getCell('T' . $row)->getValue()) $withCity++;
    if (count($sampleRows) < 20) {
        $sampleRows[] = [
            'row' => $row,
            'code' => $code,
            'name' => $name,
            'type' => $sheet->getCell('F' . $row)->getValue(),
            'phone' => $sheet->getCell('H' . $row)->getValue(),
            'contact' => $sheet->getCell('J' . $row)->getValue(),
            'balance' => $sheet->getCell('L' . $row)->getValue(),
            'addr1' => $sheet->getCell('P' . $row)->getValue(),
            'addr2' => $sheet->getCell('R' . $row)->getValue(),
            'city' => $sheet->getCell('T' . $row)->getValue(),
            'email' => $sheet->getCell('V' . $row)->getValue(),
            'credit' => $sheet->getCell('X' . $row)->getValue(),
        ];
    }
}

echo "Rows with code: {$codes}\n";
echo "Max code length: {$maxCodeLen}\n";
echo "Max name length: {$maxNameLen}\n";
echo "With email: {$withEmail}\n";
echo "With phone (col H): {$withPhone}\n";
echo "With city: {$withCity}\n";

echo "\n=== Sample rows ===\n";
foreach ($sampleRows as $r) {
    echo "Row {$r['row']}: code={$r['code']} | name={$r['name']} | type={$r['type']} | phone={$r['phone']} | contact={$r['contact']} | balance={$r['balance']} | email={$r['email']} | city={$r['city']} | credit={$r['credit']}\n";
    echo "  addr1={$r['addr1']} | addr2={$r['addr2']}\n";
}

echo "\n=== Duplicate codes? ===\n";
$seen = [];
$dups = 0;
for ($row = 7; $row <= $highestRow; $row++) {
    $code = $sheet->getCell('B' . $row)->getValue();
    if ($code === null || $code === '') continue;
    if (isset($seen[$code])) $dups++;
    $seen[$code] = true;
}
echo "Unique codes: " . count($seen) . ", duplicates: {$dups}\n";

// Check rows 1-10 structure more carefully for section headers
echo "\n=== Rows 1-12 cols A-X ===\n";
for ($row = 1; $row <= 12; $row++) {
    $parts = [];
    for ($col = 'A'; $col <= 'X'; $col++) {
        $v = $sheet->getCell($col . $row)->getValue();
        if ($v !== null && $v !== '') {
            $parts[] = $col . '=' . $v;
        }
    }
    echo "R{$row}: " . implode(' | ', $parts) . "\n";
}

// Check if any data below typical end - any "Customer Type Name" as code?
echo "\n=== Rows where col B empty but col D has value (section breaks?) ===\n";
$count = 0;
for ($row = 7; $row <= $highestRow; $row++) {
    $b = $sheet->getCell('B' . $row)->getValue();
    $d = $sheet->getCell('D' . $row)->getValue();
    if (($b === null || $b === '') && $d !== null && $d !== '') {
        echo "R{$row}: D={$d}\n";
        $count++;
        if ($count > 10) break;
    }
}
if ($count === 0) echo "(none)\n";

$spreadsheet->disconnectWorksheets();
