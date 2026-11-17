<?php
/**
 * Supabase Migration Runner
 * Applies database migrations to create necessary tables in Supabase.
 */

require_once __DIR__ . '/config.php';

echo "========================================================\n";
echo "DJANGO GIRLS SL - SUPABASE MIGRATION RUNNER\n";
echo "========================================================\n";
echo "Target Project: " . SUPABASE_URL . "\n\n";

$migrationsDir = __DIR__ . '/migrations';
$migrationFiles = [
    '001_create_tables.sql',
    '002_create_auth_triggers.sql',
    '003_create_rls_policies.sql',
    '004_seed_initial_data.sql'
];

// Check if direct PostgreSQL connection credentials exist in .env
$dbHost = $_ENV['DB_HOST'] ?? null;
$dbPassword = $_ENV['DB_PASSWORD'] ?? null;
$dbUrl = $_ENV['DATABASE_URL'] ?? null;

$appliedViaPdo = false;

if ($dbUrl || ($dbHost && $dbPassword)) {
    echo "[Info] Direct PostgreSQL credentials detected in .env.\n";
    try {
        if ($dbUrl) {
            $parsed = parse_url($dbUrl);
            $host = $parsed['host'] ?? 'aws-0-eu-west-1.pooler.supabase.com';
            $port = $parsed['port'] ?? 5432;
            $user = isset($parsed['user']) ? urldecode($parsed['user']) : 'postgres';
            $pass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
            $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'postgres';
            $dsn  = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
            $pdo  = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } else {
            $dsn = "pgsql:host={$dbHost};port=5432;dbname=postgres;sslmode=require";
            $pdo = new PDO($dsn, 'postgres', $dbPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        }

        echo "[Success] Connected to PostgreSQL directly. Applying migrations...\n";

        foreach ($migrationFiles as $file) {
            $sql = file_get_contents($migrationsDir . '/' . $file);
            echo " - Applying $file ... ";
            $pdo->exec($sql);
            echo "DONE\n";
        }

        $appliedViaPdo = true;
        echo "\nAll migrations applied successfully via PostgreSQL direct connection!\n";
    } catch (\Throwable $e) {
        echo "[Notice] Direct PDO notice: " . $e->getMessage() . "\n";
    }
}

// Check tables status in Supabase via REST API
echo "\nChecking schema status on Supabase REST API...\n";
$client = supabase_client();
$tables = ['profiles', 'events', 'resources', 'forum_topics', 'mentorships', 'announcements'];

$allExist = true;
foreach ($tables as $table) {
    $res = $client->get("rest/v1/$table?select=*&limit=1");
    $code = $res->getStatusCode();
    if ($code === 200) {
        echo " - Table '$table': EXISTS (200 OK)\n";
    } else {
        $allExist = false;
        echo " - Table '$table': NOT YET CREATED (HTTP $code)\n";
    }
}

if ($allExist) {
    echo "\nAll necessary tables are present in Supabase!\n";
} else {
    echo "\n--------------------------------------------------------\n";
    echo "HOW TO COMPLETE TABLE CREATION IN SUPABASE (1-Click SQL Editor):\n";
    echo "--------------------------------------------------------\n";
    echo "The master migration file has been generated at:\n";
    echo "  " . __DIR__ . "/migrations/master_migration.sql\n\n";
    echo "To execute it on Supabase:\n";
    echo "1. Open your project SQL editor in browser:\n";
    echo "   https://supabase.com/dashboard/project/ggrqaivaijokkebxeppd/sql/new\n";
    echo "2. Copy & paste the contents of 'master_migration.sql'.\n";
    echo "3. Click 'Run' to create all tables, triggers, and RLS policies instantly.\n";
    echo "--------------------------------------------------------\n";
}
