<?php
$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'check') {
    http_response_code(404);
    echo 'Not found';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

function checkLoadEnv($path, &$env)
{
    if (!is_file($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '') {
            $env[$key] = $value;
        }
    }
    return true;
}

$root = __DIR__;
$env = array();
$loaded = array();
if (checkLoadEnv($root . '/.env', $env)) {
    $loaded[] = '.env';
}
if (checkLoadEnv($root . '/.env.hosting', $env)) {
    $loaded[] = '.env.hosting';
}

$driver = isset($env['DB_DRIVER']) ? $env['DB_DRIVER'] : 'mysql';
$host = isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost';
$port = isset($env['DB_PORT']) ? $env['DB_PORT'] : '3306';
$name = isset($env['DB_NAME']) ? $env['DB_NAME'] : '';
$user = isset($env['DB_USER']) ? $env['DB_USER'] : '';
$pass = isset($env['DB_PASS']) ? $env['DB_PASS'] : '';

echo "Hosting check\n";
echo "=============\n";
echo "PHP_VERSION: " . PHP_VERSION . "\n";
echo "PHP >= 7.1: " . (version_compare(PHP_VERSION, '7.1.0', '>=') ? 'OK' : 'FAIL') . "\n";
echo "PHP >= 7.4: " . (version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'WARN') . "\n";
echo "Loaded env: " . ($loaded ? implode(', ', $loaded) : 'none') . "\n";
echo "Document root: " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '') . "\n";
echo "Project root: " . $root . "\n\n";

echo "Extensions\n";
echo "----------\n";
foreach (array('pdo', 'pdo_mysql', 'mbstring', 'fileinfo') as $ext) {
    echo $ext . ': ' . (extension_loaded($ext) ? 'OK' : 'MISSING') . "\n";
}

echo "\nDatabase config\n";
echo "---------------\n";
echo "DB_DRIVER: " . $driver . "\n";
echo "DB_HOST: " . $host . "\n";
echo "DB_PORT: " . $port . "\n";
echo "DB_NAME: " . $name . "\n";
echo "DB_USER: " . $user . "\n";
echo "DB_PASS: " . ($pass === '' ? 'EMPTY' : ('SET length=' . strlen($pass))) . "\n";
echo "APP_URL: " . (isset($env['APP_URL']) ? $env['APP_URL'] : '') . "\n";
echo "SEPAY_WEBHOOK_URL: " . (isset($env['SEPAY_WEBHOOK_URL']) ? $env['SEPAY_WEBHOOK_URL'] : '') . "\n";
echo "SEPAY_BANK: " . (isset($env['SEPAY_BANK_CODE']) ? $env['SEPAY_BANK_CODE'] : '') . "\n";
echo "SEPAY_ACCOUNT: " . (isset($env['SEPAY_ACCOUNT_NUMBER']) ? $env['SEPAY_ACCOUNT_NUMBER'] : '') . "\n";
echo "SEPAY_API_KEY: " . (!empty($env['SEPAY_WEBHOOK_API_KEY']) ? 'SET' : 'not set') . "\n";

echo "\nDatabase connection\n";
echo "-------------------\n";
if ($driver !== 'mysql') {
    echo "SKIP: DB_DRIVER is not mysql\n";
} elseif (!extension_loaded('pdo_mysql')) {
    echo "FAIL: pdo_mysql extension is missing\n";
} else {
    try {
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        echo "OK: connected\n";
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, 0);
        echo "Tables: " . count($tables) . "\n";
    } catch (Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }
}

echo "\nFiles and folders\n";
echo "-----------------\n";
foreach (array('index.php', 'payment-webhook.php', 'app/bootstrap.php', 'app/repositories.php', 'app/payment_webhook.php', 'views/base.php', '.htaccess', '.env') as $file) {
    echo $file . ': ' . (is_file($root . '/' . $file) ? 'OK' : 'MISSING') . "\n";
}
foreach (array('storage', 'storage/uploads') as $dir) {
    $path = $root . '/' . $dir;
    echo $dir . ': ' . (is_dir($path) ? 'OK' : 'MISSING') . ', writable=' . (is_writable($path) ? 'YES' : 'NO') . "\n";
}
