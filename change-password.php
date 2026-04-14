<?php
/**
 * FitSense — First-Login Password Change Page
 * Requirements: 4.1–4.7
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Require a valid session; redirect to login if absent/expired
if (!isset($_SESSION['user_id']) || !$auth->isSessionValid()) {
    redirectWithMessage('login.php', 'Your session has expired. Please log in again.', 'info');
}

// Allow voluntary password change from profile settings (ref=settings)
// Only force-redirect away if NOT a forced change AND NOT coming from settings
$isForced   = !empty($_SESSION['needs_password_change']);
$isVoluntary = ($_GET['ref'] ?? '') === 'settings';
if (!$isForced && !$isVoluntary) {
    $role = $_SESSION['role'] ?? '';
    $dest = match ($role) {
        'trainer' => 'trainer-dashboard.php',
        'admin'   => 'admin-dashboard.php',
        default   => 'chat.php',
    };
    header('Location: ' . $dest);
    exit;
}

$csrfToken = generateCsrfToken();
$flashHtml = displayFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title><?= $isForced ? 'Secure Your Account' : 'Change Password' ?> — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black min-h-screen flex items-center justify-center px-4 py-8" style="min-width:375px">

    <div class="w-full max-w-sm">

        <!-- Wordmark -->
        <div class="text-center mb-8">
            <span class="text-yellow-400 font-bold text-4xl tracking-tight">FitSense</span>
        </div>

        <!-- Card -->
        <div class="bg-zinc-900 border border-zinc-700 rounded-2xl p-6 shadow-xl">

            <h1 class="text-white text-2xl font-bold mb-4 text-center"><?= $isForced ? 'Secure Your Account' : 'Change Password' ?></h1>

            <!-- Security notice (only for forced change) -->
            <?php if ($isForced): ?>
            <div class="border border-yellow-400 rounded-lg p-3 mb-5">
                <p class="text-yellow-300 text-sm leading-relaxed">
                    For your security, you must set a new password before continuing.
                    Your default password was created by an administrator — please choose something private.
                </p>
            </div>
            <?php endif; ?>

            <!-- Flash message -->
            <?php if ($flashHtml): ?>
                <div class="mb-4"><?= $flashHtml ?></div>
            <?php endif; ?>

            <!-- Error message (shown by JS) -->
            <div id="error-msg" class="hidden border border-red-500 text-red-300 bg-red-950 rounded p-3 mb-4 text-sm" role="alert"></div>

            <form id="change-password-form" novalidate>
                <!-- CSRF -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">

                <!-- New Password -->
                <div class="mb-4">
                    <label for="new_password" class="block text-yellow-400 text-sm font-semibold mb-1">New Password</label>
                    <div class="relative">
                        <input
                            id="new_password"
                            name="new_password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 pr-12 text-base min-h-[44px] focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400"
                            placeholder="Enter new password"
                        >
                        <button type="button" data-toggle="new_password"
                            class="pw-toggle absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-yellow-400 transition-colors p-1 min-h-[44px] min-w-[44px] flex items-center justify-center"
                            aria-label="Toggle password visibility">
                            <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Password strength indicator -->
                <div class="mb-4">
                    <p class="text-zinc-400 text-xs mb-2">Password strength</p>
                    <div class="flex gap-2" id="strength-bars" aria-label="Password strength indicator">
                        <div id="bar-1" class="h-2 flex-1 rounded bg-zinc-700 transition-colors duration-200"></div>
                        <div id="bar-2" class="h-2 flex-1 rounded bg-zinc-700 transition-colors duration-200"></div>
                        <div id="bar-3" class="h-2 flex-1 rounded bg-zinc-700 transition-colors duration-200"></div>
                    </div>
                    <p id="strength-label" class="text-xs mt-1 text-zinc-500 h-4"></p>
                </div>

                <!-- Criteria checklist -->
                <ul class="mb-5 space-y-1" id="criteria-list" aria-label="Password requirements">
                    <li id="crit-length"   class="flex items-center gap-2 text-sm text-zinc-400"><span class="crit-icon">✗</span> At least 8 characters</li>
                    <li id="crit-upper"    class="flex items-center gap-2 text-sm text-zinc-400"><span class="crit-icon">✗</span> One uppercase letter</li>
                    <li id="crit-lower"    class="flex items-center gap-2 text-sm text-zinc-400"><span class="crit-icon">✗</span> One lowercase letter</li>
                    <li id="crit-digit"    class="flex items-center gap-2 text-sm text-zinc-400"><span class="crit-icon">✗</span> One digit</li>
                    <li id="crit-special"  class="flex items-center gap-2 text-sm text-zinc-400"><span class="crit-icon">✗</span> One special character</li>
                </ul>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="confirm_password" class="block text-yellow-400 text-sm font-semibold mb-1">Confirm Password</label>
                    <div class="relative">
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 pr-12 text-base min-h-[44px] focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400"
                            placeholder="Re-enter new password"
                        >
                        <button type="button" data-toggle="confirm_password"
                            class="pw-toggle absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-yellow-400 transition-colors p-1 min-h-[44px] min-w-[44px] flex items-center justify-center"
                            aria-label="Toggle password visibility">
                            <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    id="submit-btn"
                    type="submit"
                    class="w-full bg-yellow-400 text-black font-semibold text-base rounded-lg px-4 py-3 min-h-[44px] hover:bg-yellow-300 active:bg-yellow-500 transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 focus:ring-offset-black"
                >
                    <?= $isForced ? 'Set New Password' : 'Update Password' ?>
                </button>
            </form>

        </div>
    </div>

    <script>
    (function () {
        // ── Eye toggles (works for any .pw-toggle button) ─────────────────────
        document.querySelectorAll('.pw-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const input     = document.getElementById(btn.dataset.toggle);
                const eyeOpen   = btn.querySelector('.eye-open');
                const eyeClosed = btn.querySelector('.eye-closed');
                const hidden    = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                eyeOpen.classList.toggle('hidden', hidden);
                eyeClosed.classList.toggle('hidden', !hidden);
            });
        });

        const newPwdInput  = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const form         = document.getElementById('change-password-form');
        const btn          = document.getElementById('submit-btn');
        const errorBox     = document.getElementById('error-msg');

        // ── Criteria definitions ──────────────────────────────────────────────
        const criteria = [
            { id: 'crit-length',  test: p => p.length >= 8 },
            { id: 'crit-upper',   test: p => /[A-Z]/.test(p) },
            { id: 'crit-lower',   test: p => /[a-z]/.test(p) },
            { id: 'crit-digit',   test: p => /[0-9]/.test(p) },
            { id: 'crit-special', test: p => /[!@#$%^&*()\-_+=\[\]{}|;:,.<>?]/.test(p) },
        ];

        // ── Strength bars ─────────────────────────────────────────────────────
        const bars  = [
            document.getElementById('bar-1'),
            document.getElementById('bar-2'),
            document.getElementById('bar-3'),
        ];
        const strengthLabel = document.getElementById('strength-label');

        function updateStrength(password) {
            const met = criteria.filter(c => c.test(password)).length;

            // Update criteria icons
            criteria.forEach(c => {
                const li   = document.getElementById(c.id);
                const icon = li.querySelector('.crit-icon');
                const pass = c.test(password);
                icon.textContent = pass ? '✓' : '✗';
                li.classList.toggle('text-green-400', pass);
                li.classList.toggle('text-zinc-400',  !pass);
            });

            // Determine strength level: 0=none, 1=weak, 2=fair, 3=strong
            let level = 0;
            if (password.length > 0) {
                if (met <= 2)      level = 1;
                else if (met <= 4) level = 2;
                else               level = 3;
            }

            const barColors = ['bg-zinc-700', 'bg-red-500', 'bg-orange-400', 'bg-green-500'];
            const labels    = ['', 'Weak', 'Fair', 'Strong'];
            const labelColors = ['text-zinc-500', 'text-red-400', 'text-orange-400', 'text-green-400'];

            bars.forEach((bar, i) => {
                // Reset
                bar.className = 'h-2 flex-1 rounded transition-colors duration-200';
                if (level > 0 && i < level) {
                    bar.classList.add(barColors[level]);
                } else {
                    bar.classList.add('bg-zinc-700');
                }
            });

            strengthLabel.textContent = labels[level];
            strengthLabel.className   = 'text-xs mt-1 h-4 ' + labelColors[level];
        }

        newPwdInput.addEventListener('input', function () {
            updateStrength(this.value);
        });

        // ── Error helpers ─────────────────────────────────────────────────────
        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.classList.remove('hidden');
        }

        function hideError() {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }

        // ── Form submit ───────────────────────────────────────────────────────
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError();

            const newPassword     = newPwdInput.value;
            const confirmPassword = confirmInput.value;
            const csrf_token      = form.querySelector('[name="csrf_token"]').value;

            if (!newPassword || !confirmPassword) {
                showError('Please fill in both password fields.');
                return;
            }

            if (newPassword !== confirmPassword) {
                showError('Passwords do not match. Please try again.');
                return;
            }

            // Loading state
            btn.disabled    = true;
            btn.textContent = 'Saving…';

            try {
                const res = await fetch('api/auth.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({
                        action:       'change_password',
                        new_password: newPassword,
                        csrf_token:   csrf_token,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Password change failed. Please try again.');
                    btn.disabled    = false;
                    btn.textContent = <?= json_encode($isForced ? 'Set New Password' : 'Update Password') ?>;
                }
            } catch (err) {
                showError('A network error occurred. Please try again.');
                btn.disabled    = false;
                btn.textContent = <?= json_encode($isForced ? 'Set New Password' : 'Update Password') ?>;
            }
        });

        // Auto-focus
        newPwdInput.focus();
    })();
    </script>

</body>
</html>
