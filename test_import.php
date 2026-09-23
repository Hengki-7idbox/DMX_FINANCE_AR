<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\CustomersImport;
use App\Models\Customer;
use Maatwebsite\Excel\Facades\Excel;

$source = $argv[1] ?? 'external';
$file = $argv[2] ?? __DIR__ . '/Sample/ExportFile.xls';

echo "Importing {$file} as source_type={$source}\n";

$import = new CustomersImport($source);
$startTime = microtime(true);
Excel::import($import, $file);
$elapsed = round(microtime(true) - $startTime, 2);

echo "Created: {$import->getCreatedCount()}\n";
echo "Updated: {$import->getUpdatedCount()}\n";
echo "Skipped: {$import->getSkippedCount()}\n";
echo "Elapsed: {$elapsed}s\n";
echo "Total in DB ({$source}): " . Customer::where('source_type', $source)->count() . "\n";

$sample = Customer::where('source_type', $source)->orderBy('id')->limit(5)->get();
foreach ($sample as $c) {
    echo "- {$c->customer_code} | {$c->customer_name} | {$c->customer_type} | {$c->phone} | {$c->credit_limit} | {$c->status}\n";
}
