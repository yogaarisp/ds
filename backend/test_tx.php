<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $txCount = \App\Models\Transaction::count();
    echo "Transaction count: " . $txCount . "\n";
    
    if ($txCount > 0) {
        $tx = \App\Models\Transaction::latest()->first();
        echo "Latest transaction: " . $tx->invoice_number . " (" . $tx->total . ")\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
