<?php
$password = 'admin123';
$correct_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password: 'admin123'<br>";
echo "Against hash: " . $correct_hash . "<br><br>";

if (password_verify($password, $correct_hash)) {
    echo "✅ SUCCESS: Password matches the hash!";
} else {
    echo "❌ FAILED: Password does not match the hash";
    
    // Let's generate a new hash
    echo "<br><br>Generating new hash for 'admin123':<br>";
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "New Hash: " . $new_hash . "<br>";
    
    // Test the new hash
    if (password_verify($password, $new_hash)) {
        echo "✅ New hash works! Use this in your database.";
    }
}
?>