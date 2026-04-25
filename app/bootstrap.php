<?php
declare(strict_types=1);

$isHttpsForSession = (
    (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
);
$persistentSessionLifetime = 60 * 60 * 24 * 365 * 10; // Keep users signed in unless they explicitly log out.

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttpsForSession ? '1' : '0');
ini_set('session.cookie_lifetime', (string)$persistentSessionLifetime);
ini_set('session.gc_maxlifetime', (string)$persistentSessionLifetime);
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => $persistentSessionLifetime,
        'path' => '/',
        'secure' => $isHttpsForSession,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params($persistentSessionLifetime, '/; samesite=Lax', '', $isHttpsForSession, true);
}

$sessionSavePath = __DIR__ . '/../storage/security/sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0775, true);
}
if (is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
    session_save_path($sessionSavePath);
}

session_start();
if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
    if (PHP_VERSION_ID >= 70300) {
        setcookie(session_name(), session_id(), [
            'expires' => time() + $persistentSessionLifetime,
            'path' => '/',
            'secure' => $isHttpsForSession,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie(session_name(), session_id(), time() + $persistentSessionLifetime, '/; samesite=Lax', '', $isHttpsForSession, true);
    }
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
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

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key === '') {
            continue;
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Load deploy defaults from .env if present. Local XAMPP can override these
// with .env.local, while hosting can optionally override with .env.hosting.
loadEnv(__DIR__ . '/../.env');

$httpHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$appEnv = strtolower((string)(getenv('APP_ENV') ?: ''));
$projectPath = strtolower(str_replace('\\', '/', __DIR__));
$isLocalHost = $httpHost === ''
    || preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/', $httpHost) === 1;
$isLocalPath = strpos($projectPath, '/xampp/htdocs/') !== false
    || strpos($projectPath, '/wamp64/www/') !== false
    || strpos($projectPath, '/laragon/www/') !== false;

if (($isLocalHost || $isLocalPath) && file_exists(__DIR__ . '/../.env.local')) {
    loadEnv(__DIR__ . '/../.env.local');
} elseif (($appEnv === 'production' || (!$isLocalHost && !$isLocalPath)) && file_exists(__DIR__ . '/../.env.hosting')) {
    loadEnv(__DIR__ . '/../.env.hosting');
}

function env(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function baseUrl(): string
{
    $appUrl = trim((string) env('APP_URL', ''));
    $appEnv = strtolower((string) env('APP_ENV', ''));
    $httpHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $isLocalHost = $httpHost === ''
        || preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/', $httpHost) === 1;
    $projectPath = strtolower(str_replace('\\', '/', __DIR__));
    $isLocalPath = strpos($projectPath, '/xampp/htdocs/') !== false
        || strpos($projectPath, '/wamp64/www/') !== false
        || strpos($projectPath, '/laragon/www/') !== false;

    if ($appUrl !== '' && !$isLocalPath && ($appEnv === 'production' || !$isLocalHost)) {
        return rtrim($appUrl, '/') . '/';
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '' || $dir === '.') {
        return '/';
    }
    return $dir . '/';
}

function assetUrl(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^(?:https?:)?//~i', $path) || preg_match('~^(?:data|blob):~i', $path)) {
        return $path;
    }
    if ($path[0] === '/') {
        return $path;
    }
    return baseUrl() . ltrim($path, '/');
}

function routeUrl(string $route, array $params = []): string
{
    return baseUrl() . '?' . http_build_query(array_merge(['route' => $route], $params));
}

function sepayBankInfo(): array
{
    return [
        'bank' => (string)env('SEPAY_BANK_CODE', 'TPBank'),
        'account' => (string)env('SEPAY_ACCOUNT_NUMBER', '67868689933'),
        'name' => (string)env('SEPAY_ACCOUNT_NAME', 'LE HA NAM'),
    ];
}

function sepayQrUrl(int $amount, string $paymentCode): string
{
    $info = sepayBankInfo();
    if ($amount <= 0 || $paymentCode === '' || $info['bank'] === '' || $info['account'] === '') {
        return '';
    }

    return 'https://qr.sepay.vn/img?' . http_build_query([
        'acc' => $info['account'],
        'bank' => $info['bank'],
        'amount' => $amount,
        'des' => $paymentCode,
    ]);
}

function isHttpsRequest(): bool
{
    return (
        (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );
}

function clientIpAddress(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        $raw = trim((string)($_SERVER[$key] ?? ''));
        if ($raw === '') {
            continue;
        }
        $parts = explode(',', $raw);
        foreach ($parts as $part) {
            $ip = trim($part);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function securityStorageDir(): string
{
    $dir = __DIR__ . '/../storage/security';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function securityLog(string $event, array $context = []): void
{
    $line = sprintf(
        "[%s] %s ip=%s route=%s method=%s uri=%s %s\n",
        date('Y-m-d H:i:s'),
        $event,
        clientIpAddress(),
        (string)($context['route'] ?? ($_GET['route'] ?? '')),
        (string)($_SERVER['REQUEST_METHOD'] ?? ''),
        (string)($_SERVER['REQUEST_URI'] ?? ''),
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
    );
    @file_put_contents(securityStorageDir() . '/security.log', $line, FILE_APPEND);
}

function applySecurityHeaders(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    $httpHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $appEnv = strtolower((string) env('APP_ENV', ''));
    $projectPath = strtolower(str_replace('\\', '/', __DIR__));
    $isLocalHost = $httpHost === ''
        || preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/', $httpHost) === 1;
    $isLocalPath = strpos($projectPath, '/xampp/htdocs/') !== false
        || strpos($projectPath, '/wamp64/www/') !== false
        || strpos($projectPath, '/laragon/www/') !== false;
    $allowEmbeddedPreview = ($isLocalHost || $isLocalPath) && $appEnv !== 'production';

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    if (!$allowEmbeddedPreview) {
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    } else {
        header("Content-Security-Policy: base-uri 'self'; form-action 'self'");
    }
    if (isHttpsRequest()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function isAjaxRequest(): bool
{
    if (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        return true;
    }
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return strpos($accept, 'application/json') !== false;
}

function blockRequest(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
    exit;
}

function flattenInputForFirewall($value, array &$buffer): void
{
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            if (is_scalar($k)) {
                $buffer[] = (string)$k;
            }
            flattenInputForFirewall($v, $buffer);
        }
        return;
    }
    if (is_scalar($value)) {
        $text = (string)$value;
        if ($text !== '') {
            $buffer[] = mb_substr($text, 0, 4000);
        }
    }
}

function detectMaliciousPayload(array $chunks): ?string
{
    $patterns = [
        'script_tag' => '/<\s*script\b|%3c\s*script/i',
        'inline_js' => '/javascript\s*:|on(?:error|load|click|mouseover)\s*=/i',
        'path_traversal' => '/\.\.[\/\\\\]/',
        'null_byte' => '/\x00|%00/i',
        'sql_injection' => '/\bUNION\b.{0,24}\bSELECT\b|\bDROP\b.{0,24}\bTABLE\b|\bOR\b\s+1\s*=\s*1/i',
        'php_wrapper' => '/(?:php|data|expect|input):\/\/|php:\/\//i',
    ];
    foreach ($chunks as $chunk) {
        foreach ($patterns as $tag => $regex) {
            if (preg_match($regex, $chunk) === 1) {
                return $tag;
            }
        }
    }
    return null;
}

function enforceRequestFirewall(string $route = ''): void
{
    $enabled = filter_var((string)env('SECURITY_WAF_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN);
    if (!$enabled) {
        return;
    }

    $skipRoutes = ['payment-webhook', 'lead-notifications-stream'];
    if (in_array($route, $skipRoutes, true)) {
        return;
    }

    $chunks = [
        (string)($_SERVER['REQUEST_URI'] ?? ''),
        (string)($_SERVER['REQUEST_METHOD'] ?? ''),
        $route,
    ];
    flattenInputForFirewall($_GET ?? [], $chunks);
    flattenInputForFirewall($_POST ?? [], $chunks);
    flattenInputForFirewall($_COOKIE ?? [], $chunks);

    $reason = detectMaliciousPayload($chunks);
    if ($reason !== null) {
        securityLog('firewall_block', ['route' => $route, 'reason' => $reason]);
        blockRequest(403, 'Yêu cầu bị chặn bởi tường lửa ứng dụng.');
    }
}

function rateLimitDir(): string
{
    $dir = securityStorageDir() . '/rate_limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function maybeCleanupRateLimitFiles(string $dir): void
{
    if (mt_rand(1, 100) !== 1) {
        return;
    }
    $threshold = time() - 2 * 24 * 60 * 60;
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        $mtime = @filemtime($file);
        if ($mtime !== false && $mtime < $threshold) {
            @unlink($file);
        }
    }
}

function isRateLimitExceeded(string $key, int $maxRequests, int $windowSeconds = 60): bool
{
    $dir = rateLimitDir();
    maybeCleanupRateLimitFiles($dir);

    $slot = (int)floor(time() / $windowSeconds);
    $file = $dir . '/' . hash('sha256', $key) . '.json';
    $fp = @fopen($file, 'c+');
    if (!$fp) {
        return false;
    }

    $state = ['slot' => $slot, 'count' => 0];
    if (flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state['slot'] = (int)($decoded['slot'] ?? $slot);
                $state['count'] = (int)($decoded['count'] ?? 0);
            }
        }

        if ($state['slot'] !== $slot) {
            $state['slot'] = $slot;
            $state['count'] = 0;
        }

        $state['count']++;
        $blocked = $state['count'] > $maxRequests;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($state));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $blocked;
    }

    fclose($fp);
    return false;
}

function enforceRequestRateLimit(string $route = ''): void
{
    $enabled = filter_var((string)env('SECURITY_RATE_LIMIT_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN);
    if (!$enabled) {
        return;
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $window = 60;
    $max = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) ? 240 : 120;

    if ($method === 'POST' && in_array($route, ['login', 'register'], true)) {
        $max = 20;
    } elseif ($method === 'POST' && in_array($route, ['lead', 'cta-submit', 'push-subscribe', 'push-unsubscribe'], true)) {
        $max = 50;
    } elseif ($method === 'POST' && in_array($route, ['messages', 'admin-messages'], true)) {
        $max = 90;
    } elseif ($method === 'POST' && $route === 'payment-webhook') {
        $max = 180;
    }

    $key = implode('|', [clientIpAddress(), $method, $route !== '' ? $route : 'root']);
    if (isRateLimitExceeded($key, $max, $window)) {
        header('Retry-After: ' . $window);
        securityLog('rate_limit_block', ['route' => $route, 'limit' => $max, 'window' => $window]);
        blockRequest(429, 'Bạn thao tác quá nhanh, vui lòng thử lại sau.');
    }
}

function requestHeaderValue(string $headerName): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
    if (!empty($_SERVER[$key])) {
        return trim((string)$_SERVER[$key]);
    }

    $redirectKey = 'REDIRECT_' . $key;
    if (!empty($_SERVER[$redirectKey])) {
        return trim((string)$_SERVER[$redirectKey]);
    }

    return '';
}

function csrfToken(): string
{
    $token = (string)($_SESSION['_csrf_token'] ?? '');
    if ($token !== '' && strlen($token) >= 32) {
        return $token;
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $token = bin2hex(hash('sha256', uniqid('csrf', true), true));
    }
    $_SESSION['_csrf_token'] = $token;
    return $token;
}

function csrfTokenFromRequest(): string
{
    $fromPost = trim((string)($_POST['_csrf'] ?? ''));
    if ($fromPost !== '') {
        return $fromPost;
    }

    $fromHeader = requestHeaderValue('X-CSRF-Token');
    if ($fromHeader !== '') {
        return $fromHeader;
    }

    $fromAltHeader = requestHeaderValue('X-XSRF-TOKEN');
    if ($fromAltHeader !== '') {
        return $fromAltHeader;
    }

    return '';
}

function sameOriginSubmission(): bool
{
    $currentHostRaw = trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $currentHost = strtolower((string)(parse_url((isHttpsRequest() ? 'https://' : 'http://') . $currentHostRaw, PHP_URL_HOST) ?? $currentHostRaw));
    if ($currentHost === '') {
        return false;
    }

    $candidates = [
        (string)($_SERVER['HTTP_ORIGIN'] ?? ''),
        (string)($_SERVER['HTTP_REFERER'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === '') {
            continue;
        }
        $host = strtolower((string)(parse_url($candidate, PHP_URL_HOST) ?? ''));
        if ($host !== '' && $host === $currentHost) {
            return true;
        }
    }

    return false;
}

function enforceCsrfProtection(string $route = ''): void
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $skipRoutes = ['payment-webhook', 'lead-notifications-stream'];
    if (in_array($route, $skipRoutes, true)) {
        return;
    }

    $token = csrfTokenFromRequest();
    if ($token !== '' && hash_equals(csrfToken(), $token)) {
        return;
    }

    if (sameOriginSubmission()) {
        return;
    }

    securityLog('csrf_block', [
        'route' => $route,
        'has_token' => $token !== '' ? 1 : 0,
        'same_origin' => sameOriginSubmission() ? 1 : 0,
    ]);
    blockRequest(419, 'Yeu cau khong hop le. Vui long tai lai trang va thu lai.');
}

function authThrottleDir(): string
{
    $dir = securityStorageDir() . '/auth_throttle';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function authThrottleFilePath(string $phone, string $ip): string
{
    $phone = preg_replace('/\D+/', '', $phone);
    $key = hash('sha256', strtolower($phone) . '|' . strtolower($ip));
    return authThrottleDir() . '/' . $key . '.json';
}

function authThrottleConfig(): array
{
    return [
        'window_seconds' => 15 * 60,
        'max_failures' => 8,
        'lock_seconds' => 15 * 60,
    ];
}

function loginFailureState(string $phone, string $ip): array
{
    $path = authThrottleFilePath($phone, $ip);
    if (!is_file($path)) {
        return ['first_failed_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return ['first_failed_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['first_failed_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    }

    return [
        'first_failed_at' => (int)($decoded['first_failed_at'] ?? 0),
        'failed_count' => (int)($decoded['failed_count'] ?? 0),
        'blocked_until' => (int)($decoded['blocked_until'] ?? 0),
    ];
}

function isLoginTemporarilyBlocked(string $phone, string $ip, ?int &$retryAfter = null): bool
{
    $state = loginFailureState($phone, $ip);
    $now = time();
    $blockedUntil = (int)($state['blocked_until'] ?? 0);
    $retryAfter = max(0, $blockedUntil - $now);
    return $blockedUntil > $now;
}

function recordFailedLoginAttempt(string $phone, string $ip): void
{
    $cfg = authThrottleConfig();
    $path = authThrottleFilePath($phone, $ip);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $fp = @fopen($path, 'c+');
    if (!$fp) {
        return;
    }

    $now = time();
    $state = ['first_failed_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    if (flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state['first_failed_at'] = (int)($decoded['first_failed_at'] ?? 0);
                $state['failed_count'] = (int)($decoded['failed_count'] ?? 0);
                $state['blocked_until'] = (int)($decoded['blocked_until'] ?? 0);
            }
        }

        $windowSeconds = (int)$cfg['window_seconds'];
        if ($state['first_failed_at'] <= 0 || ($now - $state['first_failed_at']) > $windowSeconds) {
            $state['first_failed_at'] = $now;
            $state['failed_count'] = 0;
        }

        $state['failed_count']++;
        if ($state['failed_count'] >= (int)$cfg['max_failures']) {
            $state['blocked_until'] = max($state['blocked_until'], $now + (int)$cfg['lock_seconds']);
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($state));
        fflush($fp);
        flock($fp, LOCK_UN);
    }

    fclose($fp);
}

function clearFailedLoginAttempts(string $phone, string $ip): void
{
    $path = authThrottleFilePath($phone, $ip);
    if (is_file($path)) {
        @unlink($path);
    }
}

function protectIncomingRequest(string $route = ''): void
{
    applySecurityHeaders();
    enforceRequestFirewall($route);
    enforceRequestRateLimit($route);
    enforceCsrfProtection($route);
}

function getPDO(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Default to MySQL; override via .env if you intentionally want SQLite
    $driver = env('DB_DRIVER', 'mysql');

    if ($driver === 'mysql') {
        $host = env('DB_HOST', '127.0.0.1');
        // Override via .env because local XAMPP setups often move off 3306.
        $port = env('DB_PORT', '3306');
        $dbname = env('DB_NAME', 'quan_ly_tro');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');
        $autoMigrate = filter_var((string) env('DB_AUTO_MIGRATE', '0'), FILTER_VALIDATE_BOOLEAN);
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            if ($autoMigrate) {
                migrateMysql($pdo);
            }
            return $pdo;
        } catch (PDOException $e) {
            error_log('MySQL connection failed: ' . $e->getMessage());
            // Do not fall back; surface the real MySQL error
            throw $e;
        }
    }

    $path = __DIR__ . '/../storage/database.sqlite';
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    $isNew = !file_exists($path);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if ($isNew) {
        initializeSqlite($pdo);
    } else {
        migrateSqlite($pdo);
    }

    return $pdo;
}

function ensureSecurityRuntimeSchema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    try {
        $pdo = getPDO();
        $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                actor_id INT NULL,
                actor_role VARCHAR(30) NOT NULL,
                action VARCHAR(100) NOT NULL,
                route VARCHAR(120) NULL,
                entity_type VARCHAR(80) NULL,
                entity_id VARCHAR(80) NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                metadata_json TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_audit_actor_created (actor_id, created_at),
                KEY idx_audit_action_created (action, created_at)
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_transaction_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                actor_id INT NULL,
                actor_role VARCHAR(30) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                status VARCHAR(40) NULL,
                amount INT NULL,
                entity_type VARCHAR(80) NULL,
                entity_id VARCHAR(80) NULL,
                reference_code VARCHAR(120) NULL,
                note VARCHAR(255) NULL,
                route VARCHAR(120) NULL,
                ip_address VARCHAR(64) NULL,
                metadata_json TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_security_tx_event_created (event_type, created_at),
                KEY idx_security_tx_entity (entity_type, entity_id)
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS webhook_idempotency (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(40) NOT NULL,
                event_key VARCHAR(191) NOT NULL,
                payload_hash CHAR(64) NULL,
                hit_count INT NOT NULL DEFAULT 1,
                first_seen_at DATETIME NOT NULL,
                last_seen_at DATETIME NOT NULL,
                UNIQUE KEY uniq_webhook_provider_key (provider, event_key),
                KEY idx_webhook_last_seen (last_seen_at)
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS staff_scopes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_user_id INT NOT NULL,
                landlord_id INT NOT NULL,
                permissions_json TEXT NULL,
                status ENUM('active','revoked') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_staff_landlord_scope (staff_user_id, landlord_id),
                KEY idx_staff_scope_landlord (landlord_id)
            )");
            try {
                $pdo->exec("ALTER TABLE users MODIFY role ENUM('tenant','landlord','staff','admin') DEFAULT 'tenant'");
            } catch (Throwable $e) {
                // Shared hosting may block ALTER; runtime still works with existing values.
            }
        } else {
            $pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_id INTEGER NULL,
                actor_role TEXT NOT NULL,
                action TEXT NOT NULL,
                route TEXT NULL,
                entity_type TEXT NULL,
                entity_id TEXT NULL,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                metadata_json TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_actor_created ON audit_logs(actor_id, created_at)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_action_created ON audit_logs(action, created_at)');

            $pdo->exec('CREATE TABLE IF NOT EXISTS security_transaction_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_id INTEGER NULL,
                actor_role TEXT NOT NULL,
                event_type TEXT NOT NULL,
                status TEXT NULL,
                amount INTEGER NULL,
                entity_type TEXT NULL,
                entity_id TEXT NULL,
                reference_code TEXT NULL,
                note TEXT NULL,
                route TEXT NULL,
                ip_address TEXT NULL,
                metadata_json TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_security_tx_event_created ON security_transaction_logs(event_type, created_at)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_security_tx_entity ON security_transaction_logs(entity_type, entity_id)');

            $pdo->exec('CREATE TABLE IF NOT EXISTS webhook_idempotency (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                provider TEXT NOT NULL,
                event_key TEXT NOT NULL,
                payload_hash TEXT NULL,
                hit_count INTEGER NOT NULL DEFAULT 1,
                first_seen_at TEXT NOT NULL,
                last_seen_at TEXT NOT NULL
            )');
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_webhook_provider_key ON webhook_idempotency(provider, event_key)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_webhook_last_seen ON webhook_idempotency(last_seen_at)');

            $pdo->exec('CREATE TABLE IF NOT EXISTS staff_scopes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                staff_user_id INTEGER NOT NULL,
                landlord_id INTEGER NOT NULL,
                permissions_json TEXT NULL,
                status TEXT DEFAULT "active",
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )');
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_staff_landlord_scope ON staff_scopes(staff_user_id, landlord_id)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_staff_scope_landlord ON staff_scopes(landlord_id)');
        }
    } catch (Throwable $e) {
        error_log('Ensure security schema failed: ' . $e->getMessage());
    }

    $done = true;
}

function auditLog(string $action, array $context = []): void
{
    $user = currentUser();
    $actorId = $user ? (int)($user['id'] ?? 0) : 0;
    $actorRole = $user ? (string)($user['role'] ?? 'guest') : 'guest';
    $route = (string)($context['route'] ?? ($_GET['route'] ?? ''));
    $entityType = isset($context['entity_type']) ? (string)$context['entity_type'] : null;
    $entityId = isset($context['entity_id']) ? (string)$context['entity_id'] : null;
    $meta = $context;
    unset($meta['route'], $meta['entity_type'], $meta['entity_id']);
    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    securityLog('audit_' . $action, [
        'route' => $route,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'actor_id' => $actorId > 0 ? $actorId : null,
        'actor_role' => $actorRole,
    ]);

    try {
        ensureSecurityRuntimeSchema();
        $pdo = getPDO();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (
                actor_id, actor_role, action, route, entity_type, entity_id, ip_address, user_agent, metadata_json
            ) VALUES (
                :actor_id, :actor_role, :action, :route, :entity_type, :entity_id, :ip_address, :user_agent, :metadata_json
            )');
        $stmt->execute([
            ':actor_id' => $actorId > 0 ? $actorId : null,
            ':actor_role' => $actorRole,
            ':action' => $action,
            ':route' => $route,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':ip_address' => clientIpAddress(),
            ':user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8'),
            ':metadata_json' => $metaJson,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

function transactionLog(string $eventType, array $context = []): void
{
    $user = currentUser();
    $actorId = $user ? (int)($user['id'] ?? 0) : 0;
    $actorRole = $user ? (string)($user['role'] ?? 'guest') : 'system';
    $route = (string)($context['route'] ?? ($_GET['route'] ?? ''));
    $meta = $context;
    $status = isset($meta['status']) ? (string)$meta['status'] : null;
    $amount = isset($meta['amount']) ? (int)$meta['amount'] : null;
    $entityType = isset($meta['entity_type']) ? (string)$meta['entity_type'] : null;
    $entityId = isset($meta['entity_id']) ? (string)$meta['entity_id'] : null;
    $referenceCode = isset($meta['reference_code']) ? (string)$meta['reference_code'] : null;
    $note = isset($meta['note']) ? (string)$meta['note'] : null;
    unset($meta['status'], $meta['amount'], $meta['entity_type'], $meta['entity_id'], $meta['reference_code'], $meta['note'], $meta['route']);
    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    try {
        ensureSecurityRuntimeSchema();
        $pdo = getPDO();
        $stmt = $pdo->prepare('INSERT INTO security_transaction_logs (
                actor_id, actor_role, event_type, status, amount, entity_type, entity_id, reference_code, note, route, ip_address, metadata_json
            ) VALUES (
                :actor_id, :actor_role, :event_type, :status, :amount, :entity_type, :entity_id, :reference_code, :note, :route, :ip_address, :metadata_json
            )');
        $stmt->execute([
            ':actor_id' => $actorId > 0 ? $actorId : null,
            ':actor_role' => $actorRole,
            ':event_type' => $eventType,
            ':status' => $status,
            ':amount' => $amount,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':reference_code' => $referenceCode,
            ':note' => $note,
            ':route' => $route,
            ':ip_address' => clientIpAddress(),
            ':metadata_json' => $metaJson,
        ]);
    } catch (Throwable $e) {
        error_log('Transaction log failed: ' . $e->getMessage());
    }
}

function acquireWebhookIdempotencyLock(string $provider, string $eventKey, string $payloadHash = ''): bool
{
    $provider = trim(mb_strtolower($provider, 'UTF-8'));
    $eventKey = trim($eventKey);
    if ($provider === '' || $eventKey === '') {
        return true;
    }

    try {
        ensureSecurityRuntimeSchema();
        $pdo = getPDO();
        $now = date('Y-m-d H:i:s');
        $insert = $pdo->prepare('INSERT INTO webhook_idempotency (
                provider, event_key, payload_hash, hit_count, first_seen_at, last_seen_at
            ) VALUES (
                :provider, :event_key, :payload_hash, 1, :first_seen_at, :last_seen_at
            )');
        $insert->execute([
            ':provider' => $provider,
            ':event_key' => mb_substr($eventKey, 0, 191, 'UTF-8'),
            ':payload_hash' => $payloadHash !== '' ? mb_substr($payloadHash, 0, 64, 'UTF-8') : null,
            ':first_seen_at' => $now,
            ':last_seen_at' => $now,
        ]);
        return true;
    } catch (PDOException $e) {
        try {
            $pdo = getPDO();
            $update = $pdo->prepare('UPDATE webhook_idempotency
                SET hit_count = hit_count + 1,
                    last_seen_at = :last_seen_at
                WHERE provider = :provider AND event_key = :event_key');
            $update->execute([
                ':last_seen_at' => date('Y-m-d H:i:s'),
                ':provider' => $provider,
                ':event_key' => mb_substr($eventKey, 0, 191, 'UTF-8'),
            ]);
        } catch (Throwable $inner) {
            error_log('Webhook idempotency update failed: ' . $inner->getMessage());
        }
        return false;
    } catch (Throwable $e) {
        error_log('Webhook idempotency failed: ' . $e->getMessage());
        return true;
    }
}

function staffScopeForUser(int $staffUserId): ?array
{
    if ($staffUserId <= 0) {
        return null;
    }

    try {
        ensureSecurityRuntimeSchema();
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT *
            FROM staff_scopes
            WHERE staff_user_id = :staff_user_id AND status = :status
            ORDER BY id ASC
            LIMIT 1');
        $stmt->execute([
            ':staff_user_id' => $staffUserId,
            ':status' => 'active',
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('Load staff scope failed: ' . $e->getMessage());
        return null;
    }
}

function staffPermissionDefaults(): array
{
    return [
        'lead_view' => 1,
        'lead_manage' => 1,
        'room_manage' => 1,
        'invoice_manage' => 1,
        'deposit_manage' => 1,
    ];
}

function normalizedStaffPermissions(?array $scope): array
{
    $defaults = staffPermissionDefaults();
    if (!$scope) {
        return array_map(static function ($value): int {
            return 0;
        }, $defaults);
    }

    $json = trim((string)($scope['permissions_json'] ?? ''));
    if ($json === '') {
        return $defaults;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    foreach ($defaults as $key => $fallback) {
        if (!array_key_exists($key, $decoded)) {
            $decoded[$key] = $fallback;
        }
        $decoded[$key] = !empty($decoded[$key]) ? 1 : 0;
    }

    return $decoded;
}

function staffHasPermission(string $permissionKey): bool
{
    $user = currentUser();
    if (!$user || (string)($user['role'] ?? '') !== 'staff') {
        return true;
    }

    $scope = staffScopeForUser((int)$user['id']);
    if (!$scope) {
        return false;
    }

    $permissions = normalizedStaffPermissions($scope);
    return !empty($permissions[$permissionKey]);
}

function ensureRoomLeadPriceColumns(PDO $pdo): void
{
    $cols = [
        'lead_price_expect' => 'INT NULL',
        'lead_price_suggest' => 'INT NULL',
        'lead_price_final' => 'INT NULL',
        'lead_price_admin' => 'INT NULL',
    ];
    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE rooms ADD COLUMN $col $def");
        } catch (PDOException $e) {
            // ignore if exists
        }
    }
}

function ensureLeadTenantColumn(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE leads ADD COLUMN tenant_id INT NULL");
    } catch (PDOException $e) {
        // ignore if exists
    }
}

function initializeSqlite(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT UNIQUE NOT NULL,
        password TEXT,
        avatar TEXT,
        hometown TEXT,
        birthdate TEXT,
        zalo_id TEXT UNIQUE,
        zalo_avatar TEXT,
        zalo_verified_at TEXT,
        phone_verified INTEGER DEFAULT 0,
        role TEXT DEFAULT "tenant",
        status TEXT DEFAULT "active",
        landlord_points INTEGER DEFAULT 0,
        landlord_level INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        price INTEGER NOT NULL,
        lead_price_expect INTEGER,
        lead_price_suggest INTEGER,
        lead_price_final INTEGER,
        lead_price_admin INTEGER,
        area TEXT NOT NULL,
        address TEXT NOT NULL,
        landlord_id INTEGER NOT NULL,
        description TEXT,
        thumbnail TEXT,
        image1 TEXT,
        image2 TEXT,
        image3 TEXT,
        image4 TEXT,
        image5 TEXT,
        image6 TEXT,
        image7 TEXT,
        image8 TEXT,
        video_url TEXT,
        electric_price INTEGER,
        water_price INTEGER,
        closed_room INTEGER DEFAULT 0,
        shared_owner INTEGER DEFAULT 0,
        boost_until TEXT,
        status TEXT DEFAULT "pending",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE tenant_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        area TEXT,
        price_min INTEGER,
        price_max INTEGER,
        people_count INTEGER,
        note TEXT,
        gender TEXT DEFAULT "any",
        room_image TEXT,
        room_image2 TEXT,
        room_image3 TEXT,
        status TEXT DEFAULT "pending",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tenant_id INTEGER,
        tenant_post_id INTEGER,
        room_id INTEGER,
        tenant_name TEXT NOT NULL,
        tenant_phone TEXT NOT NULL,
        price INTEGER,
        status TEXT DEFAULT "new",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        opened_at TEXT,
        contact_attempted INTEGER DEFAULT 0,
        notification_read_at TEXT,
        reminded_at TEXT,
        min_price INTEGER,
        max_price INTEGER,
        province TEXT,
        district TEXT,
        ward TEXT,
        time_slot TEXT
    );');

    $pdo->exec('CREATE TABLE lead_purchases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        landlord_id INTEGER NOT NULL,
        price INTEGER NOT NULL,
        status TEXT DEFAULT "pending",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(lead_id, landlord_id)
    );');

    $pdo->exec('CREATE TABLE chats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        landlord_id INTEGER NOT NULL,
        tenant_id INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(lead_id, landlord_id)
    );');

    $pdo->exec('CREATE TABLE messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id INTEGER,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER,
        is_broadcast INTEGER DEFAULT 0,
        content TEXT,
        message TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE violations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        reason TEXT,
        penalty_until TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        landlord_id INTEGER,
        lead_id INTEGER,
        amount INTEGER,
        payment_code TEXT UNIQUE,
        type TEXT DEFAULT "lead",
        status TEXT DEFAULT "pending",
        provider TEXT,
        provider_ref TEXT,
        expires_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE lead_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        action TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE seek_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        province TEXT,
        district TEXT,
        ward TEXT,
        address TEXT,
        people_count INTEGER,
        status TEXT DEFAULT "pending",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE room_boosts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        landlord_id INTEGER NOT NULL,
        room_id INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE seek_purchases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        landlord_id INTEGER NOT NULL,
        post_id INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE cta_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT,
        province TEXT,
        message TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE TABLE push_subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        endpoint_hash TEXT NOT NULL UNIQUE,
        endpoint TEXT NOT NULL,
        p256dh TEXT NOT NULL,
        auth TEXT NOT NULL,
        user_agent TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');

    $pdo->exec('CREATE INDEX idx_leads_room_id ON leads(room_id);');
    $pdo->exec('CREATE INDEX idx_leads_tenant_post ON leads(tenant_post_id);');
    $pdo->exec('CREATE INDEX idx_rooms_landlord_id ON rooms(landlord_id);');
    $pdo->exec('CREATE INDEX idx_tenant_posts_area_price ON tenant_posts(area, price_min, price_max);');
    $pdo->exec('CREATE INDEX idx_push_user_id ON push_subscriptions(user_id);');

    $hash = '$2y$10$zNUZ15NNvAXApZgM.0n8qeNQwYBjPnbpdYLcUriAIBXR8M5G0H1i.'; // password 123456
    $pdo->exec("INSERT INTO users (name, phone, password, role) VALUES
        ('Chủ trọ A', '0909123456', '$hash', 'landlord'),
        ('Chủ trọ B', '0911222333', '$hash', 'landlord'),
        ('Admin', '0999999999', '$hash', 'admin');");

    $pdo->exec("INSERT INTO rooms (title, price, area, address, landlord_id, description, thumbnail) VALUES
        ('Phòng studio trung tâm TP Thanh Hóa', 3500000, 'TP Thanh Hóa', '25 Trần Phú, P. Điện Biên, TP Thanh Hóa', 1, 'Studio 25m2, nội thất cơ bản, gần Big C.', 'https://images.unsplash.com/photo-1505691938895-1758d7feb511'),
        ('Phòng gần Đại học Hồng Đức', 2800000, 'TP Thanh Hóa', '99 Nguyễn Trãi, P. Tân Sơn, TP Thanh Hóa', 1, 'Phòng 22m2, có cửa sổ, giờ giấc tự do, tiện sinh viên.', 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85'),
        ('Phòng giá rẻ Sầm Sơn', 1800000, 'Sầm Sơn', '12 Lê Lợi, P. Bắc Sơn, TP Sầm Sơn', 2, 'Phòng 18m2, đi bộ 7 phút ra biển, tối đa 2 người.', 'https://images.unsplash.com/photo-1484154218962-a197022b5858');");
}

function migrateSqlite(PDO $pdo): void
{
    $cols = $pdo->query('PRAGMA table_info(users)')->fetchAll();
    $names = array_column($cols, 'name');
    if (!in_array('password', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN password TEXT');
    }
    if (!in_array('role', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN role TEXT DEFAULT "tenant"');
        $pdo->exec('UPDATE users SET role = "tenant" WHERE role IS NULL');
    }
    if (!in_array('status', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN status TEXT DEFAULT "active"');
    }
    if (!in_array('landlord_points', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN landlord_points INTEGER DEFAULT 0');
    }
    if (!in_array('landlord_level', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN landlord_level INTEGER DEFAULT 0');
    }
    if (!in_array('phone_verified', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN phone_verified INTEGER DEFAULT 0');
    }
    if (!in_array('zalo_id', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN zalo_id TEXT');
        try { $pdo->exec('CREATE UNIQUE INDEX idx_users_zalo_id ON users(zalo_id)'); } catch (PDOException $e) { /* ignore */ }
    }
    if (!in_array('zalo_avatar', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN zalo_avatar TEXT');
    }
    if (!in_array('zalo_verified_at', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN zalo_verified_at TEXT');
    }
    if (!in_array('avatar', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN avatar TEXT');
    }
    if (!in_array('hometown', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN hometown TEXT');
    }
    if (!in_array('birthdate', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN birthdate TEXT');
    }

    $hasSeek = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='seek_posts'")->fetchColumn();
    if (!$hasSeek) {
        $pdo->exec('CREATE TABLE seek_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            province TEXT,
            district TEXT,
            ward TEXT,
            address TEXT,
            people_count INTEGER,
            status TEXT DEFAULT "pending",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    } else {
        $colsSeek = $pdo->query('PRAGMA table_info(seek_posts)')->fetchAll();
        $namesSeek = array_column($colsSeek, 'name');
        if (!in_array('status', $namesSeek, true)) {
            $pdo->exec('ALTER TABLE seek_posts ADD COLUMN status TEXT DEFAULT "pending"');
        }
        if (!in_array('people_count', $namesSeek, true)) {
            $pdo->exec('ALTER TABLE seek_posts ADD COLUMN people_count INTEGER');
        }
    }

    $hasBoost = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='room_boosts'")->fetchColumn();
    if (!$hasBoost) {
        $pdo->exec('CREATE TABLE room_boosts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landlord_id INTEGER NOT NULL,
            room_id INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');
    }
    // best effort: ensure landlords keep role
    $pdo->exec('UPDATE users SET role = "landlord" WHERE role IS NULL AND phone IN ("0909123456","0911222333")');
    $hash = '$2y$10$zNUZ15NNvAXApZgM.0n8qeNQwYBjPnbpdYLcUriAIBXR8M5G0H1i.'; // 123456
    $pdo->exec("UPDATE users SET password = '$hash' WHERE (password IS NULL OR password = '') AND phone IN ('0909123456','0911222333')");
    // seed admin if missing
    $exists = $pdo->prepare('SELECT id FROM users WHERE phone = :p LIMIT 1');
    $exists->execute([':p' => '0999999999']);
    if (!$exists->fetch()) {
        $ins = $pdo->prepare('INSERT INTO users (name, phone, password, role) VALUES (:n,:p,:pw,:r)');
        $ins->execute([':n' => 'Admin', ':p' => '0999999999', ':pw' => $hash, ':r' => 'admin']);
    }

    $roomCols = $pdo->query('PRAGMA table_info(rooms)')->fetchAll();
    $roomNames = array_column($roomCols, 'name');
    if (!in_array('electric_price', $roomNames, true)) {
        $pdo->exec('ALTER TABLE rooms ADD COLUMN electric_price INTEGER');
    }
    if (!in_array('water_price', $roomNames, true)) {
        $pdo->exec('ALTER TABLE rooms ADD COLUMN water_price INTEGER');
    }
    if (!in_array('shared_owner', $roomNames, true)) {
        $pdo->exec('ALTER TABLE rooms ADD COLUMN shared_owner INTEGER DEFAULT 0');
    }
    if (!in_array('closed_room', $roomNames, true)) {
        $pdo->exec('ALTER TABLE rooms ADD COLUMN closed_room INTEGER DEFAULT 0');
    }
    foreach (['image1','image2','image3','image4','image5','image6','image7','image8'] as $col) {
        if (!in_array($col, $roomNames, true)) {
            $pdo->exec("ALTER TABLE rooms ADD COLUMN $col TEXT");
        }
    }
    if (!in_array('video_url', $roomNames, true)) {
        $pdo->exec('ALTER TABLE rooms ADD COLUMN video_url TEXT');
    }
    if (!in_array('boost_until', $roomNames, true)) {
        $pdo->exec('ALTER TABLE rooms ADD COLUMN boost_until TEXT');
    }
    // ensure lead price columns always exist (older DBs may miss them)
    ensureRoomLeadPriceColumns($pdo);

    $leadCols = $pdo->query('PRAGMA table_info(leads)')->fetchAll();
    $leadNames = array_column($leadCols, 'name');
    if (!in_array('tenant_post_id', $leadNames, true)) {
        $pdo->exec('ALTER TABLE leads ADD COLUMN tenant_post_id INTEGER');
    }
    if (!in_array('reminded_at', $leadNames, true)) {
        $pdo->exec('ALTER TABLE leads ADD COLUMN reminded_at TEXT');
    }
    if (!in_array('notification_read_at', $leadNames, true)) {
        $pdo->exec('ALTER TABLE leads ADD COLUMN notification_read_at TEXT');
    }
    foreach (['price INTEGER','min_price INTEGER','max_price INTEGER','province TEXT','district TEXT','ward TEXT','time_slot TEXT'] as $col) {
        $colName = explode(' ', $col)[0];
        if (!in_array($colName, $leadNames, true)) {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col");
        }
    }
    $pdo->exec("UPDATE leads SET status = 'sold' WHERE status = 'paid'");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_room_id ON leads(room_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_tenant_post ON leads(tenant_post_id)');
    ensureLeadTenantColumn($pdo);

    // tenant_posts
    $hasTenantPosts = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tenant_posts'")->fetchColumn();
    if (!$hasTenantPosts) {
        $pdo->exec('CREATE TABLE tenant_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            area TEXT,
            price_min INTEGER,
            price_max INTEGER,
            people_count INTEGER,
            note TEXT,
            status TEXT DEFAULT "pending",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');
    }

    // lead_purchases
    $hasLeadPurchases = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='lead_purchases'")->fetchColumn();
    if (!$hasLeadPurchases) {
        $pdo->exec('CREATE TABLE lead_purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER NOT NULL,
            landlord_id INTEGER NOT NULL,
            price INTEGER NOT NULL,
            status TEXT DEFAULT "pending",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    }

    // chats
    $hasChats = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='chats'")->fetchColumn();
    if (!$hasChats) {
        $pdo->exec('CREATE TABLE chats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER NOT NULL,
            landlord_id INTEGER NOT NULL,
            tenant_id INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    }

    // messages
    $hasMessages = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='messages'")->fetchColumn();
    if (!$hasMessages) {
        $pdo->exec('CREATE TABLE messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER NOT NULL,
            sender_id INTEGER NOT NULL,
            message TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    }

    // violations
    $hasViolations = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='violations'")->fetchColumn();
    if (!$hasViolations) {
        $pdo->exec('CREATE TABLE violations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reason TEXT,
            penalty_until TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    }

    // payments add user_id if missing
    $payCols = $pdo->query('PRAGMA table_info(payments)')->fetchAll();
    $payNames = array_column($payCols, 'name');
    if (!in_array('user_id', $payNames, true)) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN user_id INTEGER');
    }
    if (!in_array('payment_code', $payNames, true)) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN payment_code TEXT');
        try { $pdo->exec('CREATE UNIQUE INDEX idx_payments_code ON payments(payment_code)'); } catch (PDOException $e) { /* ignore */ }
    }
    if (!in_array('provider_ref', $payNames, true)) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN provider_ref TEXT');
    }
    if (!in_array('expires_at', $payNames, true)) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN expires_at TEXT');
    }
    $pdo->exec("UPDATE payments SET expires_at = datetime(created_at, '+15 minutes') WHERE expires_at IS NULL AND status = 'pending'");

    // tenant_posts gender + index
    $tenantExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tenant_posts'")->fetchColumn();
    if ($tenantExists) {
        $tenantCols = $pdo->query('PRAGMA table_info(tenant_posts)')->fetchAll();
        $tenantNames = array_column($tenantCols, 'name');
        if (!in_array('gender', $tenantNames, true)) {
            $pdo->exec("ALTER TABLE tenant_posts ADD COLUMN gender TEXT DEFAULT 'any'");
        }
        foreach (['room_image','room_image2','room_image3'] as $col) {
            if (!in_array($col, $tenantNames, true)) {
                $pdo->exec("ALTER TABLE tenant_posts ADD COLUMN $col TEXT");
            }
        }
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tenant_posts_area_price ON tenant_posts(area, price_min, price_max)');
    }

    $messageCols = $pdo->query('PRAGMA table_info(messages)')->fetchAll();
    $messageNames = array_column($messageCols, 'name');
    foreach (['receiver_id INTEGER','is_broadcast INTEGER DEFAULT 0','content TEXT'] as $col) {
        $colName = explode(' ', $col)[0];
        if (!in_array($colName, $messageNames, true)) {
            $pdo->exec("ALTER TABLE messages ADD COLUMN $col");
        }
    }

    // unique constraints for purchases & chats
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_lead_purchases ON lead_purchases(lead_id, landlord_id)');
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_chats_lead_landlord ON chats(lead_id, landlord_id)');

    if ($hasSeek) {
        $colsSeek = $pdo->query('PRAGMA table_info(seek_posts)')->fetchAll();
        $namesSeek = array_column($colsSeek, 'name');
        if (!in_array('status', $namesSeek, true)) {
            $pdo->exec('ALTER TABLE seek_posts ADD COLUMN status TEXT DEFAULT "pending"');
        }
    }

    $hasSeekPurchase = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='seek_purchases'")->fetchColumn();
    if (!$hasSeekPurchase) {
        $pdo->exec('CREATE TABLE seek_purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landlord_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    }
    $hasCtaMessages = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cta_messages'")->fetchColumn();
    if (!$hasCtaMessages) {
        $pdo->exec('CREATE TABLE cta_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            province TEXT,
            message TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        endpoint_hash TEXT NOT NULL UNIQUE,
        endpoint TEXT NOT NULL,
        p256dh TEXT NOT NULL,
        auth TEXT NOT NULL,
        user_agent TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_push_user_id ON push_subscriptions(user_id)');
    $pushCols = $pdo->query('PRAGMA table_info(push_subscriptions)')->fetchAll();
    $pushNames = array_column($pushCols, 'name');
    if (!in_array('endpoint_hash', $pushNames, true)) {
        $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN endpoint_hash TEXT NOT NULL DEFAULT ''");
        $rows = $pdo->query("SELECT id, endpoint FROM push_subscriptions WHERE endpoint_hash = '' OR endpoint_hash IS NULL")->fetchAll();
        if (!empty($rows)) {
            $upd = $pdo->prepare('UPDATE push_subscriptions SET endpoint_hash = :h WHERE id = :id');
            foreach ($rows as $row) {
                $endpoint = (string)($row['endpoint'] ?? '');
                if ($endpoint === '') continue;
                $upd->execute([
                    ':h' => hash('sha256', $endpoint),
                    ':id' => (int)$row['id'],
                ]);
            }
        }
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_push_endpoint_hash ON push_subscriptions(endpoint_hash)');
    }
    foreach (['endpoint TEXT', 'p256dh TEXT', 'auth TEXT'] as $col) {
        $colName = explode(' ', $col)[0];
        if (!in_array($colName, $pushNames, true)) {
            $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN $col");
        }
    }
    if (!in_array('updated_at', $pushNames, true)) {
        $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN updated_at TEXT DEFAULT CURRENT_TIMESTAMP");
    }
    // payments: ensure payment_code
    $payCols = array_column($pdo->query('PRAGMA table_info(payments)')->fetchAll(), 'name');
    if (!in_array('payment_code', $payCols, true)) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN payment_code TEXT');
        try { $pdo->exec('CREATE UNIQUE INDEX idx_payments_code ON payments(payment_code)'); } catch (PDOException $e) { /* ignore */ }
    }
    if (!in_array('expires_at', $payCols, true)) {
        $pdo->exec('ALTER TABLE payments ADD COLUMN expires_at TEXT');
    }
    $pdo->exec("UPDATE payments SET expires_at = datetime(created_at, '+15 minutes') WHERE expires_at IS NULL AND status = 'pending'");
}

function migrateMysql(PDO $pdo): void
{
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasUsers = in_array('users', $tables, true);
    $hasRooms = in_array('rooms', $tables, true);
    $hasLeads = in_array('leads', $tables, true);
    $hasPayments = in_array('payments', $tables, true);
    $hasTenantPosts = in_array('tenant_posts', $tables, true);
    $hasLeadPurchases = in_array('lead_purchases', $tables, true);
    $hasChats = in_array('chats', $tables, true);
    $hasMessages = in_array('messages', $tables, true);
    $hasViolations = in_array('violations', $tables, true);

    if (!$hasUsers) {
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(30) UNIQUE NOT NULL,
            password VARCHAR(255),
            avatar VARCHAR(255) NULL,
            hometown VARCHAR(120) NULL,
            birthdate DATE NULL,
            zalo_id VARCHAR(64) UNIQUE,
            zalo_avatar VARCHAR(255),
            zalo_verified_at DATETIME NULL,
            phone_verified TINYINT(1) DEFAULT 0,
            role ENUM('tenant','landlord','staff','admin') DEFAULT 'tenant',
            status ENUM('active','locked') DEFAULT 'active',
            landlord_points INT DEFAULT 0,
            landlord_level TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    // ensure Zalo columns exist
    try { $pdo->exec("ALTER TABLE users ADD COLUMN zalo_id VARCHAR(64) UNIQUE"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN zalo_avatar VARCHAR(255) NULL"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN zalo_verified_at DATETIME NULL"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN phone_verified TINYINT(1) DEFAULT 0"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN hometown VARCHAR(120) NULL"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN birthdate DATE NULL"); } catch (PDOException $e) { /* ignore */ }

    if (!$hasRooms) {
        $pdo->exec("CREATE TABLE rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            price INT NOT NULL,
            lead_price_expect INT NULL,
            lead_price_suggest INT NULL,
            lead_price_final INT NULL,
            lead_price_admin INT NULL,
            area VARCHAR(100) NOT NULL,
            address VARCHAR(255) NOT NULL,
            landlord_id INT NOT NULL,
            description TEXT,
            thumbnail VARCHAR(255),
            image1 VARCHAR(255),
            image2 VARCHAR(255),
            image3 VARCHAR(255),
            image4 VARCHAR(255),
            image5 VARCHAR(255),
            image6 VARCHAR(255),
            image7 VARCHAR(255),
            image8 VARCHAR(255),
            video_url VARCHAR(255),
            electric_price INT NULL,
            water_price INT NULL,
            closed_room TINYINT(1) DEFAULT 0,
            shared_owner TINYINT(1) DEFAULT 0,
            boost_until TIMESTAMP NULL,
            status ENUM('pending','active','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (landlord_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    ensureRoomLeadPriceColumns($pdo);

    if (!$hasTenantPosts) {
        $pdo->exec("CREATE TABLE tenant_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            area VARCHAR(255),
            price_min INT,
            price_max INT,
            people_count INT,
            note TEXT,
            gender ENUM('male','female','any') DEFAULT 'any',
            room_image VARCHAR(255) NULL,
            room_image2 VARCHAR(255) NULL,
            room_image3 VARCHAR(255) NULL,
            status ENUM('pending','active','hidden') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $hasTenantPosts = true;
    }

    if (!$hasLeads) {
        $pdo->exec("CREATE TABLE leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NULL,
            tenant_post_id INT NULL,
            room_id INT NULL,
            tenant_name VARCHAR(120) NOT NULL,
            tenant_phone VARCHAR(30) NOT NULL,
            price INT NULL,
            status ENUM('new','opened','contacted','closed','invalid','sold','used','paid') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            opened_at TIMESTAMP NULL,
            contact_attempted TINYINT DEFAULT 0,
            notification_read_at TIMESTAMP NULL,
            reminded_at TIMESTAMP NULL,
            min_price INT NULL,
            max_price INT NULL,
            province VARCHAR(120) NULL,
            district VARCHAR(120) NULL,
            ward VARCHAR(120) NULL,
            time_slot VARCHAR(50) NULL,
            INDEX idx_room(room_id),
            INDEX idx_tenant_post(tenant_post_id),
            FOREIGN KEY (room_id) REFERENCES rooms(id),
            FOREIGN KEY (tenant_post_id) REFERENCES tenant_posts(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    // add tenant_id column to leads if missing
    try { $pdo->exec("ALTER TABLE leads ADD COLUMN tenant_id INT NULL"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE leads ADD COLUMN contact_attempted TINYINT DEFAULT 0"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE leads ADD COLUMN notification_read_at TIMESTAMP NULL"); } catch (PDOException $e) { /* ignore */ }

    if (!$hasPayments) {
        $pdo->exec("CREATE TABLE payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            landlord_id INT NULL,
            lead_id INT NULL,
            amount INT,
            payment_code VARCHAR(64) UNIQUE,
            type ENUM('lead','package','other') DEFAULT 'lead',
            status ENUM('pending','paid','failed') DEFAULT 'pending',
            provider VARCHAR(50),
            provider_ref VARCHAR(120),
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (landlord_id) REFERENCES users(id),
            FOREIGN KEY (lead_id) REFERENCES leads(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    try { $pdo->exec("ALTER TABLE payments ADD COLUMN payment_code VARCHAR(64) UNIQUE"); } catch (PDOException $e) { /* ignore */ }
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lead (lead_id),
        FOREIGN KEY (lead_id) REFERENCES leads(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!$hasTenantPosts) {
        $pdo->exec("CREATE TABLE tenant_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            area VARCHAR(255),
            price_min INT,
            price_max INT,
            people_count INT,
            note TEXT,
            gender ENUM('male','female','any') DEFAULT 'any',
            room_image VARCHAR(255) NULL,
            room_image2 VARCHAR(255) NULL,
            room_image3 VARCHAR(255) NULL,
            status ENUM('pending','active','hidden') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$hasLeadPurchases) {
        $pdo->exec("CREATE TABLE lead_purchases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            landlord_id INT NOT NULL,
            price INT NOT NULL,
            status ENUM('pending','paid','failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_lead_landlord (lead_id, landlord_id),
            FOREIGN KEY (lead_id) REFERENCES leads(id),
            FOREIGN KEY (landlord_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$hasChats) {
        $pdo->exec("CREATE TABLE chats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            landlord_id INT NOT NULL,
            tenant_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_chat_lead_landlord (lead_id, landlord_id),
            FOREIGN KEY (lead_id) REFERENCES leads(id),
            FOREIGN KEY (landlord_id) REFERENCES users(id),
            FOREIGN KEY (tenant_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$hasMessages) {
        $pdo->exec("CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chat_id INT NULL,
            sender_id INT NOT NULL,
            receiver_id INT NULL,
            is_broadcast TINYINT(1) DEFAULT 0,
            content TEXT NULL,
            message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chat_id) REFERENCES chats(id),
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$hasViolations) {
        $pdo->exec("CREATE TABLE violations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            reason VARCHAR(255),
            penalty_until DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('password', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL");
    }
    if (!in_array('role', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('tenant','landlord','staff','admin') DEFAULT 'tenant'");
    }
    if (!in_array('status', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','locked') DEFAULT 'active'");
    }
    if (!in_array('landlord_points', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN landlord_points INT DEFAULT 0");
    }
    if (!in_array('landlord_level', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN landlord_level TINYINT DEFAULT 0");
    }
    if (!in_array('avatar', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL");
    }
    if (!in_array('hometown', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN hometown VARCHAR(120) NULL");
    }
    if (!in_array('birthdate', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN birthdate DATE NULL");
    }

    $hasSeek = in_array('seek_posts', $tables, true);
    if (!$hasSeek) {
        $pdo->exec("CREATE TABLE seek_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            province VARCHAR(120),
            district VARCHAR(120),
            ward VARCHAR(120),
            address VARCHAR(255),
            people_count INT NULL,
            status ENUM('pending','active','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    $hasBoost = in_array('room_boosts', $tables, true);
    if (!$hasBoost) {
        $pdo->exec("CREATE TABLE room_boosts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            landlord_id INT NOT NULL,
            room_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (landlord_id) REFERENCES users(id),
            FOREIGN KEY (room_id) REFERENCES rooms(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    $roomCols = $pdo->query("SHOW COLUMNS FROM rooms")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('electric_price', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN electric_price INT NULL");
    }
    if (!in_array('water_price', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN water_price INT NULL");
    }
    if (!in_array('shared_owner', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN shared_owner TINYINT(1) DEFAULT 0");
    }
    if (!in_array('closed_room', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN closed_room TINYINT(1) DEFAULT 0");
    }
    if (!in_array('image1', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN image1 VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE rooms ADD COLUMN image2 VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE rooms ADD COLUMN image3 VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE rooms ADD COLUMN image4 VARCHAR(255) NULL");
    }
    foreach (['image5','image6','image7','image8'] as $col) {
        if (!in_array($col, $roomCols, true)) {
            $pdo->exec("ALTER TABLE rooms ADD COLUMN $col VARCHAR(255) NULL");
        }
    }
    if (!in_array('video_url', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN video_url VARCHAR(255) NULL");
    }
    if (!in_array('boost_until', $roomCols, true)) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN boost_until TIMESTAMP NULL");
    }

    $leadCols = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('reminded_at', $leadCols, true)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN reminded_at TIMESTAMP NULL");
    }
    if (!in_array('notification_read_at', $leadCols, true)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN notification_read_at TIMESTAMP NULL");
    }
    foreach (['price INT NULL','min_price INT NULL','max_price INT NULL','province VARCHAR(120) NULL','district VARCHAR(120) NULL','ward VARCHAR(120) NULL','time_slot VARCHAR(50) NULL','tenant_post_id INT NULL'] as $col) {
        $colName = explode(' ', $col)[0];
        if (!in_array($colName, $leadCols, true)) {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col");
        }
    }
    // Cho phép room_id nullable để map lead với bài tìm
    $roomCol = $pdo->query("SHOW COLUMNS FROM leads LIKE 'room_id'")->fetch(PDO::FETCH_ASSOC);
    if ($roomCol && ($roomCol['Null'] ?? '') === 'NO') {
        try { $pdo->exec("ALTER TABLE leads MODIFY room_id INT NULL"); } catch (PDOException $e) { /* ignore */ }
    }
    // Chuan hoa ENUM status theo cac trang thai app dang ghi.
    try { $pdo->exec("ALTER TABLE leads MODIFY status ENUM('new','opened','contacted','closed','invalid','sold','used','paid') DEFAULT 'new'"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("UPDATE leads SET status = 'sold' WHERE status = 'paid'"); } catch (PDOException $e) { /* ignore */ }
    // Thêm FK và index cho tenant_post_id nếu chưa có
    try { $pdo->exec("CREATE INDEX idx_lead_tenant_post ON leads(tenant_post_id)"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE leads ADD CONSTRAINT fk_leads_tenant_post FOREIGN KEY (tenant_post_id) REFERENCES tenant_posts(id)"); } catch (PDOException $e) { /* ignore */ }

    // Payments: ensure user_id exists and defaults align
    $payCols = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('user_id', $payCols, true)) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN user_id INT NULL AFTER id");
    }
    if (!in_array('payment_code', $payCols, true)) {
        try { $pdo->exec("ALTER TABLE payments ADD COLUMN payment_code VARCHAR(64) UNIQUE"); } catch (PDOException $e) { /* ignore */ }
    }
    if (!in_array('provider_ref', $payCols, true)) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN provider_ref VARCHAR(120) NULL");
    }
    if (!in_array('expires_at', $payCols, true)) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN expires_at DATETIME NULL");
    }
    try { $pdo->exec("UPDATE payments SET expires_at = DATE_ADD(created_at, INTERVAL 15 MINUTE) WHERE expires_at IS NULL AND status = 'pending'"); } catch (PDOException $e) { /* ignore */ }
    // set status default pending if possible
    try { $pdo->exec("ALTER TABLE payments MODIFY status ENUM('pending','paid','failed') DEFAULT 'pending'"); } catch (PDOException $e) { /* ignore */ }

    // tenant_posts: gender + index
    if ($hasTenantPosts) {
        $tenantCols = $pdo->query("SHOW COLUMNS FROM tenant_posts")->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!in_array('gender', $tenantCols, true)) {
            $pdo->exec("ALTER TABLE tenant_posts ADD COLUMN gender ENUM('male','female','any') DEFAULT 'any'");
        }
        foreach (['room_image','room_image2','room_image3'] as $col) {
            if (!in_array($col, $tenantCols, true)) {
                $pdo->exec("ALTER TABLE tenant_posts ADD COLUMN $col VARCHAR(255) NULL");
            }
        }
        try { $pdo->exec("CREATE INDEX idx_tenant_posts_area_price ON tenant_posts(area, price_min, price_max)"); } catch (PDOException $e) { /* ignore duplicate */ }
    }

    $msgCols = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('receiver_id', $msgCols, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN receiver_id INT NULL");
    }
    if (!in_array('is_broadcast', $msgCols, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN is_broadcast TINYINT(1) DEFAULT 0");
    }
    if (!in_array('content', $msgCols, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN content TEXT NULL");
    }
    try {
        $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
        $fkStmt = $pdo->prepare("SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME = 'messages'
              AND COLUMN_NAME = 'chat_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL");
        $fkStmt->execute([':db' => $dbName]);
        foreach ($fkStmt->fetchAll(PDO::FETCH_COLUMN, 0) as $fkName) {
            $safeFk = str_replace('`', '``', (string)$fkName);
            $pdo->exec("ALTER TABLE messages DROP FOREIGN KEY `$safeFk`");
        }
    } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE messages MODIFY chat_id INT NULL"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_messages_chat FOREIGN KEY (chat_id) REFERENCES chats(id)"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id)"); } catch (PDOException $e) { /* ignore */ }

    // lead_purchases unique per lead+landlord
    if ($hasLeadPurchases) {
        try { $pdo->exec("CREATE UNIQUE INDEX uniq_lead_purchases ON lead_purchases(lead_id, landlord_id)"); } catch (PDOException $e) { /* ignore */ }
    }
    // chats unique per lead+landlord
    if ($hasChats) {
        try { $pdo->exec("CREATE UNIQUE INDEX uniq_chats_lead_landlord ON chats(lead_id, landlord_id)"); } catch (PDOException $e) { /* ignore */ }
    }

    $seekCols = $pdo->query("SHOW COLUMNS FROM seek_posts")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('status', $seekCols, true)) {
        $pdo->exec("ALTER TABLE seek_posts ADD COLUMN status ENUM('pending','active','rejected') DEFAULT 'pending'");
    }
    if (!in_array('people_count', $seekCols, true)) {
        $pdo->exec("ALTER TABLE seek_posts ADD COLUMN people_count INT NULL");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS cta_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        email VARCHAR(160) DEFAULT NULL,
        province VARCHAR(120) DEFAULT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        endpoint_hash CHAR(64) NOT NULL,
        endpoint TEXT NOT NULL,
        p256dh VARCHAR(255) NOT NULL,
        auth VARCHAR(255) NOT NULL,
        user_agent VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_push_endpoint_hash (endpoint_hash),
        KEY idx_push_user_id (user_id),
        CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN endpoint_hash CHAR(64) NOT NULL DEFAULT '' AFTER user_id"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN endpoint TEXT NOT NULL AFTER endpoint_hash"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN p256dh VARCHAR(255) NOT NULL AFTER endpoint"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN auth VARCHAR(255) NOT NULL AFTER p256dh"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN user_agent VARCHAR(255) NULL AFTER auth"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("UPDATE push_subscriptions SET endpoint_hash = SHA2(endpoint, 256) WHERE endpoint_hash = '' AND endpoint <> ''"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("CREATE UNIQUE INDEX uniq_push_endpoint_hash ON push_subscriptions(endpoint_hash)"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("CREATE INDEX idx_push_user_id ON push_subscriptions(user_id)"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("ALTER TABLE push_subscriptions ADD CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"); } catch (PDOException $e) { /* ignore */ }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value === null) {
        if (isset($_SESSION['_flash'][$key])) {
            $msg = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }
        return null;
    }

    $_SESSION['_flash'][$key] = $value;
    return null;
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . routeUrl($route, $params));
    exit;
}


function render(string $view, array $data = []): void
{
    $viewPath = __DIR__ . '/../views/' . $view . '.php';
    if (!file_exists($viewPath)) {
        http_response_code(500);
        echo 'View not found';
        exit;
    }

    extract($data, EXTR_OVERWRITE);
    ob_start();
    include $viewPath;
    $content = ob_get_clean();
    include __DIR__ . '/../views/base.php';
}

function uploadsPath(): string
{
    $dir = __DIR__ . '/../storage/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function themeConfigPath(): string
{
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir . '/theme.json';
}

function themeConfig(): array
{
    $path = themeConfigPath();
    if (is_file($path)) {
        $json = json_decode(file_get_contents($path), true);
        if (is_array($json)) {
            return $json;
        }
    }
    return array();
}

function themeBackgroundValue(): string
{
    $config = themeConfig();
    return !empty($config['background']) ? (string)$config['background'] : 'trongdong.png';
}

function themeBackground(): string
{
    return assetUrl(themeBackgroundValue());
}

function themeBackgroundOpacity(): float
{
    $config = themeConfig();
    $opacity = isset($config['opacity']) ? (float)$config['opacity'] : 0.045;
    return max(0.0, min(0.25, $opacity));
}

function saveThemeBackground(string $value, ?float $opacity = null): void
{
    $path = themeConfigPath();
    $config = themeConfig();
    $config['background'] = $value;
    if ($opacity !== null) {
        $config['opacity'] = max(0.0, min(0.25, $opacity));
    } elseif (!isset($config['opacity'])) {
        $config['opacity'] = themeBackgroundOpacity();
    }
    file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function currentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => (int)$_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'tenant',
    ];
}

function defaultRouteForUser(?array $user = null): string
{
    $user = $user ?? currentUser();
    if (!$user) {
        return 'rooms';
    }

    $role = (string)($user['role'] ?? '');
    if ($role === 'admin') {
        return 'admin';
    }
    if ($role === 'landlord') {
        return 'portal-landlord';
    }
    if ($role === 'staff') {
        return 'portal-landlord';
    }
    if ($role === 'tenant') {
        return 'portal-tenant';
    }
    return 'rooms';
}

function isAdmin(): bool
{
    $u = currentUser();
    return $u && ($u['role'] ?? '') === 'admin';
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_phone'] = $user['phone'];
    $_SESSION['user_role'] = $user['role'];
}

function logoutUser(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_phone'], $_SESSION['user_role']);
    session_regenerate_id(true);
}

function ensureLoggedIn(?string $role = null): array
{
    $user = currentUser();
    if (!$user) {
        redirect('login', ['redirect' => $_SERVER['REQUEST_URI'] ?? '']);
    }
    if ($role !== null && $user['role'] !== $role && $user['role'] !== 'admin') {
        flash('error', 'Bạn không có quyền truy cập.');
        redirect('login');
    }
    return $user;
}

function ensureLandlord(): int
{
    $user = ensureLoggedIn();
    if ($user['role'] === 'admin') {
        return (int)$user['id'];
    }
    if (($user['role'] ?? '') === 'staff') {
        $scope = staffScopeForUser((int)$user['id']);
        if ($scope && (int)($scope['landlord_id'] ?? 0) > 0) {
            return (int)$scope['landlord_id'];
        }
        flash('error', 'Tài khoản staff chưa được cấp phạm vi làm việc.');
        redirect('login');
    }
    if ($user['role'] !== 'landlord') {
        flash('error', 'Bạn không có quyền truy cập.');
        redirect('login');
    }
    return (int)$user['id'];
}
