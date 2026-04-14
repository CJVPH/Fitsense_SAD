<?php
session_start();

$hasDashboard = isset($_SESSION['user_id']);
$role         = $_SESSION['role'] ?? '';

$dashboardUrl = match ($role) {
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
    <title>Under Maintenance — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white min-h-screen flex flex-col items-center justify-center px-4 py-10">

    <div class="w-full max-w-md text-center space-y-6">

        <!-- Icon -->
        <div class="text-7xl" role="img" aria-label="Wrench tool">🔧</div>

        <!-- Heading -->
        <h1 class="text-3xl font-bold text-yellow-400">
            FitSense is Under Maintenance
        </h1>

        <!-- Message -->
        <p class="text-gray-300 text-lg leading-relaxed">
            We're making improvements to give you a better experience.
            Please check back soon.
        </p>

        <!-- Dashboard link (only when logged in) -->
        <?php if ($hasDashboard): ?>
        <a
            href="<?= htmlspecialchars($dashboardUrl) ?>"
            class="inline-flex items-center justify-center min-h-[44px] min-w-[44px]
                   bg-yellow-400 text-black font-semibold rounded-lg px-6 py-3
                   hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-400
                   transition-colors"
        >
            Back to Dashboard
        </a>
        <?php endif; ?>

    </div>

</body>
</html>
