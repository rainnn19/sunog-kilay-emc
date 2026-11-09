<?php

/*
 * Replace these four values with the database details shown in the
 * InfinityFree Control Panel before uploading the application.
 */
define('DB_HOST', 'sql107.infinityfree.com');
define('DB_NAME', 'if0_43391878_sunog_kilay_emc');
define('DB_USER', 'if0_43391878');
define('DB_PASS', '105I7aKHlj');

if (!extension_loaded('mysqli')) {
    http_response_code(503);
    exit('The mysqli PHP extension is not available on this hosting account.');
}

if (session_status() === PHP_SESSION_NONE && !session_start()) {
    http_response_code(503);
    exit('PHP sessions could not be started. Check the hosting account session configuration.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    http_response_code(503);
    exit('Database connection failed. Verify the InfinityFree database host, name, username, and password in includes/config.php.');
}

if (!$conn->set_charset('utf8mb4')) {
    http_response_code(503);
    exit('The database connection could not use UTF-8. Check the selected InfinityFree database.');
}

$document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$application_root = realpath(dirname(__DIR__));
$base_url = '';

if ($document_root && $application_root) {
    $document_root = rtrim(str_replace('\\', '/', $document_root), '/');
    $application_root = str_replace('\\', '/', $application_root);

    if (strpos($application_root, $document_root) === 0) {
        $base_url = substr($application_root, strlen($document_root));
    }
}

define('BASE_URL', rtrim($base_url, '/'));
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);

if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
    die('The uploads directory could not be created. Check its permissions.');
}
