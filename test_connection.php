<?php
/**
 * Supabase Connection Test Script
 * Loads environment variables from .env and verifies Supabase connectivity.
 */

require_once __DIR__ . '/community-platform/Backend/config.php';

$apiResult = test_supabase_connection();

if ($apiResult['success']) {
    echo "========================================\n";
    echo "Database Connected Successfully\n";
    echo "========================================\n";
    echo "Details: " . $apiResult['details'] . "\n";
    echo "Project URL: " . SUPABASE_URL . "\n";
    echo "API Key Loaded: " . (SUPABASE_ANON_KEY ? 'Yes (Anon Key)' : 'No') . "\n";

    if (!empty($_ENV['DATABASE_URL'])) {
        $dbUrl = $_ENV['DATABASE_URL'];
        $parsed = parse_url($dbUrl);
        $host = $parsed['host'] ?? 'aws-0-eu-west-1.pooler.supabase.com';
        $port = $parsed['port'] ?? 5432;
        $user = isset($parsed['user']) ? urldecode($parsed['user']) : 'postgres';
        $pass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
        $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'postgres';
        $dsn  = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";

        try {
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
            $ver = $pdo->query('SELECT version()')->fetchColumn();
            $verShort = strpos($ver, ' on ') !== false ? substr($ver, 0, strpos($ver, ' on ')) : $ver;
            echo "Direct PostgreSQL Connection: Connected OK ($verShort)\n";
        } catch (\Throwable $e) {
            echo "Direct PostgreSQL Connection Error: " . $e->getMessage() . "\n";
        }
    }
    exit(0);
} else {
    echo "========================================\n";
    echo "Database failed to connect\n";
    echo "========================================\n";
    echo "Error details: " . $apiResult['details'] . "\n";
    exit(1);
}
