<?php
// Simple DB connectivity diagnostics for XAMPP
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo '<h2>Qnnect DB Connectivity Test</h2>';

$reportOff = defined('MYSQLI_REPORT_OFF') ? MYSQLI_REPORT_OFF : 0;
if (function_exists('mysqli_report')) {
    // Turn off strict exceptions so we can probe multiple ports safely
    @mysqli_report($reportOff);
}

$hosts = ['127.0.0.1', 'localhost'];
$ports = [3306, 3307, 3308, 33060];
$user  = 'root';
$pass  = '';
$dbs   = ['login_register', 'qr_attendance_db'];

echo '<pre>';
echo 'PHP Version: ' . PHP_VERSION . "\n";
echo 'mysqli extension: ' . (extension_loaded('mysqli') ? 'loaded' : 'NOT loaded') . "\n";
echo 'pdo_mysql extension: ' . (extension_loaded('pdo_mysql') ? 'loaded' : 'NOT loaded') . "\n\n";

$attempts = [];
foreach ($dbs as $db) {
    foreach ($hosts as $h) {
        foreach ($ports as $p) {
            $t0 = microtime(true);
            $ok = false; $err = '';
            try {
                $conn = @mysqli_connect($h, $user, $pass, $db, $p);
                $ok = (bool)$conn;
                if (!$ok) { $err = mysqli_connect_error(); }
            } catch (Throwable $e) {
                $ok = false; $err = $e->getMessage();
            }
            $ms = sprintf('%.1fms', (microtime(true) - $t0) * 1000);
            $attempts[] = [$db, $h, $p, $ok ? 'OK' : 'FAIL', $ms, $err];
            if ($ok) { mysqli_close($conn); }
        }
    }
}

printf("%-18s %-13s %-7s %-6s %-8s %s\n", 'Database', 'Host', 'Port', 'State', 'Latency', 'Error');
echo str_repeat('-', 96) . "\n";
foreach ($attempts as $a) {
    printf("%-18s %-13s %-7s %-6s %-8s %s\n", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5]);
}
echo '</pre>';

echo '<p>Tip: If only certain ports fail, update connection fallbacks to include your MySQL port.</p>';
