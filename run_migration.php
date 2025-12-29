<?php
// Migration runner script
// This script will run the migration_update.sql file to add new features to existing databases

require_once 'db_config.php';

echo "<h1>🔄 School Management System Migration</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

try {
    // Test connection
    echo "<div class='info'>📡 Testing database connection...</div>";
    $stmt = $pdo->query("SELECT 1");
    echo "<div class='success'>✅ Database connected successfully</div>";

    // Read migration file
    echo "<div class='info'>📖 Reading migration file...</div>";
    $migration_sql = file_get_contents('migration_update.sql');

    if (!$migration_sql) {
        throw new Exception("Could not read migration_update.sql file");
    }

    echo "<div class='success'>✅ Migration file loaded</div>";

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migration_sql)));

    echo "<div class='info'>🔧 Executing migration statements...</div>";

    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }

        try {
            $pdo->exec($statement);
            $success_count++;
            echo "<div class='success'>✅ Executed: " . substr($statement, 0, 60) . "...</div>";
        } catch (PDOException $e) {
            $error_count++;
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
            echo "<div class='error'>Statement: " . substr($statement, 0, 100) . "...</div>";
        }
    }

    echo "<hr>";
    echo "<div class='success'>🎉 Migration completed!</div>";
    echo "<div class='info'>📊 Statistics:</div>";
    echo "<ul>";
    echo "<li>✅ Successful statements: $success_count</li>";
    echo "<li>❌ Failed statements: $error_count</li>";
    echo "</ul>";

    if ($error_count == 0) {
        echo "<div class='success'>🎯 All migrations applied successfully!</div>";
        echo "<div class='info'>📋 New features added:</div>";
        echo "<ul>";
        echo "<li>✅ Sex fields for students and teachers</li>";
        echo "<li>✅ Passport photo upload support</li>";
        echo "<li>✅ Fee types (School Fees, Feeding, Transport)</li>";
        echo "<li>✅ Enhanced class management</li>";
        echo "<li>✅ Fee payment tracking with types</li>";
        echo "</ul>";

        echo "<div class='info'>📁 Next steps:</div>";
        echo "<ol>";
        echo "<li>Create the uploads/passports/ directory in your project folder</li>";
        echo "<li>Set directory permissions to 755 (readable/writable by web server)</li>";
        echo "<li>Test the admin dashboard fee payment functionality</li>";
        echo "</ol>";

        echo "<div class='success'>🚀 Your system is now ready with all new features!</div>";
    } else {
        echo "<div class='error'>⚠️ Some migrations failed. Please check the errors above.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>💥 Migration failed: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='admin_dashboard.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a></p>";
