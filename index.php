<?php
/**
 * FitSense — Public Landing Page
 */
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$pdo = Database::getConnection();

$memberCount  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'active'")->fetchColumn();
$workoutCount = (int) $pdo->query("SELECT COUNT(*) FROM workout_sessions")->fetchColumn();
$aiCount      = (int) $pdo->query("SELECT COUNT(*) FROM ai_recommendations WHERE type = 'workout'")->fetchColumn();
$successCount = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM workout_sessions")->fetchColumn();

$isLoggedIn   = !empty($_SESSION['user_id']);
$role         = $_SESSION['role'] ?? '';
$dashboardUrl = match($role) {
    'admin'   => 'admin-dashboard.php',
    'trainer' => 'trainer-dashboard.php',
    default   => 'chat.php',
};
$firstName = htmlspecialchars($_SESSION['first_name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>FitSense — Your AI Fitness Partner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Carousel */
        .carousel-slide { display:none; position:absolute; inset:0; opacity:0; transition:opacity 0.8s ease; }
        .carousel-slide.active { display:block; opacity:1; }
        /* Mobile sidebar */
        #mobile-nav { transition: transform 0.3s ease; }
        #mobile-overlay { transition: opacity 0.3s ease; }
        /* Logo hover */
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
        <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-zinc-800 text-yellow-400 font-medium text-sm min-h-[44px]">Home</a>
        <a href="about.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">About Us</a>
        <a href="faq.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">FAQs</a>
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
    <a href="index.php" class="flex items-center gap-2.5 group">
        <svg class="logo-dumbbell w-9 h-8 text-yellow-400 shrink-0" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
        <span class="text-yellow-400 font-bold text-xl tracking-tight">FitSense</span>
    </a>
    <!-- Desktop links -->
    <div class="hidden lg:flex items-center gap-4">
        <a href="#features" class="text-zinc-400 hover:text-white text-sm transition-colors">Features</a>
        <a href="about.php" class="text-zinc-400 hover:text-white text-sm transition-colors">About</a>
        <a href="faq.php" class="text-zinc-400 hover:text-white text-sm transition-colors">FAQs</a>
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

<!-- ── Hero with Carousel ─────────────────────────────────────────────────── -->
<section class="relative overflow-hidden min-h-[520px] sm:min-h-[600px] flex items-center justify-center">
    <!-- Carousel backgrounds -->
    <div class="absolute inset-0 z-0" id="carousel" aria-hidden="true">
        <div class="carousel-slide active" style="background:linear-gradient(135deg,#1a1a1a 0%,#2d1f00 50%,#000 100%);">
            <div class="absolute inset-0 opacity-20" style="background-image:repeating-linear-gradient(45deg,#facc15 0,#facc15 1px,transparent 0,transparent 50%);background-size:20px 20px;"></div>
        </div>
        <div class="carousel-slide" style="background:linear-gradient(135deg,#000 0%,#1c1c1c 40%,#3d2b00 100%);">
            <div class="absolute inset-0 flex items-center justify-center opacity-10">
                <svg viewBox="0 0 200 200" class="w-96 h-96 text-yellow-400" fill="none" stroke="currentColor" stroke-width="4"><circle cx="100" cy="100" r="80"/><circle cx="100" cy="100" r="50" stroke-width="2"/></svg>
            </div>
        </div>
        <div class="carousel-slide" style="background:radial-gradient(ellipse at center,#2a1f00 0%,#000 70%);">
            <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle,#facc15 1px,transparent 1px);background-size:30px 30px;"></div>
        </div>
        <div class="carousel-slide" style="background:linear-gradient(180deg,#111 0%,#1a1200 50%,#000 100%);">
            <div class="absolute bottom-0 left-0 right-0 h-32 opacity-20" style="background:linear-gradient(to top,#facc15,transparent);"></div>
        </div>
    </div>
    <!-- Dots -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2" aria-hidden="true">
        <button class="carousel-dot w-2 h-2 rounded-full bg-yellow-400 transition-all" data-index="0"></button>
        <button class="carousel-dot w-2 h-2 rounded-full bg-zinc-600 transition-all" data-index="1"></button>
        <button class="carousel-dot w-2 h-2 rounded-full bg-zinc-600 transition-all" data-index="2"></button>
        <button class="carousel-dot w-2 h-2 rounded-full bg-zinc-600 transition-all" data-index="3"></button>
    </div>
    <!-- Hero content -->
    <div class="relative z-10 flex flex-col items-center justify-center text-center px-6 py-20 max-w-3xl mx-auto w-full">
        <!-- Big dumbbell logo -->
        <svg class="w-24 h-20 text-yellow-400 drop-shadow-lg mb-6" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-yellow-400 leading-tight mb-5 drop-shadow-lg">
            Your Personal AI Fitness Coach
        </h1>
        <p class="text-zinc-300 text-lg leading-relaxed mb-4 max-w-xl">
            Get personalized workout plans, nutrition advice, and 24/7 support from your AI-powered fitness companion.
        </p>
        <?php if ($isLoggedIn): ?>
        <p class="text-zinc-400 text-sm mb-8">Welcome back, <?= $firstName ?>!</p>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-8 py-4 min-h-[56px] text-lg transition-colors shadow-lg shadow-yellow-400/20">
            Continue Your Journey
        </a>
        <?php else: ?>
        <p class="text-zinc-500 text-sm mb-8">Join <?= number_format($memberCount) ?> members on their fitness journey</p>
        <a href="login.php"
            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-8 py-4 min-h-[56px] text-lg transition-colors shadow-lg shadow-yellow-400/20">
            Become a Member
        </a>
        <?php endif; ?>
    </div>
</section>

<!-- ── Chat Preview ───────────────────────────────────────────────────────── -->
<section class="px-6 py-10 flex justify-center">
    <div class="w-full max-w-sm bg-zinc-900 border border-zinc-700 rounded-2xl p-5 text-left space-y-3">
        <p class="text-xs text-zinc-500 text-center mb-3 font-medium uppercase tracking-wider">See it in action</p>
        <div class="flex justify-end">
            <span class="bg-zinc-700 text-white text-sm rounded-2xl rounded-tr-sm px-4 py-2 max-w-[80%]">
                What's a good high-protein breakfast?
            </span>
        </div>
        <div class="flex justify-start">
            <span class="bg-yellow-400/10 border border-yellow-400/20 text-yellow-100 text-sm rounded-2xl rounded-tl-sm px-4 py-2 max-w-[80%]">
                Try Greek yogurt with berries and almonds — 30g protein, balanced macros! 💪
            </span>
        </div>
        <a href="login.php" class="block text-center text-yellow-400 hover:text-yellow-300 text-sm font-medium mt-2 transition-colors">
            Try the Chat →
        </a>
    </div>
</section>

<!-- ── Live Stats ─────────────────────────────────────────────────────────── -->
<?php if ($memberCount > 0 || $workoutCount > 0): ?>
<section class="bg-zinc-900 border-y border-zinc-700 px-6 py-10">
    <div class="max-w-3xl mx-auto grid grid-cols-2 sm:grid-cols-<?= $successCount > 0 ? '4' : '2' ?> gap-6 text-center">
        <div>
            <p class="text-3xl font-extrabold text-yellow-400"><?= number_format($memberCount) ?>+</p>
            <p class="text-zinc-400 text-sm mt-1">Active Members</p>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-yellow-400"><?= number_format($aiCount) ?>+</p>
            <p class="text-zinc-400 text-sm mt-1">Workouts Generated</p>
        </div>
        <?php if ($successCount > 0): ?>
        <div>
            <p class="text-3xl font-extrabold text-yellow-400"><?= number_format($workoutCount) ?>+</p>
            <p class="text-zinc-400 text-sm mt-1">Workouts Logged</p>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-yellow-400"><?= number_format($successCount) ?>+</p>
            <p class="text-zinc-400 text-sm mt-1">Success Stories</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ── Features ───────────────────────────────────────────────────────────── -->
<section id="features" class="px-6 py-16 max-w-3xl mx-auto w-full">
    <h2 class="text-2xl font-bold text-yellow-400 text-center mb-10">Everything You Need to Succeed</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 hover:border-yellow-400/50 transition-colors">
            <div class="text-3xl mb-3">💪</div>
            <h3 class="font-bold text-white mb-1">Custom Workouts</h3>
            <p class="text-zinc-400 text-sm">AI-generated workout plans tailored to your goals, fitness level, and available equipment.</p>
            <?php if ($aiCount > 0): ?><p class="text-yellow-400 text-xs mt-2 font-medium"><?= number_format($aiCount) ?>+ workouts created</p><?php endif; ?>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 hover:border-yellow-400/50 transition-colors">
            <div class="text-3xl mb-3">🥗</div>
            <h3 class="font-bold text-white mb-1">Nutrition Guidance</h3>
            <p class="text-zinc-400 text-sm">Personalized meal plans, macro tracking, and dietary recommendations for optimal results.</p>
            <p class="text-yellow-400 text-xs mt-2 font-medium">Personalized for your goals</p>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 hover:border-yellow-400/50 transition-colors">
            <div class="text-3xl mb-3">💬</div>
            <h3 class="font-bold text-white mb-1">24/7 AI Support</h3>
            <p class="text-zinc-400 text-sm">Ask questions anytime about fitness, nutrition, or wellness — your coach never sleeps.</p>
            <p class="text-yellow-400 text-xs mt-2 font-medium">Always available</p>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 hover:border-yellow-400/50 transition-colors">
            <div class="text-3xl mb-3">📊</div>
            <h3 class="font-bold text-white mb-1">Progress Tracking</h3>
            <p class="text-zinc-400 text-sm">Monitor your journey with detailed analytics and celebrate your achievements.</p>
            <?php if ($successCount > 0): ?><p class="text-yellow-400 text-xs mt-2 font-medium"><?= number_format($successCount) ?>+ success stories</p><?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Membership ─────────────────────────────────────────────────────────── -->
<section id="membership" class="bg-zinc-900 border-y border-zinc-700 px-6 py-16">
    <div class="max-w-md mx-auto text-center">
        <h2 class="text-2xl font-bold text-yellow-400 mb-2">Become a Member</h2>
        <p class="text-zinc-400 text-sm mb-8">Get exclusive access to your AI fitness coach</p>
        <div class="bg-black border border-zinc-700 rounded-2xl p-6 text-left space-y-3 mb-6">
            <h3 class="text-white font-bold text-lg text-center mb-4">FitSense Membership</h3>
            <ul class="space-y-2 text-sm text-zinc-300">
                <li class="flex items-center gap-2"><span class="text-yellow-400">✓</span> Unlimited AI chat support</li>
                <li class="flex items-center gap-2"><span class="text-yellow-400">✓</span> Personalized workout plans</li>
                <li class="flex items-center gap-2"><span class="text-yellow-400">✓</span> Custom nutrition guidance</li>
                <li class="flex items-center gap-2"><span class="text-yellow-400">✓</span> Progress tracking &amp; analytics</li>
                <li class="flex items-center gap-2"><span class="text-yellow-400">✓</span> Expert fitness knowledge 24/7</li>
                <li class="flex items-center gap-2"><span class="text-yellow-400">✓</span> Professional trainer oversight</li>
            </ul>
        </div>
        <?php if ($isLoggedIn && $role === 'member'): ?>
        <a href="chat.php" class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-6 py-4 min-h-[56px] text-center text-base transition-colors">Access Your Dashboard</a>
        <?php else: ?>
        <a href="login.php" class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-6 py-4 min-h-[56px] text-center text-base transition-colors">Join Now</a>
        <?php endif; ?>
    </div>
</section>

<!-- ── CTA ────────────────────────────────────────────────────────────────── -->
<section class="px-6 py-16 text-center max-w-2xl mx-auto w-full">
    <h2 class="text-2xl font-bold text-white mb-3">Ready to Transform Your Fitness Journey?</h2>
    <p class="text-zinc-400 text-sm mb-8">Join members achieving their health goals with FitSense</p>
    <?php if ($isLoggedIn && $role === 'member'): ?>
    <a href="chat.php" class="inline-block bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-8 py-4 min-h-[56px] text-lg transition-colors">Continue Your Journey</a>
    <?php else: ?>
    <a href="login.php" class="inline-block bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-8 py-4 min-h-[56px] text-lg transition-colors">Become a Member</a>
    <?php endif; ?>
</section>

<!-- ── Find Us ─────────────────────────────────────────────────────────────── -->
<section class="px-6 py-12 max-w-3xl mx-auto w-full">
    <h2 class="text-2xl font-bold text-yellow-400 text-center mb-2">Find Us</h2>
    <p class="text-zinc-400 text-sm text-center mb-6">Biofitness Gym — where FitSense was built for.</p>
    <div class="rounded-xl overflow-hidden border border-zinc-700 w-full" style="height:400px">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4590.94226754!2d121.06591079917547!3d14.62714347306496!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b7903fec1553%3A0x8118533905c67fdc!2sBiofitness%20Gym!5e0!3m2!1sen!2sph!4v1773992921879!5m2!1sen!2sph"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade" title="Biofitness Gym Location">
        </iframe>
    </div>
</section>

<!-- ── Footer ─────────────────────────────────────────────────────────────── -->
<footer class="bg-zinc-900 border-t border-zinc-700 px-6 py-8">
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
                <a href="#features" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Features</a>
                <a href="#membership" class="block text-zinc-500 hover:text-zinc-300 text-xs transition-colors">Membership</a>
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
// ── Carousel ──────────────────────────────────────────────────────────────
(function () {
    var slides  = document.querySelectorAll('.carousel-slide');
    var dots    = document.querySelectorAll('.carousel-dot');
    var current = 0;
    var total   = slides.length;
    function goTo(i) {
        slides[current].classList.remove('active');
        dots[current].classList.replace('bg-yellow-400','bg-zinc-600');
        current = (i + total) % total;
        slides[current].classList.add('active');
        dots[current].classList.replace('bg-zinc-600','bg-yellow-400');
    }
    dots.forEach(function (d) { d.addEventListener('click', function () { goTo(parseInt(this.dataset.index)); }); });
    setInterval(function () { goTo(current + 1); }, 4000);
})();

// ── Mobile sidebar ─────────────────────────────────────────────────────────
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
