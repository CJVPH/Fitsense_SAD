<?php
/**
 * FitSense — Authentication and Session Management
 *
 * Provides the Auth class for login, logout, session validation, role
 * enforcement, password management, credential generation, and audit logging.
 *
 * Requirements: 3.1–3.9, 4.1, 4.2, 4.7
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Auth
{
    private PDO $pdo;

    // ─── Constructor ──────────────────────────────────────────────────────────

    /**
     * Obtain a shared PDO connection via the Database singleton.
     */
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    // ─── Login / Logout ───────────────────────────────────────────────────────

    /**
     * Authenticate a user by username and password.
     *
     * On success, session variables are populated and last_login is updated.
     * On failure, the login-attempt counter is incremented; once it reaches
     * MAX_LOGIN_ATTEMPTS the account is suspended.
     *
     * @param  string $username Submitted username.
     * @param  string $password Submitted plaintext password.
     * @return array  ['success' => bool, ...role/needs_password_change on success,
     *                 'message' => string on failure]
     */
    public function login(string $username, string $password): array
    {
        // Initialise attempt counter if absent.
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }

        // Fetch the active user record — match by username OR email.
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, role, first_name, last_name,
                    needs_password_change, status
               FROM users
              WHERE (username = ? OR email = ?) AND status = \'active\''
        );
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();

        if ($row === false) {
            // User not found — increment attempts and return generic error.
            $_SESSION['login_attempts']++;
            return ['success' => false, 'message' => 'Incorrect username or password.'];
        }

        // Check whether the attempt limit has been reached.
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            // Lock the account.
            $lock = $this->pdo->prepare(
                "UPDATE users SET status = 'suspended' WHERE id = ?"
            );
            $lock->execute([$row['id']]);

            $this->logAuditEvent($row['id'], 'account_locked', 'users', $row['id']);

            return [
                'success' => false,
                'message' => 'Account locked. Please contact an administrator.',
            ];
        }

        // Verify the password.
        if (!password_verify($password, $row['password_hash'])) {
            $_SESSION['login_attempts']++;
            return ['success' => false, 'message' => 'Incorrect username or password.'];
        }

        // ── Successful authentication ─────────────────────────────────────────

        $_SESSION['login_attempts'] = 0;
        $_SESSION['user_id']               = $row['id'];
        $_SESSION['role']                  = $row['role'];
        $_SESSION['first_name']            = $row['first_name'];
        $_SESSION['last_name']             = $row['last_name'];
        $_SESSION['needs_password_change'] = (bool) $row['needs_password_change'];
        $_SESSION['login_time']            = time();

        // Update last_login timestamp.
        $upd = $this->pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $upd->execute([$row['id']]);

        $this->logAuditEvent($row['id'], 'login');

        return [
            'success'               => true,
            'role'                  => $row['role'],
            'needs_password_change' => (bool) $row['needs_password_change'],
        ];
    }

    /**
     * Destroy the current session and clear the session cookie.
     *
     * The audit event is written before the session is destroyed so that the
     * user_id is still available.
     */
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->logAuditEvent((int) $_SESSION['user_id'], 'logout');
        }

        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // ─── Session Validation ───────────────────────────────────────────────────

    /**
     * Return true when the current session has not exceeded SESSION_TIMEOUT.
     */
    public function isSessionValid(): bool
    {
        if (!isset($_SESSION['login_time'])) {
            return false;
        }

        return (time() - $_SESSION['login_time']) < SESSION_TIMEOUT;
    }

    // ─── Access Control ───────────────────────────────────────────────────────

    /**
     * Ensure the request carries a valid, non-expired session.
     *
     * Also enforces the needs_password_change redirect and maintenance mode.
     *
     * @param string $redirect Page to redirect to when unauthenticated.
     */
    public function requireAuth(string $redirect = 'login.php'): void
    {
        if (!isset($_SESSION['user_id']) || !$this->isSessionValid()) {
            redirectWithMessage($redirect, 'Your session has expired. Please log in again.', 'info');
            exit;
        }

        // Force password change before any other page.
        $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
        if (!empty($_SESSION['needs_password_change']) && $currentPage !== 'change-password.php') {
            header('Location: change-password.php');
            exit;
        }

        // Maintenance mode: non-admins are redirected.
        if ($currentPage !== 'maintenance.php') {
            $maintenance = getSystemSetting('maintenance_mode', $this->pdo, 'false');
            if ($maintenance === 'true' && ($_SESSION['role'] ?? '') !== 'admin') {
                header('Location: maintenance.php');
                exit;
            }
        }
    }

    /**
     * Require the session user to hold a specific role.
     *
     * @param string $role     Required role string.
     * @param string $redirect Redirect target on role mismatch.
     */
    public function requireRole(string $role, string $redirect = 'unauthorized.php'): void
    {
        $this->requireAuth();

        if (($_SESSION['role'] ?? '') !== $role) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    /**
     * Require the session user to hold one of the given roles.
     *
     * @param string[] $roles    Acceptable role strings.
     * @param string   $redirect Redirect target on role mismatch.
     */
    public function requireAnyRole(array $roles, string $redirect = 'unauthorized.php'): void
    {
        $this->requireAuth();

        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    // ─── Password Management ──────────────────────────────────────────────────

    /**
     * Change a user's password after validating complexity requirements.
     *
     * @param  int    $userId      Target user ID.
     * @param  string $newPassword Plaintext candidate password.
     * @return array  ['success' => true] or ['success' => false, 'errors' => string[]]
     */
    public function changePassword(int $userId, string $newPassword): array
    {
        $errors = validatePasswordComplexity($newPassword);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            'UPDATE users
                SET password_hash = ?,
                    needs_password_change = FALSE,
                    updated_at = NOW()
              WHERE id = ?'
        );
        $stmt->execute([$hash, $userId]);

        $_SESSION['needs_password_change'] = false;

        $this->logAuditEvent($userId, 'password_changed', 'users', $userId);

        return ['success' => true];
    }

    // ─── Credential Generation ────────────────────────────────────────────────

    /**
     * Generate a unique username and a random compliant password for a new user.
     *
     * @param  string $firstName User's first name.
     * @param  string $lastName  User's last name.
     * @return array  ['username' => string, 'password' => string (plaintext)]
     */
    public function generateCredentials(string $firstName, string $lastName): array
    {
        $username = generateUsername($firstName, $lastName, $this->pdo);
        $password = generatePassword();

        return ['username' => $username, 'password' => $password];
    }

    // ─── Current User ─────────────────────────────────────────────────────────

    /**
     * Fetch the full user row (joined with member_profiles) for the session user.
     *
     * @return array|null User + profile row, or null when not authenticated.
     */
    public function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT users.*, member_profiles.*
               FROM users
               LEFT JOIN member_profiles ON member_profiles.user_id = users.id
              WHERE users.id = ?'
        );
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    // ─── Audit Logging ────────────────────────────────────────────────────────

    /**
     * Insert a row into audit_logs.
     *
     * Failures are silently swallowed so that audit log issues never crash the
     * application.
     *
     * @param int|null    $userId    Acting user (null for unauthenticated events).
     * @param string      $action    Short action label, e.g. 'login', 'logout'.
     * @param string|null $tableName Affected table, if applicable.
     * @param int|null    $recordId  Affected record ID, if applicable.
     * @param mixed       $oldValues Previous state (array or null).
     * @param mixed       $newValues New state (array or null).
     */
    public function logAuditEvent(
        int $userId = null,
        string $action,
        string $tableName = null,
        int $recordId = null,
        $oldValues = null,
        $newValues = null
    ): void {
        try {
            $oldJson = is_array($oldValues) ? json_encode($oldValues) : null;
            $newJson = is_array($newValues) ? json_encode($newValues) : null;

            $ip        = $_SERVER['REMOTE_ADDR']     ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_logs
                    (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $action, $tableName, $recordId, $oldJson, $newJson, $ip, $userAgent]);
        } catch (\Throwable $e) {
            // Audit log failures must never crash the application.
            error_log('Audit log failure: ' . $e->getMessage());
        }
    }
}
