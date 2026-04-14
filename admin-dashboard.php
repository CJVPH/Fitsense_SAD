<?php
/**
 * FitSense — Admin Dashboard
 * Requirements: 13.1–13.9, 14.1–14.4, 15.1–15.3, 16.1–16.4, 17.1–17.4
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireRole('admin');

$pdo         = Database::getConnection();
$currentUser = $auth->getCurrentUser();
$csrfToken   = generateCsrfToken();

$firstName = htmlspecialchars($currentUser['first_name'] ?? 'Admin', ENT_QUOTES | ENT_HTML5, 'UTF-8');
$lastName  = htmlspecialchars($currentUser['last_name']  ?? '',      ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Admin Dashboard — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/theme.css">
    <script>
    (function(){var t=localStorage.getItem('fitsense_theme')||'<?php echo htmlspecialchars($currentUser['theme_preference']??'dark',ENT_QUOTES|ENT_HTML5,'UTF-8'); ?>';if(t==='light')document.documentElement.classList.add('light-mode');})();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Sidebar transition */
        #sidebar { transition: transform 0.25s ease; }
        #sidebar-overlay { transition: opacity 0.25s ease; }
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
    <span class="text-yellow-400 font-bold text-lg tracking-tight">FitSense Admin</span>
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
        <p class="text-zinc-500 text-xs mt-0.5">Admin Panel</p>
    </div>

    <!-- Nav items -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <button data-section="overview"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-yellow-400 bg-yellow-400/10 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Overview
        </button>
        <button data-section="users"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a2 2 0 100-4 2 2 0 000 4zM3 16a2 2 0 100-4 2 2 0 000 4z"/>
            </svg>
            Users
        </button>
        <button data-section="exercises"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m-7.5-7.5v15"/>
            </svg>
            Exercises
        </button>
        <button data-section="announcements"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            Announcements
        </button>
        <button data-section="analytics"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Analytics
        </button>
        <button data-section="audit"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Audit Log
        </button>
        <button data-section="settings"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </button>
        <button data-section="inquiries"
            class="nav-btn w-full flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Inquiries
            <span id="inquiries-badge" class="ml-auto hidden bg-yellow-400 text-black text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[20px] text-center"></span>
        </button>
    </nav>

    <!-- User + Logout -->
    <div class="px-4 py-4 border-t border-zinc-700">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm shrink-0">
                <?= strtoupper(substr($firstName, 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-sm font-medium truncate"><?= $firstName ?> <?= $lastName ?></p>
                <p class="text-zinc-500 text-xs">Administrator</p>
            </div>
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

<!-- ── Main content (offset by sidebar on desktop) ───────────────────────── -->
<div class="lg:pl-64 flex flex-col min-h-screen">
<main class="flex-1 px-4 py-6 max-w-5xl mx-auto w-full">

    <!-- ── Overview Section ──────────────────────────────────────────────── -->
    <section id="section-overview" class="space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-2xl font-bold text-yellow-400">Welcome back, <?= $firstName ?></h1>
                <p class="text-zinc-400 text-sm mt-0.5">Here's what's happening at FitSense today.</p>
            </div>
            <span id="overview-date" class="text-zinc-500 text-sm"></span>
        </div>

        <!-- Stat cards -->
        <div id="overview-stats" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 animate-pulse">
                <div class="h-3 bg-zinc-700 rounded w-16 mb-3"></div>
                <div class="h-7 bg-zinc-700 rounded w-10"></div>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 animate-pulse">
                <div class="h-3 bg-zinc-700 rounded w-16 mb-3"></div>
                <div class="h-7 bg-zinc-700 rounded w-10"></div>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 animate-pulse">
                <div class="h-3 bg-zinc-700 rounded w-16 mb-3"></div>
                <div class="h-7 bg-zinc-700 rounded w-10"></div>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 animate-pulse">
                <div class="h-3 bg-zinc-700 rounded w-16 mb-3"></div>
                <div class="h-7 bg-zinc-700 rounded w-10"></div>
            </div>
        </div>

        <!-- Charts row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <h3 class="font-semibold text-yellow-400 mb-3 text-sm">Chat Messages (7 days)</h3>
                <canvas id="overview-chat-chart" height="160"></canvas>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <h3 class="font-semibold text-yellow-400 mb-3 text-sm">AI Recommendations (7 days)</h3>
                <canvas id="overview-recs-chart" height="160"></canvas>
            </div>
        </div>

        <!-- Bottom row: system status + recent audit -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- System status -->
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-3">
                <h3 class="font-semibold text-white text-sm">System Status</h3>
                <div id="overview-status" class="space-y-2 text-sm text-zinc-400">Checking…</div>
            </div>
            <!-- Recent activity -->
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-white text-sm">Recent Activity</h3>
                    <button data-section="audit" class="nav-btn text-xs text-yellow-400 hover:text-yellow-300 transition-colors">View all →</button>
                </div>
                <div id="overview-activity" class="space-y-2 text-sm text-zinc-400">Loading…</div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
            <h3 class="font-semibold text-white text-sm mb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-2">
                <button data-section="users" id="qa-create-user"
                    class="nav-btn bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                    + Create Account
                </button>
                <button data-section="announcements" id="qa-announcement"
                    class="nav-btn bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                    + Announcement
                </button>
                <button data-section="analytics"
                    class="nav-btn bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                    View Analytics
                </button>
                <button data-section="settings"
                    class="nav-btn bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                    Settings
                </button>
            </div>
        </div>
    </section>

    <!-- ── Users Section ──────────────────────────────────────────────── -->
    <section id="section-users" class="hidden space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-yellow-400">User Management</h1>
            <button id="btn-create-user"
                class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                + Create Account
            </button>
        </div>
        <div class="flex gap-2 flex-wrap">
            <input type="text" id="user-search" placeholder="Search name, username, email…"
                class="flex-1 min-w-[180px] bg-zinc-900 border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            <select id="user-role-filter"
                class="bg-zinc-900 border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                <option value="">All Roles</option>
                <option value="member">Members</option>
                <option value="trainer">Trainers</option>
                <option value="admin">Admins</option>
            </select>
        </div>
        <div id="users-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="users-table" class="hidden space-y-2"></div>
        <div id="users-pagination" class="flex gap-2 justify-center mt-4 hidden"></div>
    </section>

    <!-- ── Exercises Section ──────────────────────────────────────────── -->
    <section id="section-exercises" class="hidden space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-yellow-400">Exercise Library</h1>
            <button id="btn-create-exercise"
                class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                + Add Exercise
            </button>
        </div>
        <div id="exercises-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="exercises-list" class="hidden space-y-2"></div>
    </section>

    <!-- ── Announcements Section ──────────────────────────────────────── -->
    <section id="section-announcements" class="hidden space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-yellow-400">Announcements</h1>
            <button id="btn-create-announcement"
                class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                + New Announcement
            </button>
        </div>
        <div id="announcements-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="announcements-list" class="hidden space-y-3"></div>
    </section>

    <!-- ── Analytics Section ──────────────────────────────────────────── -->
    <section id="section-analytics" class="hidden space-y-6">
        <h1 class="text-xl font-bold text-yellow-400">Analytics</h1>
        <div id="analytics-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="analytics-content" class="hidden space-y-6"></div>
    </section>

    <!-- ── Audit Log Section ───────────────────────────────────────────── -->
    <section id="section-audit" class="hidden space-y-4">
        <h1 class="text-xl font-bold text-yellow-400">Audit Log</h1>
        <div class="flex gap-2 flex-wrap">
            <input type="date" id="audit-from" class="bg-zinc-900 border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            <input type="date" id="audit-to"   class="bg-zinc-900 border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            <input type="text" id="audit-action" placeholder="Action type…"
                class="flex-1 min-w-[120px] bg-zinc-900 border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            <button id="btn-audit-filter"
                class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] text-sm transition-colors">
                Filter
            </button>
        </div>
        <div id="audit-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="audit-list" class="hidden space-y-2"></div>
        <div id="audit-pagination" class="flex gap-2 justify-center mt-4 hidden"></div>
    </section>

    <!-- ── Inquiries Section ──────────────────────────────────────────── -->
    <section id="section-inquiries" class="hidden space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-yellow-400">Contact Inquiries</h1>
            <div class="flex gap-2">
                <select id="inquiry-status-filter"
                    class="bg-zinc-900 border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                    <option value="">All</option>
                    <option value="new">New</option>
                    <option value="read">Read</option>
                    <option value="replied">Replied</option>
                </select>
            </div>
        </div>
        <div id="inquiries-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <div id="inquiries-list" class="hidden space-y-3"></div>
    </section>

    <!-- ── Settings Section ───────────────────────────────────────────── -->
    <section id="section-settings" class="hidden space-y-4">
        <h1 class="text-xl font-bold text-yellow-400">System Settings</h1>
        <div id="settings-loading" class="text-zinc-400 text-sm py-8 text-center">Loading…</div>
        <form id="settings-form" class="hidden bg-zinc-900 border border-zinc-700 rounded-xl p-5 space-y-4">
            <div>
                <label class="block text-xs text-zinc-400 mb-1">Maintenance Mode</label>
                <div class="flex items-center gap-3">
                    <button type="button" id="maintenance-toggle"
                        class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-400 bg-zinc-600"
                        role="switch" aria-checked="false">
                        <span id="maintenance-knob" class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform translate-x-1"></span>
                    </button>
                    <span id="maintenance-label" class="text-sm text-zinc-300">Off</span>
                </div>
                <input type="hidden" id="maintenance_mode" name="maintenance_mode" value="false">
            </div>
            <div>
                <label for="max_ai_requests" class="block text-xs text-zinc-400 mb-1">Max AI Requests Per Day</label>
                <input type="number" id="max_ai_requests" name="max_ai_requests_per_day" min="1" max="1000"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div>
                <label for="session_timeout" class="block text-xs text-zinc-400 mb-1">Session Timeout (seconds)</label>
                <input type="number" id="session_timeout" name="session_timeout" min="300"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div>
                <label for="password_min_length" class="block text-xs text-zinc-400 mb-1">Minimum Password Length</label>
                <input type="number" id="password_min_length" name="password_min_length" min="6" max="32"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <button type="submit"
                class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">
                Save Settings
            </button>
        </form>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 mt-4">
            <h2 class="font-semibold text-white mb-3">System Status</h2>
            <div id="system-status" class="space-y-2 text-sm">
                <p class="text-zinc-400">Checking…</p>
            </div>
        </div>
    </section>

</main>
</div><!-- end lg:pl-64 -->


<!-- ── Modals ─────────────────────────────────────────────────────────────── -->

<!-- Create/Edit User Modal -->
<div id="user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 overflow-y-auto py-8">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-md w-full space-y-4">
        <h2 id="user-modal-title" class="text-white font-bold text-lg">Create Account</h2>
        <p id="user-modal-subtitle" class="text-zinc-400 text-xs">A username and temporary password will be auto-generated. Share them with the user after creation.</p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-zinc-400 mb-1">First Name *</label>
                <input type="text" id="um-first-name" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div>
                <label class="block text-xs text-zinc-400 mb-1">Last Name *</label>
                <input type="text" id="um-last-name" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Email</label>
            <input type="email" id="um-email" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Phone</label>
            <input type="tel" id="um-phone" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Role *</label>
            <select id="um-role" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                <option value="member">Member</option>
                <option value="trainer">Trainer</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div id="um-trainer-row">
            <label class="block text-xs text-zinc-400 mb-1">Assign Trainer</label>
            <select id="um-trainer" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                <option value="">— None —</option>
            </select>
        </div>
        <!-- Change Password row — shown only when editing own account -->
        <div id="um-password-row" class="hidden border-t border-zinc-700 pt-4 space-y-2">
            <label class="block text-xs text-zinc-400 mb-1">New Password <span class="text-zinc-600">(leave blank to keep current)</span></label>
            <div class="relative">
                <input type="password" id="um-password"
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 pr-11 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400"
                    placeholder="Enter new password…" autocomplete="new-password">
                <button type="button" id="um-password-toggle"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white p-1 min-h-[44px] min-w-[44px] flex items-center justify-center transition-colors"
                    aria-label="Toggle password visibility">
                    <!-- Eye open -->
                    <svg id="um-eye-open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <!-- Eye closed -->
                    <svg id="um-eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>
        <p id="user-modal-error" class="text-red-400 text-xs hidden"></p>
        <div class="flex gap-3">
            <button id="user-modal-cancel" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] font-medium transition-colors">Cancel</button>
            <button id="user-modal-submit" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Create</button>
        </div>
    </div>
</div>

<!-- Credentials Display Modal -->
<div id="creds-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-sm w-full space-y-4">
        <div class="flex items-center gap-2">
            <span class="text-2xl">✅</span>
            <h2 class="text-white font-bold text-lg">Account Created</h2>
        </div>
        <p class="text-zinc-400 text-sm">Share these credentials with the new user. They <strong class="text-white">must change their password</strong> on first login.</p>
        <div class="bg-black rounded-lg p-4 space-y-3 border border-zinc-700">
            <div>
                <p class="text-xs text-zinc-500 mb-0.5">Username</p>
                <p id="creds-username" class="text-white font-mono text-base font-semibold"></p>
            </div>
            <div class="border-t border-zinc-800 pt-3">
                <p class="text-xs text-zinc-500 mb-0.5">Temporary Password</p>
                <p id="creds-password" class="text-yellow-400 font-mono text-base font-semibold break-all"></p>
            </div>
        </div>
        <p class="text-xs text-zinc-500">⚠️ This password will not be shown again. Copy it now.</p>
        <button id="creds-copy" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm font-medium transition-colors">📋 Copy Credentials</button>
        <button id="creds-close" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Done</button>
    </div>
</div>

<!-- Exercise Modal -->
<div id="exercise-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-md w-full space-y-4">
        <h2 id="exercise-modal-title" class="text-white font-bold text-lg">Add Exercise</h2>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Name *</label>
            <input type="text" id="em-name" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-zinc-400 mb-1">Category</label>
                <input type="text" id="em-category" placeholder="e.g. Strength" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div>
                <label class="block text-xs text-zinc-400 mb-1">Muscle Group</label>
                <input type="text" id="em-muscle" placeholder="e.g. Chest" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Difficulty</label>
            <select id="em-difficulty" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Description</label>
            <textarea id="em-description" rows="2" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
        </div>
        <input type="hidden" id="em-id" value="">
        <div class="flex gap-3">
            <button id="exercise-modal-cancel" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] font-medium transition-colors">Cancel</button>
            <button id="exercise-modal-submit" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Save</button>
        </div>
    </div>
</div>

<!-- Announcement Modal -->
<div id="announcement-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-md w-full space-y-4">
        <h2 id="ann-modal-title" class="text-white font-bold text-lg">New Announcement</h2>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Title *</label>
            <input type="text" id="ann-title" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Content *</label>
            <textarea id="ann-content" rows="3" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
        </div>
        <div>
            <label class="block text-xs text-zinc-400 mb-1">Audience</label>
            <select id="ann-audience" class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
                <option value="all">Everyone</option>
                <option value="members">Members Only</option>
                <option value="trainers">Trainers Only</option>
                <option value="admins">Admins Only</option>
            </select>
        </div>
        <input type="hidden" id="ann-id" value="">
        <div class="flex gap-3">
            <button id="ann-modal-cancel" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] font-medium transition-colors">Cancel</button>
            <button id="ann-modal-submit" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Save</button>
        </div>
    </div>
</div>

<!-- Confirm Dialog -->
<div id="confirm-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6 max-w-sm w-full space-y-4">
        <p id="confirm-message" class="text-white font-semibold"></p>
        <p id="confirm-detail" class="text-zinc-400 text-sm"></p>
        <div class="flex gap-3">
            <button id="confirm-cancel" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] font-medium transition-colors">Cancel</button>
            <button id="confirm-ok" class="flex-1 bg-red-700 hover:bg-red-600 text-white font-bold rounded-lg px-4 py-3 min-h-[44px] transition-colors">Confirm</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0"
    role="status" aria-live="polite"></div>

<script>
const FITSENSE_CSRF       = <?= json_encode($csrfToken) ?>;
const FITSENSE_CURRENT_ID = <?= json_encode((int) $_SESSION['user_id']) ?>;

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

// Close sidebar on nav item click (mobile)
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if (window.innerWidth < 1024) closeSidebar();
    });
});
</script>
<script src="js/admin-dashboard.js"></script>
<script src="js/theme.js"></script>
</body>
</html>
