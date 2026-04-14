<?php
/**
 * FitSense — Trainer API
 *
 * JSON API for trainer roster, recommendation review, and messaging.
 * Requirements: 10.1–10.4, 11.1–11.6, 12.1–12.6, 19.3
 */
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireRole('trainer');

$pdo       = Database::getConnection();
$trainerId = (int) $_SESSION['user_id'];
$action    = $_GET['action'] ?? '';
$input     = [];

// For multipart/form-data (file uploads), action comes from $_POST
if (empty($action) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $input  = $_POST;
} else {
    // For application/json requests
    $raw   = file_get_contents('php://input');
    $input = $raw ? (json_decode($raw, true) ?? []) : [];
    if (empty($action) && !empty($input['action'])) {
        $action = $input['action'];
    }
}

// ─── Route ────────────────────────────────────────────────────────────────────

switch ($action) {

    case 'roster':
        handleRoster($pdo, $trainerId);
        break;

    case 'pending_recommendations':
        handlePendingRecommendations($pdo, $trainerId);
        break;

    case 'member_detail':
        handleMemberDetail($pdo, $trainerId, $input);
        break;

    case 'messages':
        handleMessages($pdo, $trainerId, $input);
        break;

    case 'review_recommendation':
        handleReviewRecommendation($pdo, $trainerId, $input);
        break;

    case 'send_message':
        handleSendMessage($pdo, $trainerId, $input);
        break;

    case 'mark_read':
        handleMarkRead($pdo, $trainerId, $input);
        break;

    case 'overview':
        handleOverview($pdo, $trainerId);
        break;

    case 'announcements':
        handleTrainerAnnouncements($pdo);
        break;

    case 'profile':
        handleGetProfile($pdo, $trainerId);
        break;

    case 'update_profile':
        handleUpdateProfile($pdo, $trainerId);
        break;

    case 'available_members':
        handleAvailableMembers($pdo);
        break;

    case 'assign_member':
        handleAssignMember($pdo, $trainerId, $input);
        break;

    case 'unassign_member':
        handleUnassignMember($pdo, $trainerId, $input);
        break;

    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
}

// ─── Handlers ─────────────────────────────────────────────────────────────────

/**
 * GET action=roster
 * Returns all members assigned to this trainer with summary data.
 */
function handleRoster(PDO $pdo, int $trainerId): void
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.last_login, u.status,
                mp.current_weight_kg, mp.fitness_level, mp.age,
                fg.goal_type,
                (SELECT COUNT(*) FROM ai_recommendations ar
                  WHERE ar.user_id = u.id AND ar.status = \'pending\') AS pending_recs,
                (SELECT COUNT(*) FROM chat_messages cm
                  WHERE cm.user_id = u.id AND cm.sender_type = \'member\' AND cm.is_read = FALSE) AS unread_messages
           FROM users u
           LEFT JOIN member_profiles mp ON mp.user_id = u.id
           LEFT JOIN fitness_goals fg   ON fg.user_id = u.id AND fg.status = \'active\'
          WHERE mp.assigned_trainer_id = ?
            AND u.status = \'active\'
          ORDER BY u.first_name ASC'
    );
    $stmt->execute([$trainerId]);
    $members = $stmt->fetchAll();

    sendJsonResponse(['success' => true, 'members' => $members]);
}

/**
 * GET action=pending_recommendations
 * Returns pending AI recommendations for all assigned members, oldest first.
 */
function handlePendingRecommendations(PDO $pdo, int $trainerId): void
{
    $stmt = $pdo->prepare(
        'SELECT ar.*, u.first_name, u.last_name
           FROM ai_recommendations ar
           JOIN users u ON u.id = ar.user_id
           JOIN member_profiles mp ON mp.user_id = ar.user_id
          WHERE mp.assigned_trainer_id = ?
            AND ar.status = \'pending\'
          ORDER BY ar.created_at ASC'
    );
    $stmt->execute([$trainerId]);
    $recs = $stmt->fetchAll();

    sendJsonResponse(['success' => true, 'recommendations' => $recs]);
}

/**
 * GET action=member_detail&user_id=X
 * Returns full profile, workout history, weight logs, and recommendations for one member.
 */
function handleMemberDetail(PDO $pdo, int $trainerId, array $input): void
{
    $memberId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (int) ($input['user_id'] ?? 0);

    if (!$memberId) {
        sendJsonResponse(['error' => 'user_id required'], 400);
    }

    // Verify assignment
    $check = $pdo->prepare(
        'SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id = ? LIMIT 1'
    );
    $check->execute([$memberId, $trainerId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    // Profile
    $profileStmt = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.last_login,
                mp.*, fg.goal_type, fg.description AS goal_description, fg.status AS goal_status
           FROM users u
           LEFT JOIN member_profiles mp ON mp.user_id = u.id
           LEFT JOIN fitness_goals fg   ON fg.user_id = u.id AND fg.status = \'active\'
          WHERE u.id = ? LIMIT 1'
    );
    $profileStmt->execute([$memberId]);
    $profile = $profileStmt->fetch();

    // Workout sessions (last 20)
    $wsStmt = $pdo->prepare(
        'SELECT * FROM workout_sessions WHERE user_id = ? ORDER BY session_date DESC LIMIT 20'
    );
    $wsStmt->execute([$memberId]);
    $workouts = $wsStmt->fetchAll();

    // Weight logs (last 30, ASC for chart)
    $wlStmt = $pdo->prepare(
        'SELECT weight_kg, log_date FROM weight_logs WHERE user_id = ? ORDER BY log_date ASC LIMIT 30'
    );
    $wlStmt->execute([$memberId]);
    $weightLogs = $wlStmt->fetchAll();

    // Recommendations (last 20)
    $recStmt = $pdo->prepare(
        'SELECT * FROM ai_recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
    );
    $recStmt->execute([$memberId]);
    $recommendations = $recStmt->fetchAll();

    sendJsonResponse([
        'success'         => true,
        'profile'         => $profile,
        'workouts'        => $workouts,
        'weight_logs'     => $weightLogs,
        'recommendations' => $recommendations,
    ]);
}

/**
 * GET action=messages&user_id=X
 * Returns chat messages for a specific member thread.
 */
function handleMessages(PDO $pdo, int $trainerId, array $input): void
{
    $memberId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (int) ($input['user_id'] ?? 0);

    if (!$memberId) {
        sendJsonResponse(['error' => 'user_id required'], 400);
    }

    // Verify assignment
    $check = $pdo->prepare(
        'SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id = ? LIMIT 1'
    );
    $check->execute([$memberId, $trainerId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    $stmt = $pdo->prepare(
        'SELECT cm.*, u.first_name, u.last_name
           FROM chat_messages cm
           LEFT JOIN users u ON u.id = cm.sender_id
          WHERE cm.user_id = ?
          ORDER BY cm.created_at ASC
          LIMIT 50'
    );
    $stmt->execute([$memberId]);
    $messages = $stmt->fetchAll();

    sendJsonResponse(['success' => true, 'messages' => $messages]);
}

/**
 * POST action=review_recommendation
 * Approve, modify, or reject a recommendation.
 */
function handleReviewRecommendation(PDO $pdo, int $trainerId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $recId        = (int) ($input['recommendation_id'] ?? 0);
    $status       = $input['status']        ?? '';
    $trainerNotes = trim($input['trainer_notes'] ?? '');

    if (!$recId || !in_array($status, ['approved', 'modified', 'rejected'], true)) {
        sendJsonResponse(['error' => 'Invalid parameters.'], 400);
    }

    // Rejection requires notes
    if ($status === 'rejected' && $trainerNotes === '') {
        sendJsonResponse(['success' => false, 'error' => 'Please provide notes when rejecting a recommendation.']);
    }

    // Verify the recommendation belongs to an assigned member
    $check = $pdo->prepare(
        'SELECT ar.id FROM ai_recommendations ar
           JOIN member_profiles mp ON mp.user_id = ar.user_id
          WHERE ar.id = ? AND mp.assigned_trainer_id = ? LIMIT 1'
    );
    $check->execute([$recId, $trainerId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    $stmt = $pdo->prepare(
        'UPDATE ai_recommendations
            SET status = ?, reviewed_by = ?, trainer_notes = ?, updated_at = NOW()
          WHERE id = ?'
    );
    $stmt->execute([$status, $trainerId, $trainerNotes ?: null, $recId]);

    sendJsonResponse(['success' => true, 'message' => 'Recommendation ' . $status . '.']);
}

/**
 * POST action=send_message
 * Send a message to an assigned member.
 */
function handleSendMessage(PDO $pdo, int $trainerId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $memberId = (int) ($input['user_id'] ?? 0);
    $message  = trim($input['message'] ?? '');

    if (!$memberId || $message === '') {
        sendJsonResponse(['error' => 'user_id and message are required.'], 400);
    }

    // Verify assignment
    $check = $pdo->prepare(
        'SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id = ? LIMIT 1'
    );
    $check->execute([$memberId, $trainerId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO chat_messages (user_id, sender_type, sender_id, message, message_type)
         VALUES (?, \'trainer\', ?, ?, \'text\')'
    );
    $stmt->execute([$memberId, $trainerId, $message]);

    sendJsonResponse(['success' => true, 'message' => 'Message sent.']);
}

/**
 * POST action=mark_read
 * Mark messages from a member as read.
 */
function handleMarkRead(PDO $pdo, int $trainerId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $memberId = (int) ($input['user_id'] ?? 0);
    if (!$memberId) {
        sendJsonResponse(['error' => 'user_id required.'], 400);
    }

    // Verify assignment
    $check = $pdo->prepare(
        'SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id = ? LIMIT 1'
    );
    $check->execute([$memberId, $trainerId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    $stmt = $pdo->prepare(
        "UPDATE chat_messages SET is_read = TRUE
          WHERE user_id = ? AND sender_type = 'member' AND is_read = FALSE"
    );
    $stmt->execute([$memberId]);

    sendJsonResponse(['success' => true]);
}

/**
 * GET action=overview
 * Returns stat counts and recent audit activity for the trainer.
 */
function handleOverview(PDO $pdo, int $trainerId): void
{
    // Total assigned members
    $totalStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM member_profiles WHERE assigned_trainer_id = ?'
    );
    $totalStmt->execute([$trainerId]);
    $totalMembers = (int) $totalStmt->fetchColumn();

    // Active assigned members
    $activeStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM member_profiles mp
           JOIN users u ON u.id = mp.user_id
          WHERE mp.assigned_trainer_id = ? AND u.status = 'active'"
    );
    $activeStmt->execute([$trainerId]);
    $activeMembers = (int) $activeStmt->fetchColumn();

    // Pending reviews
    $pendingStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM ai_recommendations ar
           JOIN member_profiles mp ON mp.user_id = ar.user_id
          WHERE mp.assigned_trainer_id = ? AND ar.status = 'pending'"
    );
    $pendingStmt->execute([$trainerId]);
    $pendingReviews = (int) $pendingStmt->fetchColumn();

    // Unread messages
    $unreadStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM chat_messages cm
           JOIN member_profiles mp ON mp.user_id = cm.user_id
          WHERE mp.assigned_trainer_id = ? AND cm.sender_type = 'member' AND cm.is_read = FALSE"
    );
    $unreadStmt->execute([$trainerId]);
    $unreadMessages = (int) $unreadStmt->fetchColumn();

    // Recent audit activity (last 10 actions by this trainer)
    $activityStmt = $pdo->prepare(
        'SELECT action, created_at FROM audit_logs
          WHERE user_id = ?
          ORDER BY created_at DESC LIMIT 10'
    );
    $activityStmt->execute([$trainerId]);
    $activity = $activityStmt->fetchAll();

    sendJsonResponse([
        'success'  => true,
        'stats'    => [
            'total_members'   => $totalMembers,
            'active_members'  => $activeMembers,
            'pending_reviews' => $pendingReviews,
            'unread_messages' => $unreadMessages,
        ],
        'activity' => $activity,
    ]);
}

/**
 * GET action=announcements
 * Returns active announcements targeted at trainers or all.
 */
function handleTrainerAnnouncements(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "SELECT id, title, content, type, target_audience, created_at
           FROM announcements
          WHERE is_active = TRUE
            AND target_audience IN ('all', 'trainers')
          ORDER BY created_at DESC
          LIMIT 20"
    );
    $stmt->execute();
    $announcements = $stmt->fetchAll();

    sendJsonResponse(['success' => true, 'announcements' => $announcements]);
}

/**
 * Ensure profile_photo column exists on users table.
 */
function ensureProfilePhotoColumn(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL");
    } catch (PDOException $e) {
        // Column already exists — ignore error 1060
    }
}

/**
 * GET action=profile
 * Returns the trainer's own user record.
 */
function handleGetProfile(PDO $pdo, int $trainerId): void
{
    ensureProfilePhotoColumn($pdo);

    $stmt = $pdo->prepare(
        'SELECT id, username, email, first_name, last_name, phone, profile_photo
           FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$trainerId]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJsonResponse(['error' => 'User not found.'], 404);
    }

    sendJsonResponse(['success' => true, 'user' => $user]);
}

/**
 * POST action=update_profile
 * Updates the trainer's own profile. Handles multipart/form-data for photo upload.
 */
function handleUpdateProfile(PDO $pdo, int $trainerId): void
{
    // For multipart form data, CSRF comes from $_POST
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $username  = trim($_POST['username']   ?? '');
    $email     = trim($_POST['email']      ?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $newPw     = $_POST['new_password']    ?? '';

    if (!$firstName || !$lastName || !$username) {
        sendJsonResponse(['error' => 'First name, last name, and username are required.'], 400);
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['error' => 'Invalid email address.'], 400);
    }

    if ($newPw && strlen($newPw) < 8) {
        sendJsonResponse(['error' => 'Password must be at least 8 characters.'], 400);
    }

    // Ensure profile_photo column exists
    ensureProfilePhotoColumn($pdo);

    // Handle photo upload
    $photoPath = null;
    if (!empty($_FILES['profile_photo']['tmp_name'])) {
        $file     = $_FILES['profile_photo'];
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed, true)) {
            sendJsonResponse(['error' => 'Invalid image type. Use JPG, PNG, GIF, or WebP.'], 400);
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            sendJsonResponse(['error' => 'Image must be under 2 MB.'], 400);
        }

        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'trainer_' . $trainerId . '_' . time() . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            sendJsonResponse(['error' => 'Failed to save photo.'], 500);
        }

        $photoPath = 'uploads/avatars/' . $filename;

        // Delete old photo after new one is saved
        $oldStmt = $pdo->prepare('SELECT profile_photo FROM users WHERE id = ?');
        $oldStmt->execute([$trainerId]);
        $oldPhoto = $oldStmt->fetchColumn();
        if ($oldPhoto && file_exists(__DIR__ . '/../' . $oldPhoto)) {
            @unlink(__DIR__ . '/../' . $oldPhoto);
        }
    }

    // Build update query
    $fields = ['first_name = ?', 'last_name = ?', 'username = ?', 'email = ?', 'phone = ?'];
    $params = [$firstName, $lastName, $username, $email ?: null, $phone ?: null];

    if ($newPw) {
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $fields[] = 'needs_password_change = FALSE';
    }

    if ($photoPath) {
        $fields[] = 'profile_photo = ?';
        $params[] = $photoPath;
    }

    $params[] = $trainerId;

    try {
        $stmt = $pdo->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($params);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            sendJsonResponse(['error' => 'Username or email already in use.'], 409);
        }
        sendJsonResponse(['error' => 'Failed to update profile.'], 500);
    }

    $response = ['success' => true, 'message' => 'Profile updated.'];
    if ($photoPath) $response['profile_photo'] = $photoPath;
    sendJsonResponse($response);
}

/**
 * GET action=available_members
 * Returns all active members with no assigned trainer.
 */
function handleAvailableMembers(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "SELECT u.id, u.first_name, u.last_name, u.email, u.last_login,
                mp.current_weight_kg, mp.fitness_level, mp.age,
                fg.goal_type
           FROM users u
           LEFT JOIN member_profiles mp ON mp.user_id = u.id
           LEFT JOIN fitness_goals fg   ON fg.user_id = u.id AND fg.status = 'active'
          WHERE (mp.assigned_trainer_id IS NULL OR mp.id IS NULL)
            AND u.role = 'member'
            AND u.status = 'active'
          ORDER BY u.first_name ASC"
    );
    $stmt->execute();
    $members = $stmt->fetchAll();

    sendJsonResponse(['success' => true, 'members' => $members]);
}

/**
 * POST action=assign_member
 * Assigns an unassigned member to this trainer.
 */
function handleAssignMember(PDO $pdo, int $trainerId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $memberId = (int) ($input['member_id'] ?? 0);
    if (!$memberId) {
        sendJsonResponse(['error' => 'member_id required.'], 400);
    }

    // Only assign if currently unassigned
    $check = $pdo->prepare(
        "SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id IS NULL LIMIT 1"
    );
    $check->execute([$memberId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Member is already assigned to a trainer.'], 409);
    }

    $stmt = $pdo->prepare(
        'UPDATE member_profiles SET assigned_trainer_id = ? WHERE user_id = ?'
    );
    $stmt->execute([$trainerId, $memberId]);

    sendJsonResponse(['success' => true, 'message' => 'Member assigned.']);
}

/**
 * POST action=unassign_member
 * Releases a member from this trainer's roster.
 */
function handleUnassignMember(PDO $pdo, int $trainerId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $memberId = (int) ($input['member_id'] ?? 0);
    if (!$memberId) {
        sendJsonResponse(['error' => 'member_id required.'], 400);
    }

    // Only unassign if assigned to THIS trainer
    $check = $pdo->prepare(
        'SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id = ? LIMIT 1'
    );
    $check->execute([$memberId, $trainerId]);
    if (!$check->fetch()) {
        sendJsonResponse(['error' => 'Member not in your roster.'], 403);
    }

    $stmt = $pdo->prepare(
        'UPDATE member_profiles SET assigned_trainer_id = NULL WHERE user_id = ? AND assigned_trainer_id = ?'
    );
    $stmt->execute([$memberId, $trainerId]);

    sendJsonResponse(['success' => true, 'message' => 'Member unassigned.']);
}
