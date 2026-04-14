<?php
/**
 * FitSense — Member Progress Dashboard
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireRole('member');
// Redirect to one-page app
header('Location: chat.php#progress');
exit;

$pdo         = Database::getConnection();
$currentUser = $auth->getCurrentUser();
$csrfToken   = generateCsrfToken();
$userId      = (int) $_SESSION['user_id'];

$announcements = getActiveAnnouncements('member', $pdo);

$wsStmt = $pdo->prepare('SELECT * FROM workout_sessions WHERE user_id = ? ORDER BY session_date DESC LIMIT 10');
$wsStmt->execute([$userId]);
$workoutSessions = $wsStmt->fetchAll();

$wlChartStmt = $pdo->prepare('SELECT weight_kg, log_date FROM weight_logs WHERE user_id = ? ORDER BY log_date ASC LIMIT 30');
$wlChartStmt->execute([$userId]);
$weightLogsChart = $wlChartStmt->fetchAll();

$wlLatestStmt = $pdo->prepare('SELECT weight_kg, log_date FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1');
$wlLatestStmt->execute([$userId]);
$latestWeight = $wlLatestStmt->fetch();

// Fallback: if no weight log yet, use member_profiles.current_weight_kg
if (!$latestWeight && !empty($currentUser['current_weight_kg'])) {
    $latestWeight = ['weight_kg' => $currentUser['current_weight_kg'], 'log_date' => null];
}

$recStmt = $pdo->prepare('SELECT * FROM ai_recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$recStmt->execute([$userId]);
$recommendations = $recStmt->fetchAll();

$goalStmt = $pdo->prepare("SELECT * FROM fitness_goals WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
$goalStmt->execute([$userId]);
$activeGoal = $goalStmt->fetch();

$bmi = $bmiCategory = null;
$heightCm = (float)($currentUser['height_cm'] ?? 0);
$weightKg = $latestWeight ? (float)$latestWeight['weight_kg'] : 0;
if ($heightCm > 0 && $weightKg > 0) {
    $bmi = calculateBMI($weightKg, $heightCm);
    $bmiCategory = $bmi !== null ? getBMICategory($bmi) : null;
}

$weekStmt = $pdo->prepare("SELECT COUNT(*) FROM workout_sessions WHERE user_id = ? AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weekStmt->execute([$userId]);
$workoutsThisWeek = (int)$weekStmt->fetchColumn();

$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE user_id = ? AND sender_type = 'trainer' AND is_read = FALSE");
$unreadStmt->execute([$userId]);
$unreadTrainer = (int)$unreadStmt->fetchColumn();

function formatGoalType(string $t): string { return ucwords(str_replace('_', ' ', $t)); }
function renderStars(int $r): string { $o=''; for($i=1;$i<=5;$i++) $o.=$i<=$r?'★':'☆'; return $o; }

$firstName    = htmlspecialchars($currentUser['first_name']    ?? 'there', ENT_QUOTES|ENT_HTML5,'UTF-8');
$lastName     = htmlspecialchars($currentUser['last_name']     ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$username     = htmlspecialchars($currentUser['username']      ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$profilePhoto = htmlspecialchars($currentUser['profile_photo'] ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$initials     = strtoupper(substr($firstName,0,1).substr($lastName,0,1));
$today        = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Progress — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        #sidebar { transition: transform 0.25s ease; }
        #sidebar-overlay { transition: opacity 0.25s ease; }
        #main-scroll::-webkit-scrollbar,
        #session-list::-webkit-scrollbar { width: 4px; }
        #main-scroll::-webkit-scrollbar-track,
        #session-list::-webkit-scrollbar-track { background: transparent; }
        #main-scroll::-webkit-scrollbar-thumb,
        #session-list::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        .toast-show  { opacity:1; transform:translateY(0);   transition:opacity .2s,transform .2s; }
        .toast-leave { opacity:0; transform:translateY(8px); transition:opacity .3s,transform .3s; }
    </style>
</head>
<body class="bg-black text-white" style="min-width:375px" data-height-cm="<?= htmlspecialchars((string)($currentUser['height_cm'] ?? 0), ENT_QUOTES|ENT_HTML5, 'UTF-8') ?>">

<div class="flex h-screen w-screen overflow-hidden">

<!-- ══════════════════════════════════════════════════════════
     SIDEBAR (same shell as chat.php)
══════════════════════════════════════════════════════════ -->
<aside id="sidebar"
    class="fixed lg:relative z-50 lg:z-auto top-0 left-0 h-full w-72 bg-zinc-900 border-r border-zinc-800
           flex flex-col -translate-x-full lg:translate-x-0 shrink-0">

    <!-- Logo + close -->
    <div class="px-4 pt-4 pb-3 shrink-0 flex items-center justify-between">
        <span class="text-yellow-400 font-bold text-lg tracking-tight">FitSense</span>
        <button id="sidebar-close-btn"
            class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl hover:bg-zinc-800 transition-colors text-zinc-400 hover:text-white"
            aria-label="Close sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- AI Chat link -->
    <div class="px-3 pb-2 shrink-0">
        <a href="chat.php"
            class="w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-xl border border-zinc-700
                   hover:bg-zinc-800 transition-colors text-sm font-medium text-zinc-300 hover:text-white">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            AI Chat
        </a>
    </div>

    <!-- Spacer -->
    <div class="flex-1 min-h-0"></div>

    <!-- ── Bottom nav ─────────────────────────────────────────────────── -->
    <div class="shrink-0 border-t border-zinc-800 px-2 py-2 space-y-0.5">

        <!-- Progress (active) -->
        <a href="member-dashboard.php"
            class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl bg-zinc-800 text-white text-sm font-medium">
            <svg class="w-5 h-5 shrink-0 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Progress
        </a>

        <!-- Trainer Chat -->
        <a href="member-trainer-chat.php"
            class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-zinc-300 hover:text-white text-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Trainer Chat
            <?php if ($unreadTrainer > 0): ?>
            <span class="ml-auto bg-yellow-400 text-black text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shrink-0">
                <?= $unreadTrainer > 9 ? '9+' : $unreadTrainer ?>
            </span>
            <?php endif; ?>
        </a>

        <div class="border-t border-zinc-800 my-1"></div>

        <!-- User menu -->
        <div class="relative">
            <button id="user-menu-btn"
                class="w-full flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-left">
                <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
                <img src="<?= $profilePhoto ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover shrink-0">
                <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs shrink-0">
                    <?= $initials ?>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= $firstName ?> <?= $lastName ?></p>
                    <p class="text-xs text-zinc-500 truncate">@<?= $username ?></p>
                </div>
                <svg class="menu-chevron w-4 h-4 text-zinc-500 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                </svg>
            </button>
            <div id="user-menu-dropdown"
                class="hidden absolute bottom-full left-0 right-0 mb-1 bg-zinc-800 border border-zinc-700 rounded-xl shadow-xl overflow-hidden z-10">
                <div class="px-4 py-3 border-b border-zinc-700 flex items-center gap-3">
                    <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
                    <img src="<?= $profilePhoto ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover shrink-0">
                    <?php else: ?>
                    <div class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm shrink-0"><?= $initials ?></div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white truncate"><?= $firstName ?> <?= $lastName ?></p>
                        <p class="text-xs text-zinc-400 truncate">@<?= $username ?></p>
                    </div>
                </div>
                <a href="profile-settings.php" class="flex items-center gap-3 px-4 py-3 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors min-h-[44px]">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Edit Profile
                </a>
                <div class="border-t border-zinc-700"></div>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-sm text-red-400 hover:bg-zinc-700 hover:text-red-300 transition-colors min-h-[44px]">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/></svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden opacity-0 lg:hidden"></div>

<!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════════════ -->
<div class="flex flex-col flex-1 min-w-0 h-full">

    <!-- Mobile top bar -->
    <header class="lg:hidden flex items-center justify-between px-4 h-14 border-b border-zinc-800 bg-zinc-900 shrink-0">
        <button id="sidebar-open-btn" aria-label="Open sidebar"
            class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="text-yellow-400 font-bold text-lg tracking-tight">Progress</span>
        <div class="w-11"></div>
    </header>

    <?php if (!empty($announcements)): ?>
    <div id="announcement-banner" class="shrink-0 mx-4 mt-3 flex items-start gap-3 bg-yellow-400/10 border border-yellow-400/40 text-yellow-300 px-4 py-3 rounded-xl text-xs" role="alert">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        <span class="flex-1"><strong class="font-semibold"><?= htmlspecialchars($announcements[0]['title'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?>:</strong> <?= htmlspecialchars($announcements[0]['content'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span>
        <button onclick="document.getElementById('announcement-banner').remove()" class="shrink-0 text-yellow-400 hover:text-yellow-200 p-1 min-h-[28px] min-w-[28px] flex items-center justify-center rounded-lg transition-colors" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- Scrollable content -->
    <div id="main-scroll" class="flex-1 overflow-y-auto min-h-0">
        <main class="px-4 py-6 max-w-2xl mx-auto w-full space-y-8">

    <!-- Stats Cards -->
    <section aria-labelledby="stats-heading">
        <h2 id="stats-heading" class="text-lg font-bold text-yellow-400 mb-3">Overview</h2>
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">Current Weight</p>
                <p class="text-2xl font-bold text-white" data-stat="weight"><?= $latestWeight ? htmlspecialchars($latestWeight['weight_kg'],ENT_QUOTES|ENT_HTML5,'UTF-8').' kg' : '—' ?></p>
                <?php if ($latestWeight): ?><p class="text-xs text-zinc-500 mt-1"><?= htmlspecialchars(formatDate($latestWeight['log_date']),ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p><?php endif; ?>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">BMI</p>
                <p class="text-2xl font-bold text-white" data-stat="bmi"><?= $bmi !== null ? htmlspecialchars((string)$bmi,ENT_QUOTES|ENT_HTML5,'UTF-8') : '—' ?></p>
                <?php if ($bmiCategory):
                    $bmiColour = match($bmiCategory) {
                        'Underweight'   => 'bg-blue-800 text-blue-200',
                        'Normal weight' => 'bg-green-800 text-green-200',
                        'Overweight'    => 'bg-yellow-700 text-yellow-100',
                        default         => 'bg-red-800 text-red-200',
                    };
                ?><span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full <?= $bmiColour ?>" data-stat="bmi-tag"><?= htmlspecialchars($bmiCategory,ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span><?php endif; ?>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">Active Goal</p>
                <p class="text-base font-semibold text-white leading-tight"><?= $activeGoal ? htmlspecialchars(formatGoalType($activeGoal['goal_type']),ENT_QUOTES|ENT_HTML5,'UTF-8') : '—' ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">Workouts This Week</p>
                <p class="text-2xl font-bold text-yellow-400"><?= $workoutsThisWeek ?></p>
            </div>
        </div>
    </section>

    <!-- Log Today -->
    <section aria-labelledby="log-heading">
        <h2 id="log-heading" class="text-lg font-bold text-yellow-400 mb-3">Log Today</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Workout Form -->
            <form id="workout-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-3" novalidate>
                <h3 class="font-semibold text-white">Workout</h3>
                <div>
                    <label for="session_date" class="block text-xs text-zinc-400 mb-1">Date</label>
                    <input type="date" id="session_date" name="session_date" value="<?= htmlspecialchars($today,ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
                </div>
                <div>
                    <label for="duration_minutes" class="block text-xs text-zinc-400 mb-1">Duration (minutes)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="600" placeholder="e.g. 45"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
                </div>
                <div>
                    <label for="rating" class="block text-xs text-zinc-400 mb-1">Rating (1–5)</label>
                    <div class="flex gap-2" id="star-rating" role="group" aria-label="Workout rating">
                        <?php for ($s=1;$s<=5;$s++): ?>
                        <button type="button" data-star="<?= $s ?>"
                            class="star-btn text-2xl text-zinc-600 hover:text-yellow-400 min-h-[44px] min-w-[44px] flex items-center justify-center transition-colors"
                            aria-label="<?= $s ?> star<?= $s>1?'s':'' ?>">★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="rating" name="rating" value="">
                </div>
                <div>
                    <label for="calories_burned" class="block text-xs text-zinc-400 mb-1">Calories Burned (optional)</label>
                    <input type="number" id="calories_burned" name="calories_burned" min="0" placeholder="e.g. 300"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                </div>
                <div>
                    <label for="workout_notes" class="block text-xs text-zinc-400 mb-1">Notes (optional)</label>
                    <textarea id="workout_notes" name="notes" rows="2" placeholder="How did it go?"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Log Workout</button>
            </form>

            <!-- Weight Form -->
            <form id="weight-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-3" novalidate>
                <h3 class="font-semibold text-white">Weight</h3>
                <div>
                    <label for="log_date" class="block text-xs text-zinc-400 mb-1">Date</label>
                    <input type="date" id="log_date" name="log_date" value="<?= htmlspecialchars($today,ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
                </div>
                <div>
                    <label for="weight_kg" class="block text-xs text-zinc-400 mb-1">Weight (kg)</label>
                    <input type="number" id="weight_kg" name="weight_kg" min="20" max="500" step="0.1" placeholder="e.g. 75.5"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
                </div>
                <div>
                    <label for="weight_notes" class="block text-xs text-zinc-400 mb-1">Notes (optional)</label>
                    <textarea id="weight_notes" name="notes" rows="2" placeholder="Any notes?"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Log Weight</button>
            </form>
        </div>
    </section>

    <!-- Weight Chart -->
    <section aria-labelledby="chart-heading">
        <h2 id="chart-heading" class="text-lg font-bold text-yellow-400 mb-3">Weight Progress</h2>
        <?php if (!empty($weightLogsChart)): ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <canvas id="weight-chart" height="200" aria-label="Weight progress chart" role="img"></canvas>
        </div>
        <?php else: ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">No weight logs yet.</div>
        <?php endif; ?>
    </section>

    <!-- Recent Workouts -->
    <section aria-labelledby="history-heading">
        <h2 id="history-heading" class="text-lg font-bold text-yellow-400 mb-3">Recent Workouts</h2>
        <?php if (!empty($workoutSessions)): ?>
        <div class="space-y-3">
            <?php foreach ($workoutSessions as $session): ?>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-white text-sm"><?= htmlspecialchars(formatDate($session['session_date']),ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p>
                        <p class="text-xs text-zinc-400 mt-0.5"><?= (int)$session['duration_minutes'] ?> min<?php if ($session['calories_burned']): ?> · <?= (int)$session['calories_burned'] ?> kcal<?php endif; ?></p>
                    </div>
                    <?php if ($session['rating']): ?>
                    <span class="text-yellow-400 text-sm shrink-0"><?= renderStars((int)$session['rating']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($session['notes'])): ?>
                <p class="text-xs text-zinc-400 mt-2 line-clamp-2"><?= htmlspecialchars($session['notes'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">No workouts logged yet.</div>
        <?php endif; ?>
    </section>

    <!-- AI Recommendations -->
    <section aria-labelledby="recs-heading">
        <h2 id="recs-heading" class="text-lg font-bold text-yellow-400 mb-3">My Recommendations</h2>
        <?php if (!empty($recommendations)): ?>
        <div class="space-y-3">
            <?php foreach ($recommendations as $rec):
                $statusColour = match($rec['status']) { 'approved'=>'bg-green-800 text-green-200','modified'=>'bg-blue-800 text-blue-200','rejected'=>'bg-red-800 text-red-200',default=>'bg-zinc-700 text-zinc-300' };
                $typeColour   = match($rec['type'])   { 'workout'=>'bg-yellow-700 text-yellow-100','meal_plan'=>'bg-purple-800 text-purple-200',default=>'bg-zinc-700 text-zinc-300' };
                $statusLabel  = match($rec['status']) { 'approved'=>'Approved','modified'=>'Modified','rejected'=>'Rejected',default=>'Pending Review' };
            ?>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <p class="font-semibold text-white text-sm leading-tight"><?= htmlspecialchars($rec['title'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p>
                    <div class="flex gap-1 shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $typeColour ?>"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$rec['type'])),ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $statusColour ?>"><?= htmlspecialchars($statusLabel,ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span>
                    </div>
                </div>
                <?php if ($rec['status'] !== 'pending' && !empty($rec['trainer_notes'])): ?>
                <div class="mt-2 border-t border-zinc-700 pt-2">
                    <p class="text-xs text-zinc-400 font-medium mb-0.5">Trainer notes:</p>
                    <p class="text-xs text-zinc-300"><?= htmlspecialchars($rec['trainer_notes'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p>
                </div>
                <?php endif; ?>
                <p class="text-xs text-zinc-500 mt-2"><?= htmlspecialchars(formatRelativeTime($rec['created_at']),ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">No recommendations yet. <a href="chat.php" class="text-yellow-400 hover:underline">Chat with AI</a> to get started.</div>
        <?php endif; ?>
    </section>

        </main>
    </div><!-- end main-scroll -->
</div><!-- end main col -->
</div><!-- end root -->

<!-- Overwrite dialog -->
<div id="overwrite-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-sm w-full space-y-4">
        <p class="text-white font-semibold">Replace existing entry?</p>
        <p class="text-zinc-400 text-sm">You already logged your weight for this date. Replace it?</p>
        <div class="flex gap-3">
            <button id="overwrite-cancel" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] font-medium transition-colors">Cancel</button>
            <button id="overwrite-confirm" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black rounded-lg px-4 py-3 min-h-[44px] font-bold transition-colors">Replace</button>
        </div>
    </div>
</div>

<div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0" role="status" aria-live="polite"></div>

<script>
const FITSENSE_CSRF = <?= json_encode($csrfToken) ?>;
const WEIGHT_CHART_DATA = <?= !empty($weightLogsChart) ? json_encode(array_map(fn($r)=>['date'=>$r['log_date'],'weight'=>(float)$r['weight_kg']],$weightLogsChart)) : '[]' ?>;

// Sidebar
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');
document.getElementById('sidebar-open-btn').addEventListener('click', () => {
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    requestAnimationFrame(() => overlay.classList.remove('opacity-0'));
});
document.getElementById('sidebar-close-btn').addEventListener('click', closeSidebar);
overlay.addEventListener('click', closeSidebar);
function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('opacity-0');
    setTimeout(() => overlay.classList.add('hidden'), 250);
}

// User menu
document.getElementById('user-menu-btn').addEventListener('click', function(e) {
    e.stopPropagation();
    const dd = document.getElementById('user-menu-dropdown');
    const ch = this.querySelector('.menu-chevron');
    const hidden = dd.classList.toggle('hidden');
    if (ch) ch.style.transform = hidden ? '' : 'rotate(180deg)';
});
document.addEventListener('click', () => {
    const dd = document.getElementById('user-menu-dropdown');
    if (dd && !dd.classList.contains('hidden')) {
        dd.classList.add('hidden');
        const ch = document.querySelector('.menu-chevron');
        if (ch) ch.style.transform = '';
    }
});

// Toast
function showToast(msg, isError=false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none toast-show '
        + (isError ? 'bg-red-700 text-white' : 'bg-green-700 text-white');
    setTimeout(() => {
        t.classList.remove('toast-show'); t.classList.add('toast-leave');
        setTimeout(() => { t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0'; }, 300);
    }, 3000);
}

// Star rating
let selectedRating = 0;
document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        selectedRating = parseInt(btn.dataset.star);
        document.getElementById('rating').value = selectedRating;
        document.querySelectorAll('.star-btn').forEach(b => {
            b.classList.toggle('text-yellow-400', parseInt(b.dataset.star) <= selectedRating);
            b.classList.toggle('text-zinc-600',   parseInt(b.dataset.star) >  selectedRating);
        });
    });
});

// Workout form
document.getElementById('workout-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target, btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Logging…';
    try {
        const res = await fetch('api/members.php', { method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'log_workout', session_date:form.session_date.value, duration_minutes:form.duration_minutes.value,
                rating:form.rating.value||null, notes:form.notes.value||null, calories_burned:form.calories_burned.value||null, csrf_token:FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) {
            showToast('💪 Workout logged!');
            form.reset(); form.session_date.value = new Date().toISOString().split('T')[0];
            selectedRating = 0; document.querySelectorAll('.star-btn').forEach(b => { b.classList.remove('text-yellow-400'); b.classList.add('text-zinc-600'); });
            setTimeout(() => location.reload(), 1500);
        } else { showToast(data.errors ? data.errors.join(' ') : (data.message||'Failed.'), true); }
    } catch { showToast('Network error.', true); }
    finally { btn.disabled=false; btn.textContent='Log Workout'; }
});

// Weight form
let pendingWeightPayload = null;
async function submitWeight(payload) {
    const btn = document.querySelector('#weight-form button[type="submit"]');
    btn.disabled=true; btn.textContent='Logging…';
    try {
        const res = await fetch('api/members.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) {
            showToast('⚖️ Weight logged!');
            document.getElementById('weight-form').reset();
            document.getElementById('log_date').value = new Date().toISOString().split('T')[0];
            // Live update weight & BMI cards without reload
            const newWeight = parseFloat(payload.weight_kg);
            const weightEl = document.querySelector('[data-stat="weight"]');
            const bmiEl    = document.querySelector('[data-stat="bmi"]');
            const bmiTagEl = document.querySelector('[data-stat="bmi-tag"]');
            if (weightEl) weightEl.textContent = newWeight.toFixed(1) + ' kg';
            if (bmiEl) {
                const heightCm = parseFloat(document.body.dataset.heightCm || 0);
                if (heightCm > 0) {
                    const bmi = Math.round((newWeight / Math.pow(heightCm / 100, 2)) * 10) / 10;
                    bmiEl.textContent = bmi;
                    if (bmiTagEl) {
                        let cat = 'Obese', col = 'bg-red-800 text-red-200';
                        if (bmi < 18.5)      { cat = 'Underweight'; col = 'bg-blue-800 text-blue-200'; }
                        else if (bmi < 25)   { cat = 'Normal weight'; col = 'bg-green-800 text-green-200'; }
                        else if (bmi < 30)   { cat = 'Overweight'; col = 'bg-yellow-700 text-yellow-100'; }
                        bmiTagEl.textContent = cat;
                        bmiTagEl.className = 'inline-block mt-1 text-xs px-2 py-0.5 rounded-full ' + col;
                    }
                }
            }
        } else if (data.conflict) {
            pendingWeightPayload = {...payload, confirm_overwrite:true};
            document.getElementById('overwrite-dialog').classList.remove('hidden');
        } else { showToast(data.errors ? data.errors.join(' ') : (data.message||'Failed.'), true); }
    } catch { showToast('Network error.', true); }
    finally { btn.disabled=false; btn.textContent='Log Weight'; }
}
document.getElementById('weight-form').addEventListener('submit', (e) => {
    e.preventDefault(); const form = e.target;
    submitWeight({ action:'log_weight', log_date:form.log_date.value, weight_kg:form.weight_kg.value, notes:form.notes.value||null, csrf_token:FITSENSE_CSRF });
});
document.getElementById('overwrite-confirm').addEventListener('click', () => {
    document.getElementById('overwrite-dialog').classList.add('hidden');
    if (pendingWeightPayload) submitWeight(pendingWeightPayload);
});
document.getElementById('overwrite-cancel').addEventListener('click', () => {
    document.getElementById('overwrite-dialog').classList.add('hidden'); pendingWeightPayload=null;
});

// Chart
if (WEIGHT_CHART_DATA.length > 0) {
    new Chart(document.getElementById('weight-chart').getContext('2d'), {
        type:'line',
        data:{ labels:WEIGHT_CHART_DATA.map(d=>d.date), datasets:[{ label:'Weight (kg)', data:WEIGHT_CHART_DATA.map(d=>d.weight),
            borderColor:'#facc15', backgroundColor:'rgba(250,204,21,0.1)', borderWidth:2,
            pointBackgroundColor:'#facc15', pointRadius:4, tension:0.3, fill:true }] },
        options:{ responsive:true, plugins:{ legend:{labels:{color:'#a1a1aa',font:{size:12}}}, tooltip:{callbacks:{label:c=>c.parsed.y+' kg'}} },
            scales:{ x:{ticks:{color:'#a1a1aa',maxRotation:45,font:{size:11}},grid:{color:'#27272a'}},
                     y:{ticks:{color:'#a1a1aa',callback:v=>v+' kg',font:{size:11}},grid:{color:'#27272a'}} } }
    });
}
</script>
</body>
</html>
