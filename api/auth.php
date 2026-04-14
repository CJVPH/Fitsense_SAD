<?php
/**
 * FitSense — Auth API
 *
 * Handles login, staff login, logout, and password change via JSON.
 * Requirements: 3.1, 3.2, 3.3, 3.6, 19.3, 19.5
 */
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth   = new Auth();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// Allow action to be supplied in the JSON body as well
if (empty($action) && !empty($input['action'])) {
    $action = $input['action'];
}

// ─── Route ────────────────────────────────────────────────────────────────────

switch ($action) {

    // ── Member login ──────────────────────────────────────────────────────────
    case 'login':
        if (!validateCsrfToken($input['csrf_token'] ?? '')) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
        }

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if ($username === '' || $password === '') {
            sendJsonResponse(['success' => false, 'message' => 'Please enter both username and password.']);
        }

        $result = $auth->login($username, $password);

        if (!$result['success']) {
            sendJsonResponse(['success' => false, 'message' => $result['message']]);
        }

        // Member-only login — reject staff attempting to use this endpoint
        if ($result['role'] !== 'member') {
            $auth->logout();
            sendJsonResponse(['success' => false, 'message' => 'Please use the staff login page.']);
        }

        $redirect = resolveRedirect($result);
        sendJsonResponse(['success' => true, 'redirect' => $redirect]);
        break;

    // ── Staff login (trainer / admin) ─────────────────────────────────────────
    case 'login_staff':
        if (!validateCsrfToken($input['csrf_token'] ?? '')) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
        }

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if ($username === '' || $password === '') {
            sendJsonResponse(['success' => false, 'message' => 'Please enter both username and password.']);
        }

        $result = $auth->login($username, $password);

        if (!$result['success']) {
            sendJsonResponse(['success' => false, 'message' => $result['message']]);
        }

        // Staff-only — reject members attempting to use this endpoint
        if ($result['role'] === 'member') {
            $auth->logout();
            sendJsonResponse(['success' => false, 'message' => 'This login is for staff only.']);
        }

        $redirect = resolveRedirect($result);
        sendJsonResponse(['success' => true, 'redirect' => $redirect]);
        break;

    // ── Logout ────────────────────────────────────────────────────────────────
    case 'logout':
        $auth->logout();
        sendJsonResponse(['success' => true, 'redirect' => 'login.php']);
        break;

    // ── Change password ───────────────────────────────────────────────────────
    case 'change_password':
        // Must be authenticated
        if (!isset($_SESSION['user_id']) || !$auth->isSessionValid()) {
            sendJsonResponse(['success' => false, 'message' => 'Session expired. Please log in again.'], 401);
        }

        if (!validateCsrfToken($input['csrf_token'] ?? '')) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
        }

        $newPassword = $input['new_password'] ?? '';

        if ($newPassword === '') {
            sendJsonResponse(['success' => false, 'message' => 'New password is required.']);
        }

        $result = $auth->changePassword((int) $_SESSION['user_id'], $newPassword);

        if (!$result['success']) {
            $errorList = implode(' ', $result['errors'] ?? ['Password change failed.']);
            sendJsonResponse(['success' => false, 'message' => $errorList]);
        }

        // Determine post-change redirect
        $role = $_SESSION['role'] ?? '';

        if ($role === 'member') {
            // Check whether the member has completed full onboarding
            try {
                $pdo  = Database::getConnection();
                $stmt = $pdo->prepare(
                    'SELECT onboarding_completed FROM member_profiles WHERE user_id = ? LIMIT 1'
                );
                $stmt->execute([(int) $_SESSION['user_id']]);
                $row = $stmt->fetch();
                $onboardingDone = $row && (bool) $row['onboarding_completed'];
            } catch (\Throwable $e) {
                $onboardingDone = false;
            }

            $redirect = $onboardingDone ? 'chat.php' : 'onboarding.php';
        } elseif ($role === 'trainer') {
            $redirect = 'trainer-dashboard.php';
        } elseif ($role === 'admin') {
            $redirect = 'admin-dashboard.php';
        } else {
            $redirect = 'login.php';
        }

        sendJsonResponse(['success' => true, 'redirect' => $redirect]);
        break;

    // ── Unknown action ────────────────────────────────────────────────────────
    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
        break;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Determine the post-login redirect URL from the login result.
 *
 * @param  array  $result  Return value of Auth::login()
 * @return string          Relative URL to redirect to
 */
function resolveRedirect(array $result): string
{
    if (!empty($result['needs_password_change'])) {
        return 'change-password.php';
    }

    if (($result['role'] ?? '') === 'member') {
        // Check onboarding completion
        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare(
                'SELECT onboarding_completed FROM member_profiles WHERE user_id = ? LIMIT 1'
            );
            $stmt->execute([(int) ($_SESSION['user_id'] ?? 0)]);
            $row = $stmt->fetch();
            if (!$row || !(bool) $row['onboarding_completed']) {
                return 'onboarding.php';
            }
        } catch (\Throwable $e) {
            return 'onboarding.php';
        }
        return 'chat.php';
    }

    return match ($result['role'] ?? '') {
        'trainer' => 'trainer-dashboard.php',
        'admin'   => 'admin-dashboard.php',
        default   => 'login.php',
    };
}
