<?php
/**
 * FitSense — Admin API
 *
 * JSON API for user management, exercise library, announcements,
 * analytics, audit logs, and system settings.
 * Requirements: 13.1–13.9, 14.1–14.4, 15.1–15.3, 16.1–16.4, 17.1, 17.4, 19.3, 19.5
 */
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireRole('admin');

$pdo     = Database::getConnection();
$adminId = (int) $_SESSION['user_id'];
$action  = $_GET['action'] ?? '';
$input   = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($action) && !empty($input['action'])) {
    $action = $input['action'];
}

// ─── Route ────────────────────────────────────────────────────────────────────

switch ($action) {

    // ── User Management ───────────────────────────────────────────────────────
    case 'users':            handleGetUsers($pdo);                          break;
    case 'create_user':      handleCreateUser($pdo, $adminId, $input);      break;
    case 'update_user':      handleUpdateUser($pdo, $adminId, $input);      break;
    case 'suspend_user':     handleSuspendUser($pdo, $adminId, $input);     break;
    case 'activate_user':    handleActivateUser($pdo, $adminId, $input);    break;
    case 'deactivate_user':  handleDeactivateUser($pdo, $adminId, $input);  break;
    case 'delete_user':      handleDeleteUser($pdo, $adminId, $input);      break;
    case 'reset_temp_password': handleResetTempPassword($pdo, $adminId, $input); break;

    // ── Exercise Library ──────────────────────────────────────────────────────
    case 'exercises':           handleGetExercises($pdo);                          break;
    case 'create_exercise':     handleCreateExercise($pdo, $adminId, $input);      break;
    case 'update_exercise':     handleUpdateExercise($pdo, $adminId, $input);      break;
    case 'deactivate_exercise': handleDeactivateExercise($pdo, $adminId, $input);  break;

    // ── Announcements ─────────────────────────────────────────────────────────
    case 'create_announcement': handleCreateAnnouncement($pdo, $adminId, $input); break;
    case 'update_announcement': handleUpdateAnnouncement($pdo, $adminId, $input); break;
    case 'announcements':       handleGetAnnouncements($pdo);                      break;

    // ── Analytics & Settings ──────────────────────────────────────────────────
    case 'analytics':      handleAnalytics($pdo);                          break;
    case 'audit_log':      handleAuditLog($pdo, $input);                   break;
    case 'settings':       handleGetSettings($pdo);                        break;
    case 'save_settings':  handleSaveSettings($pdo, $adminId, $input);     break;
    case 'system_status':  handleSystemStatus($pdo);                       break;

    // ── Trainers list (for assignment dropdown) ───────────────────────────────
    case 'trainers':       handleGetTrainers($pdo);                        break;

    // ── Contact Inquiries ─────────────────────────────────────────────────────
    case 'inquiries':        handleGetInquiries($pdo);                         break;
    case 'update_inquiry':   handleUpdateInquiry($pdo, $adminId, $input);      break;

    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
}

// ═══════════════════════════════════════════════════════════════════════════════
// USER MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════════

function handleGetUsers(PDO $pdo): void
{
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = 20;
    $offset   = ($page - 1) * $pageSize;
    $search   = trim($_GET['search'] ?? '');
    $role     = $_GET['role'] ?? '';

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
        $like     = '%' . $search . '%';
        $params   = array_merge($params, [$like, $like, $like, $like]);
    }

    if ($role !== '') {
        $where[]  = 'u.role = ?';
        $params[] = $role;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u {$whereClause}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone,
                u.role, u.status, u.needs_password_change, u.last_login, u.created_at,
                mp.assigned_trainer_id,
                CONCAT(t.first_name, ' ', t.last_name) AS trainer_name
           FROM users u
           LEFT JOIN member_profiles mp ON mp.user_id = u.id
           LEFT JOIN users t ON t.id = mp.assigned_trainer_id
          {$whereClause}
          ORDER BY u.created_at DESC
          LIMIT {$pageSize} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    sendJsonResponse([
        'success'   => true,
        'users'     => $users,
        'total'     => $total,
        'page'      => $page,
        'page_size' => $pageSize,
    ]);
}

function handleCreateUser(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $firstName  = trim($input['first_name']  ?? '');
    $lastName   = trim($input['last_name']   ?? '');
    $email      = trim($input['email']       ?? '');
    $phone      = trim($input['phone']       ?? '');
    $role       = $input['role']             ?? 'member';
    $trainerId  = !empty($input['assigned_trainer_id']) ? (int) $input['assigned_trainer_id'] : null;

    $errors = [];
    if (!$firstName) $errors[] = 'First name is required.';
    if (!$lastName)  $errors[] = 'Last name is required.';
    if (!in_array($role, ['member', 'trainer', 'admin'], true)) $errors[] = 'Invalid role.';

    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'errors' => $errors]);
    }

    // Generate credentials
    $username = generateUsername($firstName, $lastName, $pdo);
    $password = generatePassword();
    $hash     = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password_hash, first_name, last_name, email, phone, role, needs_password_change)
             VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)'
        );
        $stmt->execute([$username, $hash, $firstName, $lastName, $email ?: null, $phone ?: null, $role]);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') {
            sendJsonResponse(['success' => false, 'errors' => ['An account with that name or email already exists.']]);
        }
        throw $e;
    }
    $newUserId = (int) $pdo->lastInsertId();

    // Create member_profiles row for members
    if ($role === 'member') {
        $mpStmt = $pdo->prepare(
            'INSERT INTO member_profiles (user_id, assigned_trainer_id) VALUES (?, ?)'
        );
        $mpStmt->execute([$newUserId, $trainerId]);
    }

    // Send welcome email with credentials if email provided
    $emailSent = false;
    if ($email) {
        require_once __DIR__ . '/../includes/mailer.php';
        $mailResult = Mailer::sendWelcome($email, $firstName . ' ' . $lastName, $username, $password);
        $emailSent  = $mailResult['success'];
        if (!$emailSent) {
            error_log('Welcome email failed for user ' . $newUserId . ': ' . ($mailResult['error'] ?? 'unknown'));
        }
    }

    // Audit log
    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'create_user', 'users', $newUserId, null, [
        'username' => $username, 'role' => $role,
    ]);

    sendJsonResponse([
        'success'    => true,
        'message'    => 'Account created.' . ($emailSent ? ' Credentials sent to ' . $email . '.' : ''),
        'username'   => $username,
        'password'   => $password,
        'user_id'    => $newUserId,
        'email_sent' => $emailSent,
    ]);
}

function handleUpdateUser(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $userId    = (int) ($input['user_id'] ?? 0);
    $firstName = trim($input['first_name'] ?? '');
    $lastName  = trim($input['last_name']  ?? '');
    $email     = trim($input['email']      ?? '');
    $phone     = trim($input['phone']      ?? '');
    $role      = $input['role']            ?? '';
    $trainerId = !empty($input['assigned_trainer_id']) ? (int) $input['assigned_trainer_id'] : null;
    $newPassword = trim($input['new_password'] ?? '');

    if (!$userId) {
        sendJsonResponse(['error' => 'user_id required.'], 400);
    }

    // Only the admin editing their own account can change password via this endpoint
    if ($newPassword !== '') {
        if ($userId !== $adminId) {
            sendJsonResponse(['error' => 'You can only change your own password here.'], 403);
        }
        if (strlen($newPassword) < 6) {
            sendJsonResponse(['success' => false, 'errors' => ['Password must be at least 6 characters.']]);
        }
    }

    // Fetch old values for audit
    $old = $pdo->prepare('SELECT first_name, last_name, email, phone, role FROM users WHERE id = ?');
    $old->execute([$userId]);
    $oldValues = $old->fetch();

    // Validate email format if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['success' => false, 'errors' => ['Please enter a valid email address.']]);
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, updated_at = NOW()
              WHERE id = ?'
        );
        $stmt->execute([$firstName ?: $oldValues['first_name'], $lastName ?: $oldValues['last_name'],
            $email ?: null, $phone ?: null, $role ?: $oldValues['role'], $userId]);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') {
            sendJsonResponse(['success' => false, 'errors' => ['That email address is already in use by another account.']]);
        }
        throw $e;
    }

    // Update password if provided (self-edit only, already validated above)
    if ($newPassword !== '') {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = ?, needs_password_change = FALSE, updated_at = NOW() WHERE id = ?')
            ->execute([$hash, $userId]);
    }

    // Update trainer assignment for members
    if ($role === 'member' || (!$role && $oldValues['role'] === 'member')) {
        $mpCheck = $pdo->prepare('SELECT id FROM member_profiles WHERE user_id = ?');
        $mpCheck->execute([$userId]);
        if ($mpCheck->fetch()) {
            $mpStmt = $pdo->prepare('UPDATE member_profiles SET assigned_trainer_id = ? WHERE user_id = ?');
            $mpStmt->execute([$trainerId, $userId]);
        }
    }

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'update_user', 'users', $userId, $oldValues, $input);

    sendJsonResponse(['success' => true, 'message' => 'User updated.']);
}

function handleSuspendUser(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if (!$userId) sendJsonResponse(['error' => 'user_id required.'], 400);
    if ($userId === $adminId) sendJsonResponse(['error' => 'You cannot suspend your own account.'], 403);

    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'suspend_user', 'users', $userId);

    sendJsonResponse(['success' => true, 'message' => 'Account suspended.']);
}

function handleDeleteUser(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if (!$userId) sendJsonResponse(['error' => 'user_id required.'], 400);
    if ($userId === $adminId) sendJsonResponse(['error' => 'You cannot delete your own account.'], 403);

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'delete_user', 'users', $userId);

    sendJsonResponse(['success' => true, 'message' => 'Account permanently deleted.']);
}

function handleDeactivateUser(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if (!$userId) sendJsonResponse(['error' => 'user_id required.'], 400);
    if ($userId === $adminId) sendJsonResponse(['error' => 'You cannot deactivate your own account.'], 403);

    $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'deactivate_user', 'users', $userId);

    sendJsonResponse(['success' => true, 'message' => 'Account deactivated.']);
}

function handleActivateUser(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if (!$userId) sendJsonResponse(['error' => 'user_id required.'], 400);

    $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'activate_user', 'users', $userId);

    sendJsonResponse(['success' => true, 'message' => 'Account activated.']);
}

function handleResetTempPassword(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if (!$userId) sendJsonResponse(['error' => 'user_id required.'], 400);

    // Only allowed while account still needs a password change
    $check = $pdo->prepare('SELECT username, needs_password_change FROM users WHERE id = ? AND status != \'inactive\'');
    $check->execute([$userId]);
    $user = $check->fetch();

    if (!$user) {
        sendJsonResponse(['error' => 'User not found.'], 404);
    }
    if (!$user['needs_password_change']) {
        sendJsonResponse(['error' => 'Credentials can only be viewed while the account has a pending password change.'], 403);
    }

    $password = generatePassword();
    $hash     = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'UPDATE users SET password_hash = ?, needs_password_change = TRUE, updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$hash, $userId]);

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'reset_temp_password', 'users', $userId);

    sendJsonResponse([
        'success'  => true,
        'username' => $user['username'],
        'password' => $password,
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// EXERCISE LIBRARY
// ═══════════════════════════════════════════════════════════════════════════════

function handleGetExercises(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT * FROM exercises ORDER BY is_active DESC, name ASC'
    );
    sendJsonResponse(['success' => true, 'exercises' => $stmt->fetchAll()]);
}

function handleCreateExercise(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $name        = trim($input['name']        ?? '');
    $category    = trim($input['category']    ?? '');
    $description = trim($input['description'] ?? '');
    $muscleGroup = trim($input['muscle_group'] ?? '');
    $equipment   = trim($input['equipment']   ?? '');
    $difficulty  = $input['difficulty_level'] ?? 'beginner';

    if (!$name) sendJsonResponse(['success' => false, 'errors' => ['Name is required.']]);

    $stmt = $pdo->prepare(
        'INSERT INTO exercises (name, category, description, muscle_group, equipment, difficulty_level, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $category ?: null, $description ?: null, $muscleGroup ?: null, $equipment ?: null, $difficulty, $adminId]);

    sendJsonResponse(['success' => true, 'message' => 'Exercise created.', 'id' => (int) $pdo->lastInsertId()]);
}

function handleUpdateExercise(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $id          = (int) ($input['id'] ?? 0);
    $name        = trim($input['name']        ?? '');
    $category    = trim($input['category']    ?? '');
    $description = trim($input['description'] ?? '');
    $muscleGroup = trim($input['muscle_group'] ?? '');
    $equipment   = trim($input['equipment']   ?? '');
    $difficulty  = $input['difficulty_level'] ?? null;

    if (!$id) sendJsonResponse(['error' => 'id required.'], 400);

    $stmt = $pdo->prepare(
        'UPDATE exercises SET name = COALESCE(NULLIF(?, \'\'), name),
            category = ?, description = ?, muscle_group = ?, equipment = ?,
            difficulty_level = COALESCE(?, difficulty_level)
          WHERE id = ?'
    );
    $stmt->execute([$name, $category ?: null, $description ?: null, $muscleGroup ?: null, $equipment ?: null, $difficulty, $id]);

    sendJsonResponse(['success' => true, 'message' => 'Exercise updated.']);
}

function handleDeactivateExercise(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $id = (int) ($input['id'] ?? 0);
    if (!$id) sendJsonResponse(['error' => 'id required.'], 400);

    $stmt = $pdo->prepare('UPDATE exercises SET is_active = FALSE WHERE id = ?');
    $stmt->execute([$id]);

    sendJsonResponse(['success' => true, 'message' => 'Exercise deactivated.']);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ANNOUNCEMENTS
// ═══════════════════════════════════════════════════════════════════════════════

function handleGetAnnouncements(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT a.*, u.first_name, u.last_name FROM announcements a
           LEFT JOIN users u ON u.id = a.created_by
          ORDER BY a.created_at DESC'
    );
    sendJsonResponse(['success' => true, 'announcements' => $stmt->fetchAll()]);
}

function handleCreateAnnouncement(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $title    = trim($input['title']           ?? '');
    $content  = trim($input['content']         ?? '');
    $audience = $input['target_audience']      ?? 'all';

    if (!$title || !$content) {
        sendJsonResponse(['success' => false, 'errors' => ['Title and content are required.']]);
    }

    if (!in_array($audience, ['all', 'members', 'trainers', 'admins'], true)) {
        $audience = 'all';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO announcements (title, content, target_audience, created_by) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$title, $content, $audience, $adminId]);

    sendJsonResponse(['success' => true, 'message' => 'Announcement created.', 'id' => (int) $pdo->lastInsertId()]);
}

function handleUpdateAnnouncement(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $id       = (int) ($input['id'] ?? 0);
    $title    = trim($input['title']   ?? '');
    $content  = trim($input['content'] ?? '');
    $audience = $input['target_audience'] ?? null;
    $isActive = isset($input['is_active']) ? (bool) $input['is_active'] : null;

    if (!$id) sendJsonResponse(['error' => 'id required.'], 400);

    $stmt = $pdo->prepare(
        'UPDATE announcements
            SET title           = COALESCE(NULLIF(?, \'\'), title),
                content         = COALESCE(NULLIF(?, \'\'), content),
                target_audience = COALESCE(?, target_audience),
                is_active       = COALESCE(?, is_active)
          WHERE id = ?'
    );
    $stmt->execute([$title, $content, $audience, $isActive, $id]);

    sendJsonResponse(['success' => true, 'message' => 'Announcement updated.']);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ANALYTICS & AUDIT
// ═══════════════════════════════════════════════════════════════════════════════

function handleAnalytics(PDO $pdo): void
{
    // Active users by role
    $roleStmt = $pdo->query(
        "SELECT role, COUNT(*) AS count FROM users WHERE status = 'active' GROUP BY role"
    );
    $byRole = [];
    foreach ($roleStmt->fetchAll() as $row) {
        $byRole[$row['role']] = (int) $row['count'];
    }

    // Chat sessions per day (last 7 days)
    $chatStmt = $pdo->query(
        "SELECT DATE(created_at) AS day, COUNT(*) AS count
           FROM chat_messages
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY day ORDER BY day ASC"
    );
    $chatPerDay = $chatStmt->fetchAll();

    // AI recommendations per day (last 7 days)
    $recStmt = $pdo->query(
        "SELECT DATE(created_at) AS day, COUNT(*) AS count
           FROM ai_recommendations
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY day ORDER BY day ASC"
    );
    $recsPerDay = $recStmt->fetchAll();

    // Average workout rating
    $ratingStmt = $pdo->query(
        "SELECT ROUND(AVG(rating), 1) AS avg_rating FROM workout_sessions WHERE rating IS NOT NULL"
    );
    $avgRating = $ratingStmt->fetchColumn();

    // Total workouts logged
    $totalWorkouts = (int) $pdo->query('SELECT COUNT(*) FROM workout_sessions')->fetchColumn();

    sendJsonResponse([
        'success'        => true,
        'users_by_role'  => $byRole,
        'chat_per_day'   => $chatPerDay,
        'recs_per_day'   => $recsPerDay,
        'avg_rating'     => $avgRating,
        'total_workouts' => $totalWorkouts,
    ]);
}

function handleAuditLog(PDO $pdo, array $input): void
{
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(50, max(10, (int) ($_GET['page_size'] ?? 20)));
    $offset   = ($page - 1) * $pageSize;

    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo   = $_GET['date_to']   ?? '';
    $userId   = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;
    $action   = $_GET['filter_action'] ?? '';

    $where  = [];
    $params = [];

    if ($dateFrom) { $where[] = 'al.created_at >= ?'; $params[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo)   { $where[] = 'al.created_at <= ?'; $params[] = $dateTo   . ' 23:59:59'; }
    if ($userId)   { $where[] = 'al.user_id = ?';     $params[] = $userId; }
    if ($action)   { $where[] = 'al.action = ?';      $params[] = $action; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs al {$whereClause}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT al.*, u.first_name, u.last_name, u.username
           FROM audit_logs al
           LEFT JOIN users u ON u.id = al.user_id
          {$whereClause}
          ORDER BY al.created_at DESC
          LIMIT {$pageSize} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    sendJsonResponse([
        'success'   => true,
        'logs'      => $logs,
        'total'     => $total,
        'page'      => $page,
        'page_size' => $pageSize,
    ]);
}

function handleGetSettings(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    sendJsonResponse(['success' => true, 'settings' => $settings]);
}

function handleSaveSettings(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $allowed = ['maintenance_mode', 'max_ai_requests_per_day', 'session_timeout', 'password_min_length'];

    foreach ($allowed as $key) {
        if (!isset($input[$key])) continue;
        $value = $input[$key];

        $stmt = $pdo->prepare(
            'INSERT INTO system_settings (setting_key, setting_value, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
        );
        $stmt->execute([$key, $value, $adminId]);
    }

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'save_settings', 'system_settings');

    sendJsonResponse(['success' => true, 'message' => 'Settings saved.']);
}

function handleSystemStatus(PDO $pdo): void
{
    // DB check
    $dbOk = false;
    try {
        $pdo->query('SELECT 1');
        $dbOk = true;
    } catch (\Throwable $e) {
        error_log('System status DB check failed: ' . $e->getMessage());
    }

    // Claude API check
    $claudeOk = false;
    $apiKey   = CLAUDE_API_KEY;
    if ($apiKey) {
        try {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'      => CLAUDE_MODEL,
                    'max_tokens' => 1,
                    'messages'   => [['role' => 'user', 'content' => 'ping']],
                ]),
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $apiKey,
                    'anthropic-version: 2023-06-01',
                ],
            ]);
            curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $claudeOk = in_array($httpCode, [200, 400], true); // 400 = bad request but key is valid
        } catch (\Throwable $e) {
            error_log('Claude status check failed: ' . $e->getMessage());
        }
    }

    // Maintenance mode value
    $maintenance = 'false';
    try {
        $ms = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'")->fetchColumn();
        if ($ms !== false) $maintenance = $ms;
    } catch (\Throwable $e) {}

    sendJsonResponse([
        'success'     => true,
        'db'          => $dbOk,
        'claude_api'  => $claudeOk,
        'maintenance' => $maintenance,
    ]);
}

function handleGetTrainers(PDO $pdo): void
{
    $stmt = $pdo->query(
        "SELECT id, first_name, last_name FROM users WHERE role = 'trainer' AND status = 'active' ORDER BY first_name ASC"
    );
    sendJsonResponse(['success' => true, 'trainers' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// CONTACT INQUIRIES
// ═══════════════════════════════════════════════════════════════════════════════

function ensureContactInquiriesTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_inquiries (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100)  NOT NULL,
        email      VARCHAR(150)  NOT NULL,
        subject    VARCHAR(200)  NOT NULL,
        message    TEXT          NOT NULL,
        user_id    INT           DEFAULT NULL,
        status     ENUM('new','read','replied') DEFAULT 'new',
        created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function handleGetInquiries(PDO $pdo): void
{
    ensureContactInquiriesTable($pdo);

    $status = $_GET['status'] ?? '';

    $where  = [];
    $params = [];

    if ($status !== '') {
        $where[]  = 'status = ?';
        $params[] = $status;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $unread = (int) $pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'new'")->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT * FROM contact_inquiries {$whereClause} ORDER BY created_at DESC"
    );
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();

    sendJsonResponse([
        'success'   => true,
        'inquiries' => $inquiries,
        'unread'    => $unread,
    ]);
}

function handleUpdateInquiry(PDO $pdo, int $adminId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    ensureContactInquiriesTable($pdo);

    $id     = (int) ($input['id']     ?? 0);
    $status = $input['status'] ?? '';

    if (!$id) sendJsonResponse(['error' => 'id required.'], 400);
    if (!in_array($status, ['new', 'read', 'replied'], true)) {
        sendJsonResponse(['error' => 'Invalid status.'], 400);
    }

    $stmt = $pdo->prepare('UPDATE contact_inquiries SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);

    $auth = new Auth();
    $auth->logAuditEvent($adminId, 'update_inquiry', 'contact_inquiries', $id);

    sendJsonResponse(['success' => true, 'message' => 'Inquiry updated.']);
}
