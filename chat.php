<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$auth = new Auth();
$auth->requireRole('member');
$pdo = Database::getConnection();
$currentUser = $auth->getCurrentUser();
$csrfToken = generateCsrfToken();
$userId = (int) $_SESSION['user_id'];
$firstName = htmlspecialchars($currentUser['first_name'] ?? 'there', ENT_QUOTES|ENT_HTML5,'UTF-8');
$lastName = htmlspecialchars($currentUser['last_name'] ?? '', ENT_QUOTES|ENT_HTML5,'UTF-8');
$username = htmlspecialchars($currentUser['username'] ?? '', ENT_QUOTES|ENT_HTML5,'UTF-8');
$email = htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES|ENT_HTML5,'UTF-8');
$profilePhoto = htmlspecialchars($currentUser['profile_photo'] ?? '', ENT_QUOTES|ENT_HTML5,'UTF-8');
$initials = strtoupper(substr($firstName,0,1).substr($lastName,0,1));
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE user_id=? AND sender_type='trainer' AND is_read=FALSE");
$unreadStmt->execute([$userId]);
$unreadTrainer = (int)$unreadStmt->fetchColumn();
$announcements = getActiveAnnouncements('member', $pdo);
$wsStmt = $pdo->prepare('SELECT * FROM workout_sessions WHERE user_id=? ORDER BY session_date DESC LIMIT 10');
$wsStmt->execute([$userId]);
$workoutSessions = $wsStmt->fetchAll();
$wlChartStmt = $pdo->prepare('SELECT weight_kg, log_date FROM weight_logs WHERE user_id=? ORDER BY log_date ASC LIMIT 30');
$wlChartStmt->execute([$userId]);
$weightLogsChart = $wlChartStmt->fetchAll();
$wlLatestStmt = $pdo->prepare('SELECT weight_kg, log_date FROM weight_logs WHERE user_id=? ORDER BY log_date DESC LIMIT 1');
$wlLatestStmt->execute([$userId]);
$latestWeight = $wlLatestStmt->fetch();
// Fallback: use member_profiles.current_weight_kg if no weight log exists
if (!$latestWeight && !empty($currentUser['current_weight_kg'])) {
    $latestWeight = ['weight_kg' => $currentUser['current_weight_kg'], 'log_date' => null];
}
// Fallback: use member_profiles.current_weight_kg if no weight log exists
if (!$latestWeight && !empty($currentUser['current_weight_kg'])) {
    $latestWeight = ['weight_kg' => $currentUser['current_weight_kg'], 'log_date' => null];
}
$goalStmt = $pdo->prepare("SELECT * FROM fitness_goals WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1");
$goalStmt->execute([$userId]);
$activeGoal = $goalStmt->fetch();
$bmi = $bmiCategory = null;
$heightCm = (float)($currentUser['height_cm'] ?? 0);
$weightKg = $latestWeight ? (float)$latestWeight['weight_kg'] : 0;
if ($heightCm > 0 && $weightKg > 0) { $bmi = calculateBMI($weightKg, $heightCm); $bmiCategory = $bmi !== null ? getBMICategory($bmi) : null; }
$weekStmt = $pdo->prepare("SELECT COUNT(*) FROM workout_sessions WHERE user_id=? AND session_date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)");
$weekStmt->execute([$userId]);
$workoutsThisWeek = (int)$weekStmt->fetchColumn();
$trainerStmt = $pdo->prepare('SELECT u.id,u.first_name,u.last_name,u.profile_photo FROM users u JOIN member_profiles mp ON mp.assigned_trainer_id=u.id WHERE mp.user_id=? LIMIT 1');
$trainerStmt->execute([$userId]);
$trainer = $trainerStmt->fetch();
if ($trainer) { $pdo->prepare("UPDATE chat_messages SET is_read=TRUE WHERE user_id=? AND sender_type='trainer' AND is_read=FALSE")->execute([$userId]); $unreadTrainer = 0; }
$msgStmt = $pdo->prepare("SELECT cm.*,u.first_name,u.last_name FROM chat_messages cm LEFT JOIN users u ON u.id=cm.sender_id AND cm.sender_type='trainer' WHERE cm.user_id=? AND cm.sender_type IN ('member','trainer') AND cm.session_id IS NULL ORDER BY cm.created_at ASC LIMIT 200");
$msgStmt->execute([$userId]);
$trainerMessages = $msgStmt->fetchAll();
$trainerName = $trainer ? htmlspecialchars($trainer['first_name'].' '.$trainer['last_name'],ENT_QUOTES|ENT_HTML5,'UTF-8') : null;
$trainerInitials = $trainer ? strtoupper(substr($trainer['first_name'],0,1).substr($trainer['last_name'],0,1)) : 'T';
$hpStmt = $pdo->prepare('SELECT * FROM member_profiles WHERE user_id=? LIMIT 1');
$hpStmt->execute([$userId]);
$healthProfile = $hpStmt->fetch();
$today = date('Y-m-d');
function fmtGoal(string $t): string { return ucwords(str_replace('_',' ',$t)); }
function renderStars(int $r): string { $o=''; for($i=1;$i<=5;$i++) $o.=$i<=$r?'&#9733;':'&#9734;'; return $o; }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>FitSense</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/theme.css"><script>(function(){var t=localStorage.getItem('fitsense_theme')||'dark';if(t==='light')document.documentElement.classList.add('light-mode');})();</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
html,body{height:100%;overflow:hidden}
#sidebar{transition:transform .25s ease}
#sidebar-overlay{transition:opacity .25s ease}
.typing-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#a1a1aa;animation:tdot 1.2s infinite ease-in-out}
.typing-dot:nth-child(2){animation-delay:.2s}
.typing-dot:nth-child(3){animation-delay:.4s}
@keyframes tdot{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}
#chat-messages::-webkit-scrollbar,#session-list::-webkit-scrollbar,#trainer-messages::-webkit-scrollbar,#progress-scroll::-webkit-scrollbar,#settings-scroll::-webkit-scrollbar{width:4px}
#chat-messages::-webkit-scrollbar-track,#session-list::-webkit-scrollbar-track,#trainer-messages::-webkit-scrollbar-track,#progress-scroll::-webkit-scrollbar-track,#settings-scroll::-webkit-scrollbar-track{background:transparent}
#chat-messages::-webkit-scrollbar-thumb,#session-list::-webkit-scrollbar-thumb,#trainer-messages::-webkit-scrollbar-thumb,#progress-scroll::-webkit-scrollbar-thumb,#settings-scroll::-webkit-scrollbar-thumb{background:#3f3f46;border-radius:4px}
.section{display:none;flex-direction:column;flex:1;min-width:0;height:100%}
.section.active{display:flex}
.nav-active{background-color:rgb(39 39 42)!important;color:#fff!important}
.nav-active .nav-icon{color:#facc15!important}

/* -- Light Mode (inline to override Tailwind CDN) -- */
html.light-mode body,html.light-mode .flex.h-screen{background-color:#f1f5f9!important;color:#1e293b!important}
html.light-mode #sidebar,html.light-mode aside{background-color:#ffffff!important;border-color:#e2e8f0!important}
html.light-mode header{background-color:#ffffff!important;border-color:#e2e8f0!important}
html.light-mode .bg-zinc-900{background-color:#f8fafc!important}
html.light-mode .bg-zinc-800{background-color:#e2e8f0!important}
html.light-mode .bg-zinc-700{background-color:#cbd5e1!important}
html.light-mode .bg-black{background-color:#ffffff!important}
html.light-mode .text-white{color:#1e293b!important}
html.light-mode .text-zinc-300{color:#475569!important}
html.light-mode .text-zinc-400{color:#64748b!important}
html.light-mode .text-zinc-500{color:#94a3b8!important}
html.light-mode .text-zinc-600{color:#94a3b8!important}
html.light-mode .border-zinc-800,html.light-mode .border-zinc-700,html.light-mode .border-zinc-600{border-color:#e2e8f0!important}
html.light-mode input:not([type=radio]):not([type=checkbox]),html.light-mode textarea,html.light-mode select{background-color:#ffffff!important;color:#1e293b!important;border-color:#cbd5e1!important}
html.light-mode input::placeholder,html.light-mode textarea::placeholder{color:#94a3b8!important}
html.light-mode #user-menu-dropdown{background-color:#ffffff!important;border-color:#e2e8f0!important}
html.light-mode .nav-active{background-color:#e2e8f0!important;color:#1e293b!important}
html.light-mode .session-btn:hover,.session-btn.bg-zinc-800{background-color:#e2e8f0!important}
html.light-mode #progress-scroll,html.light-mode #settings-scroll{background-color:#f1f5f9!important}
html.light-mode footer{background-color:#f8fafc!important;border-color:#e2e8f0!important}
html.light-mode ::-webkit-scrollbar-thumb{background:#cbd5e1!important}
html.light-mode ::-webkit-scrollbar-track{background:#f1f5f9!important}</style>
</head>
<body class="bg-black text-white" style="min-width:375px">
<div class="flex h-screen w-screen overflow-hidden"><aside id="sidebar" class="fixed lg:relative z-50 lg:z-auto top-0 left-0 h-full w-72 bg-zinc-900 border-r border-zinc-800 flex flex-col -translate-x-full lg:translate-x-0 shrink-0">

  <!-- -- Header: Logo + Close -- -->
  <div class="px-4 pt-4 pb-3 shrink-0 flex items-center justify-between border-b border-zinc-800">
    <a href="chat.php" class="flex items-center gap-2 hover:opacity-90 transition-opacity">
      <svg class="w-7 h-6 text-yellow-400 shrink-0" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
      <span class="text-yellow-400 font-bold text-lg tracking-tight">FitSense</span>
    </a>
    <button id="sidebar-close-btn" class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl hover:bg-zinc-800 transition-colors text-zinc-400 hover:text-white" aria-label="Close sidebar">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>

  <!-- -- New Chat Button -- -->
  <div class="px-3 pt-3 pb-2 shrink-0">
    <button id="new-chat-btn" class="w-full flex items-center justify-center gap-2 px-4 py-3 min-h-[48px] rounded-xl bg-yellow-400 hover:bg-yellow-300 transition-colors text-sm font-bold text-black shadow-lg shadow-yellow-400/20">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      New Chat
    </button>
  </div>

  <!-- -- Quick Actions -- -->
  <div class="px-3 pb-2 shrink-0">
    <p class="text-xs text-zinc-500 px-1 pb-2 font-semibold uppercase tracking-widest">Quick Actions</p>
    <div class="grid grid-cols-2 gap-2">
      <button class="quick-action-btn flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 hover:border-yellow-400/50 transition-all text-center min-h-[72px]" data-prompt="Create a personalized workout plan for me">
        <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 640 512" fill="currentColor"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
        <span class="text-xs font-medium text-white leading-tight">Workout Plan</span>
      </button>
      <button class="quick-action-btn flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 hover:border-yellow-400/50 transition-all text-center min-h-[72px]" data-prompt="Give me a personalized meal plan and nutrition guide">
        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
        <span class="text-xs font-medium text-white leading-tight">Nutrition</span>
      </button>
      <button class="quick-action-btn flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 hover:border-yellow-400/50 transition-all text-center min-h-[72px]" data-section="progress">
        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span class="text-xs font-medium text-white leading-tight">Progress</span>
      </button>
      <button class="quick-action-btn flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 hover:border-yellow-400/50 transition-all text-center min-h-[72px]" data-prompt="Give me some motivation tips and advice to stay consistent with my fitness goals">
        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span class="text-xs font-medium text-white leading-tight">Motivation</span>
      </button>
    </div>
  </div>

  <!-- -- Recent Chats -- -->
  <div class="px-3 shrink-0">
    <p class="text-xs text-zinc-500 px-1 pb-1 font-semibold uppercase tracking-widest">Recent Chats</p>
  </div>
  <div id="session-list" class="flex-1 overflow-y-auto px-3 space-y-0.5 pb-2 min-h-0"></div>

  <!-- -- Bottom Nav -- -->
  <div class="shrink-0 border-t border-zinc-800 px-3 py-2 space-y-0.5">
    <button data-section="progress" class="nav-btn flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-zinc-300 hover:text-white text-sm w-full text-left"><svg class="nav-icon w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>Progress</button>
    <button data-section="trainer" class="nav-btn flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-zinc-300 hover:text-white text-sm w-full text-left">
      <svg class="nav-icon w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
      Trainer Chat
      <?php if ($unreadTrainer > 0): ?>
      <span class="ml-auto bg-yellow-400 text-black text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shrink-0"><?php echo $unreadTrainer > 9 ? '9+' : $unreadTrainer; ?></span>
      <?php endif; ?>
    </button>
    <div class="border-t border-zinc-800 my-1"></div>
    <!-- User menu -->
    <div class="relative">
      <button id="user-menu-btn" class="w-full flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-left">
        <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
        <img id="sidebar-avatar-img" src="<?php echo $profilePhoto; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover shrink-0">
        <?php else: ?>
        <div id="sidebar-avatar-initials" class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs shrink-0"><?php echo $initials; ?></div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate"><?php echo $firstName; ?> <?php echo $lastName; ?></p>
          <p class="text-xs text-zinc-500 truncate">@<?php echo $username; ?></p>
        </div>
        <svg class="menu-chevron w-4 h-4 text-zinc-500 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
      </button>
      <div id="user-menu-dropdown" class="hidden absolute bottom-full left-0 right-0 mb-1 bg-zinc-800 border border-zinc-700 rounded-xl shadow-xl overflow-hidden z-10">
        <div class="px-4 py-3 border-b border-zinc-700 flex items-center gap-3">
          <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
          <img src="<?php echo $profilePhoto; ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover shrink-0">
          <?php else: ?>
          <div class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm shrink-0"><?php echo $initials; ?></div>
          <?php endif; ?>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-white truncate"><?php echo $firstName; ?> <?php echo $lastName; ?></p>
            <p class="text-xs text-zinc-400 truncate">@<?php echo $username; ?></p>
          </div>
        </div>
        <button data-section="settings" class="nav-btn w-full flex items-center gap-3 px-4 py-3 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors min-h-[44px] text-left">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          Edit Profile
        </button>
        <button class="theme-toggle-btn w-full flex items-center gap-3 px-4 py-3 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors min-h-[44px] text-left">
          <svg class="icon-dark w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-light w-4 h-4 shrink-0 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
          <span class="theme-label">Light Mode</span>
        </button>
        <div class="border-t border-zinc-700"></div>
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-sm text-red-400 hover:bg-zinc-700 hover:text-red-300 transition-colors min-h-[44px]">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/></svg>
          Logout
        </a>
      </div>
    </div>
  </div>
</aside><div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden opacity-0 lg:hidden"></div>
<div class="flex flex-1 min-w-0 h-full overflow-hidden"><div id="section-chat" class="section active">
  <!-- Mobile header -->
  <header class="lg:hidden flex items-center justify-between px-4 h-14 border-b border-zinc-800 bg-zinc-900 shrink-0">
    <button class="sidebar-open-btn text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Open sidebar">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="flex items-center gap-2">
      <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 640 512" fill="currentColor"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
      <span class="text-yellow-400 font-bold text-lg tracking-tight">FitSense AI</span>
    </div>
    <button id="mobile-new-chat-btn" aria-label="New chat" class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    </button>
  </header>
  <!-- AI disclaimer -->
  <div class="shrink-0 mx-4 mt-3 mb-0 border border-yellow-400/40 bg-yellow-400/5 text-yellow-300 px-4 py-2 text-xs rounded-xl" role="note">
    &#9888;&#65039; AI-generated advice &mdash; not a substitute for professional medical or fitness guidance.
  </div>
  <!-- Messages area -->
  <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-4 min-h-0" aria-live="polite">
    <!-- Empty / Welcome state -->
    <div id="empty-state" class="flex flex-col items-center justify-center h-full text-center gap-5 px-2 py-8">
      <div class="w-16 h-16 rounded-full bg-yellow-400 flex items-center justify-center shadow-lg shadow-yellow-400/30">
        <svg class="w-9 h-9 text-black" fill="currentColor" viewBox="0 0 24 24">
          <path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6c-1.1 0-2 .9-2 2v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zm-2 10H6V7h12v12zm-9-6c-.83 0-1.5-.67-1.5-1.5S8.17 10 9 10s1.5.67 1.5 1.5S9.83 13 9 13zm6 0c-.83 0-1.5-.67-1.5-1.5S14.17 10 15 10s1.5.67 1.5 1.5S15.83 13 15 13z"/>
        </svg>
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-yellow-400 mb-2">Welcome to FitSense!</h2>
        <p class="text-zinc-400 text-sm max-w-sm leading-relaxed">I'm your personal AI fitness coach powered by advanced fitness knowledge and personalized recommendations. What would you like to work on today?</p>
      </div>
      <div class="w-full max-w-sm space-y-2">
        <button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-prompt="Create a personalized workout plan for me based on my fitness level and goals">
          <span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-black" viewBox="0 0 640 512" fill="currentColor"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
          </span>
          <div><p class="text-sm font-bold text-white">Workout Plan</p><p class="text-xs text-zinc-400 mt-0.5">Get a personalized exercise routine</p></div>
        </button>
        <button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-prompt="Give me a personalized meal plan and nutrition guide based on my fitness goals">
          <span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
          </span>
          <div><p class="text-sm font-bold text-white">Nutrition Guide</p><p class="text-xs text-zinc-400 mt-0.5">Healthy meal plans and tips</p></div>
        </button>
        <button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-prompt="Give me motivation tips and strategies to stay consistent with my fitness goals">
          <span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </span>
          <div><p class="text-sm font-bold text-white">Stay Motivated</p><p class="text-xs text-zinc-400 mt-0.5">Tips for consistent progress</p></div>
        </button>
        <button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-section="progress">
          <span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </span>
          <div><p class="text-sm font-bold text-white">Track Progress</p><p class="text-xs text-zinc-400 mt-0.5">Monitor your fitness journey</p></div>
        </button>
      </div>
    </div>
  </div>
  <!-- Typing indicator template -->
  <template id="typing-tpl">
    <div id="typing-indicator" class="flex items-end gap-2 px-0 py-1">
      <div class="w-7 h-7 rounded-full bg-zinc-700 flex items-center justify-center shrink-0">
        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
      </div>
      <div class="bg-zinc-800 rounded-2xl rounded-bl-sm px-4 py-3 flex gap-1 items-center">
        <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
      </div>
    </div>
  </template>
  <!-- Input area -->
  <div class="shrink-0 px-4 pb-4 pt-2">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-end gap-2 bg-zinc-900 border border-zinc-700 rounded-2xl px-4 py-3 focus-within:border-yellow-400 transition-colors">
        <textarea id="message-input" rows="1" placeholder="Ask me anything about fitness..." class="flex-1 bg-transparent text-white text-sm resize-none focus:outline-none placeholder-zinc-500 min-h-[24px] max-h-32 overflow-y-auto" aria-label="Message input"></textarea>
        <button id="send-btn" type="button" class="bg-yellow-400 hover:bg-yellow-300 disabled:opacity-40 disabled:cursor-not-allowed text-black rounded-xl p-2 min-h-[36px] min-w-[36px] flex items-center justify-center transition-colors shrink-0" aria-label="Send message" disabled>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
        </button>
      </div>
      <p class="text-zinc-600 text-xs text-center mt-1.5">Press Enter to send, Shift+Enter for new line</p>
      <p id="limit-warning" class="hidden text-yellow-400 text-xs mt-2 text-center"></p>
    </div>
  </div>
</div>
<div id="section-progress" class="section">
  <header class="lg:hidden flex items-center justify-between px-4 h-14 border-b border-zinc-800 bg-zinc-900 shrink-0">
    <button class="sidebar-open-btn text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Open sidebar">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <span class="text-yellow-400 font-bold text-lg tracking-tight">Progress</span>
    <div class="w-11"></div>
  </header>
  <?php if (!empty($announcements)): ?>
  <div class="shrink-0 mx-4 mt-3 flex items-start gap-3 bg-yellow-400/10 border border-yellow-400/40 text-yellow-300 px-4 py-3 rounded-xl text-xs announcement-banner" role="alert">
    <svg class="w-4 h-4 shrink-0 mt-0.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
    <span class="flex-1"><strong><?php echo htmlspecialchars($announcements[0]['title'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($announcements[0]['content'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></span>
    <button onclick="this.closest('.announcement-banner').remove()" class="shrink-0 text-yellow-400 hover:text-yellow-200 p-1 min-h-[28px] min-w-[28px] flex items-center justify-center rounded-lg" aria-label="Dismiss">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <?php endif; ?>
  <div id="progress-scroll" class="flex-1 overflow-y-auto min-h-0">
    <main class="px-4 py-6 max-w-2xl mx-auto w-full space-y-8">
      <section>
        <h2 class="text-lg font-bold text-yellow-400 mb-3">Overview</h2>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <p class="text-xs text-zinc-400 mb-1">Current Weight</p>
            <p class="text-2xl font-bold text-white"><?php echo $latestWeight ? htmlspecialchars($latestWeight['weight_kg'],ENT_QUOTES|ENT_HTML5,'UTF-8').' kg' : '&mdash;'; ?></p>
            <?php if ($latestWeight): ?><p class="text-xs text-zinc-500 mt-1"><?php echo htmlspecialchars(formatDate($latestWeight['log_date']),ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></p><?php endif; ?>
          </div>
          <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <p class="text-xs text-zinc-400 mb-1">BMI</p>
            <p class="text-2xl font-bold text-white" data-stat="bmi"><?php echo $bmi !== null ? htmlspecialchars((string)$bmi,ENT_QUOTES|ENT_HTML5,'UTF-8') : '&mdash;'; ?></p>
            <?php if ($bmiCategory): $bmiC = match($bmiCategory){'Underweight'=>'bg-blue-800 text-blue-200','Normal weight'=>'bg-green-800 text-green-200','Overweight'=>'bg-yellow-700 text-yellow-100',default=>'bg-red-800 text-red-200'}; ?>
            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full <?php echo $bmiC; ?>"><?php echo htmlspecialchars($bmiCategory,ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
          <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <p class="text-xs text-zinc-400 mb-1">Active Goal</p>
            <p class="text-base font-semibold text-white leading-tight"><?php echo $activeGoal ? htmlspecialchars(fmtGoal($activeGoal['goal_type']),ENT_QUOTES|ENT_HTML5,'UTF-8') : '&mdash;'; ?></p>
          </div>
          <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <p class="text-xs text-zinc-400 mb-1">Workouts This Week</p>
            <p class="text-2xl font-bold text-yellow-400"><?php echo $workoutsThisWeek; ?></p>
          </div>
        </div>
      </section>
      <section>
        <h2 class="text-lg font-bold text-yellow-400 mb-3">Log</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <form id="workout-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-3" novalidate>
            <h3 class="font-semibold text-white">Workout</h3>
            <div>
              <label for="session_date" class="block text-xs text-zinc-400 mb-1">Date</label>
              <input type="date" id="session_date" name="session_date" value="<?php echo $today; ?>" max="<?php echo $today; ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label for="duration_minutes" class="block text-xs text-zinc-400 mb-1">Duration (minutes)</label>
              <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="600" placeholder="e.g. 45" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label class="block text-xs text-zinc-400 mb-1">Rating (1&ndash;5)</label>
              <div class="flex gap-1" id="star-rating" role="group" aria-label="Workout rating">
                <?php for ($s=1;$s<=5;$s++): ?>
                <button type="button" data-star="<?php echo $s; ?>" class="star-btn text-2xl text-zinc-600 hover:text-yellow-400 min-h-[44px] min-w-[36px] flex items-center justify-center transition-colors" aria-label="<?php echo $s; ?> star<?php echo $s>1?'s':''; ?>">&#9733;</button>
                <?php endfor; ?>
              </div>
              <input type="hidden" id="rating" name="rating" value="">
            </div>
            <div>
              <label for="calories_burned" class="block text-xs text-zinc-400 mb-1">Calories Burned (optional)</label>
              <input type="number" id="calories_burned" name="calories_burned" min="0" placeholder="e.g. 300" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div>
              <label for="workout_notes" class="block text-xs text-zinc-400 mb-1">Notes (optional)</label>
              <textarea id="workout_notes" name="notes" rows="2" placeholder="How did it go?" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
            </div>
            <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Log Workout</button>
          </form>
          <form id="weight-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-3" novalidate>
            <h3 class="font-semibold text-white">Weight</h3>
            <div>
              <label for="log_date" class="block text-xs text-zinc-400 mb-1">Date</label>
              <input type="date" id="log_date" name="log_date" value="<?php echo $today; ?>" max="<?php echo $today; ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label for="weight_kg" class="block text-xs text-zinc-400 mb-1">Weight (kg)</label>
              <input type="number" id="weight_kg" name="weight_kg" min="20" max="500" step="0.1" placeholder="e.g. 75.5" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label for="weight_notes" class="block text-xs text-zinc-400 mb-1">Notes (optional)</label>
              <textarea id="weight_notes" name="notes" rows="2" placeholder="Any notes?" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
            </div>
            <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Log Weight</button>
          </form>
        </div>
      </section>
      <section>
        <h2 class="text-lg font-bold text-yellow-400 mb-3">Weight Progress</h2>
        <?php if (!empty($weightLogsChart)): ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
          <canvas id="weight-chart" height="200" aria-label="Weight progress chart" role="img"></canvas>
        </div>
        <?php else: ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">No weight logs yet.</div>
        <?php endif; ?>
      </section>
      <section>
        <h2 class="text-lg font-bold text-yellow-400 mb-3">Recent Workouts</h2>
        <?php if (!empty($workoutSessions)): ?>
        <div class="space-y-3">
          <?php foreach ($workoutSessions as $session): ?>
          <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars(formatDate($session['session_date']),ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></p>
                <p class="text-xs text-zinc-400 mt-0.5"><?php echo (int)$session['duration_minutes']; ?> min<?php if ($session['calories_burned']): ?> &middot; <?php echo (int)$session['calories_burned']; ?> kcal<?php endif; ?></p>
              </div>
              <?php if ($session['rating']): ?><span class="text-yellow-400 text-sm shrink-0"><?php echo renderStars((int)$session['rating']); ?></span><?php endif; ?>
            </div>
            <?php if (!empty($session['notes'])): ?><p class="text-xs text-zinc-400 mt-2 line-clamp-2"><?php echo htmlspecialchars($session['notes'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></p><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">No workouts logged yet.</div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</div><div id="section-trainer" class="section">
  <header class="lg:hidden flex items-center justify-between px-4 h-14 border-b border-zinc-800 bg-zinc-900 shrink-0">
    <button class="sidebar-open-btn text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Open sidebar">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <span class="text-white font-semibold text-base truncate"><?php echo $trainerName ?? 'Trainer Chat'; ?></span>
    <div class="w-11"></div>
  </header>
  <div class="hidden lg:flex items-center gap-3 px-6 py-4 border-b border-zinc-800 bg-zinc-900 shrink-0">
    <?php if ($trainer && !empty($trainer['profile_photo']) && file_exists($trainer['profile_photo'])): ?>
    <img src="<?php echo htmlspecialchars($trainer['profile_photo'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>" alt="Trainer" class="w-9 h-9 rounded-full object-cover">
    <?php else: ?>
    <div class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm"><?php echo $trainerInitials; ?></div>
    <?php endif; ?>
    <div>
      <p class="font-semibold text-white text-sm"><?php echo $trainerName ?? 'No trainer assigned'; ?></p>
      <p class="text-xs text-zinc-500">Your trainer</p>
    </div>
  </div>
  <div id="trainer-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-2 min-h-0" aria-live="polite">
    <?php if (!$trainer): ?>
    <div class="flex flex-col items-center justify-center h-full text-center gap-3">
      <div class="w-14 h-14 rounded-2xl bg-zinc-800 flex items-center justify-center">
        <svg class="w-7 h-7 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </div>
      <p class="text-zinc-400 text-sm">You don&apos;t have a trainer assigned yet.</p>
    </div>
    <?php elseif (empty($trainerMessages)): ?>
    <div class="flex flex-col items-center justify-center h-full text-center gap-3">
      <div class="w-14 h-14 rounded-2xl bg-zinc-800 flex items-center justify-center">
        <svg class="w-7 h-7 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
      </div>
      <p class="text-zinc-400 text-sm">No messages yet. Say hi to your trainer!</p>
    </div>
    <?php else: ?>
    <?php $prevDate = null; foreach ($trainerMessages as $msg):
      $msgDate = date('Y-m-d', strtotime($msg['created_at']));
      if ($msgDate !== $prevDate): $prevDate = $msgDate;
        $label = $msgDate === date('Y-m-d') ? 'Today' : ($msgDate === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('M j, Y', strtotime($msgDate))); ?>
    <div class="flex items-center gap-3 my-3">
      <div class="flex-1 h-px bg-zinc-800"></div>
      <span class="text-xs text-zinc-600 shrink-0"><?php echo htmlspecialchars($label,ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></span>
      <div class="flex-1 h-px bg-zinc-800"></div>
    </div>
    <?php endif; ?>
    <?php if ($msg['sender_type'] === 'member'): ?>
    <div class="flex justify-end">
      <div class="max-w-[70%]">
        <div class="bg-yellow-400 text-black rounded-2xl rounded-br-sm px-3 py-2 text-sm break-words"><?php echo htmlspecialchars($msg['message'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></div>
        <p class="text-xs text-zinc-600 mt-0.5 text-right"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></p>
      </div>
    </div>
    <?php else: ?>
    <div class="flex items-end gap-2">
      <?php if (!empty($trainer['profile_photo']) && file_exists($trainer['profile_photo'])): ?>
      <img src="<?php echo htmlspecialchars($trainer['profile_photo'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>" alt="Trainer" class="w-6 h-6 rounded-full object-cover shrink-0 mb-4">
      <?php else: ?>
      <div class="w-6 h-6 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs shrink-0 mb-4"><?php echo $trainerInitials; ?></div>
      <?php endif; ?>
      <div class="max-w-[70%]">
        <div class="bg-zinc-800 text-white rounded-2xl rounded-bl-sm px-3 py-2 text-sm break-words whitespace-pre-wrap"><?php echo htmlspecialchars($msg['message'],ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></div>
        <p class="text-xs text-zinc-600 mt-0.5"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></p>
      </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php if ($trainer): ?>
  <div class="shrink-0 px-4 pb-4 pt-2">
    <div class="flex items-end gap-2 bg-zinc-900 border border-zinc-700 rounded-2xl px-4 py-3 focus-within:border-yellow-400 transition-colors">
      <textarea id="trainer-input" rows="1" placeholder="Message your trainer&hellip;" class="flex-1 bg-transparent text-white text-sm resize-none focus:outline-none placeholder-zinc-500 min-h-[24px] max-h-32 overflow-y-auto" aria-label="Message input"></textarea>
      <button id="trainer-send-btn" type="button" class="bg-yellow-400 hover:bg-yellow-300 disabled:opacity-40 disabled:cursor-not-allowed text-black rounded-xl p-2 min-h-[36px] min-w-[36px] flex items-center justify-center transition-colors shrink-0" aria-label="Send message" disabled>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
      </button>
    </div>
  </div>
  <?php endif; ?>
</div><div id="section-settings" class="section">
  <header class="lg:hidden flex items-center justify-between px-4 h-14 border-b border-zinc-800 bg-zinc-900 shrink-0">
    <button class="sidebar-open-btn text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Open sidebar">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <span class="text-yellow-400 font-bold text-lg tracking-tight">Settings</span>
    <div class="w-11"></div>
  </header>
  <div id="settings-scroll" class="flex-1 overflow-y-auto min-h-0">
    <main class="px-4 py-6 max-w-2xl mx-auto w-full space-y-6">
      <div class="flex gap-1 bg-zinc-900 border border-zinc-700 rounded-xl p-1">
        <button id="tab-account-btn" onclick="switchSettingsTab('account')" class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors bg-zinc-700 text-white">Account</button>
        <button id="tab-health-btn" onclick="switchSettingsTab('health')" class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors text-zinc-400 hover:text-white">Health Profile</button>
      </div>
      <div id="tab-account" class="space-y-5">
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5">
          <h3 class="font-semibold text-white mb-4">Profile Photo</h3>
          <div class="flex items-center gap-4">
            <div id="avatar-preview-wrap" class="shrink-0">
              <?php if ($profilePhoto && file_exists($profilePhoto)): ?>
              <img id="avatar-preview" src="<?php echo $profilePhoto; ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover">
              <?php else: ?>
              <div id="avatar-preview-initials" class="w-16 h-16 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xl"><?php echo $initials; ?></div>
              <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
              <label for="avatar-file" class="cursor-pointer inline-flex items-center gap-2 bg-zinc-700 hover:bg-zinc-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors min-h-[40px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload Photo
              </label>
              <input type="file" id="avatar-file" accept="image/*" class="hidden">
              <p class="text-xs text-zinc-500 mt-1">JPG, PNG, WebP &mdash; max 2 MB</p>
            </div>
          </div>
        </div>
        <form id="account-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4" novalidate>
          <h3 class="font-semibold text-white">Account Info</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="acc_first_name" class="block text-xs text-zinc-400 mb-1">First Name</label>
              <input type="text" id="acc_first_name" name="first_name" value="<?php echo $firstName; ?>" required class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div>
              <label for="acc_last_name" class="block text-xs text-zinc-400 mb-1">Last Name</label>
              <input type="text" id="acc_last_name" name="last_name" value="<?php echo $lastName; ?>" required class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
          </div>
          <div>
            <label for="acc_username" class="block text-xs text-zinc-400 mb-1">Username</label>
            <input type="text" id="acc_username" name="username" value="<?php echo $username; ?>" required class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          </div>
          <div>
            <label for="acc_email" class="block text-xs text-zinc-400 mb-1">Email</label>
            <input type="email" id="acc_email" name="email" value="<?php echo $email; ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          </div>
          <div id="account-msg" class="hidden text-sm rounded-lg px-3 py-2"></div>
          <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Save Changes</button>
        </form>
        <form id="password-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4" novalidate>
          <h3 class="font-semibold text-white">Change Password</h3>
          <div>
            <label for="current_password" class="block text-xs text-zinc-400 mb-1">Current Password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          </div>
          <div>
            <label for="new_password" class="block text-xs text-zinc-400 mb-1">New Password</label>
            <input type="password" id="new_password" name="new_password" required autocomplete="new-password" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          </div>
          <div>
            <label for="confirm_password" class="block text-xs text-zinc-400 mb-1">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          </div>
          <div id="password-msg" class="hidden text-sm rounded-lg px-3 py-2"></div>
          <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Update Password</button>
        </form>
      </div>
      <div id="tab-health" class="hidden space-y-5">
        <form id="health-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4" novalidate>
          <h3 class="font-semibold text-white">My Health Profile</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="hp_weight" class="block text-xs text-zinc-400 mb-1">Current Weight (kg)</label>
              <input type="number" id="hp_weight" name="current_weight_kg" min="20" max="500" step="0.1" value="<?php echo htmlspecialchars($healthProfile['current_weight_kg'] ?? '',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label for="hp_height" class="block text-xs text-zinc-400 mb-1">Height (cm)</label>
              <input type="number" id="hp_height" name="height_cm" min="50" max="300" step="0.1" value="<?php echo htmlspecialchars($currentUser['height_cm'] ?? '',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label for="hp_age" class="block text-xs text-zinc-400 mb-1">Age</label>
              <input type="number" id="hp_age" name="age" min="10" max="120" value="<?php echo htmlspecialchars($healthProfile['age'] ?? '',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400" required>
            </div>
            <div>
              <label for="hp_target_weight" class="block text-xs text-zinc-400 mb-1">Target Weight (kg, optional)</label>
              <input type="number" id="hp_target_weight" name="target_weight_kg" min="20" max="500" step="0.1" value="<?php echo htmlspecialchars($healthProfile['target_weight_kg'] ?? '',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
          </div>
          <div>
            <label for="hp_fitness_level" class="block text-xs text-zinc-400 mb-1">Fitness Level</label>
            <select id="hp_fitness_level" name="fitness_level" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
              <?php foreach (['beginner','intermediate','advanced'] as $lvl): ?>
              <option value="<?php echo $lvl; ?>" <?php echo ($healthProfile['fitness_level'] ?? '') === $lvl ? 'selected' : ''; ?>><?php echo ucfirst($lvl); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="hp_goal_type" class="block text-xs text-zinc-400 mb-1">Goal</label>
            <select id="hp_goal_type" name="goal_type" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
              <?php $goals=['weight_loss'=>'Weight Loss','muscle_gain'=>'Muscle Gain','maintain_fitness'=>'Maintain Fitness','improve_endurance'=>'Improve Endurance','flexibility'=>'Flexibility']; $cgt=$activeGoal['goal_type']??''; foreach($goals as $v=>$l): ?>
              <option value="<?php echo $v; ?>" <?php echo $cgt===$v?'selected':''; ?>><?php echo $l; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="hp_medical" class="block text-xs text-zinc-400 mb-1">Medical Conditions (optional)</label>
            <textarea id="hp_medical" name="medical_conditions" rows="2" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"><?php echo htmlspecialchars($healthProfile['medical_conditions'] ?? '',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?></textarea>
          </div>
          <div id="health-msg" class="hidden text-sm rounded-lg px-3 py-2"></div>
          <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Save Health Profile</button>
        </form>
      </div>
    </main>
  </div>
</div>
</div></div></div><div id="overwrite-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
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
const FITSENSE_CSRF = <?php echo json_encode($csrfToken); ?>;
const MEMBER_HEIGHT_CM = <?php echo (float)($currentUser['height_cm'] ?? 0); ?>;
const TRAINER_ID = <?php echo json_encode($trainer ? (int)$trainer['id'] : null); ?>;
const WEIGHT_CHART_DATA = <?php echo !empty($weightLogsChart) ? json_encode(array_map(fn($r)=>['date'=>$r['log_date'],'weight'=>(float)$r['weight_kg']],$weightLogsChart)) : '[]'; ?>;
const SECTIONS = ['chat','progress','trainer','settings'];
const navBtns = document.querySelectorAll('.nav-btn');
function showSection(name) {
    if (!SECTIONS.includes(name)) name = 'chat';
    SECTIONS.forEach(s => { const el = document.getElementById('section-'+s); if (el) el.classList.toggle('active', s===name); });
    navBtns.forEach(btn => { const active = btn.dataset.section===name; btn.classList.toggle('nav-active',active); btn.classList.toggle('text-zinc-300',!active); btn.classList.toggle('hover:text-white',!active); });
    if (name==='trainer') { const tm=document.getElementById('trainer-messages'); if(tm) setTimeout(()=>{tm.scrollTop=tm.scrollHeight;},50); }
    if (name==='progress') initChart();
    const titles={chat:'FitSense AI',progress:'Progress ? FitSense',trainer:'Trainer Chat ? FitSense',settings:'Settings ? FitSense'};
    document.title = titles[name]||'FitSense';
    const dd=document.getElementById('user-menu-dropdown'); if(dd){dd.classList.add('hidden'); const ch=document.querySelector('.menu-chevron'); if(ch) ch.style.transform='';}
}
function getHash() { const h=location.hash.replace('#',''); return SECTIONS.includes(h)?h:'chat'; }
window.addEventListener('hashchange', ()=>showSection(getHash()));
showSection(getHash());
navBtns.forEach(btn => { btn.addEventListener('click', ()=>{ if(window.innerWidth<1024) closeSidebar(); location.hash=btn.dataset.section; }); });
const sidebar=document.getElementById('sidebar'), overlay=document.getElementById('sidebar-overlay');
function openSidebar(){sidebar.classList.remove('-translate-x-full');overlay.classList.remove('hidden');requestAnimationFrame(()=>overlay.classList.remove('opacity-0'));}
function closeSidebar(){sidebar.classList.add('-translate-x-full');overlay.classList.add('opacity-0');setTimeout(()=>overlay.classList.add('hidden'),250);}
document.querySelectorAll('.sidebar-open-btn').forEach(b=>b.addEventListener('click',openSidebar));
document.getElementById('sidebar-close-btn').addEventListener('click',closeSidebar);
overlay.addEventListener('click',closeSidebar);
document.getElementById('user-menu-btn').addEventListener('click',function(e){e.stopPropagation();e.preventDefault();const dd=document.getElementById('user-menu-dropdown'),ch=this.querySelector('.menu-chevron');const hidden=dd.classList.toggle('hidden');if(ch) ch.style.transform=hidden?'':'rotate(180deg)';});
document.addEventListener('click',function(e){const btn=document.getElementById('user-menu-btn');if(btn&&btn.contains(e.target)) return;const dd=document.getElementById('user-menu-dropdown');if(dd&&!dd.classList.contains('hidden')){dd.classList.add('hidden');const ch=document.querySelector('.menu-chevron');if(ch) ch.style.transform='';}});
function showToast(msg,isError=false){const t=document.getElementById('toast');t.textContent=msg;t.className='fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none '+(isError?'bg-red-700 text-white':'bg-green-700 text-white');t.style.opacity='1';setTimeout(()=>{t.style.transition='opacity .3s';t.style.opacity='0';setTimeout(()=>{t.style.transition='';},300);},3000);}
let selectedRating=0;
document.querySelectorAll('.star-btn').forEach(btn=>{btn.addEventListener('click',()=>{selectedRating=parseInt(btn.dataset.star);document.getElementById('rating').value=selectedRating;document.querySelectorAll('.star-btn').forEach(b=>{b.classList.toggle('text-yellow-400',parseInt(b.dataset.star)<=selectedRating);b.classList.toggle('text-zinc-600',parseInt(b.dataset.star)>selectedRating);});});});
const workoutForm=document.getElementById('workout-form');
if(workoutForm){workoutForm.addEventListener('submit',async(e)=>{e.preventDefault();const form=e.target,btn=form.querySelector('button[type="submit"]');btn.disabled=true;btn.textContent='Logging?';try{const res=await fetch('api/members.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'log_workout',session_date:form.session_date.value,duration_minutes:form.duration_minutes.value,rating:form.rating.value||null,notes:form.workout_notes.value||null,calories_burned:form.calories_burned.value||null,csrf_token:FITSENSE_CSRF})});const data=await res.json();if(data.success){showToast('Workout logged!');form.reset();form.session_date.value=new Date().toISOString().split('T')[0];selectedRating=0;document.querySelectorAll('.star-btn').forEach(b=>{b.classList.remove('text-yellow-400');b.classList.add('text-zinc-600');});setTimeout(()=>{location.href='chat.php#progress';},1500);}else{showToast(data.errors?data.errors.join(' '):(data.message||'Failed.'),true);}}catch{showToast('Network error.',true);}finally{btn.disabled=false;btn.textContent='Log Workout';}});}
let pendingWeightPayload=null;
async function submitWeight(payload){const btn=document.querySelector('#weight-form button[type="submit"]');btn.disabled=true;btn.textContent='Logging?';try{const res=await fetch('api/members.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const data=await res.json();if(data.success){showToast('Weight logged!');document.getElementById('weight-form').reset();document.getElementById('log_date').value=new Date().toISOString().split('T')[0];const w=parseFloat(payload.weight_kg);const wEl=document.querySelector('[data-stat="weight"]');const bmiEl=document.querySelector('[data-stat="bmi"]');if(wEl)wEl.textContent=w.toFixed(1)+' kg';if(bmiEl&&MEMBER_HEIGHT_CM>0){const bmi=Math.round((w/Math.pow(MEMBER_HEIGHT_CM/100,2))*10)/10;bmiEl.textContent=bmi;}}else if(data.conflict){pendingWeightPayload={...payload,confirm_overwrite:true};document.getElementById('overwrite-dialog').classList.remove('hidden');}else{showToast(data.errors?data.errors.join(' '):(data.message||'Failed.'),true);}}catch{showToast('Network error.',true);}finally{btn.disabled=false;btn.textContent='Log Weight';}}
const weightForm=document.getElementById('weight-form');
if(weightForm){weightForm.addEventListener('submit',(e)=>{e.preventDefault();const form=e.target,logDate=form.log_date.value;if(logDate>new Date().toISOString().split('T')[0]){showToast('Cannot log weight for a future date.',true);return;}submitWeight({action:'log_weight',log_date:logDate,weight_kg:form.weight_kg.value,notes:form.weight_notes.value||null,csrf_token:FITSENSE_CSRF});});}
document.getElementById('overwrite-confirm').addEventListener('click',()=>{document.getElementById('overwrite-dialog').classList.add('hidden');if(pendingWeightPayload) submitWeight(pendingWeightPayload);});
document.getElementById('overwrite-cancel').addEventListener('click',()=>{document.getElementById('overwrite-dialog').classList.add('hidden');pendingWeightPayload=null;});
let chartInited=false;
// -- Daily midnight reset ------------------------------------------------------
(function(){
  var today = new Date().toISOString().split('T')[0];
  var lastDate = localStorage.getItem('fitsense_last_date');
  if (lastDate && lastDate !== today) {
    // New day ? reset workout/weight forms to today's date
    var sd = document.getElementById('session_date');
    var ld = document.getElementById('log_date');
    if (sd) sd.value = today;
    if (ld) ld.value = today;
    showToast('Good morning! Your daily log has been reset for today.');
  }
  localStorage.setItem('fitsense_last_date', today);
  // Schedule a check at next midnight
  var now = new Date();
  var msUntilMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate()+1, 0, 0, 5).getTime() - now.getTime();
  setTimeout(function(){
    var newDay = new Date().toISOString().split('T')[0];
    localStorage.setItem('fitsense_last_date', newDay);
    var sd2 = document.getElementById('session_date');
    var ld2 = document.getElementById('log_date');
    if (sd2) sd2.value = newDay;
    if (ld2) ld2.value = newDay;
    showToast('Midnight reset ? your daily log is fresh for today!');
  }, msUntilMidnight);
})();
function initChart(){if(chartInited||!WEIGHT_CHART_DATA.length) return;const canvas=document.getElementById('weight-chart');if(!canvas) return;chartInited=true;new Chart(canvas.getContext('2d'),{type:'line',data:{labels:WEIGHT_CHART_DATA.map(d=>d.date),datasets:[{label:'Weight (kg)',data:WEIGHT_CHART_DATA.map(d=>d.weight),borderColor:'#facc15',backgroundColor:'rgba(250,204,21,0.1)',borderWidth:2,pointBackgroundColor:'#facc15',pointRadius:4,tension:0.3,fill:true}]},options:{responsive:true,plugins:{legend:{labels:{color:'#a1a1aa',font:{size:12}}},tooltip:{callbacks:{label:c=>c.parsed.y+' kg'}}},scales:{x:{ticks:{color:'#a1a1aa',maxRotation:45,font:{size:11}},grid:{color:'#27272a'}},y:{ticks:{color:'#a1a1aa',callback:v=>v+' kg',font:{size:11}},grid:{color:'#27272a'}}}}});}
function switchSettingsTab(tab){const isAccount=tab==='account';document.getElementById('tab-account').classList.toggle('hidden',!isAccount);document.getElementById('tab-health').classList.toggle('hidden',isAccount);document.getElementById('tab-account-btn').className='flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors '+(isAccount?'bg-zinc-700 text-white':'text-zinc-400 hover:text-white');document.getElementById('tab-health-btn').className='flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors '+(!isAccount?'bg-zinc-700 text-white':'text-zinc-400 hover:text-white');}
document.getElementById('avatar-file').addEventListener('change',async function(){if(!this.files[0]) return;const fd=new FormData();fd.append('action','upload_avatar');fd.append('csrf_token',FITSENSE_CSRF);fd.append('profile_photo',this.files[0]);try{const res=await fetch('api/members.php',{method:'POST',body:fd});const data=await res.json();if(data.success){showToast('Photo updated!');const newSrc=data.profile_photo+'?t='+Date.now();const preview=document.getElementById('avatar-preview');if(preview){preview.src=newSrc;}else{const wrap=document.getElementById('avatar-preview-wrap');if(wrap) wrap.innerHTML='<img id="avatar-preview" src="'+newSrc+'" alt="Avatar" class="w-16 h-16 rounded-full object-cover">';}const sidebarImg=document.getElementById('sidebar-avatar-img');if(sidebarImg){sidebarImg.src=newSrc;}else{const sidebarInit=document.getElementById('sidebar-avatar-initials');if(sidebarInit) sidebarInit.outerHTML='<img id="sidebar-avatar-img" src="'+newSrc+'" alt="Avatar" class="w-8 h-8 rounded-full object-cover shrink-0">';}}else{showToast(data.error||'Upload failed.',true);}}catch{showToast('Network error.',true);}});
document.getElementById('account-form').addEventListener('submit',async(e)=>{e.preventDefault();const form=e.target,btn=form.querySelector('button[type="submit"]'),msgEl=document.getElementById('account-msg');btn.disabled=true;btn.textContent='Saving?';try{const res=await fetch('api/members.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update_account',first_name:form.first_name.value,last_name:form.last_name.value,username:form.username.value,email:form.email.value,csrf_token:FITSENSE_CSRF})});const data=await res.json();if(data.success){msgEl.textContent='Account updated successfully.';msgEl.className='text-sm rounded-lg px-3 py-2 bg-green-900 text-green-300';msgEl.classList.remove('hidden');showToast('Account updated!');}else{msgEl.textContent=data.errors?data.errors.join(' '):(data.message||'Failed.');msgEl.className='text-sm rounded-lg px-3 py-2 bg-red-900 text-red-300';msgEl.classList.remove('hidden');}}catch{showToast('Network error.',true);}finally{btn.disabled=false;btn.textContent='Save Changes';}});
document.getElementById('password-form').addEventListener('submit',async(e)=>{e.preventDefault();const form=e.target,btn=form.querySelector('button[type="submit"]'),msgEl=document.getElementById('password-msg'),newPw=form.new_password.value,confirmPw=form.confirm_password.value;if(newPw!==confirmPw){msgEl.textContent='New passwords do not match.';msgEl.className='text-sm rounded-lg px-3 py-2 bg-red-900 text-red-300';msgEl.classList.remove('hidden');return;}btn.disabled=true;btn.textContent='Updating?';try{const res=await fetch('api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'change_password',current_password:form.current_password.value,new_password:newPw,csrf_token:FITSENSE_CSRF})});const data=await res.json();if(data.success){msgEl.textContent='Password updated successfully.';msgEl.className='text-sm rounded-lg px-3 py-2 bg-green-900 text-green-300';msgEl.classList.remove('hidden');form.reset();showToast('Password updated!');}else{msgEl.textContent=data.message||'Failed to update password.';msgEl.className='text-sm rounded-lg px-3 py-2 bg-red-900 text-red-300';msgEl.classList.remove('hidden');}}catch{showToast('Network error.',true);}finally{btn.disabled=false;btn.textContent='Update Password';}});
document.getElementById('health-form').addEventListener('submit',async(e)=>{e.preventDefault();const form=e.target,btn=form.querySelector('button[type="submit"]'),msgEl=document.getElementById('health-msg');btn.disabled=true;btn.textContent='Saving?';try{const res=await fetch('api/members.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update_profile',current_weight_kg:form.current_weight_kg.value,height_cm:form.height_cm.value,age:form.age.value,fitness_level:form.fitness_level.value,goal_type:form.goal_type.value,target_weight_kg:form.target_weight_kg.value||null,medical_conditions:form.medical_conditions.value||null,csrf_token:FITSENSE_CSRF})});const data=await res.json();if(data.success){msgEl.textContent='Health profile saved.';msgEl.className='text-sm rounded-lg px-3 py-2 bg-green-900 text-green-300';msgEl.classList.remove('hidden');showToast('Health profile saved!');}else{msgEl.textContent=data.errors?data.errors.join(' '):(data.message||'Failed.');msgEl.className='text-sm rounded-lg px-3 py-2 bg-red-900 text-red-300';msgEl.classList.remove('hidden');}}catch{showToast('Network error.',true);}finally{btn.disabled=false;btn.textContent='Save Health Profile';}});
const trainerInput=document.getElementById('trainer-input'),trainerSendBtn=document.getElementById('trainer-send-btn'),trainerMsgs=document.getElementById('trainer-messages');
if(trainerInput&&trainerSendBtn){trainerInput.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,128)+'px';trainerSendBtn.disabled=this.value.trim()==='';});trainerInput.addEventListener('keydown',(e)=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendTrainerMessage();}});trainerSendBtn.addEventListener('click',sendTrainerMessage);}
function sendTrainerMessage(){if(!trainerInput) return;const text=trainerInput.value.trim();if(!text||!TRAINER_ID) return;appendTrainerMsg(text,'member');trainerInput.value='';trainerInput.style.height='auto';trainerSendBtn.disabled=true;fetch('api/chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'send_member_message',message:text,csrf_token:FITSENSE_CSRF})}).then(r=>r.json()).then(d=>{if(!d.success) showToast(d.message||'Failed to send.',true);}).catch(()=>showToast('Connection error.',true));}
function appendTrainerMsg(text,type){if(!trainerMsgs) return;const wrap=document.createElement('div'),time=new Date().toLocaleTimeString([],{hour:'numeric',minute:'2-digit'}),esc=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');if(type==='member'){wrap.className='flex justify-end';wrap.innerHTML=`<div class="max-w-[70%]"><div class="bg-yellow-400 text-black rounded-2xl rounded-br-sm px-3 py-2 text-sm break-words">${esc(text)}</div><p class="text-xs text-zinc-600 mt-0.5 text-right">${time}</p></div>`;}trainerMsgs.appendChild(wrap);trainerMsgs.scrollTop=trainerMsgs.scrollHeight;}
<?php if ($trainer): ?>
setInterval(()=>{if(location.hash!=='#trainer') return;fetch('api/chat.php?action=trainer_messages').then(r=>r.json()).then(d=>{if(d.success&&d.new_count>0){location.href='chat.php#trainer';}}).catch(()=>{});},5000);
<?php endif; ?>
</script>
<script src="js/chat.js"></script>
<script>
// Quick action buttons (sidebar)
document.querySelectorAll('.quick-action-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var section = this.dataset.section;
    var prompt  = this.dataset.prompt;
    var sb = document.getElementById('sidebar');
    var ov = document.getElementById('sidebar-overlay');
    if(window.innerWidth < 1024 && sb){
      sb.classList.add('-translate-x-full');
      if(ov){ov.classList.add('opacity-0');setTimeout(function(){ov.classList.add('hidden');},250);}
    }
    if(section){
      location.hash = section;
    } else if(prompt){
      location.hash = 'chat';
      var tryFill = function(n){
        var inp = document.getElementById('message-input');
        if(inp){ inp.value = prompt; inp.dispatchEvent(new Event('input')); inp.focus(); }
        else if(n > 0){ setTimeout(function(){ tryFill(n-1); }, 100); }
      };
      setTimeout(function(){ tryFill(5); }, 100);
    }
  });
});
// Welcome action buttons (main chat area)
document.querySelectorAll('.welcome-action-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var section = this.dataset.section;
    var prompt  = this.dataset.prompt;
    if(section){ location.hash = section; }
    else if(prompt){
      var inp = document.getElementById('message-input');
      if(inp){ inp.value = prompt; inp.dispatchEvent(new Event('input')); inp.focus(); }
    }
  });
});
</script>
<script src="js/theme.js"></script>
</body>
</html>