<?php
/**
 * FitSense — About Us
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
    <title>About Us — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .team-photo { aspect-ratio: 1/1; object-fit: cover; }
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
        <a href="about.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-zinc-800 text-yellow-400 font-medium text-sm min-h-[44px]">About Us</a>
        <a href="faq.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">FAQs</a>
        <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-zinc-300 hover:bg-zinc-800 hover:text-white text-sm transition-colors min-h-[44px]">Contact Us</a>
    </nav>
    <div class="px-4 pb-6">
        <?php if ($isLoggedIn): ?>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
            class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] text-center text-sm transition-colors">My Dashboard</a>
        <?php else: ?>
        <a href="login.php"
            class="block w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] text-center text-sm transition-colors">Login</a>
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
        <a href="about.php" class="text-yellow-400 text-sm font-medium">About</a>
        <a href="faq.php" class="text-zinc-400 hover:text-white text-sm transition-colors">FAQs</a>
        <a href="contact.php" class="text-zinc-400 hover:text-white text-sm transition-colors">Contact</a>
        <?php if ($isLoggedIn): ?>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-4 py-2 min-h-[44px] flex items-center text-sm transition-colors">My Dashboard</a>
        <?php else: ?>
        <a href="login.php"
            class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-lg px-5 py-2 min-h-[44px] flex items-center text-sm transition-colors">Login</a>
        <?php endif; ?>
    </div>
    <button id="mobile-open" class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800 transition-colors text-zinc-400 hover:text-white" aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</nav>

<!-- ── Hero ───────────────────────────────────────────────────────────────── -->
<section class="px-6 py-16 text-center max-w-3xl mx-auto w-full">
    <div class="flex justify-center mb-4">
        <svg class="w-20 h-16 text-yellow-400 drop-shadow-lg" viewBox="0 0 640 512" fill="currentColor" aria-hidden="true"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
    </div>
    <h1 class="text-4xl font-extrabold text-yellow-400 mb-4">About FitSense</h1>
    <p class="text-zinc-300 text-lg leading-relaxed">
        A web-based AI fitness application built for <span class="text-white font-semibold">Biofitness Gym</span> — combining artificial intelligence, human-centered design, and real trainer oversight to modernize gym operations and improve member outcomes.
    </p>
</section>

<!-- ── Problem / Solution ─────────────────────────────────────────────────── -->
<section class="px-6 pb-12 max-w-3xl mx-auto w-full space-y-6">

    <!-- Photo Carousel -->
    <div class="relative w-full rounded-2xl overflow-hidden border border-zinc-700 bg-zinc-900" id="carousel">
        <!-- Slides -->
        <div class="relative w-full" style="aspect-ratio:16/9">
            <img src="uploads/about/about1.jpg" alt="Biofitness Gym photo 1"
                class="carousel-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-100" data-index="0">
            <img src="uploads/about/about2.jpg" alt="Biofitness Gym photo 2"
                class="carousel-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0" data-index="1">
            <img src="uploads/about/about3.jpg" alt="Biofitness Gym photo 3"
                class="carousel-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0" data-index="2">
        </div>

        <!-- Prev / Next -->
        <button onclick="carouselMove(-1)" aria-label="Previous photo"
            class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full w-9 h-9 flex items-center justify-center transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button onclick="carouselMove(1)" aria-label="Next photo"
            class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full w-9 h-9 flex items-center justify-center transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <!-- Dots -->
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
            <button onclick="carouselGo(0)" class="carousel-dot w-2 h-2 rounded-full bg-yellow-400 transition-all" aria-label="Photo 1"></button>
            <button onclick="carouselGo(1)" class="carousel-dot w-2 h-2 rounded-full bg-white/40 transition-all" aria-label="Photo 2"></button>
            <button onclick="carouselGo(2)" class="carousel-dot w-2 h-2 rounded-full bg-white/40 transition-all" aria-label="Photo 3"></button>
        </div>
    </div>

    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6">
        <h2 class="text-yellow-400 font-bold text-lg mb-3">The Problem</h2>
        <p class="text-zinc-300 text-sm leading-relaxed">
            Many single-location gyms like Biofitness Gym rely on manual or semi-automated systems focused mainly on administrative tasks — membership check-ins, attendance tracking, and simple workout logs. While these help manage basic operations, they lack real-time personalized feedback and centralized data analysis.
        </p>
        <p class="text-zinc-400 text-sm leading-relaxed mt-3">
            As a result, members may not get the guidance they need, trainers find it hard to monitor everyone at once, and management lacks the data to make good decisions.
        </p>
    </div>

    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6">
        <h2 class="text-yellow-400 font-bold text-lg mb-3">Our Solution</h2>
        <p class="text-zinc-300 text-sm leading-relaxed">
            FitSense is a web-based AI system designed to give personalized workout recommendations and centralized storage of member performance data — all built around the needs of real gym members.
        </p>
        <p class="text-zinc-400 text-sm leading-relaxed mt-3">
            Through these features, FitSense aims to reduce injuries, improve workout results, increase member motivation, and support trainers and management with accurate, data-driven insights.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 text-center">
            <div class="text-3xl mb-2">🤖</div>
            <h3 class="text-white font-bold text-sm mb-1">AI-Powered</h3>
            <p class="text-zinc-400 text-xs">Claude AI delivers personalized workout and nutrition recommendations tailored to each member.</p>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 text-center">
            <div class="text-3xl mb-2">🧑‍🏫</div>
            <h3 class="text-white font-bold text-sm mb-1">Trainer Oversight</h3>
            <p class="text-zinc-400 text-xs">Every AI recommendation is reviewed by an assigned human trainer for safety and accuracy.</p>
        </div>
        <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-5 text-center">
            <div class="text-3xl mb-2">📊</div>
            <h3 class="text-white font-bold text-sm mb-1">Data-Driven</h3>
            <p class="text-zinc-400 text-xs">Centralized analytics give management real-time insights to improve gym operations and member retention.</p>
        </div>
    </div>

    <div class="bg-zinc-900 border border-zinc-700 rounded-xl p-6">
        <h2 class="text-yellow-400 font-bold text-lg mb-3">Impact</h2>
        <p class="text-zinc-300 text-sm leading-relaxed">
            FitSense extends coaching support beyond trainer availability and provides continuous guidance to members. By integrating AI-driven feedback and centralized analytics, FitSense improves service quality, enhances member retention, and modernizes gym operations.
        </p>
    </div>

</section>

<!-- ── Meet the Team ──────────────────────────────────────────────────────── -->
<section class="bg-zinc-900 border-y border-zinc-700 px-6 py-16">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-yellow-400 font-bold text-2xl text-center mb-2">Meet the Team</h2>
        <p class="text-zinc-400 text-sm text-center mb-10">The students behind FitSense — IT32S5, Technological Institute of the Philippines</p>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">

            <!-- Leader -->
            <div class="col-span-2 sm:col-span-3 flex flex-col sm:flex-row items-center gap-5 bg-black border border-yellow-400/30 rounded-xl p-5">
                <div class="w-24 h-24 rounded-full bg-zinc-800 border-2 border-yellow-400 overflow-hidden shrink-0 flex items-center justify-center">
                    <img src="uploads/avatars/Pedrigal.jpg" alt="John Mark Pedrigal"
                        class="team-photo w-full h-full"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="hidden w-full h-full items-center justify-center text-yellow-400 font-bold text-2xl">JM</div>
                </div>
                <div class="text-center sm:text-left">
                    <span class="inline-block bg-yellow-400 text-black text-xs font-bold px-2 py-0.5 rounded-full mb-1">Leader</span>
                    <p class="text-white font-bold">Pedrigal, John Mark D.</p>
                    <p class="text-zinc-400 text-sm mt-1">Led the group, coding support, organized the documentation, and coordinated the team throughout the project.</p>
                </div>
            </div>

            <!-- Member 1 -->
            <div class="flex flex-col items-center gap-3 bg-black border border-zinc-700 rounded-xl p-4 text-center">
                <div class="w-20 h-20 rounded-full bg-zinc-800 border-2 border-zinc-600 overflow-hidden flex items-center justify-center">
                    <img src="uploads/avatars/Blockstock.jpg" alt="Karl Christopher Blockstock"
                        class="team-photo w-full h-full"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="hidden w-full h-full items-center justify-center text-yellow-400 font-bold text-xl">KC</div>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm">Blockstock, Karl Christopher D.</p>
                    <p class="text-zinc-400 text-xs mt-1">Backend development, AI integration, database, and document formatting.</p>
                </div>
            </div>

            <!-- Member 2 -->
            <div class="flex flex-col items-center gap-3 bg-black border border-zinc-700 rounded-xl p-4 text-center">
                <div class="w-20 h-20 rounded-full bg-zinc-800 border-2 border-zinc-600 overflow-hidden flex items-center justify-center">
                    <img src="uploads/avatars/Delima.png" alt="Jedric Lloyd Delima"
                        class="team-photo w-full h-full"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="hidden w-full h-full items-center justify-center text-yellow-400 font-bold text-xl">JL</div>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm">Delima, Jedric Lloyd S.</p>
                    <p class="text-zinc-400 text-xs mt-1">Coding support and writing descriptions for the documentation.</p>
                </div>
            </div>

            <!-- Member 3 -->
            <div class="flex flex-col items-center gap-3 bg-black border border-zinc-700 rounded-xl p-4 text-center">
                <div class="w-20 h-20 rounded-full bg-zinc-800 border-2 border-zinc-600 overflow-hidden flex items-center justify-center">
                    <img src="uploads/avatars/Ramos.jpg" alt="Jan Ramos"
                        class="team-photo w-full h-full"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="hidden w-full h-full items-center justify-center text-yellow-400 font-bold text-xl">JR</div>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm">Ramos, Jan M.</p>
                    <p class="text-zinc-400 text-xs mt-1">Coding support, organized the content in the documentation.</p>
                </div>
            </div>

            <!-- Member 4 -->
            <div class="flex flex-col items-center gap-3 bg-black border border-zinc-700 rounded-xl p-4 text-center">
                <div class="w-20 h-20 rounded-full bg-zinc-800 border-2 border-zinc-600 overflow-hidden flex items-center justify-center">
                    <img src="uploads/avatars/Selda.jpg" alt="Lennuel Adrianne Selda"
                        class="team-photo w-full h-full"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="hidden w-full h-full items-center justify-center text-yellow-400 font-bold text-xl">LA</div>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm">Selda, Lennuel Adrianne A.</p>
                    <p class="text-zinc-400 text-xs mt-1">Frontend and backend coding, UI components, and testing.</p>
                </div>
            </div>

            <!-- Member 5 -->
            <div class="flex flex-col items-center gap-3 bg-black border border-zinc-700 rounded-xl p-4 text-center">
                <div class="w-20 h-20 rounded-full bg-zinc-800 border-2 border-zinc-600 overflow-hidden flex items-center justify-center">
                    <img src="uploads/avatars/Vergara.png" alt="Christian James Vergara"
                        class="team-photo w-full h-full"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="hidden w-full h-full items-center justify-center text-yellow-400 font-bold text-xl">CJ</div>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm">Vergara, Christian James N.</p>
                    <p class="text-zinc-400 text-xs mt-1">Frontend and backend development, member pages, and responsive design.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── Location ───────────────────────────────────────────────────────────── -->
<section class="px-6 py-12 max-w-3xl mx-auto w-full">
    <h2 class="text-yellow-400 font-bold text-xl mb-2 text-center">Find Us</h2>
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

var _carouselIdx = 0;
var _carouselTotal = 3;

function carouselGo(idx) {
    var slides = document.querySelectorAll('.carousel-slide');
    var dots   = document.querySelectorAll('.carousel-dot');
    slides[_carouselIdx].classList.replace('opacity-100', 'opacity-0');
    dots[_carouselIdx].classList.replace('bg-yellow-400', 'bg-white/40');
    _carouselIdx = (idx + _carouselTotal) % _carouselTotal;
    slides[_carouselIdx].classList.replace('opacity-0', 'opacity-100');
    dots[_carouselIdx].classList.replace('bg-white/40', 'bg-yellow-400');
}

function carouselMove(dir) { carouselGo(_carouselIdx + dir); }

// Auto-advance every 4s
setInterval(function () { carouselMove(1); }, 4000);
</script>
</body>
</html>
