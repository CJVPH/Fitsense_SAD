<?php
/**
 * FitSense — Member Profile Settings (3-tab layout)
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireRole('member');

$pdo         = Database::getConnection();
$currentUser = $auth->getCurrentUser();
$csrfToken   = generateCsrfToken();
$userId      = (int) $_SESSION['user_id'];

$announcements = getActiveAnnouncements('member', $pdo);

$goalStmt = $pdo->prepare("SELECT * FROM fitness_goals WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
$goalStmt->execute([$userId]);
$activeGoal = $goalStmt->fetch();

// Progress stats
$wlStmt = $pdo->prepare('SELECT weight_kg, log_date FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1');
$wlStmt->execute([$userId]);
$latestWeight = $wlStmt->fetch();

$weekStmt = $pdo->prepare("SELECT COUNT(*) FROM workout_sessions WHERE user_id = ? AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weekStmt->execute([$userId]);
$workoutsThisWeek = (int)$weekStmt->fetchColumn();

$todayStmt = $pdo->prepare("SELECT COUNT(*) FROM workout_sessions WHERE user_id = ? AND session_date = CURDATE()");
$todayStmt->execute([$userId]);
$workoutToday = (int)$todayStmt->fetchColumn();

$bmi = $bmiCategory = null;
$heightCm = (float)($currentUser['height_cm'] ?? 0);
$weightKg = $latestWeight ? (float)$latestWeight['weight_kg'] : 0;
if ($heightCm > 0 && $weightKg > 0) {
    $bmi = calculateBMI($weightKg, $heightCm);
    $bmiCategory = $bmi !== null ? getBMICategory($bmi) : null;
}

$firstName    = htmlspecialchars($currentUser['first_name']    ?? 'there', ENT_QUOTES|ENT_HTML5,'UTF-8');
$lastName     = htmlspecialchars($currentUser['last_name']     ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$username     = htmlspecialchars($currentUser['username']      ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$email        = htmlspecialchars($currentUser['email']         ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$profilePhoto = htmlspecialchars($currentUser['profile_photo'] ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$initials     = strtoupper(substr($firstName,0,1).substr($lastName,0,1));

$activeTab = $_GET['tab'] ?? 'account';
if (!in_array($activeTab, ['account','health','progress'])) $activeTab = 'account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Settings — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        #sidebar { transition: transform 0.25s ease; }
        #sidebar-overlay { transition: opacity 0.25s ease; }
        #main-scroll::-webkit-scrollbar { width: 4px; }
        #main-scroll::-webkit-scrollbar-track { background: transparent; }
        #main-scroll::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
    </style>
</head>
<body class="bg-black text-white" style="min-width:375px">

<div class="flex h-screen w-screen overflow-hidden">

<!-- ══════════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════════ -->
<aside id="sidebar"
    class="fixed lg:relative z-50 lg:z-auto top-0 left-0 h-full w-72 bg-zinc-900 border-r border-zinc-800
           flex flex-col -translate-x-full lg:translate-x-0 shrink-0">

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

    <div class="flex-1 min-h-0"></div>

    <div class="shrink-0 border-t border-zinc-800 px-2 py-2 space-y-0.5">
        <a href="member-dashboard.php"
            class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-zinc-300 hover:text-white text-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Progress
        </a>
        <a href="member-trainer-chat.php"
            class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-zinc-300 hover:text-white text-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Trainer Chat
        </a>
        <div class="border-t border-zinc-800 my-1"></div>
        <div class="relative">
            <button id="user-menu-btn"
                class="w-full flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl bg-zinc-800 text-white text-left">
                <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
                <img src="<?= $profilePhoto ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover shrink-0">
                <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs shrink-0"><?= $initials ?></div>
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
     MAIN
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
        <span class="text-yellow-400 font-bold text-lg tracking-tight">Settings</span>
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

    <!-- Tab bar -->
    <div class="shrink-0 px-4 pt-4 pb-0">
        <div class="max-w-lg mx-auto">
            <div class="flex gap-1 bg-zinc-900 border border-zinc-800 rounded-xl p-1">
                <a href="?tab=account"
                    class="flex-1 text-center text-xs font-medium py-2.5 rounded-lg min-h-[40px] flex items-center justify-center transition-colors
                           <?= $activeTab==='account' ? 'bg-yellow-400 text-black' : 'text-zinc-400 hover:text-white' ?>">
                    Account
                </a>
                <a href="?tab=health"
                    class="flex-1 text-center text-xs font-medium py-2.5 rounded-lg min-h-[40px] flex items-center justify-center transition-colors
                           <?= $activeTab==='health' ? 'bg-yellow-400 text-black' : 'text-zinc-400 hover:text-white' ?>">
                    Health Profile
                </a>
                <a href="?tab=progress"
                    class="flex-1 text-center text-xs font-medium py-2.5 rounded-lg min-h-[40px] flex items-center justify-center transition-colors
                           <?= $activeTab==='progress' ? 'bg-yellow-400 text-black' : 'text-zinc-400 hover:text-white' ?>">
                    Progress
                </a>
            </div>
        </div>
    </div>

    <div id="main-scroll" class="flex-1 overflow-y-auto min-h-0">
        <main class="px-4 py-6 max-w-lg mx-auto w-full space-y-5">

<?php if ($activeTab === 'account'): ?>
<!-- ══ ACCOUNT SETTINGS ══ -->

<!-- Avatar with upload -->
<div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 flex items-center gap-4">
    <div class="relative shrink-0">
        <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
        <img id="avatar-preview" src="<?= $profilePhoto ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover">
        <?php else: ?>
        <div id="avatar-initials" class="w-16 h-16 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xl"><?= $initials ?></div>
        <img id="avatar-preview" src="" alt="Avatar" class="w-16 h-16 rounded-full object-cover hidden">
        <?php endif; ?>
        <label for="avatar-input" class="absolute bottom-0 right-0 w-6 h-6 bg-yellow-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-yellow-300 transition-colors" aria-label="Change profile picture">
            <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </label>
        <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" aria-label="Upload profile picture">
    </div>
    <div>
        <p class="font-semibold text-white"><?= $firstName ?> <?= $lastName ?></p>
        <p class="text-xs text-zinc-500">@<?= $username ?></p>
        <p class="text-xs text-zinc-600 mt-1">JPG, PNG, WebP · max 2 MB</p>
    </div>
</div>

<!-- Account form -->
<form id="account-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4" novalidate>
    <h2 class="font-semibold text-white">Account Settings</h2>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="first_name" class="block text-xs text-zinc-400 mb-1">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?= $firstName ?>" required
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label for="last_name" class="block text-xs text-zinc-400 mb-1">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?= $lastName ?>" required
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
    </div>

    <div>
        <label for="acc_username" class="block text-xs text-zinc-400 mb-1">Username</label>
        <input type="text" id="acc_username" name="username" value="<?= $username ?>" required
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
    </div>

    <div>
        <label for="acc_email" class="block text-xs text-zinc-400 mb-1">Email</label>
        <input type="email" id="acc_email" name="email" value="<?= $email ?>"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
    </div>

    <button type="submit"
        class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">
        Save Changes
    </button>
</form>

<!-- Change Password -->
<div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-3">
    <h2 class="font-semibold text-white">Change Password</h2>
    <p class="text-zinc-400 text-sm">Keep your account secure with a strong password.</p>
    <a href="change-password.php?ref=settings"
        class="flex items-center justify-center gap-2 w-full bg-zinc-700 hover:bg-zinc-600 text-white font-medium rounded-lg px-4 py-3 min-h-[44px] transition-colors text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        Change Password
    </a>
</div>

<?php elseif ($activeTab === 'health'): ?>
<!-- ══ HEALTH PROFILE ══ -->

<form id="profile-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4" novalidate>
    <h2 class="font-semibold text-white">My Health Profile</h2>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="current_weight_kg" class="block text-xs text-zinc-400 mb-1">Weight (kg)</label>
            <input type="number" id="current_weight_kg" name="current_weight_kg" min="20" max="500" step="0.1"
                value="<?= htmlspecialchars($currentUser['current_weight_kg']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Height</label>
            <!-- Unit toggle -->
            <div class="flex gap-1 mb-1">
                <button type="button" id="ps-unit-cm" onclick="psSetUnit('cm')"
                    class="flex-1 py-1 rounded-lg text-xs font-semibold border border-yellow-400 bg-yellow-400 text-black transition-colors">cm</button>
                <button type="button" id="ps-unit-ft" onclick="psSetUnit('ft')"
                    class="flex-1 py-1 rounded-lg text-xs font-semibold border border-zinc-600 text-zinc-400 transition-colors">ft/in</button>
            </div>
            <div id="ps-cm-input">
                <input type="number" id="height_cm" name="height_cm" min="50" max="300" step="0.1"
                    value="<?= htmlspecialchars($currentUser['height_cm']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div id="ps-ft-input" class="hidden flex gap-1">
                <input type="number" id="ps_ft" min="1" max="9" placeholder="ft" oninput="psConvert()"
                    class="w-1/2 bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                <input type="number" id="ps_in" min="0" max="11" placeholder="in" oninput="psConvert()"
                    class="w-1/2 bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <p id="ps-converted" class="hidden text-zinc-500 text-xs mt-1"></p>
        </div>
    </div>

    <div>
        <label for="age" class="block text-xs text-zinc-400 mb-1">Age</label>
        <input type="number" id="age" name="age" min="10" max="120"
            value="<?= htmlspecialchars($currentUser['age']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
    </div>

    <div>
        <label for="fitness_level" class="block text-xs text-zinc-400 mb-1">Fitness Level</label>
        <select id="fitness_level" name="fitness_level"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            <?php foreach (['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($currentUser['fitness_level']??'beginner')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="goal_type" class="block text-xs text-zinc-400 mb-1">Fitness Goal</label>
        <select id="goal_type" name="goal_type"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            <?php
            $goals = ['lose_weight'=>'Lose Weight','build_muscle'=>'Build Muscle','improve_stamina'=>'Improve Stamina','maintain_fitness'=>'Maintain Fitness','other'=>'Other'];
            $cg = $activeGoal['goal_type'] ?? 'maintain_fitness';
            foreach ($goals as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $cg===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="target_weight_kg" class="block text-xs text-zinc-400 mb-1">Target Weight (kg, optional)</label>
        <input type="number" id="target_weight_kg" name="target_weight_kg" min="20" max="500" step="0.1"
            value="<?= htmlspecialchars($currentUser['target_weight_kg']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
    </div>

    <div>
        <label for="medical_conditions" class="block text-xs text-zinc-400 mb-1">Medical Conditions (optional)</label>
        <textarea id="medical_conditions" name="medical_conditions" rows="2"
            placeholder="Any conditions your trainer should know about"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"><?= htmlspecialchars($currentUser['medical_conditions']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?></textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="emergency_contact_name" class="block text-xs text-zinc-400 mb-1">Emergency Contact</label>
            <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                value="<?= htmlspecialchars($currentUser['emergency_contact_name']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label for="emergency_contact_phone" class="block text-xs text-zinc-400 mb-1">Contact Phone</label>
            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                value="<?= htmlspecialchars($currentUser['emergency_contact_phone']??'',ENT_QUOTES|ENT_HTML5,'UTF-8') ?>"
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
    </div>

    <button type="submit"
        class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">
        Save Health Profile
    </button>
</form>

<?php else: ?>
<!-- ══ PROGRESS ══ -->

<div class="grid grid-cols-2 gap-3">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
        <p class="text-xs text-zinc-400 mb-1">Current Weight</p>
        <p class="text-2xl font-bold text-white"><?= $latestWeight ? htmlspecialchars($latestWeight['weight_kg'],ENT_QUOTES|ENT_HTML5,'UTF-8').' kg' : '—' ?></p>
        <?php if ($latestWeight): ?><p class="text-xs text-zinc-500 mt-1"><?= htmlspecialchars(formatDate($latestWeight['log_date']),ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p><?php endif; ?>
    </div>
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
        <p class="text-xs text-zinc-400 mb-1">BMI</p>
        <p class="text-2xl font-bold text-white"><?= $bmi !== null ? htmlspecialchars((string)$bmi,ENT_QUOTES|ENT_HTML5,'UTF-8') : '—' ?></p>
        <?php if ($bmiCategory):
            $bmiColour = match($bmiCategory) { 'Underweight'=>'bg-blue-800 text-blue-200','Normal weight'=>'bg-green-800 text-green-200','Overweight'=>'bg-yellow-700 text-yellow-100',default=>'bg-red-800 text-red-200' };
        ?><span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full <?= $bmiColour ?>"><?= htmlspecialchars($bmiCategory,ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
        <p class="text-xs text-zinc-400 mb-1">Workout Today</p>
        <p class="text-2xl font-bold <?= $workoutToday > 0 ? 'text-yellow-400' : 'text-zinc-500' ?>"><?= $workoutToday > 0 ? '✓ Done' : 'Not yet' ?></p>
    </div>
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
        <p class="text-xs text-zinc-400 mb-1">This Week</p>
        <p class="text-2xl font-bold text-yellow-400"><?= $workoutsThisWeek ?> <span class="text-sm font-normal text-zinc-400">workouts</span></p>
    </div>
    <?php if ($activeGoal): ?>
    <div class="col-span-2 bg-zinc-900 border border-zinc-700 rounded-xl p-4">
        <p class="text-xs text-zinc-400 mb-1">Active Goal</p>
        <p class="text-base font-semibold text-white"><?= htmlspecialchars(ucwords(str_replace('_',' ',$activeGoal['goal_type'])),ENT_QUOTES|ENT_HTML5,'UTF-8') ?></p>
    </div>
    <?php endif; ?>
</div>

<a href="member-dashboard.php"
    class="flex items-center justify-center gap-2 w-full bg-zinc-800 hover:bg-zinc-700 text-white font-medium rounded-xl px-4 py-3 min-h-[44px] transition-colors text-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    View Full Progress & Log Workout
</a>

<?php endif; ?>

        </main>
    </div>
</div>
</div>

<div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0" role="status" aria-live="polite"></div>

<script>
const FITSENSE_CSRF = <?= json_encode($csrfToken) ?>;

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

function showToast(msg, isError=false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none '
        + (isError ? 'bg-red-700 text-white' : 'bg-green-700 text-white');
    t.style.opacity = '1';
    setTimeout(() => { t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(()=>{t.style.transition='';},300); }, 3000);
}

// Account form
const accountForm = document.getElementById('account-form');
if (accountForm) {
    accountForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target, btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Saving…';
        try {
            const res = await fetch('api/members.php', { method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ action:'update_account', first_name:form.first_name.value, last_name:form.last_name.value,
                    username:form.username.value, email:form.email.value, csrf_token:FITSENSE_CSRF }) });
            const data = await res.json();
            if (data.success) { showToast('✅ Account updated!'); }
            else { showToast(data.errors ? data.errors.join(' ') : (data.message||'Failed.'), true); }
        } catch { showToast('Network error.', true); }
        finally { btn.disabled=false; btn.textContent='Save Changes'; }
    });
}

// Avatar upload
const avatarInput = document.getElementById('avatar-input');
if (avatarInput) {
    avatarInput.addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { showToast('Image must be under 2 MB.', true); return; }

        // Preview
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('avatar-preview');
            const initials = document.getElementById('avatar-initials');
            if (preview) { preview.src = e.target.result; preview.classList.remove('hidden'); }
            if (initials) initials.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        // Upload
        const fd = new FormData();
        fd.append('action', 'upload_avatar');
        fd.append('profile_photo', file);
        fd.append('csrf_token', FITSENSE_CSRF);
        try {
            const res = await fetch('api/members.php', { method:'POST', body: fd });
            const data = await res.json();
            if (data.success) { showToast('Profile picture updated!'); }
            else { showToast(data.message || 'Upload failed.', true); }
        } catch { showToast('Network error.', true); }
    });
}

const profileForm = document.getElementById('profile-form');
if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target, btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Saving…';
        try {
            const res = await fetch('api/members.php', { method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ action:'update_profile', current_weight_kg:form.current_weight_kg.value,
                    height_cm:form.height_cm.value, age:form.age.value, fitness_level:form.fitness_level.value,
                    goal_type:form.goal_type.value, target_weight_kg:form.target_weight_kg.value||null,
                    medical_conditions:form.medical_conditions.value||null,
                    emergency_contact_name:form.emergency_contact_name.value||null,
                    emergency_contact_phone:form.emergency_contact_phone.value||null,
                    csrf_token:FITSENSE_CSRF }) });
            const data = await res.json();
            if (data.success) { showToast('✅ Health profile saved!'); }
            else { showToast(data.errors ? data.errors.join(' ') : (data.message||'Failed.'), true); }
        } catch { showToast('Network error.', true); }
        finally { btn.disabled=false; btn.textContent='Save Health Profile'; }
    });
}

// ── Height unit toggle (Health Profile tab) ───────────────────────────────────
function psSetUnit(unit) {
    var cmBtn  = document.getElementById('ps-unit-cm');
    var ftBtn  = document.getElementById('ps-unit-ft');
    var cmDiv  = document.getElementById('ps-cm-input');
    var ftDiv  = document.getElementById('ps-ft-input');
    var convEl = document.getElementById('ps-converted');
    if (!cmBtn) return;
    if (unit === 'cm') {
        cmBtn.classList.add('bg-yellow-400','text-black','border-yellow-400');
        cmBtn.classList.remove('text-zinc-400','border-zinc-600');
        ftBtn.classList.remove('bg-yellow-400','text-black','border-yellow-400');
        ftBtn.classList.add('text-zinc-400','border-zinc-600');
        cmDiv.classList.remove('hidden');
        ftDiv.classList.add('hidden');
        if (convEl) convEl.classList.add('hidden');
    } else {
        ftBtn.classList.add('bg-yellow-400','text-black','border-yellow-400');
        ftBtn.classList.remove('text-zinc-400','border-zinc-600');
        cmBtn.classList.remove('bg-yellow-400','text-black','border-yellow-400');
        cmBtn.classList.add('text-zinc-400','border-zinc-600');
        cmDiv.classList.add('hidden');
        ftDiv.classList.remove('hidden');
        psConvert();
    }
}
function psConvert() {
    var ft  = parseFloat(document.getElementById('ps_ft').value) || 0;
    var ins = parseFloat(document.getElementById('ps_in').value) || 0;
    var cm  = (ft * 30.48) + (ins * 2.54);
    var convEl = document.getElementById('ps-converted');
    if (ft > 0 || ins > 0) {
        document.getElementById('height_cm').value = cm.toFixed(1);
        if (convEl) { convEl.textContent = '≈ ' + cm.toFixed(1) + ' cm'; convEl.classList.remove('hidden'); }
    } else {
        if (convEl) convEl.classList.add('hidden');
    }
}
</script>
</body>
</html>
