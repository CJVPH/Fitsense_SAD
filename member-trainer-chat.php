<?php
/**
 * FitSense — Member ↔ Trainer Chat
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireRole('member');
// Redirect to one-page app
header('Location: chat.php#trainer');
exit;

$pdo         = Database::getConnection();
$currentUser = $auth->getCurrentUser();
$csrfToken   = generateCsrfToken();
$userId      = (int) $_SESSION['user_id'];

// Get assigned trainer
$trainerStmt = $pdo->prepare(
    'SELECT u.id, u.first_name, u.last_name, u.profile_photo
       FROM users u
       JOIN member_profiles mp ON mp.assigned_trainer_id = u.id
      WHERE mp.user_id = ? LIMIT 1'
);
$trainerStmt->execute([$userId]);
$trainer = $trainerStmt->fetch();

// Mark trainer messages as read
if ($trainer) {
    $pdo->prepare("UPDATE chat_messages SET is_read = TRUE WHERE user_id = ? AND sender_type = 'trainer' AND is_read = FALSE")
        ->execute([$userId]);
}

// Fetch conversation (no session_id — trainer messages don't use sessions)
$msgStmt = $pdo->prepare(
    "SELECT cm.*, u.first_name, u.last_name
       FROM chat_messages cm
       LEFT JOIN users u ON u.id = cm.sender_id AND cm.sender_type = 'trainer'
      WHERE cm.user_id = ? AND cm.sender_type IN ('member','trainer')
        AND cm.session_id IS NULL
      ORDER BY cm.created_at ASC
      LIMIT 200"
);
$msgStmt->execute([$userId]);
$messages = $msgStmt->fetchAll();

$firstName    = htmlspecialchars($currentUser['first_name']    ?? 'there', ENT_QUOTES|ENT_HTML5,'UTF-8');
$lastName     = htmlspecialchars($currentUser['last_name']     ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$username     = htmlspecialchars($currentUser['username']      ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$profilePhoto = htmlspecialchars($currentUser['profile_photo'] ?? '',       ENT_QUOTES|ENT_HTML5,'UTF-8');
$initials     = strtoupper(substr($firstName,0,1).substr($lastName,0,1));

$trainerName = $trainer ? htmlspecialchars($trainer['first_name'].' '.$trainer['last_name'], ENT_QUOTES|ENT_HTML5,'UTF-8') : null;
$trainerInitials = $trainer ? strtoupper(substr($trainer['first_name'],0,1).substr($trainer['last_name'],0,1)) : 'T';

$announcements = getActiveAnnouncements('member', $pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Trainer Chat — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        #sidebar { transition: transform 0.25s ease; }
        #sidebar-overlay { transition: opacity 0.25s ease; }
        #chat-messages::-webkit-scrollbar { width: 4px; }
        #chat-messages::-webkit-scrollbar-track { background: transparent; }
        #chat-messages::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
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

    <div class="flex-1 min-h-0"></div>

    <!-- Bottom nav -->
    <div class="shrink-0 border-t border-zinc-800 px-2 py-2 space-y-0.5">

        <a href="member-dashboard.php"
            class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-zinc-300 hover:text-white text-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Progress
        </a>

        <!-- Trainer Chat (active) -->
        <a href="member-trainer-chat.php"
            class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl bg-zinc-800 text-white text-sm font-medium">
            <svg class="w-5 h-5 shrink-0 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Trainer Chat
        </a>

        <div class="border-t border-zinc-800 my-1"></div>

        <!-- User menu -->
        <div class="relative">
            <button id="user-menu-btn"
                class="w-full flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-xl hover:bg-zinc-800 transition-colors text-left">
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
     MAIN CHAT AREA
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
        <span class="text-white font-semibold text-base truncate"><?= $trainerName ?? 'Trainer Chat' ?></span>
        <div class="w-11"></div>
    </header>

    <!-- Desktop trainer header -->
    <div class="hidden lg:flex items-center gap-3 px-6 py-4 border-b border-zinc-800 bg-zinc-900 shrink-0">
        <?php if ($trainer && !empty($trainer['profile_photo']) && file_exists($trainer['profile_photo'])): ?>
        <img src="<?= htmlspecialchars($trainer['profile_photo'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?>" alt="Trainer" class="w-9 h-9 rounded-full object-cover">
        <?php else: ?>
        <div class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm"><?= $trainerInitials ?></div>
        <?php endif; ?>
        <div>
            <p class="font-semibold text-white text-sm"><?= $trainerName ?? 'No trainer assigned' ?></p>
            <p class="text-xs text-zinc-500">Your trainer</p>
        </div>
    </div>

    <!-- Announcements -->
    <?php if (!empty($announcements)): ?>
    <div id="announcement-banner" class="shrink-0 mx-4 mt-3 flex items-start gap-3 bg-yellow-400/10 border border-yellow-400/40 text-yellow-300 px-4 py-3 rounded-xl text-xs" role="alert">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        <span class="flex-1"><strong class="font-semibold"><?= htmlspecialchars($announcements[0]['title'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?>:</strong> <?= htmlspecialchars($announcements[0]['content'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span>
        <button onclick="document.getElementById('announcement-banner').remove()" class="shrink-0 text-yellow-400 hover:text-yellow-200 p-1 min-h-[28px] min-w-[28px] flex items-center justify-center rounded-lg transition-colors" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- Messages -->
    <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 min-h-0" aria-live="polite">
        <?php if (!$trainer): ?>
        <div class="flex flex-col items-center justify-center h-full text-center gap-3">
            <div class="w-14 h-14 rounded-2xl bg-zinc-800 flex items-center justify-center">
                <svg class="w-7 h-7 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <p class="text-zinc-400 text-sm">You don't have a trainer assigned yet.</p>
        </div>
        <?php elseif (empty($messages)): ?>
        <div class="flex flex-col items-center justify-center h-full text-center gap-3">
            <div class="w-14 h-14 rounded-2xl bg-zinc-800 flex items-center justify-center">
                <svg class="w-7 h-7 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="text-zinc-400 text-sm">No messages yet. Say hi to your trainer!</p>
        </div>
        <?php else: ?>
        <?php
        $prevDate = null;
        foreach ($messages as $msg):
            $msgDate = date('Y-m-d', strtotime($msg['created_at']));
            if ($msgDate !== $prevDate):
                $prevDate = $msgDate;
                $label = $msgDate === date('Y-m-d') ? 'Today' : ($msgDate === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('M j, Y', strtotime($msgDate)));
        ?>
        <div class="flex items-center gap-3 my-2">
            <div class="flex-1 h-px bg-zinc-800"></div>
            <span class="text-xs text-zinc-600 shrink-0"><?= htmlspecialchars($label,ENT_QUOTES|ENT_HTML5,'UTF-8') ?></span>
            <div class="flex-1 h-px bg-zinc-800"></div>
        </div>
        <?php endif; ?>

        <?php if ($msg['sender_type'] === 'member'): ?>
        <div class="flex justify-end">
            <div class="max-w-[75%]">
                <div class="bg-yellow-400 text-black rounded-2xl rounded-br-sm px-3 py-2 text-sm break-words inline-block">
                    <?= htmlspecialchars($msg['message'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?>
                </div>
                <p class="text-xs text-zinc-600 mt-0.5 text-right"><?= date('g:i A', strtotime($msg['created_at'])) ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="flex items-end gap-2">
            <?php if (!empty($trainer['profile_photo']) && file_exists($trainer['profile_photo'])): ?>
            <img src="<?= htmlspecialchars($trainer['profile_photo'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?>" alt="Trainer" class="w-7 h-7 rounded-full object-cover shrink-0 mb-4">
            <?php else: ?>
            <div class="w-7 h-7 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs shrink-0 mb-4"><?= $trainerInitials ?></div>
            <?php endif; ?>
            <div class="max-w-[75%]">
                <div class="bg-zinc-800 text-white rounded-2xl rounded-bl-sm px-3 py-2 text-sm break-words whitespace-pre-wrap inline-block">
                    <?= htmlspecialchars($msg['message'],ENT_QUOTES|ENT_HTML5,'UTF-8') ?>
                </div>
                <p class="text-xs text-zinc-600 mt-0.5"><?= date('g:i A', strtotime($msg['created_at'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Input -->
    <?php if ($trainer): ?>
    <div class="shrink-0 px-4 pb-4 pt-2">
        <div class="flex items-end gap-2 bg-zinc-900 border border-zinc-700 rounded-2xl px-4 py-3 focus-within:border-yellow-400 transition-colors">
            <textarea id="message-input" rows="1" placeholder="Message your trainer…"
                class="flex-1 bg-transparent text-white text-sm resize-none focus:outline-none placeholder-zinc-500 min-h-[24px] max-h-32 overflow-y-auto"
                aria-label="Message input"></textarea>
            <button id="send-btn" type="button"
                class="bg-yellow-400 hover:bg-yellow-300 disabled:opacity-40 disabled:cursor-not-allowed text-black rounded-xl p-2 min-h-[36px] min-w-[36px] flex items-center justify-center transition-colors shrink-0"
                aria-label="Send message" disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0" role="status" aria-live="polite"></div>

<script>
const FITSENSE_CSRF = <?= json_encode($csrfToken) ?>;
const TRAINER_ID    = <?= json_encode($trainer ? (int)$trainer['id'] : null) ?>;

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

// Scroll to bottom on load
const chatEl = document.getElementById('chat-messages');
if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;

// Send message
const input   = document.getElementById('message-input');
const sendBtn = document.getElementById('send-btn');

if (input && sendBtn) {
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 128) + 'px';
        sendBtn.disabled = this.value.trim() === '';
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    sendBtn.addEventListener('click', sendMessage);
}

function sendMessage() {
    if (!input) return;
    const text = input.value.trim();
    if (!text || !TRAINER_ID) return;

    // Optimistic render
    appendMessage(text, 'member');
    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
    chatEl.scrollTop = chatEl.scrollHeight;

    fetch('api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'send_member_message', message: text, csrf_token: FITSENSE_CSRF })
    })
    .then(r => r.json())
    .then(d => { if (!d.success) showToast(d.message || 'Failed to send.', true); })
    .catch(() => showToast('Connection error.', true));
}

function appendMessage(text, type) {
    const wrap = document.createElement('div');
    const time = new Date().toLocaleTimeString([], {hour:'numeric',minute:'2-digit'});
    if (type === 'member') {
        wrap.className = 'flex justify-end';
        wrap.innerHTML = `<div class="max-w-[75%]">
            <div class="bg-yellow-400 text-black rounded-2xl rounded-br-sm px-3 py-2 text-sm break-words inline-block">${esc(text)}</div>
            <p class="text-xs text-zinc-600 mt-0.5 text-right">${time}</p>
        </div>`;
    }
    chatEl.appendChild(wrap);
    chatEl.scrollTop = chatEl.scrollHeight;
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function showToast(msg, isError=false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none '
        + (isError ? 'bg-red-700 text-white' : 'bg-green-700 text-white');
    t.style.opacity = '1';
    setTimeout(() => { t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(()=>{t.style.transition='';},300); }, 3000);
}

// Poll for new messages every 5s
<?php if ($trainer): ?>
setInterval(() => {
    fetch('api/chat.php?action=trainer_messages')
        .then(r => r.json())
        .then(d => {
            if (d.success && d.new_count > 0) location.reload();
        })
        .catch(() => {});
}, 5000);
<?php endif; ?>
</script>
</body>
</html>
