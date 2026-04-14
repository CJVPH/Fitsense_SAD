<?php
/**
 * FitSense — Chat API
 * Requirements: 6.1–6.9, 7.1, 7.2, 19.3
 */
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
// require_once '../includes/gemini.php'; // Disabled — using Claude
require_once '../includes/claude.php';
require_once '../includes/openai.php';

$auth = new Auth();
$auth->requireRole('member');

$pdo    = Database::getConnection();
$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($action) && !empty($input['action'])) {
    $action = $input['action'];
}

// Ensure session_id column exists on chat_messages
ensureChatSessionColumn($pdo);

// ─── Route ────────────────────────────────────────────────────────────────────
switch ($action) {

    case 'sessions':
        handleGetSessions($pdo, $userId);
        break;

    case 'new_session':
        handleNewSession($pdo, $userId, $input);
        break;

    case 'history':
        handleHistory($pdo, $userId);
        break;

    case 'send_message':
        handleSendMessage($pdo, $userId, $input);
        break;

    case 'clear_chat':
        handleClearChat($pdo, $userId, $input);
        break;

    case 'send_member_message':
        handleSendMemberMessage($pdo, $userId, $input);
        break;

    case 'trainer_messages':
        handleGetTrainerMessages($pdo, $userId);
        break;

    case 'welcome_message':
        handleWelcomeMessage($pdo, $userId);
        break;

    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
        break;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ensureChatSessionColumn(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN session_id INT NULL DEFAULT NULL");
    } catch (PDOException $e) {
        // Column already exists — ignore
    }
}

/**
 * GET action=sessions
 * Returns all chat sessions for this member, newest first.
 * Each session = first member message as title + message count.
 */
function handleGetSessions(PDO $pdo, int $userId): void
{
    // Get distinct session_ids with their first member message and timestamp
    $stmt = $pdo->prepare(
        "SELECT
            session_id,
            MIN(created_at) AS started_at,
            (SELECT message FROM chat_messages cm2
              WHERE cm2.user_id = :uid2 AND cm2.session_id = cm.session_id
                AND cm2.sender_type = 'member'
              ORDER BY cm2.created_at ASC LIMIT 1) AS first_message
         FROM chat_messages cm
         WHERE user_id = :uid AND session_id IS NOT NULL
         GROUP BY session_id
         ORDER BY started_at DESC
         LIMIT 20"
    );
    $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
    $sessions = $stmt->fetchAll();

    sendJsonResponse(['success' => true, 'sessions' => $sessions]);
}

/**
 * POST action=new_session
 * Creates a new session ID (just returns a new ID — messages will reference it).
 */
function handleNewSession(PDO $pdo, int $userId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['error' => 'Invalid request.'], 403);
    }
    // Use a timestamp-based session ID unique per user
    $sessionId = $userId * 1000000 + (time() % 1000000);
    sendJsonResponse(['success' => true, 'session_id' => $sessionId]);
}

/**
 * GET action=history&session_id=X
 * Returns messages for a specific session (or latest session if none given).
 */
function handleHistory(PDO $pdo, int $userId): void
{
    $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : null;

    if ($sessionId) {
        $stmt = $pdo->prepare(
            "SELECT cm.*, u.first_name, u.last_name
               FROM chat_messages cm
               LEFT JOIN users u ON u.id = cm.sender_id AND cm.sender_type = 'trainer'
              WHERE cm.user_id = :uid AND cm.session_id = :sid
              ORDER BY cm.created_at ASC
              LIMIT 100"
        );
        $stmt->execute([':uid' => $userId, ':sid' => $sessionId]);
    } else {
        // Latest session
        $latestStmt = $pdo->prepare(
            "SELECT session_id FROM chat_messages
              WHERE user_id = :uid AND session_id IS NOT NULL
              ORDER BY created_at DESC LIMIT 1"
        );
        $latestStmt->execute([':uid' => $userId]);
        $latest = $latestStmt->fetchColumn();

        if (!$latest) {
            sendJsonResponse(['success' => true, 'messages' => [], 'session_id' => null]);
            return;
        }

        $stmt = $pdo->prepare(
            "SELECT cm.*, u.first_name, u.last_name
               FROM chat_messages cm
               LEFT JOIN users u ON u.id = cm.sender_id AND cm.sender_type = 'trainer'
              WHERE cm.user_id = :uid AND cm.session_id = :sid
              ORDER BY cm.created_at ASC
              LIMIT 100"
        );
        $stmt->execute([':uid' => $userId, ':sid' => $latest]);
        $sessionId = $latest;
    }

    $rows = $stmt->fetchAll();
    $messages = array_map(function (array $row) use ($pdo): array {
        if ($row['sender_type'] !== 'trainer') {
            $row['first_name'] = null;
            $row['last_name']  = null;
        }
        // For recommendation messages, attach trainer_notes and reviewer name
        if ($row['message_type'] === 'recommendation') {
            $recStmt = $pdo->prepare(
                'SELECT ar.trainer_notes, ar.status, u.first_name, u.last_name
                   FROM ai_recommendations ar
                   LEFT JOIN users u ON u.id = ar.reviewed_by
                  WHERE ar.user_id = :uid
                  ORDER BY ar.created_at DESC LIMIT 1'
            );
            $recStmt->execute([':uid' => $row['user_id']]);
            $rec = $recStmt->fetch();
            if ($rec) {
                $row['trainer_notes']    = $rec['trainer_notes'];
                $row['status']           = $rec['status'];
                $row['reviewed_by_name'] = ($rec['first_name'] && $rec['last_name'])
                    ? $rec['first_name'] . ' ' . $rec['last_name']
                    : null;
            }
        }
        return $row;
    }, $rows);

    sendJsonResponse(['success' => true, 'messages' => $messages, 'session_id' => $sessionId]);
}

/**
 * POST action=send_message
 */
function handleSendMessage(PDO $pdo, int $userId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
    }

    $message   = trim($input['message'] ?? '');
    $sessionId = isset($input['session_id']) ? (int) $input['session_id'] : null;

    if ($message === '') {
        sendJsonResponse(['success' => false, 'message' => 'Message cannot be empty.']);
    }

    // Daily AI limit
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM chat_messages
          WHERE user_id = :uid AND sender_type = 'ai' AND DATE(created_at) = CURDATE()"
    );
    $countStmt->execute([':uid' => $userId]);
    $dailyCount = (int) $countStmt->fetchColumn();
    $maxLimit   = (int) getSystemSetting('max_ai_requests_per_day', $pdo, '50');

    if ($dailyCount >= $maxLimit) {
        sendJsonResponse([
            'success'       => false,
            'message'       => "You've reached your daily AI limit. It resets at midnight.",
            'limit_reached' => true,
        ], 429);
    }

    // Member profile
    $profileStmt = $pdo->prepare(
        'SELECT users.first_name, member_profiles.*
           FROM users
           JOIN member_profiles ON member_profiles.user_id = users.id
          WHERE users.id = :uid LIMIT 1'
    );
    $profileStmt->execute([':uid' => $userId]);
    $profile = $profileStmt->fetch();

    if ($profile === false) {
        sendJsonResponse(['success' => false, 'message' => 'Please complete your health profile first.']);
    }

    // Active goal
    $goalStmt = $pdo->prepare(
        "SELECT * FROM fitness_goals WHERE user_id = :uid AND status = 'active' ORDER BY id DESC LIMIT 1"
    );
    $goalStmt->execute([':uid' => $userId]);
    $goal = $goalStmt->fetch();

    // AI: Claude primary → OpenAI fallback
    if (CLAUDE_API_KEY) {
        $ai = new ClaudeClient();
    } else {
        $ai = new OpenAIClient();
    }

    // Fetch last 10 messages from this session for conversation memory
    $history = [];
    if ($sessionId) {
        $histStmt = $pdo->prepare(
            "SELECT sender_type, message, message_type
               FROM chat_messages
              WHERE user_id = :uid AND session_id = :sid
                AND sender_type IN ('member','ai')
              ORDER BY created_at DESC
              LIMIT 10"
        );
        $histStmt->execute([':uid' => $userId, ':sid' => $sessionId]);
        $history = array_reverse($histStmt->fetchAll()); // oldest first
    }

    $prompt = $ai->buildPrompt($message, $profile, $goal ?: [], $history);
    $result = $ai->sendRequest($prompt);

    if (!$result['success']) {
        sendJsonResponse(['success' => false, 'message' => $result['error'] ?? 'Something went wrong — please try again.']);
    }

    $aiText = $result['text'];

    // Persist member message
    $insertMember = $pdo->prepare(
        "INSERT INTO chat_messages (user_id, sender_type, sender_id, message, message_type, session_id)
         VALUES (:uid, 'member', :sid, :msg, 'text', :sess)"
    );
    $insertMember->execute([':uid' => $userId, ':sid' => $userId, ':msg' => $message, ':sess' => $sessionId]);

    // Persist AI response
    $insertAi = $pdo->prepare(
        "INSERT INTO chat_messages (user_id, sender_type, sender_id, message, message_type, session_id)
         VALUES (:uid, 'ai', NULL, :msg, 'text', :sess)"
    );
    $insertAi->execute([':uid' => $userId, ':msg' => $aiText, ':sess' => $sessionId]);
    $aiMessageId = (int) $pdo->lastInsertId();

    // Structured recommendation
    $isRecommendation = false;
    $recommendationId = null;

    if ($ai->isStructuredRequest($message)) {
        $parsed = $ai->parseStructuredResponse($aiText);
        if ($parsed !== null) {
            $type  = isset($parsed['exercises']) ? 'workout' : 'meal_plan';
            $title = $parsed['title'] ?? ($type === 'workout' ? 'Workout Plan' : 'Meal Plan');

            $insertRec = $pdo->prepare(
                "INSERT INTO ai_recommendations
                    (user_id, type, title, content, ai_prompt, ai_response, status)
                 VALUES (:uid, :type, :title, :content, :prompt, :response, 'pending')"
            );
            $insertRec->execute([
                ':uid'      => $userId,
                ':type'     => $type,
                ':title'    => $title,
                ':content'  => json_encode($parsed),
                ':prompt'   => $prompt,
                ':response' => $aiText,
            ]);
            $recommendationId = (int) $pdo->lastInsertId();

            $pdo->prepare("UPDATE chat_messages SET message_type = 'recommendation' WHERE id = :id")
                ->execute([':id' => $aiMessageId]);

            $isRecommendation = true;
        }
    }

    sendJsonResponse([
        'success'           => true,
        'message'           => $aiText,
        'is_recommendation' => $isRecommendation,
        'recommendation_id' => $recommendationId,
    ]);
}

/**
 * POST action=clear_chat
 */
function handleClearChat(PDO $pdo, int $userId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
    }

    $sessionId = isset($input['session_id']) ? (int) $input['session_id'] : null;

    if ($sessionId) {
        $stmt = $pdo->prepare(
            "DELETE FROM chat_messages WHERE user_id = :uid AND session_id = :sid AND sender_type IN ('member','ai')"
        );
        $stmt->execute([':uid' => $userId, ':sid' => $sessionId]);
    } else {
        $stmt = $pdo->prepare(
            "DELETE FROM chat_messages WHERE user_id = :uid AND sender_type IN ('member','ai')"
        );
        $stmt->execute([':uid' => $userId]);
    }

    sendJsonResponse(['success' => true]);
}

/**
 * POST action=send_member_message
 * Member sends a message to their assigned trainer.
 */
function handleSendMemberMessage(PDO $pdo, int $userId, array $input): void
{
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
    }

    $message = trim($input['message'] ?? '');
    if ($message === '') {
        sendJsonResponse(['success' => false, 'message' => 'Message cannot be empty.']);
    }

    // Verify member has an assigned trainer
    $trainerStmt = $pdo->prepare(
        'SELECT assigned_trainer_id FROM member_profiles WHERE user_id = ? LIMIT 1'
    );
    $trainerStmt->execute([$userId]);
    $row = $trainerStmt->fetch();

    if (!$row || !$row['assigned_trainer_id']) {
        sendJsonResponse(['success' => false, 'message' => 'No trainer assigned.']);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO chat_messages (user_id, sender_type, sender_id, message, message_type)
         VALUES (?, 'member', ?, ?, 'text')"
    );
    $stmt->execute([$userId, $userId, $message]);

    sendJsonResponse(['success' => true]);
}

/**
 * GET action=trainer_messages
 * Returns count of new trainer messages since last check (for polling).
 */
function handleGetTrainerMessages(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM chat_messages WHERE user_id = ? AND sender_type = 'trainer' AND is_read = FALSE"
    );
    $stmt->execute([$userId]);
    $count = (int) $stmt->fetchColumn();

    sendJsonResponse(['success' => true, 'new_count' => $count]);
}

/**
 * GET action=welcome_message
 * Returns a personalized welcome message for first-time chat.
 * Only fires if the member has never sent an AI chat message before.
 */
function handleWelcomeMessage(PDO $pdo, int $userId): void
{
    // Check if member has any prior AI chat messages
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM chat_messages WHERE user_id = ? AND sender_type IN ('member','ai') AND session_id IS NOT NULL"
    );
    $check->execute([$userId]);
    if ((int) $check->fetchColumn() > 0) {
        sendJsonResponse(['success' => true, 'show' => false]);
        return;
    }

    // Fetch member profile + goal
    $stmt = $pdo->prepare(
        'SELECT u.first_name, mp.current_weight_kg, fg.goal_type
           FROM users u
           LEFT JOIN member_profiles mp ON mp.user_id = u.id
           LEFT JOIN fitness_goals fg   ON fg.user_id = u.id AND fg.status = \'active\'
          WHERE u.id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        sendJsonResponse(['success' => true, 'show' => false]);
        return;
    }

    $name   = htmlspecialchars_decode($row['first_name'] ?? 'there', ENT_QUOTES | ENT_HTML5);
    $weight = $row['current_weight_kg'] ? $row['current_weight_kg'] . ' kg' : 'not set yet';
    $goal   = $row['goal_type']
        ? ucwords(str_replace('_', ' ', $row['goal_type']))
        : 'general fitness';

    $message = "Hi {$name}! 👋 I can see your goal is **{$goal}** and your current weight is **{$weight}**. "
             . "I'm your personal AI fitness coach — I'm here to help you reach your goals with personalized workout plans, nutrition advice, and daily support. "
             . "Ready to get started? Try asking me to **generate your first workout plan**! 💪";

    sendJsonResponse(['success' => true, 'show' => true, 'message' => $message]);
}
