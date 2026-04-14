<?php
/**
 * FitSense — Contact Us
 */
session_start();
$isLoggedIn = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$dashboardUrl = match($role) {
    'admin'   => 'admin-dashboard.php',
    'trainer' => 'trainer-dashboard.php',
    default   => 'chat.php',
};

$success = false;
$errors  = [];
$fields  = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

// Pre-fill name/email if logged in
if ($isLoggedIn) {
    $fields['name']  = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields['name']    = trim($_POST['name']    ?? '');
    $fields['email']   = trim($_POST['email']   ?? '');
    $fields['subject'] = trim($_POST['subject'] ?? '');
    $fields['message'] = trim($_POST['message'] ?? '');

    if (!$fields['name'])                          $errors[] = 'Name is required.';
    if (!$fields['email'])                         $errors[] = 'Email is required.';
    elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!$fields['subject'])                       $errors[] = 'Subject is required.';
    if (strlen($fields['message']) < 10)           $errors[] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        // Store inquiry in DB if available, otherwise just mark success
        try {
            require_once 'config/database.php';
            $pdo = Database::getConnection();

            // Check if contact_inquiries table exists, create if not
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                user_id INT DEFAULT NULL,
                status ENUM('new','read','replied') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare(
                'INSERT INTO contact_inquiries (name, email, subject, message, user_id) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $fields['name'],
                $fields['email'],
                $fields['subject'],
                $fields['message'],
                $isLoggedIn ? (int) $_SESSION['user_id'] : null,
            ]);

            // Email notification to admin
            $adminEmail = 'admin@fitsense.local'; // change to real admin email
            $mailSubject = '[FitSense] New Contact Inquiry: ' . $fields['subject'];
            $mailBody    = "New contact form submission:\n\n"
                         . "Name:    " . $fields['name']    . "\n"
                         . "Email:   " . $fields['email']   . "\n"
                         . "Subject: " . $fields['subject'] . "\n\n"
                         . "Message:\n" . $fields['message'] . "\n";
            $mailHeaders = "From: noreply@fitsense.local\r\nReply-To: " . $fields['email'] . "\r\n";
            @mail($adminEmail, $mailSubject, $mailBody, $mailHeaders);

        } catch (\Throwable $e) {
            // DB save failed — still show success to user, log the error
            error_log('Contact form DB error: ' . $e->getMessage());
        }

        $success = true;
        $fields  = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

function esc(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Contact Us — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #mobile-nav { transition: transform 0.3s ease; }
        #mobile-overlay { transition: opacity 0.3s ease; }
        .logo-dumbbell { transition: transform 0.3s ease; }
        a:hover .logo-dumbbell { transform: scale(1.12) rotate(-5deg); }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col" style="min-width:375px">

<!-- ── Mobile sidebar overlay ────────────────────────────────────────────── -->
<div id="mobile-overlay" class="fixed inset-0 z-40 bg-black/70 hidden opacity-0 lg:hidden" aria-hidden="true"></div>

<!-- ── Mobile sidebar ────────────────────────────────────────────────────── -->
<div id="mobile-nav" class="fixed top-0 right-0 h-full w-72 z-50 bg-zinc-900 border-l border-zinc-700 flex flex-col translate-x-full lg:hidden" role="dialog" aria-label="Navigation menu">
    <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
        <div class="flex items-center gap-2">
            <svg class="w-7 h-6 text-yellow-400" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
            <span class="text-yellow-400 font-bold">FitSense</span>
        </div>
        <button id="mobile-close" class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Close menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex flex-col px-4 py-4 gap-1 flex-1">
        <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">Home</a>
        <a href="about.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">About Us</a>
        <a href="faq.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">FAQs</a>
        <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-zinc-800 text-yellow-400 font-medium text-sm min-h-[44px]">Contact Us</a>
    </nav>
    <div class="px-4 pb-6">
        <?php if ($isLoggedIn): ?>
        <a href="<?= esc($dashboardUrl) ?>" class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] text-center text-sm transition-colors">My Dashboard</a>
        <?php else: ?>
        <a href="login.php" class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] text-center text-sm transition-colors">Login</a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Nav ───────────────────────────────────────────────────────────────── -->
<nav class="sticky top-0 z-30 bg-zinc-900/95 backdrop-blur border-b border-zinc-700 px-5 py-3 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2.5 group">
        <svg class="logo-dumbbell w-9 h-8 text-yellow-400 shrink-0" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
        <span class="text-yellow-400 font-bold text-xl tracking-tight">FitSense</span>
    </a>
    <div class="hidden lg:flex items-center gap-4">
        <a href="index.php" class="text-zinc-400 hover:text-white text-sm transition-colors">Home</a>
        <a href="about.php" class="text-zinc-400 hover:text-white text-sm transition-colors">About</a>
        <a href="faq.php" class="text-zinc-400 hover:text-white text-sm transition-colors">FAQs</a>
        <a href="contact.php" class="text-yellow-400 text-sm font-medium">Contact</a>
        <?php if ($isLoggedIn): ?>
        <a href="<?= esc($dashboardUrl) ?>" class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] flex items-center text-sm transition-colors">My Dashboard</a>
        <?php else: ?>
        <a href="login.php" class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-5 py-2 min-h-[44px] flex items-center text-sm transition-colors">Login</a>
        <?php endif; ?>
    </div>
    <button id="mobile-open" class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors text-zinc-400 hover:text-white" aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</nav>

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<section class="px-6 py-12 text-center max-w-2xl mx-auto w-full">
    <h1 class="text-4xl font-extrabold text-yellow-400 mb-3">Contact Us</h1>
    <p class="text-zinc-400 text-base">Have a question or want to know more about FitSense? We'd love to hear from you.</p>
</section>

<!-- ── Main content ───────────────────────────────────────────────────────── -->
<div class="px-6 pb-16 max-w-4xl mx-auto w-full grid grid-cols-1 sm:grid-cols-2 gap-8">

    <!-- Contact info -->
    <div class="space-y-5">
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5">
            <div class="text-2xl mb-2">📍</div>
            <h3 class="text-white font-bold mb-1">Location</h3>
            <p class="text-zinc-400 text-sm">Biofitness Gym</p>
            <p class="text-zinc-500 text-xs mt-1">Cainta, Rizal, Philippines</p>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5">
            <div class="text-2xl mb-2">🕐</div>
            <h3 class="text-white font-bold mb-1">Gym Hours</h3>
            <div class="text-zinc-400 text-sm space-y-0.5">
                <p>Monday – Saturday: 7:30 AM – 9:30 PM</p>
                <p>Sunday: 7:30 AM – 7:30 PM</p>
            </div>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5">
            <div class="text-2xl mb-2">💬</div>
            <h3 class="text-white font-bold mb-1">Support</h3>
            <p class="text-zinc-400 text-sm">For account issues or technical support, please use the form and we'll get back to you within 24 hours.</p>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5">
            <div class="text-2xl mb-2">🏋️</div>
            <h3 class="text-white font-bold mb-1">Membership Inquiries</h3>
            <p class="text-zinc-400 text-sm">Interested in joining FitSense? Accounts are created by gym staff. Visit us or send a message and we'll set you up.</p>
        </div>
    </div>

    <!-- Contact form -->
    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6">
        <?php if ($success): ?>
        <div class="flex flex-col items-center justify-center text-center py-8 space-y-3">
            <span class="text-5xl">✅</span>
            <h2 class="text-white font-bold text-xl">Message Sent!</h2>
            <p class="text-zinc-400 text-sm">Thanks for reaching out. We'll get back to you within 24 hours.</p>
            <a href="contact.php" class="mt-4 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-5 py-2 min-h-[44px] flex items-center text-sm transition-colors">Send Another Message</a>
        </div>
        <?php else: ?>
        <h2 class="text-white font-bold text-lg mb-5">Send a Message</h2>
        <?php if (!empty($errors)): ?>
        <div class="bg-red-900/40 border border-red-700 rounded-lg px-4 py-3 mb-4 space-y-1">
            <?php foreach ($errors as $e): ?><p class="text-red-300 text-xs"><?= esc($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="contact.php" class="space-y-4" novalidate>
            <div>
                <label for="name" class="block text-xs text-zinc-400 mb-1">Full Name *</label>
                <input type="text" id="name" name="name" value="<?= esc($fields['name']) ?>" required
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400 transition-colors" placeholder="Your name">
            </div>
            <div>
                <label for="email" class="block text-xs text-zinc-400 mb-1">Email Address *</label>
                <input type="email" id="email" name="email" value="<?= esc($fields['email']) ?>" required
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400 transition-colors" placeholder="you@example.com">
            </div>
            <div>
                <label for="subject" class="block text-xs text-zinc-400 mb-1">Subject *</label>
                <select id="subject" name="subject" required
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400 transition-colors">
                    <option value="" disabled <?= !$fields['subject'] ? 'selected' : '' ?>>Select a topic…</option>
                    <option value="Membership Inquiry" <?= $fields['subject']==='Membership Inquiry'?'selected':'' ?>>Membership Inquiry</option>
                    <option value="Technical Support"  <?= $fields['subject']==='Technical Support'?'selected':'' ?>>Technical Support</option>
                    <option value="Account Issue"      <?= $fields['subject']==='Account Issue'?'selected':'' ?>>Account Issue</option>
                    <option value="Trainer Feedback"   <?= $fields['subject']==='Trainer Feedback'?'selected':'' ?>>Trainer Feedback</option>
                    <option value="General Question"   <?= $fields['subject']==='General Question'?'selected':'' ?>>General Question</option>
                    <option value="Other"              <?= $fields['subject']==='Other'?'selected':'' ?>>Other</option>
                </select>
            </div>
            <div>
                <label for="message" class="block text-xs text-zinc-400 mb-1">Message *</label>
                <textarea id="message" name="message" rows="5" required
                    class="w-full bg-black border border-zinc-600 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-yellow-400 transition-colors resize-none"
                    placeholder="Tell us how we can help…"><?= esc($fields['message']) ?></textarea>
            </div>
            <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-3 min-h-[44px] text-sm transition-colors">Send Message</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- ── Footer ─────────────────────────────────────────────────────────────── -->
<footer class="bg-zinc-900 border-t border-zinc-700 px-6 py-8 mt-auto">
    <div class="max-w-3xl mx-auto grid grid-cols-2 sm:grid-cols-3 gap-6 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-7 h-6 text-yellow-400 shrink-0" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
                <p class="text-yellow-400 font-bold">FitSense</p>
            </div>
            <p class="text-zinc-500 text-xs">Your AI-powered fitness companion</p>
        </div>
        <div>
            <p class="text-white text-sm font-medium mb-2">Product</p>
            <div class="space-y-1">
                <a href="index.php#features" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Features</a>
                <a href="index.php#membership" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Membership</a>
                <a href="faq.php" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">FAQs</a>
                <a href="about.php" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">About Us</a>
                <a href="contact.php" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Contact</a>
            </div>
        </div>
        <div>
            <p class="text-white text-sm font-medium mb-2">Account</p>
            <div class="space-y-1">
                <?php if ($isLoggedIn): ?>
                <a href="<?= esc($dashboardUrl) ?>" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Dashboard</a>
                <a href="logout.php" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Logout</a>
                <?php else: ?>
                <a href="login.php" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="border-t border-zinc-800 pt-5 text-center space-y-1">
        <p class="text-zinc-600 text-xs">© <?= date('Y') ?> FitSense. All rights reserved.</p>
        <p class="text-zinc-700 text-xs">AI recommendations are not a substitute for professional medical advice.</p>
        <a href="staff-login.php" class="text-zinc-800 hover:text-zinc-600 text-xs transition-colors">Staff Portal</a>
    </div>
</footer>

<script>
(function () {
    var nav     = document.getElementById('mobile-nav');
    var overlay = document.getElementById('mobile-overlay');
    function open() {
        nav.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        requestAnimationFrame(function () { overlay.classList.remove('opacity-0'); });
    }
    function close() {
        nav.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(function () { overlay.classList.add('hidden'); }, 300);
    }
    document.getElementById('mobile-open').addEventListener('click', open);
    document.getElementById('mobile-close').addEventListener('click', close);
    overlay.addEventListener('click', close);
})();
</script>
</body>
</html>

