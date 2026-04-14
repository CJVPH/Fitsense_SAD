<?php
/**
 * FitSense Database Configuration
 * DB connection class + all application constants
 */

// Load .env file if it exists and dotenv is not already loaded
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// ─── Application Constants ────────────────────────────────────────────────────

define('SESSION_TIMEOUT',        3600);
define('PASSWORD_MIN_LENGTH',    8);
define('MAX_LOGIN_ATTEMPTS',     5);

define('GEMINI_API_KEY',
    $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?? ''
);
define('GEMINI_API_URL',
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'
);
define('GEMINI_TIMEOUT_SECONDS', 60);

define('BASE_URL',
    $_ENV['BASE_URL'] ?? getenv('BASE_URL') ?: 'http://localhost/fitsense/'
);

// ─── Claude (Anthropic) API ───────────────────────────────────────────────────
define('CLAUDE_API_KEY',
    $_ENV['CLAUDE_API_KEY'] ?? getenv('CLAUDE_API_KEY') ?? ''
);
define('CLAUDE_MODEL',          'claude-sonnet-4-6');
define('CLAUDE_TIMEOUT_SECONDS', 60);

// ─── OpenAI API ───────────────────────────────────────────────────────────────
define('OPENAI_API_KEY',
    $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? ''
);
define('OPENAI_MODEL',          'gpt-4o');
define('OPENAI_TIMEOUT_SECONDS', 60);

// ─── SMTP Constants ───────────────────────────────────────────────────────────
define('MAIL_HOST',       $_ENV['MAIL_HOST']       ?? getenv('MAIL_HOST')       ?? 'smtp.gmail.com');
define('MAIL_PORT',  (int)($_ENV['MAIL_PORT']       ?? getenv('MAIL_PORT')       ?? 587));
define('MAIL_USERNAME',   $_ENV['MAIL_USERNAME']    ?? getenv('MAIL_USERNAME')   ?? '');
define('MAIL_PASSWORD',   $_ENV['MAIL_PASSWORD']    ?? getenv('MAIL_PASSWORD')   ?? '');
define('MAIL_FROM_NAME',  $_ENV['MAIL_FROM_NAME']   ?? getenv('MAIL_FROM_NAME')  ?? 'FitSense');
define('MAIL_FROM_EMAIL', $_ENV['MAIL_FROM_EMAIL']  ?? getenv('MAIL_FROM_EMAIL') ?? '');

// ─── Database Class ───────────────────────────────────────────────────────────

class Database {
    private static ?PDO $instance = null;

    /**
     * Returns a shared PDO connection.
     * Reads credentials from environment variables with sensible defaults.
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host   = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'fitsense_db';
            $user   = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
            $pass   = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }

    // Prevent instantiation
    private function __construct() {}
}
