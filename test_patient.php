<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $patient = App\Models\Patient::find(2);
    echo "Patient: " . $patient->full_name . "\n";
    $patient->load([
        'visits.vitalSign',
        'visits.consultation',
        'visits.labRequests',
        'visits.prescriptions'
    ]);
    echo "Loaded relations successfully!\n";
    $visit = $patient->visits->first();
    echo "Visit JSON: " . json_encode($visit->toArray()) . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString();
}
