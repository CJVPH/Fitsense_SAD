<?php
/**
 * FitSense — Members API
 *
 * JSON API for member profile, onboarding, and progress logging.
 * Requirements: 5.6, 5.7, 5.8, 8.1–8.6, 19.3
 */
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireAnyRole(['member', 'trainer', 'admin']);

$pdo    = Database::getConnection();
$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($action) && !empty($input['action'])) {
    $action = $input['action'];
}
// Also support multipart form data (file uploads)
if (empty($action) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $input  = $_POST;
}

// ─── Route ────────────────────────────────────────────────────────────────────

switch ($action) {

    // ── GET: profile ──────────────────────────────────────────────────────────
    case 'profile':
        handleGetProfile($pdo, $userId, $role, $input);
        break;

    // ── POST: update_profile ──────────────────────────────────────────────────
    case 'update_profile':
        handleUpdateProfile($pdo, $userId, $role, $input);
        break;

    // ── POST: update_account ──────────────────────────────────────────────────
    case 'update_account':
        handleUpdateAccount($pdo, $userId, $role, $input);
        break;

    // ── POST: save_onboarding ─────────────────────────────────────────────────
    case 'save_onboarding':
        handleSaveOnboarding($pdo, $userId, $role, $input);
        break;

    // ── POST: log_workout ─────────────────────────────────────────────────────
    case 'log_workout':
        handleLogWorkout($pdo, $userId, $role, $input);
        break;

    // ── POST: log_weight ──────────────────────────────────────────────────────
    case 'log_weight':
        handleLogWeight($pdo, $userId, $role, $input);
        break;

    // ── POST: upload_avatar ───────────────────────────────────────────────────
    case 'upload_avatar':
        handleUploadAvatar($pdo, $userId, $role);
        break;

    // ── POST: save_theme ──────────────────────────────────────────────────────
    case 'save_theme':
        handleSaveTheme($pdo, $userId, $input);
        break;

    // ── Default ───────────────────────────────────────────────────────────────
    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
}

// ─── Handlers ─────────────────────────────────────────────────────────────────

/**
 * GET action=profile
 * Accessible by member (own), trainer (assigned member), admin (any).
 */
function handleGetProfile(PDO $pdo, int $userId, string $role, array $input): void
{
    $targetId = isset($input['user_id']) ? (int) $input['user_id'] : $userId;

    // Members can only view their own profile
    if ($role === 'member') {
        $targetId = $userId;
    }

    // Trainers may only view members assigned to them
    if ($role === 'trainer') {
        $check = $pdo->prepare(
            'SELECT id FROM member_profiles WHERE user_id = ? AND assigned_trainer_id = ? LIMIT 1'
        );
        $check->execute([$targetId, $userId]);
        if ($check->fetch() === false) {
            sendJsonResponse(['error' => 'Access denied.'], 403);
        }
    }

    // Fetch main profile row
    $stmt = $pdo->prepare(
        'SELECT users.id, users.first_name, users.last_name, users.email, users.role,
                users.last_login, member_profiles.*,
                fitness_goals.goal_type, fitness_goals.description,
                fitness_goals.status AS goal_status
           FROM users
           LEFT JOIN member_profiles ON member_profiles.user_id = users.id
           LEFT JOIN fitness_goals   ON fitness_goals.user_id = users.id
                                    AND fitness_goals.status = \'active\'
          WHERE users.id = ?
          LIMIT 1'
    );
    $stmt->execute([$targetId]);
    $profile = $stmt->fetch();

    if ($profile === false) {
        sendJsonResponse(['error' => 'Member not found.'], 404);
    }

    // Last 10 weight logs
    $wStmt = $pdo->prepare(
        'SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 10'
    );
    $wStmt->execute([$targetId]);
    $weightLogs = $wStmt->fetchAll();

    // Last 10 workout sessions
    $sStmt = $pdo->prepare(
        'SELECT * FROM workout_sessions WHERE user_id = ? ORDER BY session_date DESC LIMIT 10'
    );
    $sStmt->execute([$targetId]);
    $workoutSessions = $sStmt->fetchAll();

    sendJsonResponse([
        'success'          => true,
        'profile'          => $profile,
        'weight_logs'      => $weightLogs,
        'workout_sessions' => $workoutSessions,
    ]);
}

/**
 * POST action=update_profile
 * Members only — update their own member_profiles row and active goal.
 */
function handleUpdateProfile(PDO $pdo, int $userId, string $role, array $input): void
{
    if ($role !== 'member') {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $weight = $input['current_weight_kg'] ?? null;
    $height = $input['height_cm']         ?? null;
    $age    = $input['age']               ?? null;

    $errors = validateHealthProfile($weight, $height, $age);
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'errors' => $errors]);
    }

    $fitnessLevel          = $input['fitness_level']           ?? 'beginner';
    $targetWeight          = $input['target_weight_kg']        ?? null;
    $medicalConditions     = $input['medical_conditions']      ?? null;
    $emergencyContactName  = $input['emergency_contact_name']  ?? null;
    $emergencyContactPhone = $input['emergency_contact_phone'] ?? null;

    // Check if a profile row already exists
    $check = $pdo->prepare('SELECT id FROM member_profiles WHERE user_id = ? LIMIT 1');
    $check->execute([$userId]);
    $exists = $check->fetch();

    if ($exists) {
        $stmt = $pdo->prepare(
            'UPDATE member_profiles
                SET current_weight_kg       = ?,
                    height_cm               = ?,
                    age                     = ?,
                    fitness_level           = ?,
                    target_weight_kg        = ?,
                    medical_conditions      = ?,
                    emergency_contact_name  = ?,
                    emergency_contact_phone = ?
              WHERE user_id = ?'
        );
        $stmt->execute([
            (float) $weight,
            (float) $height,
            (int)   $age,
            $fitnessLevel,
            $targetWeight !== null ? (float) $targetWeight : null,
            $medicalConditions,
            $emergencyContactName,
            $emergencyContactPhone,
            $userId,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO member_profiles
                (user_id, current_weight_kg, height_cm, age, fitness_level,
                 target_weight_kg, medical_conditions, emergency_contact_name, emergency_contact_phone)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            (float) $weight,
            (float) $height,
            (int)   $age,
            $fitnessLevel,
            $targetWeight !== null ? (float) $targetWeight : null,
            $medicalConditions,
            $emergencyContactName,
            $emergencyContactPhone,
        ]);
    }

    // Update active goal type if provided
    if (!empty($input['goal_type'])) {
        $goalStmt = $pdo->prepare(
            'UPDATE fitness_goals SET goal_type = ? WHERE user_id = ? AND status = \'active\''
        );
        $goalStmt->execute([$input['goal_type'], $userId]);
    }

    sendJsonResponse(['success' => true, 'message' => 'Profile updated successfully.']);
}

/**
 * POST action=save_onboarding
 * Members only — upsert member_profiles and insert a new active fitness goal.
 */
function handleSaveOnboarding(PDO $pdo, int $userId, string $role, array $input): void
{
    if ($role !== 'member') {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $weight = $input['current_weight_kg'] ?? null;
    $height = $input['height_cm']         ?? null;
    $age    = $input['age']               ?? null;

    $errors = validateHealthProfile($weight, $height, $age);
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'errors' => $errors]);
    }

    $fitnessLevel       = $input['fitness_level']        ?? 'beginner';
    $goalType           = $input['goal_type']            ?? 'maintain_fitness';
    $targetWeight       = !empty($input['target_weight_kg'])      ? (float) $input['target_weight_kg']      : null;
    $medicalConditions  = !empty($input['medical_conditions'])    ? trim($input['medical_conditions'])       : null;
    $address            = !empty($input['address'])               ? trim($input['address'])                  : null;
    $workSchedule       = !empty($input['work_schedule'])         ? $input['work_schedule']                  : null;
    $occupation         = !empty($input['occupation'])            ? trim($input['occupation'])               : null;
    $sleepHours         = !empty($input['sleep_hours_per_night']) ? (float) $input['sleep_hours_per_night']  : null;
    $activityLevel      = !empty($input['activity_level'])        ? $input['activity_level']                 : null;
    $dietaryPreference  = !empty($input['dietary_preference'])    ? $input['dietary_preference']             : null;
    $allergies          = !empty($input['allergies'])             ? trim($input['allergies'])                : null;

    // Check if profile row exists
    $check = $pdo->prepare('SELECT id FROM member_profiles WHERE user_id = ? LIMIT 1');
    $check->execute([$userId]);
    $exists = $check->fetch();

    if ($exists) {
        $stmt = $pdo->prepare(
            'UPDATE member_profiles
                SET current_weight_kg      = ?,
                    height_cm              = ?,
                    age                    = ?,
                    fitness_level          = ?,
                    target_weight_kg       = ?,
                    medical_conditions     = ?,
                    address                = ?,
                    work_schedule          = ?,
                    occupation             = ?,
                    sleep_hours_per_night  = ?,
                    activity_level         = ?,
                    dietary_preference     = ?,
                    allergies              = ?,
                    onboarding_completed   = TRUE
              WHERE user_id = ?'
        );
        $stmt->execute([
            (float) $weight, (float) $height, (int) $age, $fitnessLevel,
            $targetWeight, $medicalConditions, $address, $workSchedule,
            $occupation, $sleepHours, $activityLevel, $dietaryPreference,
            $allergies, $userId,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO member_profiles
                (user_id, current_weight_kg, height_cm, age, fitness_level,
                 target_weight_kg, medical_conditions, address, work_schedule,
                 occupation, sleep_hours_per_night, activity_level, dietary_preference,
                 allergies, onboarding_completed)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)'
        );
        $stmt->execute([
            $userId, (float) $weight, (float) $height, (int) $age, $fitnessLevel,
            $targetWeight, $medicalConditions, $address, $workSchedule,
            $occupation, $sleepHours, $activityLevel, $dietaryPreference, $allergies,
        ]);
    }

    // Insert new active fitness goal
    $goalStmt = $pdo->prepare(
        'INSERT INTO fitness_goals (user_id, goal_type, status) VALUES (?, ?, \'active\')'
    );
    $goalStmt->execute([$userId, $goalType]);

    // Also seed weight_logs so the dashboard shows current weight immediately
    $wlCheck = $pdo->prepare('SELECT id FROM weight_logs WHERE user_id = ? AND log_date = CURDATE()');
    $wlCheck->execute([$userId]);
    if (!$wlCheck->fetch()) {
        $pdo->prepare('INSERT INTO weight_logs (user_id, weight_kg, log_date) VALUES (?, ?, CURDATE())')
            ->execute([$userId, (float) $weight]);
    }

    sendJsonResponse(['success' => true, 'message' => 'Profile saved successfully.']);
}

/**
 * POST action=log_workout
 * Members only — insert a workout session.
 */
function handleLogWorkout(PDO $pdo, int $userId, string $role, array $input): void
{
    if ($role !== 'member') {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $sessionDate      = $input['session_date']      ?? '';
    $durationMinutes  = $input['duration_minutes']  ?? null;
    $rating           = $input['rating']            ?? null;
    $notes            = $input['notes']             ?? null;
    $caloriesBurned   = $input['calories_burned']   ?? null;
    $exercisesCompleted = $input['exercises_completed'] ?? [];

    // Validate required fields
    $errors = [];

    if (empty($sessionDate)) {
        $errors[] = 'Session date is required.';
    }

    if ($durationMinutes === null || !is_numeric($durationMinutes) || (int) $durationMinutes <= 0) {
        $errors[] = 'Duration must be a positive number of minutes.';
    }

    if ($rating !== null && (!is_numeric($rating) || (int) $rating < 1 || (int) $rating > 5)) {
        $errors[] = 'Rating must be between 1 and 5.';
    }

    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'errors' => $errors]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO workout_sessions
            (user_id, session_date, duration_minutes, exercises_completed, notes, rating, calories_burned)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $sessionDate,
        (int) $durationMinutes,
        json_encode($exercisesCompleted),
        $notes,
        $rating !== null ? (int) $rating : null,
        $caloriesBurned !== null ? (int) $caloriesBurned : null,
    ]);

    sendJsonResponse(['success' => true, 'message' => 'Workout logged.']);
}

/**
 * POST action=log_weight
 * Members only — insert or update a weight log entry.
 */
function handleLogWeight(PDO $pdo, int $userId, string $role, array $input): void
{
    if ($role !== 'member') {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $weightKg        = $input['weight_kg']        ?? null;
    $logDate         = $input['log_date']         ?? '';
    $notes           = $input['notes']            ?? null;
    $confirmOverwrite = $input['confirm_overwrite'] ?? false;

    // Validate
    $errors = [];

    if (!is_numeric($weightKg) || (float) $weightKg < 20 || (float) $weightKg > 500) {
        $errors[] = 'Weight must be a number between 20 and 500 kg.';
    }

    if (empty($logDate)) {
        $errors[] = 'Log date is required.';
    }

    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'errors' => $errors]);
    }

    // Check for existing entry on the same date
    $check = $pdo->prepare(
        'SELECT id FROM weight_logs WHERE user_id = ? AND log_date = ?'
    );
    $check->execute([$userId, $logDate]);
    $existing = $check->fetch();

    if ($existing) {
        if ($confirmOverwrite !== true) {
            sendJsonResponse([
                'success'  => false,
                'conflict' => true,
                'message'  => 'You already logged your weight for this date. Replace it?',
            ]);
        }

        // Overwrite existing entry
        $stmt = $pdo->prepare(
            'UPDATE weight_logs SET weight_kg = ?, notes = ? WHERE user_id = ? AND log_date = ?'
        );
        $stmt->execute([(float) $weightKg, $notes, $userId, $logDate]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO weight_logs (user_id, weight_kg, log_date, notes) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, (float) $weightKg, $logDate, $notes]);
    }

    sendJsonResponse(['success' => true, 'message' => 'Weight logged.']);
}

/**
 * POST action=update_account
 * Members only — update username, email, first/last name.
 */
function handleUpdateAccount(PDO $pdo, int $userId, string $role, array $input): void
{
    if ($role !== 'member') {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    $firstName = trim($input['first_name'] ?? '');
    $lastName  = trim($input['last_name']  ?? '');
    $username  = trim($input['username']   ?? '');
    $email     = trim($input['email']      ?? '');

    $errors = [];
    if ($firstName === '') $errors[] = 'First name is required.';
    if ($lastName  === '') $errors[] = 'Last name is required.';
    if ($username  === '') $errors[] = 'Username is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'errors' => $errors]);
    }

    // Check username uniqueness (exclude self)
    $uCheck = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
    $uCheck->execute([$username, $userId]);
    if ($uCheck->fetch()) {
        sendJsonResponse(['success' => false, 'errors' => ['Username is already taken.']]);
    }

    // Check email uniqueness if provided
    if ($email !== '') {
        $eCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $eCheck->execute([$email, $userId]);
        if ($eCheck->fetch()) {
            sendJsonResponse(['success' => false, 'errors' => ['Email is already in use.']]);
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$firstName, $lastName, $username, $email ?: null, $userId]);

    // Update session name
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name']  = $lastName;

    sendJsonResponse(['success' => true, 'message' => 'Account updated successfully.']);
}

/**
 * POST action=upload_avatar (multipart/form-data)
 * Members only — upload a profile picture.
 */
function handleUploadAvatar(PDO $pdo, int $userId, string $role): void
{
    if ($role !== 'member') {
        sendJsonResponse(['error' => 'Access denied.'], 403);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }

    if (empty($_FILES['profile_photo']['tmp_name'])) {
        sendJsonResponse(['error' => 'No file uploaded.'], 400);
    }

    $file    = $_FILES['profile_photo'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
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
    $filename = 'member_' . $userId . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        sendJsonResponse(['error' => 'Failed to save photo.'], 500);
    }

    $photoPath = 'uploads/avatars/' . $filename;

    // Delete old photo
    $oldStmt = $pdo->prepare('SELECT profile_photo FROM users WHERE id = ?');
    $oldStmt->execute([$userId]);
    $oldPhoto = $oldStmt->fetchColumn();
    if ($oldPhoto && file_exists(__DIR__ . '/../' . $oldPhoto)) {
        @unlink(__DIR__ . '/../' . $oldPhoto);
    }

    $pdo->prepare('UPDATE users SET profile_photo = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$photoPath, $userId]);

    sendJsonResponse(['success' => true, 'profile_photo' => $photoPath]);
}

/**
 * POST action=save_theme
 * All roles — persist theme preference to users table.
 */
function handleSaveTheme(PDO $pdo, int $userId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }
    $theme = ($input['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
    $pdo->prepare('UPDATE users SET theme_preference = ? WHERE id = ?')
        ->execute([$theme, $userId]);
    sendJsonResponse(['success' => true]);
}
