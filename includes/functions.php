<?php
/**
 * FitSense — Core Utility Library
 *
 * Provides sanitisation, password/username generation, date formatting,
 * BMI helpers, JSON/redirect helpers, flash messages, CSRF protection,
 * and input validation functions used throughout the application.
 *
 * Requirements: 1.2, 4.4, 5.3, 19.1, 19.2
 */

// ─── Input Sanitisation ───────────────────────────────────────────────────────

/**
 * Sanitise a single input value: trim whitespace, strip magic-quote slashes,
 * and encode HTML special characters to prevent XSS.
 *
 * @param  string $data Raw input value.
 * @return string       Sanitised value safe for HTML output.
 */
function sanitizeInput(string $data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

// ─── Credential Generation ────────────────────────────────────────────────────

/**
 * Generate a cryptographically random password that is guaranteed to meet
 * complexity requirements (uppercase, lowercase, digit, special character).
 *
 * @param  int    $length Desired password length (minimum 12).
 * @return string         Random password string.
 */
function generatePassword(int $length = 12): string
{
    if ($length < 12) {
        $length = 12;
    }

    $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower   = 'abcdefghijklmnopqrstuvwxyz';
    $digits  = '0123456789';
    $special = '!@#$%^&*';
    $all     = $upper . $lower . $digits . $special;

    // Guarantee at least one character from each required class.
    $password  = $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $digits[random_int(0, strlen($digits) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];

    // Fill the remainder with random characters from the full set.
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    // Shuffle so the guaranteed characters are not always at the front.
    $chars = str_split($password);
    for ($i = count($chars) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
    }

    return implode('', $chars);
}

/**
 * Generate a unique username slug from a first and last name.
 *
 * The base slug is "firstname.lastname" (lowercased, non-alphanumeric chars
 * stripped). If the slug is already taken in the `users` table a numeric
 * suffix is appended and incremented until a free slot is found.
 *
 * @param  string $firstName User's first name.
 * @param  string $lastName  User's last name.
 * @param  PDO    $pdo       Active database connection.
 * @return string            Unique username slug.
 */
function generateUsername(string $firstName, string $lastName, PDO $pdo): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $firstName))
          . '.'
          . strtolower(preg_replace('/[^a-z0-9]/i', '', $lastName));

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');

    $candidate = $base;
    $suffix    = 1;

    while (true) {
        $stmt->execute([':username' => $candidate]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $candidate;
        }
        $candidate = $base . $suffix;
        $suffix++;
    }
}

// ─── Date / Time Formatting ───────────────────────────────────────────────────

/**
 * Format a date string using the given PHP date format.
 *
 * @param  string $dateString Any date string parseable by strtotime().
 * @param  string $format     PHP date() format string.
 * @return string             Formatted date.
 */
function formatDate(string $dateString, string $format = 'd M Y'): string
{
    return date($format, strtotime($dateString));
}

/**
 * Return a human-readable relative time string for a given timestamp.
 *
 * Rules:
 *  - < 60 seconds  → "Just now"
 *  - < 60 minutes  → "X minutes ago"
 *  - < 24 hours    → "X hours ago"
 *  - yesterday     → "Yesterday"
 *  - < 7 days      → "X days ago"
 *  - otherwise     → formatted date, e.g. "15 Jan 2025"
 *
 * The function never returns a raw ISO date/time string.
 *
 * @param  string|int $timestamp Unix timestamp or date string.
 * @return string                Human-readable relative time.
 */
function formatRelativeTime($timestamp): string
{
    $time = is_numeric($timestamp) ? (int) $timestamp : strtotime((string) $timestamp);
    $now  = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    }

    if ($diff < 3600) {
        $minutes = (int) floor($diff / 60);
        return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes') . ' ago';
    }

    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours') . ' ago';
    }

    // Check for "yesterday" by comparing calendar dates.
    $todayMidnight     = strtotime('today midnight');
    $yesterdayMidnight = $todayMidnight - 86400;
    if ($time >= $yesterdayMidnight && $time < $todayMidnight) {
        return 'Yesterday';
    }

    if ($diff < 604800) { // 7 days
        $days = (int) floor($diff / 86400);
        return $days . ' ' . ($days === 1 ? 'day' : 'days') . ' ago';
    }

    return date('d M Y', $time);
}

// ─── BMI Helpers ──────────────────────────────────────────────────────────────

/**
 * Calculate Body Mass Index (BMI).
 *
 * @param  float      $weightKg Weight in kilograms.
 * @param  float      $heightCm Height in centimetres.
 * @return float|null           BMI rounded to 1 decimal place, or null if
 *                              either value is <= 0.
 */
function calculateBMI(float $weightKg, float $heightCm): ?float
{
    if ($weightKg <= 0 || $heightCm <= 0) {
        return null;
    }

    $heightM = $heightCm / 100;
    return round($weightKg / pow($heightM, 2), 1);
}

/**
 * Return the WHO BMI category label for a given BMI value.
 *
 * @param  float  $bmi Calculated BMI.
 * @return string      Category label.
 */
function getBMICategory(float $bmi): string
{
    if ($bmi < 18.5) {
        return 'Underweight';
    }
    if ($bmi < 25.0) {
        return 'Normal weight';
    }
    if ($bmi < 30.0) {
        return 'Overweight';
    }
    return 'Obese';
}

// ─── HTTP / Response Helpers ──────────────────────────────────────────────────

/**
 * Send a JSON response and terminate execution.
 *
 * @param  mixed $data       Data to encode as JSON.
 * @param  int   $statusCode HTTP status code (default 200).
 * @return never
 */
function sendJsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Store a flash message in the session and redirect to a URL.
 *
 * @param  string $url     Redirect destination.
 * @param  string $message Message text.
 * @param  string $type    Message type: 'success', 'error', or 'info'.
 * @return never
 */
function redirectWithMessage(string $url, string $message, string $type = 'info'): void
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Return an HTML string for the current flash message (if any) and clear it.
 *
 * Styling uses Tailwind utility classes with the Black/Yellow HCD theme:
 *  - success → green border
 *  - error   → red border
 *  - info    → yellow border
 *
 * @return string HTML markup, or empty string when no message is set.
 */
function displayFlashMessage(): string
{
    if (empty($_SESSION['flash_message'])) {
        return '';
    }

    $message = $_SESSION['flash_message'];
    $type    = $_SESSION['flash_type'] ?? 'info';

    unset($_SESSION['flash_message'], $_SESSION['flash_type']);

    $borderClass = match ($type) {
        'success' => 'border-green-500 text-green-300 bg-green-950',
        'error'   => 'border-red-500 text-red-300 bg-red-950',
        default   => 'border-yellow-400 text-yellow-300 bg-yellow-950',
    };

    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return '<div class="border ' . $borderClass . ' rounded p-3 mb-4 text-sm" role="alert">'
         . $safeMessage
         . '</div>';
}

// ─── CSRF Protection ──────────────────────────────────────────────────────────

/**
 * Generate a CSRF token, store it in the session, and return it.
 *
 * @return string Hex-encoded CSRF token.
 */
function generateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

/**
 * Validate a submitted CSRF token against the one stored in the session.
 *
 * Uses hash_equals() to prevent timing attacks.
 *
 * @param  string $token Token submitted with the request.
 * @return bool          True if the token is valid, false otherwise.
 */
function validateCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Validation ───────────────────────────────────────────────────────────────

/**
 * Validate password complexity and return an array of unmet criteria.
 *
 * An empty return array means the password is valid.
 *
 * Criteria checked:
 *  - Minimum length (PASSWORD_MIN_LENGTH constant)
 *  - At least one uppercase letter
 *  - At least one lowercase letter
 *  - At least one digit
 *  - At least one special character from: !@#$%^&*()_+-=[]{}|;:,.<>?
 *
 * @param  string   $password Candidate password.
 * @return string[]           Array of unmet criteria descriptions.
 */
function validatePasswordComplexity(string $password): array
{
    $errors = [];

    $minLength = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;

    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one digit.';
    }

    if (!preg_match('/[!@#$%^&*()\-_+=\[\]{}|;:,.<>?]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&*()_+-=[]{}|;:,.<>?).';
    }

    return $errors;
}

/**
 * Validate health profile fields and return an array of error messages.
 *
 * An empty return array means all values are valid.
 *
 * Constraints:
 *  - weight: numeric, 20–500 kg
 *  - height: numeric, 50–300 cm
 *  - age:    integer, 10–120
 *
 * @param  mixed    $weight Weight in kg.
 * @param  mixed    $height Height in cm.
 * @param  mixed    $age    Age in years.
 * @return string[]         Array of validation error messages.
 */
function validateHealthProfile($weight, $height, $age): array
{
    $errors = [];

    if (!is_numeric($weight) || (float) $weight < 20 || (float) $weight > 500) {
        $errors[] = 'Weight must be a number between 20 and 500 kg.';
    }

    if (!is_numeric($height) || (float) $height < 50 || (float) $height > 300) {
        $errors[] = 'Height must be a number between 50 and 300 cm.';
    }

    if (!is_numeric($age) || (int) $age != $age || (int) $age < 10 || (int) $age > 120) {
        $errors[] = 'Age must be a whole number between 10 and 120.';
    }

    return $errors;
}

// ─── System Settings & Announcements ─────────────────────────────────────────

/**
 * Retrieve a system setting value from the `system_settings` table.
 *
 * @param  string      $key     Setting key.
 * @param  PDO         $pdo     Active database connection.
 * @param  mixed       $default Value to return when the key is not found.
 * @return mixed                Setting value or $default.
 */
function getSystemSetting(string $key, PDO $pdo, $default = null)
{
    $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $row = $stmt->fetch();

    return $row !== false ? $row['setting_value'] : $default;
}

/**
 * Return active announcements visible to a given role.
 *
 * An announcement is included when its `target_audience` is 'all' or matches
 * the plural form of the role (member→members, trainer→trainers, admin→admins).
 *
 * @param  string $role Role string: 'member', 'trainer', or 'admin'.
 * @param  PDO    $pdo  Active database connection.
 * @return array        Array of announcement rows.
 */
function getActiveAnnouncements(string $role, PDO $pdo): array
{
    $audienceMap = [
        'member'  => 'members',
        'trainer' => 'trainers',
        'admin'   => 'admins',
    ];

    $audience = $audienceMap[$role] ?? $role . 's';

    $stmt = $pdo->prepare(
        'SELECT * FROM announcements
          WHERE is_active = TRUE
            AND (target_audience = \'all\' OR target_audience = :audience)
          ORDER BY created_at DESC'
    );
    $stmt->execute([':audience' => $audience]);

    return $stmt->fetchAll();
}
