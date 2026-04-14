<?php
session_start();

$role = $_SESSION['role'] ?? '';

$dashboardUrl = match ($role) {
    'admin'   => 'admin-dashboard.php',
    'trainer' => 'trainer-dashboard.php',
    'member'  => 'chat.php',
    default   => 'login.php',
};

$linkLabel = $role !== '' ? 'Back to Dashboard' : 'Go to Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>Access Denied — FitSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white min-h-screen flex flex-col items-center justify-center px-4 py-10">

    <div class="w-full max-w-md text-center space-y-6">

        <!-- Icon -->
        <div class="text-7xl" role="img" aria-label="Padlock">🔒</div>

        <!-- Heading -->
        <h1 class="text-3xl font-bold text-yellow-400">
            Access Denied
        </h1>

        <!-- Message -->
        <p class="text-gray-300 text-lg leading-relaxed">
            You don't have permission to view this page.
            If you think this is a mistake, please contact your trainer or administrator.
        </p>

        <!-- Navigation link -->
        <a
            href="<?= htmlspecialchars($dashboardUrl) ?>"
            class="inline-flex items-center justify-center min-h-[44px] min-w-[44px]
                   bg-yellow-400 text-black font-semibold rounded-lg px-6 py-3
                   hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-400
                   transition-colors"
        >
            <?= htmlspecialchars($linkLabel) ?>
        </a>

    </div>

</body>
</html>
