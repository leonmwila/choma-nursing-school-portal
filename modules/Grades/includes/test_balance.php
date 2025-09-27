<?php
require_once '../../config.inc.php';
require_once '../../database.inc.php';

// Check if table exists
$table_exists = DBGetOne("SELECT 1 FROM information_schema.tables 
    WHERE table_schema = DATABASE()
    AND table_name = 'billing_fees'");

echo "Table exists: " . ($table_exists ? "Yes" : "No") . "\n";

if ($table_exists) {
    // Get table structure
    $structure = DBGet("DESCRIBE billing_fees");
    echo "\nTable structure:\n";
    print_r($structure);

    // Get a sample of data
    $sample = DBGet("SELECT * FROM billing_fees LIMIT 1");
    echo "\nSample data:\n";
    print_r($sample);
}
