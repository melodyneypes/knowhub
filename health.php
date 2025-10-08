<?php
header('Content-Type: application/json');

// Check if required files exist
$required_files = [
    'db.php',
    'vendor/autoload.php',
    'callback.php'
];

$checks = [
    'php_version' => phpversion(),
    'required_files' => [],
    'database' => false,
    'environment' => getenv('DATABASE_URL') ? 'render' : 'local'
];

foreach ($required_files as $file) {
    $checks['required_files'][$file] = file_exists($file);
}

// Check database connection
if (file_exists('db.php')) {
    try {
        include 'db.php';
        if ($conn) {
            $checks['database'] = true;
        }
    } catch (Exception $e) {
        $checks['database_error'] = $e->getMessage();
    }
}

http_response_code($checks['database'] ? 200 : 500);
echo json_encode($checks, JSON_PRETTY_PRINT);
?>