<?php
/**
 * FitSense — FAQ Page
 */
session_start();
$isLoggedIn = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$dashboardUrl = match($role) {
    'admin'   => 'admin-dashboard.php',
    'trainer' => 'trainer-dashboard.php',
    default   => 'chat.php',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>FAQ — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        .faq-answer.open { max-height: 400px; }
        .faq-chevron { transition: transform 0.3s ease; }
        .faq-chevron.open { transform: rotate(180deg); }
        /* Mobile sidebar */
        #mobile-nav { transition: transform 0.3s ease; }
        #mobile-overlay { transition: opacity 0.3s ease; }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col" style="min-width:375px">

<!-- ── Mobile sidebar overlay ────────────────────────────────────────────── -->
<div id="mobile-overlay" class="fixed inset-0 z-40 bg-black/70 hidden opacity-0 lg:hidden" aria-hidden="true"></div>

<!-- ── Mobile sidebar ────────────────────────────────────────────────────── -->
<div id="mobile-nav" class="fixed top-0 right-0 h-full w-72 z-50 bg-zinc-900 border-l border-zinc-700 flex flex-col translate-x-full lg:hidden" role="dialog" aria-label="Navigation menu">
    <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
        <span class="text-yellow-400 font-bold text-lg">Menu</span>
        <button id="mobile-close" class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors" aria-label="Close menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex flex-col px-4 py-4 gap-1 flex-1">
        <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">Home</a>
        <a href="about.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">About Us</a>
        <a href="faq.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-zinc-800 text-yellow-400 font-medium text-sm min-h-[44px]">FAQs</a>
        <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">Contact Us</a>
    </nav>
    <div class="px-4 pb-6">
        <?php if ($isLoggedIn): ?>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
            class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] text-center text-sm transition-colors">
            My Dashboard
        </a>
        <?php else: ?>
        <a href="login.php"
            class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] text-center text-sm transition-colors">
            Login
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Nav ───────────────────────────────────────────────────────────────── -->
<nav class="sticky top-0 z-30 bg-zinc-900/95 backdrop-blur border-b border-zinc-700 px-5 py-3 flex items-center justify-between">
    <!-- Logo -->
    <a href="index.php" class="flex items-center gap-2 group">
        <svg class="w-9 h-8 text-yellow-400 shrink-0 group-hover:scale-110 transition-transform" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
        <span class="text-yellow-400 font-bold text-xl tracking-tight">FitSense</span>
    </a>
    <!-- Desktop links -->
    <div class="hidden lg:flex items-center gap-4">
        <a href="index.php" class="text-zinc-400 hover:text-white text-sm transition-colors">Home</a>
        <a href="about.php" class="text-zinc-400 hover:text-white text-sm transition-colors">About</a>
        <a href="faq.php" class="text-yellow-400 text-sm font-medium">FAQs</a>
        <a href="contact.php" class="text-zinc-400 hover:text-white text-sm transition-colors">Contact</a>
        <?php if ($isLoggedIn): ?>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] flex items-center text-sm transition-colors">
            My Dashboard
        </a>
        <?php else: ?>
        <a href="login.php"
            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-5 py-2 min-h-[44px] flex items-center text-sm transition-colors">
            Login
        </a>
        <?php endif; ?>
    </div>
    <!-- Mobile hamburger -->
    <button id="mobile-open" class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors text-zinc-400 hover:text-white" aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</nav>

<!-- ── Hero ───────────────────────────────────────────────────────────────── -->
<section class="px-6 py-14 text-center max-w-2xl mx-auto w-full">
    <h1 class="text-4xl font-extrabold text-yellow-400 mb-3">Frequently Asked Questions</h1>
    <p class="text-zinc-400 text-base">Everything you need to know about FitSense.</p>
</section>

<!-- ── FAQ List ───────────────────────────────────────────────────────────── -->
<section class="px-6 pb-16 max-w-2xl mx-auto w-full">
    <div class="space-y-3">
        <?php
        $faqs = [
            ['q' => 'How do I get access to FitSense?',
             'a' => 'FitSense is available to registered members of Biofitness Gym. Once you sign up at the gym, the admin will create your account and give you your login details. You cannot register on your own through the website.'],
            ['q' => 'Is the AI advice safe to follow?',
             'a' => 'The AI gives personalized suggestions based on your health profile, but every workout and meal plan recommendation is reviewed by your assigned trainer before it is finalized. Always consult a medical professional for health concerns.'],
            ['q' => 'Can I talk to my trainer through the app?',
             'a' => 'Yes. FitSense has a built-in Trainer Chat where you can send messages directly to your assigned trainer and receive replies inside the app.'],
            ['q' => 'How does the AI know what to recommend for me?',
             'a' => 'When you first log in, you fill in your health profile — your age, height, weight, fitness level, and goal. The AI uses this information every time you ask a question so the advice it gives fits your specific situation.'],
            ['q' => 'Can I track my workouts and weight?',
             'a' => 'Yes. The Progress section lets you log your workouts and weight over time. Your weight history is shown as a chart so you can see how you are doing at a glance.'],
            ['q' => 'What happens if I forget my password?',
             'a' => 'Contact the gym admin or your trainer. Since accounts are created and managed by the admin, they can reset your password for you.'],
            ['q' => 'Is my personal data safe?',
             'a' => 'Yes. FitSense stores your data securely and only authorized users like your trainer and the admin can view your profile information. Your data is never shared outside the system.'],
            ['q' => 'How many AI messages can I send per day?',
             'a' => 'There is a daily limit set by the admin to keep the system running smoothly for all members. The limit resets every midnight. If you reach the limit, you will see a message letting you know.'],
            ['q' => 'Can I update my health profile after I log in?',
             'a' => 'Yes. You can update your health profile anytime from the Settings section inside the app. Keeping it up to date helps the AI give you better advice.'],
            ['q' => 'What is the difference between the AI chat and the trainer chat?',
             'a' => 'The AI chat is available 24/7 and gives instant responses based on your profile. The trainer chat is a direct message thread with your assigned human trainer, who can give more personal guidance and review your AI recommendations.'],
        ];
        foreach ($faqs as $i => $faq): ?>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl overflow-hidden">
            <button class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left text-white font-medium text-sm hover:bg-zinc-800 transition-colors min-h-[52px]"
                aria-expanded="false">
                <span><?= htmlspecialchars($faq['q'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                <svg class="faq-chevron w-4 h-4 text-yellow-400 shrink-0 ml-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="faq-answer px-5 text-zinc-400 text-sm">
                <p class="pb-4"><?= htmlspecialchars($faq['a'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-10 text-center">
        <p class="text-zinc-400 text-sm mb-4">Still have questions?</p>
        <a href="contact.php" class="inline-block bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-6 py-3 min-h-[44px] text-sm transition-colors">
            Contact Us
        </a>
    </div>
</section>

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
                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Dashboard</a>
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
// FAQ accordion
document.querySelectorAll('.faq-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var answer  = this.nextElementSibling;
        var chevron = this.querySelector('.faq-chevron');
        var isOpen  = answer.classList.contains('open');
        document.querySelectorAll('.faq-answer').forEach(function (a) { a.classList.remove('open'); });
        document.querySelectorAll('.faq-chevron').forEach(function (c) { c.classList.remove('open'); });
        document.querySelectorAll('.faq-btn').forEach(function (b) { b.setAttribute('aria-expanded','false'); });
        if (!isOpen) { answer.classList.add('open'); chevron.classList.add('open'); this.setAttribute('aria-expanded','true'); }
    });
});

// Mobile sidebar
var mobileNav     = document.getElementById('mobile-nav');
var mobileOverlay = document.getElementById('mobile-overlay');
function openNav() {
    mobileNav.classList.remove('translate-x-full');
    mobileOverlay.classList.remove('hidden');
    requestAnimationFrame(function () { mobileOverlay.classList.remove('opacity-0'); });
}
function closeNav() {
    mobileNav.classList.add('translate-x-full');
    mobileOverlay.classList.add('opacity-0');
    setTimeout(function () { mobileOverlay.classList.add('hidden'); }, 300);
}
document.getElementById('mobile-open').addEventListener('click', openNav);
document.getElementById('mobile-close').addEventListener('click', closeNav);
mobileOverlay.addEventListener('click', closeNav);
</script>
</body>
</html>
