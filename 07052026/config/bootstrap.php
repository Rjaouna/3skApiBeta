<?php
declare(strict_types=1);

/**
 * config/bootstrap.php
 * - Charge .env (vlucas/phpdotenv)
 * - Configure sessions (cookies sécurisés)
 * - Fournit db(), csrf_token(), auth_guard(), logout_now(), etc.
 */

$root = dirname(__DIR__);

// ===== Composer autoload =====
$autoload = $root . '/vendor/autoload.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  echo "Erreur: vendor/autoload.php introuvable. Lance composer install puis upload vendor/.";
  exit;
}
require_once $autoload;

use Dotenv\Dotenv;

// ===== .env load =====
if (file_exists($root . '/.env')) {
  Dotenv::createImmutable($root)->safeLoad();
}

/**
 * Env helpers (protégés contre redeclare)
 */
if (!function_exists('envv')) {
  function envv(string $key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') return $default;
    return $v;
  }
}

if (!function_exists('envBool')) {
  function envBool(string $key, bool $default = false): bool {
    $v = strtolower(trim((string) envv($key, '')));
    if ($v === '') return $default;
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
  }
}

// ===== Sessions hardening =====
// Auto-détection HTTPS + override via .env (SESSION_SECURE)
$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

$secure = envBool('SESSION_SECURE', $httpsOn);

$cookieParams = [
  'lifetime' => 0,
  'path'     => '/',
  'secure'   => $secure,
  'httponly' => true,
  'samesite' => (string) envv('SESSION_SAMESITE', 'Lax'),
];

session_name((string) envv('SESSION_NAME', '3sk_session'));
session_set_cookie_params($cookieParams);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
// ini_set('session.cookie_secure', $secure ? '1' : '0'); // optionnel
// ini_set('session.cookie_samesite', $cookieParams['samesite']); // optionnel selon config serveur

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// ===== PDO =====
if (!function_exists('db')) {
  function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host    = (string) envv('DB_HOST');
    $port    = (string) envv('DB_PORT', '3306');
    $name    = (string) envv('DB_NAME');
    $user    = (string) envv('DB_USER');
    $pass    = (string) envv('DB_PASS');
    $charset = (string) envv('DB_CHARSET', 'utf8mb4');

    if ($host === '' || $name === '' || $user === '') {
      throw new RuntimeException('DB config manquante dans .env (DB_HOST/DB_NAME/DB_USER).');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
  }
}

// ===== CSRF =====
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
  }
}

if (!function_exists('csrf_check')) {
  function csrf_check(?string $token): bool {
    if (!$token || empty($_SESSION['csrf'])) return false;
    return hash_equals((string) $_SESSION['csrf'], (string) $token);
  }
}

// ===== Auth helpers =====
if (!function_exists('is_logged_in')) {
  function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['sess_db_id']);
  }
}

if (!function_exists('session_bind')) {
  function session_bind(): void {
    // On bind seulement sur User-Agent (moins cassant que l'IP)
    $_SESSION['ua_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
  }
}

if (!function_exists('session_bind_ok')) {
  function session_bind_ok(): bool {
    $ua = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    return isset($_SESSION['ua_hash']) && hash_equals((string) $_SESSION['ua_hash'], $ua);
  }
}

if (!function_exists('auth_guard')) {
  function auth_guard(): void {
    if (!is_logged_in()) {
      header('Location: login.php');
      exit;
    }
    if (!session_bind_ok()) {
      header('Location: logout.php');
      exit;
    }

    // Vérifie la session côté DB
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id FROM user_sessions WHERE id = :id AND revoked_at IS NULL LIMIT 1");
    $stmt->execute([':id' => (int) $_SESSION['sess_db_id']]);

    if (!$stmt->fetch()) {
      header('Location: logout.php');
      exit;
    }

    // Keep-alive
    $pdo->prepare("UPDATE user_sessions SET last_seen_at = NOW() WHERE id = :id")
        ->execute([':id' => (int) $_SESSION['sess_db_id']]);
  }
}

if (!function_exists('logout_now')) {
  function logout_now(): void {
    try {
      if (!empty($_SESSION['sess_db_id'])) {
        db()->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE id = :id")
           ->execute([':id' => (int) $_SESSION['sess_db_id']]);
      }
    } catch (\Throwable $e) {
      // ignore
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p['path'], '', (bool)$p['secure'], (bool)$p['httponly']);
    }

    session_destroy();
  }
}