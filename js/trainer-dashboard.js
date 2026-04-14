/**
 * FitSense — Trainer Dashboard JS
 */

// ─── State ────────────────────────────────────────────────────────────────────
let currentSection  = 'overview';
let currentMemberId = null;
let currentRecId    = null;
let weightChartInst = null;

// ─── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none '
        + (isError ? 'bg-red-700 text-white' : 'bg-green-700 text-white');
    t.style.opacity = '1';
    setTimeout(() => { t.style.opacity = '0'; }, 3000);
}

// ─── Section Switching ────────────────────────────────────────────────────────
const TRAINER_SECTIONS = ['overview','roster','member-detail','recommendations','messages','announcements','profile'];

// ─── Nav click handlers ───────────────────────────────────────────────────────
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const s = btn.dataset.section;
        if (!s) return;
        // Close sidebar on mobile
        if (window.innerWidth < 1024) {
            const sb = document.getElementById('sidebar');
            const ov = document.getElementById('sidebar-overlay');
            if (sb) sb.classList.add('-translate-x-full');
            if (ov) { ov.classList.add('opacity-0'); setTimeout(() => ov.classList.add('hidden'), 250); }
        }
        showSection(s);
        if (s === 'overview')        loadOverview();
        if (s === 'roster')          loadRoster();
        if (s === 'recommendations') loadPendingRecs();
        if (s === 'messages')        loadMessagesRoster();
        if (s === 'announcements')   loadAnnouncements();
        if (s === 'profile')         loadProfile();
    });
});

// ─── Overview ─────────────────────────────────────────────────────────────────
async function loadOverview() {
    try {
        const res  = await fetch('api/trainer.php?action=overview');
        const data = await res.json();
        if (!data.success) return;
        document.getElementById('stat-members').textContent = data.stats.total_members  ?? 0;
        document.getElementById('stat-pending').textContent = data.stats.pending_reviews ?? 0;
        document.getElementById('stat-unread').textContent  = data.stats.unread_messages ?? 0;
        document.getElementById('stat-active').textContent  = data.stats.active_members  ?? 0;
        const actEl = document.getElementById('overview-activity');
        if (!data.activity || !data.activity.length) {
            actEl.innerHTML = '<p class="text-center py-2 text-zinc-500 text-sm">No recent activity.</p>';
            return;
        }
        actEl.innerHTML = data.activity.map(a => `
            <div class="flex items-start gap-2 py-1.5 border-b border-zinc-800 last:border-0">
                <span class="text-yellow-400 mt-0.5 shrink-0">•</span>
                <div class="min-w-0">
                    <p class="text-sm text-zinc-300">${esc(a.action)}</p>
                    <p class="text-xs text-zinc-500">${relativeTime(a.created_at)}</p>
                </div>
            </div>`).join('');
    } catch(e) { console.error('Overview error', e); }
}

// ─── Announcements ────────────────────────────────────────────────────────────
async function loadAnnouncements() {
    const loading = document.getElementById('announcements-loading');
    const list    = document.getElementById('announcements-list');
    const empty   = document.getElementById('announcements-empty');
    loading.classList.remove('hidden');
    list.classList.add('hidden');
    empty.classList.add('hidden');
    try {
        const res  = await fetch('api/trainer.php?action=announcements');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success || !data.announcements.length) { empty.classList.remove('hidden'); return; }
        const colours = {
            maintenance: 'bg-orange-900/40 border-orange-700/50 text-orange-300',
            event:       'bg-blue-900/40 border-blue-700/50 text-blue-300',
            policy:      'bg-purple-900/40 border-purple-700/50 text-purple-300',
            general:     'bg-zinc-800 border-zinc-700 text-zinc-300',
        };
        list.innerHTML = data.announcements.map(a => {
            const c = colours[a.type] || colours.general;
            return `<div class="border rounded-xl p-4 ${c}">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <p class="font-semibold text-white text-sm">${esc(a.title)}</p>
                    ${a.type ? `<span class="text-xs px-2 py-0.5 rounded-full bg-black/30 shrink-0">${capitalize(a.type)}</span>` : ''}
                </div>
                <p class="text-sm">${esc(a.content)}</p>
                <p class="text-xs opacity-60 mt-2">${relativeTime(a.created_at)}</p>
            </div>`;
        }).join('');
        list.classList.remove('hidden');
    } catch(e) { loading.classList.add('hidden'); showToast('Failed to load announcements.', true); }
}

// ─── Profile ──────────────────────────────────────────────────────────────────
async function loadProfile() {
    try {
        const res  = await fetch('api/trainer.php?action=profile');
        const data = await res.json();
        if (!data.success) { showToast('Failed to load profile.', true); return; }
        const u = data.user;
        document.getElementById('pf-first-name').value = u.first_name || '';
        document.getElementById('pf-last-name').value  = u.last_name  || '';
        document.getElementById('pf-username').value   = u.username   || '';
        document.getElementById('pf-email').value      = u.email      || '';
        document.getElementById('pf-phone').value      = u.phone      || '';
        const preview = document.getElementById('avatar-preview');
        if (u.profile_photo) {
            preview.innerHTML = `<img src="${esc(u.profile_photo)}" alt="Profile" class="w-full h-full object-cover rounded-full">`;
        } else {
            preview.textContent = (u.first_name || 'T').charAt(0).toUpperCase();
        }
    } catch(e) { showToast('Failed to load profile.', true); }
}

document.getElementById('profile-photo-input').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { showToast('Image must be under 2 MB.', true); this.value = ''; return; }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatar-preview').innerHTML =
            `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(file);
});

document.getElementById('profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const newPw  = document.getElementById('pf-new-password').value;
    const confPw = document.getElementById('pf-confirm-password').value;
    if (newPw && newPw !== confPw) { showToast('Passwords do not match.', true); return; }
    const btn = document.getElementById('profile-save-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    const fd = new FormData();
    fd.append('action',     'update_profile');
    fd.append('csrf_token', FITSENSE_CSRF);
    fd.append('first_name', document.getElementById('pf-first-name').value.trim());
    fd.append('last_name',  document.getElementById('pf-last-name').value.trim());
    fd.append('username',   document.getElementById('pf-username').value.trim());
    fd.append('email',      document.getElementById('pf-email').value.trim());
    fd.append('phone',      document.getElementById('pf-phone').value.trim());
    if (newPw) fd.append('new_password', newPw);
    const photoFile = document.getElementById('profile-photo-input').files[0];
    if (photoFile) fd.append('profile_photo', photoFile);
    try {
        const res  = await fetch('api/trainer.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Profile updated.');
            document.getElementById('pf-new-password').value     = '';
            document.getElementById('pf-confirm-password').value = '';
            document.getElementById('profile-photo-input').value = '';
            // Update sidebar avatar if photo changed
            if (data.profile_photo) {
                const sidebarAvatar = document.getElementById('sidebar-avatar-img');
                if (sidebarAvatar) {
                    const img = document.createElement('img');
                    img.src = data.profile_photo;
                    img.alt = 'Profile';
                    img.className = 'w-9 h-9 rounded-full object-cover shrink-0';
                    img.id = 'sidebar-avatar-img';
                    sidebarAvatar.replaceWith(img);
                }
            }
        } else { showToast(data.error || 'Failed to save.', true); }
    } catch { showToast('Network error.', true); }
    finally { btn.disabled = false; btn.textContent = 'Save Changes'; }
});

document.querySelectorAll('.eye-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.toggle);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        const icon = btn.querySelector('.eye-icon');
        icon.innerHTML = show
            ? `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`
            : `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    });
});

// ─── Roster ───────────────────────────────────────────────────────────────────
let activeRosterTab = 'my';

function switchRosterTab(tab) {
    activeRosterTab = tab;
    const myTab  = document.getElementById('tab-my-members');
    const avTab  = document.getElementById('tab-available');
    const myPane = document.getElementById('roster-tab-my');
    const avPane = document.getElementById('roster-tab-available');

    if (tab === 'my') {
        myTab.className  = 'flex-1 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] bg-yellow-400 text-black';
        avTab.className  = 'flex-1 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] text-zinc-400 hover:text-white';
        myPane.classList.remove('hidden');
        avPane.classList.add('hidden');
        loadMyMembers();
    } else {
        avTab.className  = 'flex-1 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] bg-yellow-400 text-black';
        myTab.className  = 'flex-1 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] text-zinc-400 hover:text-white';
        avPane.classList.remove('hidden');
        myPane.classList.add('hidden');
        loadAvailableMembers();
    }
}

async function loadRoster() {
    switchRosterTab(activeRosterTab);
}

async function loadMyMembers() {
    const loading = document.getElementById('roster-loading');
    const list    = document.getElementById('roster-list');
    const empty   = document.getElementById('roster-empty');
    loading.classList.remove('hidden'); list.classList.add('hidden'); empty.classList.add('hidden');
    try {
        const res  = await fetch('api/trainer.php?action=roster');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success || !data.members.length) { empty.classList.remove('hidden'); return; }
        list.innerHTML = data.members.map(m => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white">${esc(m.first_name)} ${esc(m.last_name)}</p>
                        <p class="text-xs text-zinc-400 mt-0.5">
                            ${m.fitness_level ? esc(capitalize(m.fitness_level)) : '—'}
                            ${m.goal_type ? ' · ' + esc(formatGoal(m.goal_type)) : ''}
                            ${m.current_weight_kg ? ' · ' + m.current_weight_kg + ' kg' : ''}
                        </p>
                        <p class="text-xs text-zinc-500 mt-0.5">Last login: ${m.last_login ? relativeTime(m.last_login) : 'Never'}</p>
                        <div class="flex gap-2 mt-1">
                            ${m.pending_recs > 0 ? `<span class="text-xs bg-yellow-700 text-yellow-100 px-2 py-0.5 rounded-full">${m.pending_recs} pending</span>` : ''}
                            ${m.unread_messages > 0 ? `<span class="text-xs bg-blue-700 text-blue-100 px-2 py-0.5 rounded-full">${m.unread_messages} unread</span>` : ''}
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 shrink-0">
                        <button onclick="openMemberDetail(${m.id})"
                            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-3 py-2 min-h-[44px] text-sm transition-colors">View</button>
                        <button onclick="unassignMember(${m.id}, '${esc(m.first_name)} ${esc(m.last_name)}')"
                            class="bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm transition-colors">Release</button>
                    </div>
                </div>
            </div>`).join('');
        list.classList.remove('hidden');
    } catch { loading.classList.add('hidden'); showToast('Failed to load roster.', true); }
}

async function loadAvailableMembers() {
    const loading = document.getElementById('available-loading');
    const list    = document.getElementById('available-list');
    const empty   = document.getElementById('available-empty');
    loading.classList.remove('hidden'); list.classList.add('hidden'); empty.classList.add('hidden');
    try {
        const res  = await fetch('api/trainer.php?action=available_members');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success || !data.members.length) { empty.classList.remove('hidden'); return; }
        list.innerHTML = data.members.map(m => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-white">${esc(m.first_name)} ${esc(m.last_name)}</p>
                    <p class="text-xs text-zinc-400 mt-0.5">
                        ${m.fitness_level ? esc(capitalize(m.fitness_level)) : '—'}
                        ${m.goal_type ? ' · ' + esc(formatGoal(m.goal_type)) : ''}
                        ${m.current_weight_kg ? ' · ' + m.current_weight_kg + ' kg' : ''}
                    </p>
                    <p class="text-xs text-zinc-500 mt-0.5">Last login: ${m.last_login ? relativeTime(m.last_login) : 'Never'}</p>
                </div>
                <button onclick="assignMember(${m.id})"
                    class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-3 py-2 min-h-[44px] text-sm transition-colors shrink-0">Assign</button>
            </div>`).join('');
        list.classList.remove('hidden');
    } catch { loading.classList.add('hidden'); showToast('Failed to load available members.', true); }
}

async function assignMember(memberId) {
    try {
        const res  = await fetch('api/trainer.php', { method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'assign_member', member_id: memberId, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Member added to your roster.'); loadAvailableMembers(); }
        else { showToast(data.error || 'Failed to assign.', true); }
    } catch { showToast('Network error.', true); }
}

async function unassignMember(memberId, name) {
    if (!confirm(`Release ${name} from your roster?`)) return;
    try {
        const res  = await fetch('api/trainer.php', { method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'unassign_member', member_id: memberId, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { showToast('Member released.'); loadMyMembers(); }
        else { showToast(data.error || 'Failed to unassign.', true); }
    } catch { showToast('Network error.', true); }
}

// ─── Member Detail ────────────────────────────────────────────────────────────
async function openMemberDetail(memberId) {
    currentMemberId = memberId;
    showSection('member-detail');
    const content = document.getElementById('member-detail-content');
    content.innerHTML = '<p class="text-zinc-400 text-sm py-8 text-center">Loading…</p>';
    try {
        const res  = await fetch(`api/trainer.php?action=member_detail&user_id=${memberId}`);
        const data = await res.json();
        if (!data.success) { content.innerHTML = '<p class="text-red-400 text-sm">Failed to load.</p>'; return; }
        const p = data.profile;
        const bmi = p.current_weight_kg && p.height_cm
            ? (p.current_weight_kg / Math.pow(p.height_cm / 100, 2)).toFixed(1) : null;
        content.innerHTML = `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 mb-4">
                <h2 class="text-lg font-bold text-white mb-1">${esc(p.first_name)} ${esc(p.last_name)}</h2>
                <div class="grid grid-cols-2 gap-2 text-sm text-zinc-300">
                    <span>Age: <strong>${p.age||'—'}</strong></span>
                    <span>Height: <strong>${p.height_cm ? p.height_cm+' cm':'—'}</strong></span>
                    <span>Weight: <strong>${p.current_weight_kg ? p.current_weight_kg+' kg':'—'}</strong></span>
                    <span>BMI: <strong>${bmi||'—'}</strong></span>
                    <span>Level: <strong>${p.fitness_level ? capitalize(p.fitness_level):'—'}</strong></span>
                    <span>Goal: <strong>${p.goal_type ? formatGoal(p.goal_type):'—'}</strong></span>
                </div>
                ${p.medical_conditions ? `<p class="text-xs text-zinc-400 mt-2">⚕️ ${esc(p.medical_conditions)}</p>` : ''}
            </div>
            ${data.weight_logs.length ? `<div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 mb-4">
                <h3 class="font-semibold text-yellow-400 mb-3">Weight Progress</h3>
                <canvas id="member-weight-chart" height="180"></canvas></div>` : ''}
            <div class="mb-4"><h3 class="font-semibold text-yellow-400 mb-3">AI Recommendations</h3>
                ${data.recommendations.length ? data.recommendations.map(r => renderRecCard(r)).join('') : '<p class="text-zinc-400 text-sm">No recommendations yet.</p>'}
            </div>
            <div class="mb-4"><h3 class="font-semibold text-yellow-400 mb-3">Recent Workouts</h3>
                ${data.workouts.length ? data.workouts.map(w => `
                    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-3 mb-2">
                        <p class="text-sm font-medium text-white">${esc(w.session_date)}</p>
                        <p class="text-xs text-zinc-400">${w.duration_minutes} min${w.calories_burned?' · '+w.calories_burned+' kcal':''}${w.rating?' · '+'★'.repeat(w.rating):''}</p>
                        ${w.notes ? `<p class="text-xs text-zinc-500 mt-1">${esc(w.notes)}</p>` : ''}
                    </div>`).join('') : '<p class="text-zinc-400 text-sm">No workouts logged yet.</p>'}
            </div>
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <h3 class="font-semibold text-yellow-400 mb-3">Send Message</h3>
                <button onclick="showSection('messages'); loadMessagesRoster(); setTimeout(()=>openChat(${memberId},'${esc(p.first_name)} ${esc(p.last_name)}','${(p.first_name.charAt(0)+p.last_name.charAt(0)).toUpperCase()}'),300);"
                    class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] text-sm transition-colors">Open Chat</button>
            </div>`;
        if (data.weight_logs.length) {
            if (weightChartInst) weightChartInst.destroy();
            weightChartInst = new Chart(document.getElementById('member-weight-chart').getContext('2d'), {
                type: 'line',
                data: { labels: data.weight_logs.map(d => d.log_date),
                    datasets: [{ label: 'Weight (kg)', data: data.weight_logs.map(d => parseFloat(d.weight_kg)),
                        borderColor: '#facc15', backgroundColor: 'rgba(250,204,21,0.1)', borderWidth: 2,
                        pointBackgroundColor: '#facc15', pointRadius: 4, tension: 0.3, fill: true }] },
                options: { responsive: true, plugins: { legend: { labels: { color: '#a1a1aa' } } },
                    scales: { x: { ticks: { color: '#a1a1aa' }, grid: { color: '#27272a' } },
                              y: { ticks: { color: '#a1a1aa', callback: v => v+' kg' }, grid: { color: '#27272a' } } } }
            });
        }
    } catch { content.innerHTML = '<p class="text-red-400 text-sm">Failed to load member data.</p>'; }
}

function renderRecCard(r) {
    const c = { approved:'bg-green-800 text-green-200', modified:'bg-blue-800 text-blue-200',
                rejected:'bg-red-800 text-red-200', pending:'bg-zinc-700 text-zinc-300' };
    return `<div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4 mb-2">
        <div class="flex items-start justify-between gap-2 mb-2">
            <p class="font-semibold text-white text-sm">${esc(r.title)}</p>
            <span class="text-xs px-2 py-0.5 rounded-full ${c[r.status]||c.pending} shrink-0">${capitalize(r.status)}</span>
        </div>
        ${r.status==='pending' ? `<button onclick="openReviewModal(${r.id},'${esc(r.title)}')"
            class="mt-2 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-3 py-2 min-h-[44px] text-sm transition-colors w-full">Review</button>` : ''}
        ${r.trainer_notes ? `<p class="text-xs text-zinc-400 mt-2">Notes: ${esc(r.trainer_notes)}</p>` : ''}
    </div>`;
}

document.getElementById('back-to-roster').addEventListener('click', () => { showSection('roster'); loadRoster(); });

// ─── Pending Recommendations ──────────────────────────────────────────────────
async function loadPendingRecs() {
    const loading = document.getElementById('recs-loading');
    const list    = document.getElementById('recs-list');
    const empty   = document.getElementById('recs-empty');
    loading.classList.remove('hidden'); list.classList.add('hidden'); empty.classList.add('hidden');
    try {
        const res  = await fetch('api/trainer.php?action=pending_recommendations');
        const data = await res.json();
        loading.classList.add('hidden');
        if (!data.success || !data.recommendations.length) { empty.classList.remove('hidden'); return; }
        list.innerHTML = data.recommendations.map(r => `
            <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-4">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div>
                        <p class="font-semibold text-white text-sm">${esc(r.title)}</p>
                        <p class="text-xs text-zinc-400">${esc(r.first_name)} ${esc(r.last_name)} · ${relativeTime(r.created_at)}</p>
                    </div>
                    <span class="text-xs bg-zinc-700 text-zinc-300 px-2 py-0.5 rounded-full shrink-0">${capitalize(r.type.replace('_',' '))}</span>
                </div>
                <button onclick="openReviewModal(${r.id},'${esc(r.title)}')"
                    class="mt-3 w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-3 py-2 min-h-[44px] text-sm transition-colors">Review</button>
            </div>`).join('');
        list.classList.remove('hidden');
    } catch { loading.classList.add('hidden'); showToast('Failed to load recommendations.', true); }
}

// ─── Review Modal ─────────────────────────────────────────────────────────────
function openReviewModal(recId, title) {
    currentRecId = recId;
    document.getElementById('review-modal-title').textContent = 'Review: ' + title;
    document.getElementById('trainer-notes').value = '';
    document.getElementById('review-modal').classList.remove('hidden');
}
document.getElementById('review-modal-close').addEventListener('click', () => {
    document.getElementById('review-modal').classList.add('hidden');
});
async function submitReview(status) {
    const notes = document.getElementById('trainer-notes').value.trim();
    if (status === 'rejected' && !notes) { showToast('Please add notes when rejecting.', true); return; }
    try {
        const res  = await fetch('api/trainer.php', { method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'review_recommendation', recommendation_id: currentRecId,
                status, trainer_notes: notes, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) {
            document.getElementById('review-modal').classList.add('hidden');
            showToast('Recommendation ' + status + '.');
            if (currentSection === 'recommendations') loadPendingRecs();
            if (currentSection === 'member-detail' && currentMemberId) openMemberDetail(currentMemberId);
        } else { showToast(data.error || 'Failed.', true); }
    } catch { showToast('Network error.', true); }
}
document.getElementById('btn-approve').addEventListener('click', () => submitReview('approved'));
document.getElementById('btn-modify').addEventListener('click',  () => submitReview('modified'));
document.getElementById('btn-reject').addEventListener('click',  () => submitReview('rejected'));

// ─── Messenger ────────────────────────────────────────────────────────────────
let activeChatMemberId   = null;
let activeChatMemberName = '';
let msgPollTimer         = null;
let allContacts          = []; // cache for search filtering

function showSection(name) {
    if (!TRAINER_SECTIONS.includes(name)) name = 'overview';
    currentSection = name;

    const isMessages = name === 'messages';
    const messenger  = document.getElementById('messenger-layout');
    const mainEl     = document.getElementById('main-content');

    // Toggle messenger vs normal main
    messenger.classList.toggle('hidden', !isMessages);
    mainEl.classList.toggle('hidden', isMessages);

    TRAINER_SECTIONS.forEach(s => {
        const el = document.getElementById('section-' + s);
        if (el) el.classList.toggle('hidden', s !== name);
    });
    document.querySelectorAll('.nav-btn').forEach(btn => {
        const active = btn.dataset.section === name;
        btn.classList.toggle('text-yellow-400',   active);
        btn.classList.toggle('bg-yellow-400/10',  active);
        btn.classList.toggle('text-zinc-400',    !active);
        btn.classList.toggle('hover:text-white', !active);
        btn.classList.toggle('hover:bg-zinc-800',!active);
    });
    history.replaceState(null, '', '#' + name);

    // Stop polling when leaving messages
    if (!isMessages && msgPollTimer) { clearInterval(msgPollTimer); msgPollTimer = null; }
}

async function loadMessagesRoster() {
    const contacts = document.getElementById('msg-contacts');
    contacts.innerHTML = '<p class="text-zinc-500 text-sm px-4 py-6 text-center">Loading…</p>';
    try {
        const res  = await fetch('api/trainer.php?action=roster');
        const data = await res.json();
        if (!data.success || !data.members.length) {
            contacts.innerHTML = '<p class="text-zinc-500 text-sm px-4 py-6 text-center">No members assigned.</p>';
            return;
        }
        allContacts = data.members;
        renderContacts(allContacts);
    } catch { contacts.innerHTML = '<p class="text-red-400 text-sm px-4 py-6 text-center">Failed to load.</p>'; }
}

function renderContacts(members) {
    const contacts = document.getElementById('msg-contacts');
    if (!members.length) {
        contacts.innerHTML = '<p class="text-zinc-500 text-sm px-4 py-6 text-center">No results.</p>';
        return;
    }
    contacts.innerHTML = members.map(m => {
        const initials = (m.first_name.charAt(0) + m.last_name.charAt(0)).toUpperCase();
        const unread   = parseInt(m.unread_messages) || 0;
        return `<button onclick="openChat(${m.id},'${esc(m.first_name)} ${esc(m.last_name)}','${initials}')"
            data-member-id="${m.id}"
            class="msg-contact w-full flex items-center gap-3 px-4 py-3 min-h-[64px] hover:bg-zinc-800 transition-colors text-left">
            <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-sm shrink-0">${initials}</div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">${esc(m.first_name)} ${esc(m.last_name)}</p>
                <p class="text-zinc-500 text-xs truncate">${unread > 0 ? unread + ' unread' : 'Tap to chat'}</p>
            </div>
            ${unread > 0 ? `<span class="bg-yellow-400 text-black text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shrink-0">${unread}</span>` : ''}
        </button>`;
    }).join('');
    if (activeChatMemberId) highlightContact(activeChatMemberId);
}

// Search filter
document.getElementById('msg-search').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (!q) { renderContacts(allContacts); return; }
    renderContacts(allContacts.filter(m =>
        (m.first_name + ' ' + m.last_name).toLowerCase().includes(q)
    ));
});

function highlightContact(memberId) {
    document.querySelectorAll('.msg-contact').forEach(btn => {
        const active = parseInt(btn.dataset.memberId) === memberId;
        btn.classList.toggle('bg-zinc-800', active);
        btn.classList.toggle('border-l-2',  active);
        btn.classList.toggle('border-yellow-400', active);
    });
}

async function openChat(memberId, name, initials) {
    activeChatMemberId   = memberId;
    activeChatMemberName = name;

    // On mobile: hide thread list, show chat pane
    const threadList = document.getElementById('msg-thread-list');
    const chatPane   = document.getElementById('msg-chat-pane');
    const emptyPane  = document.getElementById('msg-empty-pane');
    if (window.innerWidth < 1024) {
        threadList.classList.add('hidden');
    }
    chatPane.classList.remove('hidden');
    emptyPane.classList.add('hidden');

    // Update header
    document.getElementById('msg-chat-name').textContent = name;
    document.getElementById('msg-chat-avatar').textContent = initials;

    highlightContact(memberId);

    // Mark read
    fetch('api/trainer.php', { method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_read', user_id: memberId, csrf_token: FITSENSE_CSRF }) });

    await fetchMessages(memberId, true);

    // Poll every 5s
    if (msgPollTimer) clearInterval(msgPollTimer);
    msgPollTimer = setInterval(() => fetchMessages(memberId, false), 5000);
}

async function fetchMessages(memberId, scrollToBottom) {
    try {
        const res  = await fetch(`api/trainer.php?action=messages&user_id=${memberId}`);
        const data = await res.json();
        if (!data.success) return;
        renderMessages(data.messages, scrollToBottom);
    } catch { /* silent */ }
}

function renderMessages(messages, scrollToBottom) {
    const body = document.getElementById('msg-chat-body');
    if (!messages.length) {
        body.innerHTML = '<p class="text-zinc-600 text-sm text-center py-8">No messages yet. Say hello!</p>';
        return;
    }

    let html = '';
    let lastDate = '';
    messages.forEach(m => {
        const isTrainer = m.sender_type === 'trainer';
        const msgDate   = new Date(m.created_at).toLocaleDateString();
        if (msgDate !== lastDate) {
            lastDate = msgDate;
            html += `<div class="flex justify-center my-3">
                <span class="text-xs text-zinc-600 bg-zinc-900 px-3 py-1 rounded-full">${msgDate}</span>
            </div>`;
        }
        const time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (isTrainer) {
            html += `<div class="flex justify-end">
                <div class="max-w-[75%]">
                    <div class="bg-yellow-400 text-black rounded-2xl rounded-br-sm px-4 py-2.5 text-sm">${esc(m.message)}</div>
                    <p class="text-xs text-zinc-600 text-right mt-1 pr-1">${time}</p>
                </div>
            </div>`;
        } else {
            html += `<div class="flex justify-start gap-2">
                <div class="w-7 h-7 rounded-full bg-zinc-700 flex items-center justify-center text-white text-xs font-bold shrink-0 mt-1">M</div>
                <div class="max-w-[75%]">
                    <div class="bg-zinc-800 text-white rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm">${esc(m.message)}</div>
                    <p class="text-xs text-zinc-600 mt-1 pl-1">${time}</p>
                </div>
            </div>`;
        }
    });
    body.innerHTML = html;
    if (scrollToBottom) body.scrollTop = body.scrollHeight;
}

async function sendChatMessage() {
    const input = document.getElementById('msg-chat-input');
    const msg   = input.value.trim();
    if (!msg || !activeChatMemberId) return;
    input.value = '';
    input.style.height = '';
    try {
        const res  = await fetch('api/trainer.php', { method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send_message', user_id: activeChatMemberId, message: msg, csrf_token: FITSENSE_CSRF }) });
        const data = await res.json();
        if (data.success) { await fetchMessages(activeChatMemberId, true); loadMessagesRoster(); }
        else { showToast(data.error || 'Failed to send.', true); input.value = msg; }
    } catch { showToast('Network error.', true); input.value = msg; }
}

// Send on Enter (Shift+Enter = newline)
document.getElementById('msg-chat-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); }
});
document.getElementById('msg-send-btn').addEventListener('click', sendChatMessage);

// Back button (mobile)
document.getElementById('msg-back-btn').addEventListener('click', () => {
    document.getElementById('msg-thread-list').classList.remove('hidden');
    document.getElementById('msg-chat-pane').classList.add('hidden');
    document.getElementById('msg-empty-pane').classList.remove('hidden');
    activeChatMemberId = null;
    if (msgPollTimer) { clearInterval(msgPollTimer); msgPollTimer = null; }
    loadMessagesRoster();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''; }
function formatGoal(g)   { return g ? g.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase()) : ''; }
function relativeTime(ts) {
    if (!ts) return 'Never';
    const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return Math.floor(diff/60) + ' min ago';
    if (diff < 86400)  return Math.floor(diff/3600) + ' hr ago';
    if (diff < 604800) return Math.floor(diff/86400) + ' days ago';
    return new Date(ts).toLocaleDateString();
}

// ─── Init ─────────────────────────────────────────────────────────────────────
(function init() {
    const hash = window.location.hash.replace('#', '');
    const start = TRAINER_SECTIONS.includes(hash) ? hash : 'overview';
    showSection(start);
    if (start === 'overview')        loadOverview();
    else if (start === 'roster')     loadRoster();
    else if (start === 'recommendations') loadPendingRecs();
    else if (start === 'messages')   loadMessagesRoster();
    else if (start === 'announcements') loadAnnouncements();
    else if (start === 'profile')    loadProfile();
    else loadOverview();
})();
