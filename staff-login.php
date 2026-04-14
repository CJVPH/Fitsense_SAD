<?php
/**
 * FitSense — Staff Login Page (Trainers & Administrators)
 * Requirements: 3.7
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Redirect already-authenticated staff to their dashboard
if ($auth->isSessionValid() && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'trainer') {
        header('Location: trainer-dashboard.php');
        exit;
    }
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-dashboard.php');
        exit;
    }
}

$csrfToken = generateCsrfToken();
$flashHtml = displayFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Staff Login — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black min-h-screen flex items-center justify-center px-4 py-8" style="min-width:375px">

    <div class="w-full max-w-sm">

        <!-- Back + Logo row -->
        <div class="flex items-center mb-8">
            <a href="index.php" aria-label="Back to home"
                class="text-zinc-400 hover:text-yellow-400 transition-colors p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 -ml-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-1 flex items-center justify-center gap-2">
                <svg class="w-8 h-7 text-yellow-400 shrink-0" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true">
                    <path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/>
                </svg>
                <span class="text-yellow-400 font-bold text-4xl tracking-tight">FitSense</span>
            </div>
            <!-- spacer to keep logo centered -->
            <div class="w-9"></div>
        </div>

        <!-- Card -->
        <div class="bg-zinc-900 border border-zinc-700 rounded-2xl p-6 shadow-xl">

            <h1 class="text-white text-2xl font-bold mb-1 text-center">Staff Login</h1>
            <p class="text-zinc-400 text-sm text-center mb-6">For Trainers and Administrators</p>

            <!-- Flash message -->
            <?php if ($flashHtml): ?>
                <div class="mb-4"><?= $flashHtml ?></div>
            <?php endif; ?>

            <!-- Error message (populated by JS) -->
            <div id="error-msg" class="hidden border border-red-500 text-red-300 bg-red-950 rounded p-3 mb-4 text-sm" role="alert"></div>

            <form id="login-form" novalidate>
                <!-- CSRF -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">

                <!-- Username or Email -->
                <div class="mb-4">
                    <label for="username" class="block text-yellow-400 text-sm font-semibold mb-1">Username or Email</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        required
                        class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 text-base min-h-[44px] focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400"
                        placeholder="Enter your username or email"
                    >
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-yellow-400 text-sm font-semibold mb-1">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 pr-12 text-base min-h-[44px] focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400"
                            placeholder="Enter your password"
                        >
                        <button type="button" id="pw-toggle"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-yellow-400 transition-colors p-1 min-h-[44px] min-w-[44px] flex items-center justify-center"
                            aria-label="Toggle password visibility">
                            <svg id="pw-eye-open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="pw-eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                    Sign In
                </button>
            </form>

            <!-- Member login link -->
            <p class="mt-6 text-center text-sm text-zinc-400">
                <a href="login.php" class="text-yellow-400 underline hover:text-yellow-300">← Member Login</a>
            </p>

        </div>
    </div>

    <script>
    (function () {
        // Eye toggle
        const pwToggle  = document.getElementById('pw-toggle');
        const pwInput   = document.getElementById('password');
        const eyeOpen   = document.getElementById('pw-eye-open');
        const eyeClosed = document.getElementById('pw-eye-closed');
        if (pwToggle) {
            pwToggle.addEventListener('click', () => {
                const hidden = pwInput.type === 'password';
                pwInput.type = hidden ? 'text' : 'password';
                eyeOpen.classList.toggle('hidden', hidden);
                eyeClosed.classList.toggle('hidden', !hidden);
            });
        }

        const form     = document.getElementById('login-form');
        const btn      = document.getElementById('submit-btn');
        const errorBox = document.getElementById('error-msg');

        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.classList.remove('hidden');
        }

        function hideError() {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError();

            const username   = document.getElementById('username').value.trim();
            const password   = document.getElementById('password').value;
            const csrf_token = form.querySelector('[name="csrf_token"]').value;

            if (!username || !password) {
                showError('Please enter both username and password.');
                return;
            }

            // Loading state
            btn.disabled    = true;
            btn.textContent = 'Signing in…';

            try {
                const res = await fetch('api/auth.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ action: 'login_staff', username, password, csrf_token })
                });

                const data = await res.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Login failed. Please try again.');
                    btn.disabled    = false;
                    btn.textContent = 'Sign In';
                }
            } catch (err) {
                showError('A network error occurred. Please try again.');
                btn.disabled    = false;
                btn.textContent = 'Sign In';
            }
        });
    })();
    </script>

</body>
</html>
