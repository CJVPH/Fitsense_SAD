<?php
/**
 * FitSense — Trainer Dashboard
 * Requirements: 10.1–10.4, 11.1–11.6, 12.1–12.6, 15.2
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireRole('trainer');

$pdo         = Database::getConnection();
$currentUser = $auth->getCurrentUser();
$csrfToken   = generateCsrfToken();
$trainerId   = (int) $_SESSION['user_id'];

$announcements = getActiveAnnouncements('trainer', $pdo);

$pendingStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM ai_recommendations ar
       JOIN member_profiles mp ON mp.user_id = ar.user_id
      WHERE mp.assigned_trainer_id = ? AND ar.status = \'pending\''
);
$pendingStmt->execute([$trainerId]);
$pendingCount = (int) $pendingStmt->fetchColumn();

$unreadStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM chat_messages cm
       JOIN member_profiles mp ON mp.user_id = cm.user_id
      WHERE mp.assigned_trainer_id = ? AND cm.sender_type = \'member\' AND cm.is_read = FALSE'
);
$unreadStmt->execute([$trainerId]);
$unreadCount = (int) $unreadStmt->fetchColumn();

$firstName = htmlspecialchars($currentUser['first_name'] ?? 'Trainer', ENT_QUOTES | ENT_HTML5, 'UTF-8');
$lastName  = htmlspecialchars($currentUser['last_name']  ?? '',         ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Trainer Dashboard — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/theme.css">
    <script>
    (function(){var t=localStorage.getItem('fitsense_theme')||'<?php echo htmlspecialchars($currentUser['theme_preference']??'dark',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>';if(t==='light')document.documentElement.classList.add('light-mode');})();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        #sidebar { transition: transform 0.25s ease; }
        #sidebar-overlay { transition: opacity 0.25s ease; }
        /* Messenger: fill remaining height below mobile top bar */
        #messenger-layout {
            height: calc(100vh - 56px);
            height: calc(100dvh - 56px);
        }
        @media (min-width: 1024px) {
            #messenger-layout {
                height: 100vh;
                height: 100dvh;
            }
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen" style="min-width:375px">

<!-- ── Mobile top bar ────────────────────────────────────────────────────── -->
<header class="lg:hidden sticky top-0 z-40 bg-zinc-900 border-b border-zinc-700 px-4 flex items-center justify-between h-14">
    <button id="sidebar-open" aria-label="Open menu"
        class="text-yellow-400 p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
    <span class="text-yellow-400 font-bold text-lg tracking-tight">FitSense</span>
    <a href="logout.php" class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Logout">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
        </svg>
    </a>
</header>

<!-- ── Sidebar overlay (mobile) ──────────────────────────────────────────── -->
<div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden opacity-0 lg:hidden"></div>

<!-- ── Sidebar ───────────────────────────────────────────────────────────── -->
<aside id="sidebar"
    class="fixed top-0 left-0 z-50 h-full w-64 bg-zinc-900 border-r border-zinc-700 flex flex-col
           -translate-x-full lg:translate-x-0 lg:z-30">

    <!-- Brand -->
    <div class="px-5 py-5 border-b border-zinc-700">
        <span class="text-yellow-400 font-bold text-xl tracking-tight">FitSense</span>
        <p class="text-zinc-500 text-xs mt-0.5">Trainer Portal</p>
    </div>

    <!-- Nav items -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <button data-section="overview"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Overview
        </button>
        <button data-section="roster"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a2 2 0 100-4 2 2 0 000 4zM3 16a2 2 0 100-4 2 2 0 000 4z"/>
            </svg>
            My Members
        </button>
        <button data-section="recommendations"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Reviews
            <?php if ($pendingCount > 0): ?>
            <span id="pending-badge" class="ml-auto bg-yellow-400 text-black text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[20px] text-center"><?= $pendingCount ?></span>
            <?php endif; ?>
        </button>
        <button data-section="messages"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Messages
            <?php if ($unreadCount > 0): ?>
            <span id="unread-badge" class="ml-auto bg-yellow-400 text-black text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[20px] text-center"><?= $unreadCount ?></span>
            <?php endif; ?>
        </button>
        <button data-section="announcements"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            Announcements
        </button>
    </nav>

    <!-- User + Logout -->
    <div class="px-4 py-4 border-t border-zinc-700">
        <div class="flex items-center gap-3 mb-3">
            <?php
            $profilePhoto = htmlspecialchars($currentUser['profile_photo'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($profilePhoto && file_exists($profilePhoto)):
            ?>
            <img id="sidebar-avatar-img" src="<?= $profilePhoto ?>" alt="Profile" class="w-9 h-9 rounded-full object-cover shrink-0">
            <?php else: ?>
            <div id="sidebar-avatar-img" class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm shrink-0">
                <?= strtoupper(substr($firstName, 0, 1)) ?>
            </div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
                <p class="text-white text-sm font-medium truncate"><?= $firstName ?> <?= $lastName ?></p>
                <p class="text-zinc-500 text-xs">Trainer</p>
            </div>
            <button data-section="profile" class="nav-btn text-zinc-400 hover:text-yellow-400 p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors shrink-0" aria-label="Edit Profile">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
        </div>
        <button class="theme-toggle-btn w-full flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors mb-1">
            <svg class="icon-dark w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="icon-light w-4 h-4 shrink-0 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span class="theme-label">Light Mode</span>
        </button>
        <a href="logout.php"
            class="w-full flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
            </svg>
            Logout
        </a>
    </div>
</aside>

<!-- ── Main content ───────────────────────────────────────────────────────── -->
<div class="lg:pl-64">

<!-- Messenger layout (full height, shown only when messages section is active) -->
<div id="messenger-layout" class="hidden flex overflow-hidden">
    <!-- Conversation list -->
    <div id="msg-thread-list" class="w-full lg:w-72 bg-zinc-900 border-r border-zinc-700 flex flex-col shrink-0">
        <div class="px-4 py-3 border-b border-zinc-700 shrink-0">
            <h2 class="text-white font-bold text-base mb-2">Messages</h2>
            <!-- Search -->
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="msg-search" type="text" placeholder="Search members…"
                    class="w-full bg-zinc-800 border border-zinc-700 text-white text-sm rounded-xl pl-9 pr-3 py-2 focus:outline-none focus:border-yellow-400 min-h-[40px]">
            </div>
        </div>
        <div id="msg-contacts" class="flex-1 overflow-y-auto divide-y divide-zinc-800"></div>
    </div>
    <!-- Chat pane -->
    <div id="msg-chat-pane" class="hidden lg:flex flex-col flex-1 bg-black min-w-0">
        <!-- Chat header -->
        <div id="msg-chat-header" class="flex items-center gap-3 px-4 py-3 border-b border-zinc-700 bg-zinc-900 shrink-0">
            <button id="msg-back-btn" class="lg:hidden text-yellow-400 p-1 min-h-[44px] min-w-[44px] flex items-center justify-center" aria-label="Back">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <div id="msg-chat-avatar" class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm shrink-0"></div>
            <div class="min-w-0">
                <p id="msg-chat-name" class="text-white font-semibold text-sm truncate"></p>
                <p class="text-zinc-500 text-xs">Member</p>
            </div>
        </div>
        <!-- Messages area -->
        <div id="msg-chat-body" class="flex-1 overflow-y-auto px-4 py-4 space-y-2"></div>
        <!-- Input bar -->
        <div class="px-3 py-3 border-t border-zinc-700 bg-zinc-900 shrink-0">
            <div class="flex items-end gap-2">
                <textarea id="msg-chat-input" rows="1" placeholder="Type a message…"
                    class="flex-1 bg-zinc-800 border border-zinc-600 text-white rounded-2xl px-4 py-2.5 text-sm focus:outline-none focus:border-yellow-400 resize-none overflow-y-auto min-h-[44px] max-h-28"></textarea>
                <button id="msg-send-btn"
                    class="bg-yellow-400 hover:bg-yellow-300 text-black rounded-full p-2.5 min-h-[44px] min-w-[44px] flex items-center justify-center transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <!-- Empty state (desktop) -->
    <div id="msg-empty-pane" class="hidden lg:flex flex-col flex-1 items-center justify-center bg-black text-zinc-500">
        <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <p class="text-sm">Select a conversation</p>
    </div>
</div>

<main class="flex flex-col min-h-screen px-4 py-6 max-w-3xl mx-auto w-full" id="main-content">

    <!-- ── Overview Section ───────────────────────────────────────────── -->
    <section id="section-overview" class="hidden space-y-5">
        <h1 class="text-xl font-bold text-yellow-400">Overview</h1>
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 gap-3" id="overview-stats">
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-yellow-400" id="stat-members">—</p>
                <p class="text-xs text-zinc-400 mt-1">Assigned Members</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-yellow-400" id="stat-pending">—</p>
                <p class="text-xs text-zinc-400 mt-1">Pending Reviews</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-yellow-400" id="stat-unread">—</p>
                <p class="text-xs text-zinc-400 mt-1">Unread Messages</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-yellow-400" id="stat-active">—</p>
                <p class="text-xs text-zinc-400 mt-1">Active Members</p>
            </div>
        </div>
        <!-- Recent Activity -->
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <h2 class="text-sm font-semibold text-zinc-300 mb-3">Recent Activity</h2>
            <div id="overview-activity" class="space-y-2 text-sm text-zinc-400">
                <p class="text-center py-4">Loading…</p>
            </div>
        </div>
        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-3">
            <button onclick="showSection('roster'); loadRoster();"
                class="bg-zinc-900 border border-zinc-700 hover:border-yellow-400/50 rounded-xl p-4 text-left transition-colors min-h-[44px]">
                <p class="text-yellow-400 font-semibold text-sm">View Roster</p>
                <p class="text-zinc-500 text-xs mt-0.5">See all your members</p>
            </button>
            <button onclick="showSection('recommendations'); loadPendingRecs();"
                class="bg-zinc-900 border border-zinc-700 hover:border-yellow-400/50 rounded-xl p-4 text-left transition-colors min-h-[44px]">
                <p class="text-yellow-400 font-semibold text-sm">Pending Reviews</p>
                <p class="text-zinc-500 text-xs mt-0.5">Review AI recommendations</p>
            </button>
        </div>
    </section>

    <!-- ── Roster Section ─────────────────────────────────────────────── -->
    <section id="section-roster" class="hidden space-y-4">
        <h1 class="text-xl font-bold text-yellow-400">Members</h1>
        <!-- Tabs -->
        <div class="flex gap-1 bg-zinc-900 border border-zinc-700 rounded-xl p-1">
            <button id="tab-my-members" onclick="switchRosterTab('my')"
                class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] bg-yellow-400 text-black">
                My Members
            </button>
            <button id="tab-available" onclick="switchRosterTab('available')"
                class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] text-zinc-400 hover:text-white">
                Available
            </button>
        </div>
        <!-- My Members tab -->
        <div id="roster-tab-my">
            <div id="roster-loading" class="text-zinc-400 text-sm py-8 text-center">Loading roster…</div>
            <div id="roster-list" class="space-y-3 hidden"></div>
            <div id="roster-empty" class="hidden bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">
                No members assigned to you yet.
            </div>
        </div>
        <!-- Available Members tab -->
        <div id="roster-tab-available" class="hidden">
            <div id="available-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
            <div id="available-list" class="space-y-3 hidden"></div>
            <div id="available-empty" class="hidden bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">
                No unassigned members available.
            </div>
        </div>
    </section>

    <!-- ── Member Detail Section ──────────────────────────────────────── -->
    <section id="section-member-detail" class="hidden space-y-6">
        <button id="back-to-roster" class="flex items-center gap-2 text-yellow-400 hover:text-yellow-300 text-sm font-medium min-h-[44px] transition-colors">
            ← Back to Roster
        </button>
        <div id="member-detail-content"></div>
    </section>

    <!-- ── Recommendations Section ────────────────────────────────────── -->
    <section id="section-recommendations" class="hidden space-y-4">
        <h1 class="text-xl font-bold text-yellow-400">Pending Reviews</h1>
        <div id="recs-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="recs-list" class="space-y-4 hidden"></div>
        <div id="recs-empty" class="hidden bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">
            No pending recommendations.
        </div>
    </section>

    <!-- ── Messages Section ───────────────────────────────────────────── -->
    <section id="section-messages" class="hidden"></section>

    <!-- ── Announcements Section ──────────────────────────────────────── -->
    <section id="section-announcements" class="hidden space-y-4">
        <h1 class="text-xl font-bold text-yellow-400">Announcements</h1>
        <div id="announcements-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="announcements-list" class="space-y-3 hidden"></div>
        <div id="announcements-empty" class="hidden bg-zinc-900 border border-zinc-700 rounded-xl p-6 text-center text-zinc-400 text-sm">
            No announcements at this time.
        </div>
    </section>

    <!-- ── Profile Section ────────────────────────────────────────────── -->
    <section id="section-profile" class="hidden space-y-5">
        <h1 class="text-xl font-bold text-yellow-400">Edit Profile</h1>

        <!-- Avatar -->
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 flex flex-col items-center gap-4">
            <div class="relative">
                <div id="avatar-preview" class="w-20 h-20 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-2xl overflow-hidden">
                    <?= strtoupper(substr($firstName, 0, 1)) ?>
                </div>
                <label for="profile-photo-input"
                    class="absolute bottom-0 right-0 bg-zinc-700 hover:bg-zinc-600 border border-zinc-600 rounded-full p-1.5 cursor-pointer transition-colors"
                    aria-label="Upload photo">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </label>
                <input type="file" id="profile-photo-input" accept="image/*" class="hidden">
            </div>
            <p class="text-xs text-zinc-500">JPG, PNG or GIF · Max 2 MB</p>
        </div>

        <!-- Profile Form -->
        <form id="profile-form" class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4" onsubmit="saveProfile(event)">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-zinc-400 mb-1" for="pf-first-name">First Name</label>
                    <input type="text" id="pf-first-name" name="first_name" required
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
                </div>
                <div>
                    <label class="block text-xs text-zinc-400 mb-1" for="pf-last-name">Last Name</label>
                    <input type="text" id="pf-last-name" name="last_name" required
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
                </div>
            </div>
            <div>
                <label class="block text-xs text-zinc-400 mb-1" for="pf-username">Username</label>
                <input type="text" id="pf-username" name="username" required
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
            </div>
            <div>
                <label class="block text-xs text-zinc-400 mb-1" for="pf-email">Email</label>
                <input type="email" id="pf-email" name="email"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
            </div>
            <div>
                <label class="block text-xs text-zinc-400 mb-1" for="pf-phone">Phone</label>
                <input type="tel" id="pf-phone" name="phone"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
            </div>

            <hr class="border-zinc-700">
            <p class="text-xs text-zinc-500">Leave password fields blank to keep your current password.</p>

            <div>
                <label class="block text-xs text-zinc-400 mb-1" for="pf-new-password">New Password</label>
                <div class="relative">
                    <input type="password" id="pf-new-password" name="new_password" autocomplete="new-password"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 pr-11 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
                    <button type="button" data-toggle="pf-new-password"
                        class="eye-toggle absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white p-1 min-h-[44px] min-w-[44px] flex items-center justify-center"
                        aria-label="Toggle password visibility">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs text-zinc-400 mb-1" for="pf-confirm-password">Confirm Password</label>
                <div class="relative">
                    <input type="password" id="pf-confirm-password" name="confirm_password" autocomplete="new-password"
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2.5 pr-11 text-sm focus:outline-none focus:border-yellow-400 min-h-[44px]">
                    <button type="button" data-toggle="pf-confirm-password"
                        class="eye-toggle absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white p-1 min-h-[44px] min-w-[44px] flex items-center justify-center"
                        aria-label="Toggle password visibility">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" id="profile-save-btn"
                class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] text-sm transition-colors">
                Save Changes
            </button>
        </form>
    </section>

</main>
</div><!-- end lg:pl-64 -->

<!-- ── Review Modal ──────────────────────────────────────────────────────── -->
<div id="review-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-md w-full space-y-4">
        <h2 class="text-white font-bold text-lg" id="review-modal-title">Review Recommendation</h2>
        <div id="review-rec-content" class="text-zinc-300 text-sm bg-zinc-800 rounded-lg p-3 max-h-48 overflow-y-auto"></div>
        <div>
            <label for="trainer-notes" class="block text-xs text-zinc-400 mb-1">Trainer Notes <span class="text-zinc-500">(required for rejection)</span></label>
            <textarea id="trainer-notes" rows="3" placeholder="Add notes for the member…"
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
        </div>
        <div class="flex gap-2">
            <button id="btn-approve" class="flex-1 bg-green-700 hover:bg-green-600 text-white font-bold rounded-lg px-3 py-3 min-h-[44px] text-sm transition-colors">Approve</button>
            <button id="btn-modify"  class="flex-1 bg-blue-700 hover:bg-blue-600 text-white font-bold rounded-lg px-3 py-3 min-h-[44px] text-sm transition-colors">Modify</button>
            <button id="btn-reject"  class="flex-1 bg-red-700 hover:bg-red-600 text-white font-bold rounded-lg px-3 py-3 min-h-[44px] text-sm transition-colors">Reject</button>
        </div>
        <button id="review-modal-close" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm transition-colors">Cancel</button>
    </div>
</div>

<!-- Toast -->
<div id="toast"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0"
    role="status" aria-live="polite"></div>

<script>
const FITSENSE_CSRF = <?= json_encode($csrfToken) ?>;

// ── Sidebar toggle (mobile) ───────────────────────────────────────────────
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const sidebarOpen    = document.getElementById('sidebar-open');

function openSidebar() {
    sidebar.classList.remove('-translate-x-full');
    sidebarOverlay.classList.remove('hidden');
    requestAnimationFrame(() => sidebarOverlay.classList.remove('opacity-0'));
}
function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('opacity-0');
    setTimeout(() => sidebarOverlay.classList.add('hidden'), 250);
}

sidebarOpen?.addEventListener('click', openSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);
</script>
<script src="js/trainer-dashboard.js"></script>
<script src="js/theme.js"></script>
</body>
</html>
