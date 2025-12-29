<?php
$file = 'admin_dashboard.php';
$content = file($file);
$ids = ['feeModal', 'addStoreItemModal', 'clearOrdersModal', 'editStoreItemModal'];

foreach ($ids as $id) {
    echo "Checking for id=\"$id\"...\n";
    $count = 0;
    foreach ($content as $line_num => $line) {
        if (strpos($line, "id=\"$id\"") !== false) {
            echo "Found at line " . ($line_num + 1) . ": " . trim($line) . "\n";
            $count++;
        }
    }
    echo "Total found: $count\n\n";
}
?>
