<?php
// File: templates/generate_template.php

// Prevent caching
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="lead_import_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create CSV content
$header = [
    'Full Name',
    'City',
    'Phone Number',
    'Product Code',
    'Other'
];

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, $header);

// Close output stream
fclose($output);
exit;
?>