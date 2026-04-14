/**
 * FitSense — Admin Dashboard JS
 * Requirements: 13.1–13.9, 14.1–14.4, 15.1–15.3, 16.1–16.4, 17.1–17.4
 */

// ─── Helpers ──────────────────────────────────────────────────────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''; }
function formatRole(r) { return capitalize(r || ''); }
function relativeTime(ts) {
    if (!ts) return 'Never';
    const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400)  return Math.floor(diff / 3600) + ' hr ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    return new Date(ts).toLocaleDateString();
}

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none '
        + (isError ? 'bg-red-700 text-white' : 'bg-green-700 text-white');
    toast.style.opacity = '1';
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0'; }, 300);
    }, 3000);
}

// ─── Confirm Dialog ───────────────────────────────────────────────────────────
function showConfirm(message, detail, onConfirm) {
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-detail').textContent  = detail;
    document.getElementById('confirm-dialog').classList.remove('hidden');
    const ok     = document.getElementById('confirm-ok');
    const cancel = document.getElementById('confirm-cancel');
    const cleanup = () => { document.getElementById('confirm-dialog').classList.add('hidden'); ok.onclick = null; cancel.onclick = null; };
    ok.onclick     = () => { cleanup(); onConfirm(); };
    cancel.onclick = () => cleanup();
}

// ─── Section Switching ────────────────────────────────────────────────────────
const SECTIONS = ['overview', 'users', 'exercises', 'announcements', 'analytics', 'audit', 'settings', 'inquiries'];
let overviewCharts = [];

function showSection(name) {
    if (!SECTIONS.includes(name)) name = 'overview';
    SECTIONS.forEach(s => {
        const el = document.getElementById('section-' + s);
        if (el) el.classList.toggle('hidden', s !== name);
    });
    document.querySelectorAll('.nav-btn').forEach(btn => {
        const active = btn.dataset.section === name;
        btn.classList.toggle('text-yellow-400',    active);
        btn.classList.toggle('bg-yellow-400/10',   active);
        btn.classList.toggle('font-semibold',      active);
        btn.classList.toggle('text-zinc-400',      !active);
        btn.classList.toggle('hover:text-white',   !active);
        btn.classList.toggle('hover:bg-zinc-800',  !active);
        btn.classList.remove('border-b-2', 'border-yellow-400');
    });
    // Update URL hash without triggering a scroll
    history.replaceState(null, '', '#' + name);
    if (name === 'overview')      loadOverview();
    if (name === 'users')         loadUsers();
    if (name === 'exercises')     loadExercises();
    if (name === 'announcements') loadAnnouncements();
    if (name === 'analytics')     loadAnalytics();
    if (name === 'audit')         loadAuditLog();
    if (name === 'settings')      loadSettings();
    if (name === 'inquiries')     loadInquiries();
}

document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => showSection(btn.dataset.section));
});

// ─── Overview ─────────────────────────────────────────────────────────────────
async function loadOverview() {
    // Date stamp
    const dateEl = document.getElementById('overview-date');
    if (dateEl) dateEl.textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Destroy old charts
    overviewCharts.forEach(c => c.destroy());
    overviewCharts = [];

    try {
        const [analyticsRes, auditRes, statusRes] = await Promise.all([
            fetch('api/admin.php?action=analytics'),
            fetch('api/admin.php?action=audit_log&page=1&page_size=5'),
            fetch('api/admin.php?action=system_status'),
        ]);
        const [analytics, audit, status] = await Promise.all([
            analyticsRes.json(), auditRes.json(), statusRes.json()
        ]);

        // ── Stat cards ──────────────────────────────────────────────────────
        const r = analytics.users_by_role || {};
        const totalUsers = (r.member || 0) + (r.trainer || 0) + (r.admin || 0);
        const chatToday  = (analytics.chat_per_day || []).slice(-1)[0]?.count || 0;
        const recsToday  = (analytics.recs_per_day || []).slice(-1)[0]?.count || 0;
        const avgRating  = analytics.avg_rating || '—';

        document.getElementById('overview-stats').innerHTML = `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">Total Users</p>
                <p class="text-3xl font-bold text-white">${totalUsers}</p>
                <p class="text-xs text-zinc-500 mt-1">${r.member||0} members · ${r.trainer||0} trainers</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">Chats Today</p>
                <p class="text-3xl font-bold text-yellow-400">${chatToday}</p>
                <p class="text-xs text-zinc-500 mt-1">AI messages sent</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">AI Recs Today</p>
                <p class="text-3xl font-bold text-purple-400">${recsToday}</p>
                <p class="text-xs text-zinc-500 mt-1">Pending trainer review</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <p class="text-xs text-zinc-400 mb-1">Avg Workout Rating</p>
                <p class="text-3xl font-bold text-green-400">${avgRating}</p>
                <p class="text-xs text-zinc-500 mt-1">out of 5</p>
            </div>
        `;

        // ── Charts ──────────────────────────────────────────────────────────
        const chartDefaults = {
            type: 'bar',
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#71717a', font: { size: 10 } }, grid: { color: '#27272a' } },
                    y: { ticks: { color: '#71717a', stepSize: 1, font: { size: 10 } }, grid: { color: '#27272a' }, beginAtZero: true },
                },
            },
        };

        const chatCtx = document.getElementById('overview-chat-chart')?.getContext('2d');
        if (chatCtx) {
            overviewCharts.push(new Chart(chatCtx, {
                ...chartDefaults,
                data: {
                    labels:   (analytics.chat_per_day || []).map(d => d.day),
                    datasets: [{ data: (analytics.chat_per_day || []).map(d => d.count), backgroundColor: '#facc15', borderRadius: 4 }],
                },
            }));
        }

        const recCtx = document.getElementById('overview-recs-chart')?.getContext('2d');
        if (recCtx) {
            overviewCharts.push(new Chart(recCtx, {
                ...chartDefaults,
                data: {
                    labels:   (analytics.recs_per_day || []).map(d => d.day),
                    datasets: [{ data: (analytics.recs_per_day || []).map(d => d.count), backgroundColor: '#a78bfa', borderRadius: 4 }],
                },
            }));
        }

        // ── System status ────────────────────────────────────────────────────
        const statusEl = document.getElementById('overview-status');
        if (statusEl && status.success) {
            const dot = ok => `<span class="inline-block w-2 h-2 rounded-full mr-2 ${ok ? 'bg-green-400' : 'bg-red-500'}"></span>`;
            statusEl.innerHTML = `
                <div class="flex items-center">${dot(status.db)}Database <span class="ml-auto text-xs ${status.db ? 'text-green-400' : 'text-red-400'}">${status.db ? 'Connected' : 'Error'}</span></div>
                <div class="flex items-center">${dot(status.claude_api)}Claude API <span class="ml-auto text-xs ${status.claude_api ? 'text-green-400' : 'text-yellow-400'}">${status.claude_api ? 'Available' : 'Unavailable'}</span></div>
                <div class="flex items-center">${dot(status.maintenance !== 'true')}Maintenance Mode <span class="ml-auto text-xs ${status.maintenance === 'true' ? 'text-yellow-400' : 'text-zinc-400'}">${status.maintenance === 'true' ? 'ON' : 'Off'}</span></div>
            `;
        }

        // ── Recent activity ──────────────────────────────────────────────────
        const actEl = document.getElementById('overview-activity');
        if (actEl) {
            const logs = audit.logs || [];
            if (logs.length === 0) {
                actEl.innerHTML = '<p class="text-zinc-500 text-xs">No recent activity.</p>';
            } else {
                actEl.innerHTML = logs.map(l => `
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 inline-block w-1.5 h-1.5 rounded-full bg-yellow-400 shrink-0"></span>
                        <div class="min-w-0">
                            <span class="text-white text-xs font-medium">${esc(l.action)}</span>
                            ${l.username ? `<span class="text-zinc-500 text-xs"> · ${esc(l.username)}</span>` : ''}
                            <span class="block text-zinc-600 text-xs">${relativeTime(l.created_at)}</span>
                        </div>
                    </div>
                `).join('');
            }
        }

    } catch (e) {
        document.getElementById('overview-stats').innerHTML = '<p class="col-span-4 text-zinc-500 text-sm text-center py-4">Failed to load overview data.</p>';
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// USERS
// ═══════════════════════════════════════════════════════════════════════════════
let usersPage = 1;
let editingUserId = null;

async function loadUsers(page = 1) {
    usersPage = page;
    const search = document.getElementById('user-search').value;
    const role   = document.getElementById('user-role-filter').value;
    const loading = document.getElementById('users-loading');
    const table   = document.getElementById('users-table');
    const pager   = document.getElementById('users-pagination');

    loading.classList.remove('hidden');
    table.classList.add('hidden');
    pager.classList.add('hidden');

    try {
        const params = new URLSearchParams({ action: 'users', page, search, role });
        const res  = await fetch('api/admin.php?' + params);
        const data = await res.json();

        loading.classList.add('hidden');
        if (!data.success) { showToast('Failed to load users.', true); return; }

        table.innerHTML = data.users.map(u => {
            const isSelf = u.id == FITSENSE_CURRENT_ID;
            const needsPwChange = (u.needs_password_change == 1 || u.needs_password_change === true);
            const isInactive = u.status === 'inactive';
            const pwBadge = needsPwChange
                ? '<span class="text-xs bg-orange-800 text-orange-200 px-2 py-0.5 rounded-full">Needs PW change</span>'
                : '';


            // Status-aware action buttons
            let actionBtns = '';
            if (!isSelf) {
                if (u.status === 'suspended') {
                    actionBtns += `<button onclick="activateUser(${u.id}, '${esc(u.first_name)} ${esc(u.last_name)}')"
                        class="bg-green-800 hover:bg-green-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Unsuspend</button>`;
                } else if (u.status === 'inactive') {
                    actionBtns += `<button onclick="activateUser(${u.id}, '${esc(u.first_name)} ${esc(u.last_name)}')"
                        class="bg-green-800 hover:bg-green-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Activate</button>`;
                } else {
                    // active
                    actionBtns += `<button onclick="suspendUser(${u.id}, '${esc(u.first_name)} ${esc(u.last_name)}')"
                        class="bg-orange-800 hover:bg-orange-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Suspend</button>`;
                    actionBtns += `<button onclick="deactivateUser(${u.id}, '${esc(u.first_name)} ${esc(u.last_name)}')"
                        class="bg-zinc-600 hover:bg-zinc-500 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Deactivate</button>`;
                }
                actionBtns += `<button onclick="deleteUser(${u.id}, '${esc(u.first_name)} ${esc(u.last_name)}')"
                    class="bg-red-800 hover:bg-red-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Delete</button>`;
            }

            const selfBadge = isSelf
                ? '<span class="text-xs bg-zinc-600 text-zinc-300 px-2 py-0.5 rounded-full">You</span>'
                : '';
            return `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 flex items-start justify-between gap-3 ${isInactive ? 'opacity-60' : ''}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-white">${esc(u.first_name)} ${esc(u.last_name)}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full ${roleBadge(u.role)}">${formatRole(u.role)}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full ${statusBadge(u.status)}">${capitalize(u.status)}</span>
                        ${pwBadge}${selfBadge}
                    </div>
                    <p class="text-xs text-zinc-400 mt-0.5">@${esc(u.username)} ${u.email ? '· ' + esc(u.email) : ''}</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Last login: ${relativeTime(u.last_login)}</p>
                    ${u.trainer_name ? `<p class="text-xs text-zinc-500">Trainer: ${esc(u.trainer_name)}</p>` : ''}
                </div>
                <div class="flex flex-col gap-1 shrink-0">
                    <button onclick="openEditUser(${JSON.stringify(u).replace(/"/g,'&quot;')})"
                        class="bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Edit</button>
                    ${actionBtns}
                </div>
            </div>`;
        }).join('') || '<p class="text-zinc-400 text-sm text-center py-4">No users found.</p>';

        table.classList.remove('hidden');

        // Pagination
        const totalPages = Math.ceil(data.total / data.page_size);
        if (totalPages > 1) {
            pager.innerHTML = '';
            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = 'px-3 py-2 min-h-[44px] min-w-[44px] rounded-lg text-sm font-medium transition-colors '
                    + (p === page ? 'bg-yellow-400 text-black' : 'bg-zinc-700 text-white hover:bg-zinc-600');
                btn.addEventListener('click', () => loadUsers(p));
                pager.appendChild(btn);
            }
            pager.classList.remove('hidden');
        }
    } catch { loading.classList.add('hidden'); showToast('Network error.', true); }
}

function roleBadge(r) {
    return { member: 'bg-blue-800 text-blue-200', trainer: 'bg-purple-800 text-purple-200', admin: 'bg-yellow-700 text-yellow-100' }[r] || 'bg-zinc-700 text-zinc-300';
}
function statusBadge(s) {
    return { active: 'bg-green-800 text-green-200', suspended: 'bg-orange-800 text-orange-200', inactive: 'bg-zinc-700 text-zinc-300' }[s] || 'bg-zinc-700 text-zinc-300';
}

// Debounced search
let searchTimer;
document.getElementById('user-search').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadUsers(1), 400); });
document.getElementById('user-role-filter').addEventListener('change', () => loadUsers(1));

// ─── User Modal ───────────────────────────────────────────────────────────────
let trainersCache = [];

async function loadTrainers() {
    if (trainersCache.length) return;
    const res  = await fetch('api/admin.php?action=trainers');
    const data = await res.json();
    if (data.success) {
        trainersCache = data.trainers;
        const sel = document.getElementById('um-trainer');
        data.trainers.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.first_name + ' ' + t.last_name;
            sel.appendChild(opt);
        });
    }
}

function openCreateUser() {
    editingUserId = null;
    document.getElementById('user-modal-title').textContent = 'Create Account';
    document.getElementById('user-modal-subtitle').classList.remove('hidden');
    document.getElementById('user-modal-submit').textContent = 'Create';
    ['um-first-name','um-last-name','um-email','um-phone'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('um-role').value = 'member';
    document.getElementById('um-trainer').value = '';
    document.getElementById('um-trainer-row').classList.remove('hidden');
    document.getElementById('user-modal-error').classList.add('hidden');
    document.getElementById('user-modal').classList.remove('hidden');
    loadTrainers();
}

function openEditUser(u) {
    editingUserId = u.id;
    document.getElementById('user-modal-title').textContent = 'Edit User';
    document.getElementById('user-modal-subtitle').classList.add('hidden');
    document.getElementById('user-modal-submit').textContent = 'Save';
    document.getElementById('um-first-name').value = u.first_name || '';
    document.getElementById('um-last-name').value  = u.last_name  || '';
    document.getElementById('um-email').value      = u.email      || '';
    document.getElementById('um-phone').value      = u.phone      || '';
    document.getElementById('um-role').value       = u.role       || 'member';
    document.getElementById('um-trainer-row').classList.toggle('hidden', u.role !== 'member');
    document.getElementById('user-modal-error').classList.add('hidden');
    // Show password row only when editing own account
    const isSelf = u.id == FITSENSE_CURRENT_ID;
    document.getElementById('um-password-row').classList.toggle('hidden', !isSelf);
    document.getElementById('um-password').value = '';
    document.getElementById('user-modal').classList.remove('hidden');
    loadTrainers().then(() => {
        if (u.assigned_trainer_id) document.getElementById('um-trainer').value = u.assigned_trainer_id;
    });
}

// Eye toggle for password field
document.getElementById('um-password-toggle').addEventListener('click', () => {
    const input   = document.getElementById('um-password');
    const eyeOpen = document.getElementById('um-eye-open');
    const eyeClosed = document.getElementById('um-eye-closed');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    eyeOpen.classList.toggle('hidden', isHidden);
    eyeClosed.classList.toggle('hidden', !isHidden);
});

document.getElementById('um-role').addEventListener('change', function() {
    document.getElementById('um-trainer-row').classList.toggle('hidden', this.value !== 'member');
});

document.getElementById('btn-create-user').addEventListener('click', openCreateUser);
document.getElementById('user-modal-cancel').addEventListener('click', () => document.getElementById('user-modal').classList.add('hidden'));

document.getElementById('user-modal-submit').addEventListener('click', async () => {
    const errEl = document.getElementById('user-modal-error');
    errEl.classList.add('hidden');

    const firstName = document.getElementById('um-first-name').value.trim();
    const lastName  = document.getElementById('um-last-name').value.trim();
    const email     = document.getElementById('um-email').value.trim();
    const phone     = document.getElementById('um-phone').value.trim();

    // Client-side validation
    if (!firstName) { errEl.textContent = 'First name is required.'; errEl.classList.remove('hidden'); return; }
    if (!lastName)  { errEl.textContent = 'Last name is required.';  errEl.classList.remove('hidden'); return; }
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errEl.textContent = 'Please enter a valid email address (must include @).';
        errEl.classList.remove('hidden');
        return;
    }

    const payload = {
        action:               editingUserId ? 'update_user' : 'create_user',
        first_name:           firstName,
        last_name:            lastName,
        email:                email,
        phone:                phone,
        role:                 document.getElementById('um-role').value,
        assigned_trainer_id:  document.getElementById('um-trainer').value || null,
        csrf_token:           FITSENSE_CSRF,
    };
    if (editingUserId) payload.user_id = editingUserId;
    // Include new password if editing self and field is filled
    const newPw = document.getElementById('um-password').value;
    if (editingUserId && editingUserId == FITSENSE_CURRENT_ID && newPw) {
        payload.new_password = newPw;
    }

    try {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) {
            document.getElementById('user-modal').classList.add('hidden');
            if (!editingUserId && data.username) {
                document.getElementById('creds-username').textContent = data.username;
                document.getElementById('creds-password').textContent = data.password;
                document.getElementById('creds-modal').classList.remove('hidden');
            } else {
                showToast('User updated.');
            }
            loadUsers(usersPage);
        } else {
            errEl.textContent = (data.errors || [data.error || 'Failed.']).join(' ');
            errEl.classList.remove('hidden');
        }
    } catch { errEl.textContent = 'Network error.'; errEl.classList.remove('hidden'); }
});

// Credentials modal
document.getElementById('creds-copy').addEventListener('click', () => {
    const u = document.getElementById('creds-username').textContent;
    const p = document.getElementById('creds-password').textContent;
    navigator.clipboard.writeText('Username: ' + u + '\nPassword: ' + p).then(() => showToast('Copied!'));
});
document.getElementById('creds-close').addEventListener('click', () => document.getElementById('creds-modal').classList.add('hidden'));

async function suspendUser(userId, name) {
    showConfirm('Suspend ' + name + '?', 'This will prevent them from logging in. You can reactivate them later.', async () => {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'suspend_user', user_id: userId, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Account suspended.'); loadUsers(usersPage); }
        else showToast(data.error || 'Failed.', true);
    });
}

async function deleteUser(userId, name) {
    showConfirm('Permanently delete ' + name + '?', 'This cannot be undone. All their data will be removed from the database.', async () => {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_user', user_id: userId, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Account permanently deleted.'); loadUsers(usersPage); }
        else showToast(data.error || 'Failed.', true);
    });
}

async function deactivateUser(userId, name) {
    showConfirm('Deactivate ' + name + '?', 'Their account will be disabled. You can reactivate it later.', async () => {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'deactivate_user', user_id: userId, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Account deactivated.'); loadUsers(usersPage); }
        else showToast(data.error || 'Failed.', true);
    });
}

async function activateUser(userId, name) {
    showConfirm('Activate ' + name + '?', 'Their account will be restored to active status.', async () => {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'activate_user', user_id: userId, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Account activated.'); loadUsers(usersPage); }
        else showToast(data.error || 'Failed.', true);
    });
}

async function viewUserCredentials(userId, username) {
    showConfirm(
        'Reset & View Credentials?',
        'This will generate a new temporary password for @' + username + ' and show it to you. The user will need to change it on next login.',
        async () => {
            try {
                const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'reset_temp_password', user_id: userId, csrf_token: FITSENSE_CSRF }) });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('creds-username').textContent = data.username;
                    document.getElementById('creds-password').textContent = data.password;
                    document.getElementById('creds-modal').classList.remove('hidden');
                } else {
                    showToast(data.error || 'Failed to reset credentials.', true);
                }
            } catch { showToast('Network error.', true); }
        }
    );
}

// ═══════════════════════════════════════════════════════════════════════════════
// EXERCISES
// ═══════════════════════════════════════════════════════════════════════════════
async function loadExercises() {
    const loading = document.getElementById('exercises-loading');
    const list    = document.getElementById('exercises-list');
    loading.classList.remove('hidden');
    list.classList.add('hidden');

    try {
        const res  = await fetch('api/admin.php?action=exercises');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success) { showToast('Failed to load exercises.', true); return; }

        list.innerHTML = data.exercises.map(e => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 flex items-start justify-between gap-3 ${!e.is_active ? 'opacity-50' : ''}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-white">${esc(e.name)}</p>
                        ${e.category ? `<span class="text-xs bg-zinc-700 text-zinc-300 px-2 py-0.5 rounded-full">${esc(e.category)}</span>` : ''}
                        ${!e.is_active ? '<span class="text-xs bg-zinc-700 text-zinc-400 px-2 py-0.5 rounded-full">Inactive</span>' : ''}
                    </div>
                    <p class="text-xs text-zinc-400 mt-0.5">${e.muscle_group ? esc(e.muscle_group) + ' · ' : ''}${capitalize(e.difficulty_level || '')}</p>
                    ${e.description ? `<p class="text-xs text-zinc-500 mt-1 line-clamp-1">${esc(e.description)}</p>` : ''}
                </div>
                <div class="flex flex-col gap-1 shrink-0">
                    <button onclick="openEditExercise(${JSON.stringify(e).replace(/"/g,'&quot;')})"
                        class="bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Edit</button>
                    ${e.is_active ? `<button onclick="deactivateExercise(${e.id}, '${esc(e.name)}')"
                        class="bg-red-800 hover:bg-red-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Deactivate</button>` : ''}
                </div>
            </div>
        `).join('') || '<p class="text-zinc-400 text-sm text-center py-4">No exercises found.</p>';

        list.classList.remove('hidden');
    } catch { loading.classList.add('hidden'); showToast('Network error.', true); }
}

function openCreateExercise() {
    document.getElementById('exercise-modal-title').textContent = 'Add Exercise';
    ['em-name','em-category','em-muscle','em-description'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('em-difficulty').value = 'beginner';
    document.getElementById('em-id').value = '';
    document.getElementById('exercise-modal').classList.remove('hidden');
}

function openEditExercise(e) {
    document.getElementById('exercise-modal-title').textContent = 'Edit Exercise';
    document.getElementById('em-name').value        = e.name        || '';
    document.getElementById('em-category').value    = e.category    || '';
    document.getElementById('em-muscle').value      = e.muscle_group || '';
    document.getElementById('em-description').value = e.description || '';
    document.getElementById('em-difficulty').value  = e.difficulty_level || 'beginner';
    document.getElementById('em-id').value          = e.id;
    document.getElementById('exercise-modal').classList.remove('hidden');
}

document.getElementById('btn-create-exercise').addEventListener('click', openCreateExercise);
document.getElementById('exercise-modal-cancel').addEventListener('click', () => document.getElementById('exercise-modal').classList.add('hidden'));

document.getElementById('exercise-modal-submit').addEventListener('click', async () => {
    const id = document.getElementById('em-id').value;
    const payload = {
        action:           id ? 'update_exercise' : 'create_exercise',
        name:             document.getElementById('em-name').value.trim(),
        category:         document.getElementById('em-category').value.trim(),
        muscle_group:     document.getElementById('em-muscle').value.trim(),
        description:      document.getElementById('em-description').value.trim(),
        difficulty_level: document.getElementById('em-difficulty').value,
        csrf_token:       FITSENSE_CSRF,
    };
    if (id) payload.id = parseInt(id);

    try {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) { document.getElementById('exercise-modal').classList.add('hidden'); showToast(id ? 'Exercise updated.' : 'Exercise added.'); loadExercises(); }
        else showToast((data.errors || [data.error || 'Failed.']).join(' '), true);
    } catch { showToast('Network error.', true); }
});

async function deactivateExercise(id, name) {
    showConfirm('Deactivate "' + name + '"?', 'It will no longer appear in AI prompts or member-facing lists.', async () => {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'deactivate_exercise', id, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Exercise deactivated.'); loadExercises(); }
        else showToast(data.error || 'Failed.', true);
    });
}

// ═══════════════════════════════════════════════════════════════════════════════
// ANNOUNCEMENTS
// ═══════════════════════════════════════════════════════════════════════════════
async function loadAnnouncements() {
    const loading = document.getElementById('announcements-loading');
    const list    = document.getElementById('announcements-list');
    loading.classList.remove('hidden');
    list.classList.add('hidden');

    try {
        const res  = await fetch('api/admin.php?action=announcements');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success) { showToast('Failed to load announcements.', true); return; }

        list.innerHTML = data.announcements.map(a => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 ${!a.is_active ? 'opacity-50' : ''}">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div>
                        <p class="font-semibold text-white">${esc(a.title)}</p>
                        <p class="text-xs text-zinc-400">${capitalize(a.target_audience)} · ${relativeTime(a.created_at)}</p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button onclick="openEditAnnouncement(${JSON.stringify(a).replace(/"/g,'&quot;')})"
                            class="bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Edit</button>
                        ${a.is_active ? `<button onclick="deactivateAnnouncement(${a.id})"
                            class="bg-red-800 hover:bg-red-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Deactivate</button>` : ''}
                    </div>
                </div>
                <p class="text-sm text-zinc-300 mt-1">${esc(a.content)}</p>
            </div>
        `).join('') || '<p class="text-zinc-400 text-sm text-center py-4">No announcements.</p>';

        list.classList.remove('hidden');
    } catch { loading.classList.add('hidden'); showToast('Network error.', true); }
}

function openCreateAnnouncement() {
    document.getElementById('ann-modal-title').textContent = 'New Announcement';
    document.getElementById('ann-title').value   = '';
    document.getElementById('ann-content').value = '';
    document.getElementById('ann-audience').value = 'all';
    document.getElementById('ann-id').value = '';
    document.getElementById('announcement-modal').classList.remove('hidden');
}

function openEditAnnouncement(a) {
    document.getElementById('ann-modal-title').textContent = 'Edit Announcement';
    document.getElementById('ann-title').value    = a.title    || '';
    document.getElementById('ann-content').value  = a.content  || '';
    document.getElementById('ann-audience').value = a.target_audience || 'all';
    document.getElementById('ann-id').value       = a.id;
    document.getElementById('announcement-modal').classList.remove('hidden');
}

document.getElementById('btn-create-announcement').addEventListener('click', openCreateAnnouncement);
document.getElementById('ann-modal-cancel').addEventListener('click', () => document.getElementById('announcement-modal').classList.add('hidden'));

document.getElementById('ann-modal-submit').addEventListener('click', async () => {
    const id = document.getElementById('ann-id').value;
    const payload = {
        action:          id ? 'update_announcement' : 'create_announcement',
        title:           document.getElementById('ann-title').value.trim(),
        content:         document.getElementById('ann-content').value.trim(),
        target_audience: document.getElementById('ann-audience').value,
        csrf_token:      FITSENSE_CSRF,
    };
    if (id) payload.id = parseInt(id);

    try {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) { document.getElementById('announcement-modal').classList.add('hidden'); showToast('Announcement saved.'); loadAnnouncements(); }
        else showToast((data.errors || [data.error || 'Failed.']).join(' '), true);
    } catch { showToast('Network error.', true); }
});

async function deactivateAnnouncement(id) {
    showConfirm('Deactivate this announcement?', 'It will no longer be shown to users.', async () => {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'update_announcement', id, is_active: false, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Announcement deactivated.'); loadAnnouncements(); }
        else showToast(data.error || 'Failed.', true);
    });
}

// ═══════════════════════════════════════════════════════════════════════════════
// ANALYTICS
// ═══════════════════════════════════════════════════════════════════════════════
let analyticsCharts = [];

async function loadAnalytics() {
    const loading = document.getElementById('analytics-loading');
    const content = document.getElementById('analytics-content');
    loading.classList.remove('hidden');
    content.classList.add('hidden');
    analyticsCharts.forEach(c => c.destroy());
    analyticsCharts = [];

    try {
        const res  = await fetch('api/admin.php?action=analytics');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success) { showToast('Failed to load analytics.', true); return; }

        const r = data.users_by_role || {};
        content.innerHTML = `
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                ${statCard('Members', r.member || 0, 'bg-blue-900')}
                ${statCard('Trainers', r.trainer || 0, 'bg-purple-900')}
                ${statCard('Admins', r.admin || 0, 'bg-yellow-900')}
                ${statCard('Avg Rating', data.avg_rating || '—', 'bg-zinc-800')}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                    <h3 class="font-semibold text-yellow-400 mb-3">Chat Messages (7 days)</h3>
                    <canvas id="chat-chart" height="180"></canvas>
                </div>
                <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                    <h3 class="font-semibold text-yellow-400 mb-3">AI Recommendations (7 days)</h3>
                    <canvas id="recs-chart" height="180"></canvas>
                </div>
            </div>
        `;
        content.classList.remove('hidden');

        const chartOpts = (label, colour) => ({
            type: 'bar',
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#a1a1aa' } } },
                scales: {
                    x: { ticks: { color: '#a1a1aa' }, grid: { color: '#27272a' } },
                    y: { ticks: { color: '#a1a1aa', stepSize: 1 }, grid: { color: '#27272a' }, beginAtZero: true },
                },
            },
        });

        const chatCtx = document.getElementById('chat-chart').getContext('2d');
        analyticsCharts.push(new Chart(chatCtx, {
            ...chartOpts('Messages', '#facc15'),
            data: {
                labels:   data.chat_per_day.map(d => d.day),
                datasets: [{ label: 'Messages', data: data.chat_per_day.map(d => d.count), backgroundColor: '#facc15' }],
            },
        }));

        const recCtx = document.getElementById('recs-chart').getContext('2d');
        analyticsCharts.push(new Chart(recCtx, {
            ...chartOpts('Recommendations', '#a78bfa'),
            data: {
                labels:   data.recs_per_day.map(d => d.day),
                datasets: [{ label: 'Recommendations', data: data.recs_per_day.map(d => d.count), backgroundColor: '#a78bfa' }],
            },
        }));

    } catch { loading.classList.add('hidden'); showToast('Network error.', true); }
}

function statCard(label, value, bg) {
    return `<div class="${bg} border border-zinc-700 rounded-xl p-4"><p class="text-xs text-zinc-400 mb-1">${label}</p><p class="text-2xl font-bold text-white">${value}</p></div>`;
}

// ═══════════════════════════════════════════════════════════════════════════════
// AUDIT LOG
// ═══════════════════════════════════════════════════════════════════════════════
let auditPage = 1;

async function loadAuditLog(page = 1) {
    auditPage = page;
    const loading = document.getElementById('audit-loading');
    const list    = document.getElementById('audit-list');
    const pager   = document.getElementById('audit-pagination');
    loading.classList.remove('hidden');
    list.classList.add('hidden');
    pager.classList.add('hidden');

    const params = new URLSearchParams({
        action:        'audit_log',
        page,
        date_from:     document.getElementById('audit-from').value,
        date_to:       document.getElementById('audit-to').value,
        filter_action: document.getElementById('audit-action').value,
    });

    try {
        const res  = await fetch('api/admin.php?' + params);
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success) { showToast('Failed to load audit log.', true); return; }

        list.innerHTML = data.logs.map(l => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs bg-zinc-700 text-zinc-300 px-2 py-0.5 rounded-full">${esc(l.action)}</span>
                        ${l.username ? `<span class="text-xs text-zinc-400 ml-1">by ${esc(l.first_name)} ${esc(l.last_name)} (@${esc(l.username)})</span>` : ''}
                    </div>
                    <span class="text-xs text-zinc-500 shrink-0">${relativeTime(l.created_at)}</span>
                </div>
                ${l.table_name ? `<p class="text-xs text-zinc-500 mt-1">Table: ${esc(l.table_name)}${l.record_id ? ' #' + l.record_id : ''}</p>` : ''}
                ${l.ip_address ? `<p class="text-xs text-zinc-600 mt-0.5">IP: ${esc(l.ip_address)}</p>` : ''}
            </div>
        `).join('') || '<p class="text-zinc-400 text-sm text-center py-4">No audit records found.</p>';

        list.classList.remove('hidden');

        const totalPages = Math.ceil(data.total / data.page_size);
        if (totalPages > 1) {
            pager.innerHTML = '';
            for (let p = 1; p <= Math.min(totalPages, 10); p++) {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = 'px-3 py-2 min-h-[44px] min-w-[44px] rounded-lg text-sm font-medium transition-colors '
                    + (p === page ? 'bg-yellow-400 text-black' : 'bg-zinc-700 text-white hover:bg-zinc-600');
                btn.addEventListener('click', () => loadAuditLog(p));
                pager.appendChild(btn);
            }
            pager.classList.remove('hidden');
        }
    } catch { loading.classList.add('hidden'); showToast('Network error.', true); }
}

document.getElementById('btn-audit-filter').addEventListener('click', () => loadAuditLog(1));

// ═══════════════════════════════════════════════════════════════════════════════
// SETTINGS
// ═══════════════════════════════════════════════════════════════════════════════
async function loadSettings() {
    const loading = document.getElementById('settings-loading');
    const form    = document.getElementById('settings-form');
    loading.classList.remove('hidden');
    form.classList.add('hidden');

    try {
        const [settingsRes, statusRes] = await Promise.all([
            fetch('api/admin.php?action=settings'),
            fetch('api/admin.php?action=system_status'),
        ]);
        const settings = await settingsRes.json();
        const status   = await statusRes.json();

        loading.classList.add('hidden');

        if (settings.success) {
            const s = settings.settings;
            document.getElementById('max_ai_requests').value    = s.max_ai_requests_per_day || 20;
            document.getElementById('session_timeout').value    = s.session_timeout          || 3600;
            document.getElementById('password_min_length').value = s.password_min_length     || 8;

            const isOn = s.maintenance_mode === 'true';
            setMaintenanceToggle(isOn);
            form.classList.remove('hidden');
        }

        if (status.success) {
            document.getElementById('system-status').innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full ${status.db ? 'bg-green-400' : 'bg-red-400'}"></span>
                    <span class="text-zinc-300">Database: ${status.db ? 'Connected' : 'Error'}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full ${status.claude_api ? 'bg-green-400' : 'bg-yellow-400'}"></span>
                    <span class="text-zinc-300">Claude API: ${status.claude_api ? 'Available' : 'Unavailable / Not configured'}</span>
                </div>
            `;
        }
    } catch { loading.classList.add('hidden'); showToast('Failed to load settings.', true); }
}

function setMaintenanceToggle(isOn) {
    const toggle = document.getElementById('maintenance-toggle');
    const knob   = document.getElementById('maintenance-knob');
    const label  = document.getElementById('maintenance-label');
    const input  = document.getElementById('maintenance_mode');
    toggle.setAttribute('aria-checked', isOn ? 'true' : 'false');
    toggle.classList.toggle('bg-yellow-400', isOn);
    toggle.classList.toggle('bg-zinc-600', !isOn);
    knob.classList.toggle('translate-x-8', isOn);
    knob.classList.toggle('translate-x-1', !isOn);
    label.textContent = isOn ? 'On — non-admins see maintenance page' : 'Off';
    input.value = isOn ? 'true' : 'false';
}

document.getElementById('maintenance-toggle').addEventListener('click', function() {
    const current = this.getAttribute('aria-checked') === 'true';
    setMaintenanceToggle(!current);
});

document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    const payload = {
        action:                  'save_settings',
        maintenance_mode:        document.getElementById('maintenance_mode').value,
        max_ai_requests_per_day: document.getElementById('max_ai_requests').value,
        session_timeout:         document.getElementById('session_timeout').value,
        password_min_length:     document.getElementById('password_min_length').value,
        csrf_token:              FITSENSE_CSRF,
    };

    try {
        const res  = await fetch('api/admin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) showToast('Settings saved.');
        else showToast(data.error || 'Failed.', true);
    } catch { showToast('Network error.', true); }
    finally { btn.disabled = false; btn.textContent = 'Save Settings'; }
});

// ═══════════════════════════════════════════════════════════════════════════════
// INQUIRIES
// ═══════════════════════════════════════════════════════════════════════════════
async function loadInquiries() {
    const loading = document.getElementById('inquiries-loading');
    const list    = document.getElementById('inquiries-list');
    const filterEl = document.getElementById('inquiry-status-filter');
    if (!loading || !list || !filterEl) return;
    const filter  = filterEl.value;
    loading.classList.remove('hidden');
    list.classList.add('hidden');

    try {
        const params = new URLSearchParams({ action: 'inquiries' });
        if (filter) params.set('status', filter);
        const res  = await fetch('api/admin.php?' + params);
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success) { showToast('Failed to load inquiries.', true); return; }

        // Update badge
        updateInquiriesBadge(data.unread);

        const statusColors = {
            new:     'bg-yellow-700 text-yellow-100',
            read:    'bg-zinc-600 text-zinc-200',
            replied: 'bg-green-800 text-green-200',
        };

        list.innerHTML = data.inquiries.map(inq => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 space-y-2">
                <div class="flex items-start justify-between gap-2 flex-wrap">
                    <div>
                        <p class="font-semibold text-white">${esc(inq.name)}
                            <span class="text-xs ${statusColors[inq.status] || 'bg-zinc-700 text-zinc-300'} px-2 py-0.5 rounded-full ml-1">${capitalize(inq.status)}</span>
                        </p>
                        <p class="text-xs text-zinc-400">${esc(inq.email)} · ${relativeTime(inq.created_at)}</p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        ${inq.status !== 'read' && inq.status !== 'replied' ? `<button onclick="updateInquiryStatus(${inq.id}, 'read')"
                            class="bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Mark Read</button>` : ''}
                        ${inq.status !== 'replied' ? `<button onclick="updateInquiryStatus(${inq.id}, 'replied')"
                            class="bg-green-800 hover:bg-green-700 text-white rounded-lg px-3 py-2 min-h-[44px] text-xs font-medium transition-colors">Replied</button>` : ''}
                    </div>
                </div>
                <p class="text-sm text-yellow-400 font-medium">${esc(inq.subject)}</p>
                <p class="text-sm text-zinc-300 whitespace-pre-wrap">${esc(inq.message)}</p>
            </div>
        `).join('') || '<p class="text-zinc-400 text-sm text-center py-4">No inquiries found.</p>';

        list.classList.remove('hidden');
    } catch { loading.classList.add('hidden'); showToast('Network error.', true); }
}

async function updateInquiryStatus(id, status) {
    try {
        const res  = await fetch('api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_inquiry', id, status, csrf_token: FITSENSE_CSRF }),
        });
        const data = await res.json();
        if (data.success) { showToast('Inquiry updated.'); loadInquiries(); }
        else showToast(data.error || 'Failed.', true);
    } catch { showToast('Network error.', true); }
}

function updateInquiriesBadge(count) {
    const badge = document.getElementById('inquiries-badge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// Load unread count on page init
async function loadInquiriesBadge() {
    try {
        const res  = await fetch('api/admin.php?action=inquiries');
        const data = await res.json();
        if (data.success) updateInquiriesBadge(data.unread);
    } catch {}
}

// ─── Init ─────────────────────────────────────────────────────────────────────
const initialSection = window.location.hash.replace('#', '') || 'overview';
showSection(initialSection);
loadInquiriesBadge();

document.getElementById('inquiry-status-filter')?.addEventListener('change', () => loadInquiries());
