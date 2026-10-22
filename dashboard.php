
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if (time() - ($_SESSION["last_activity"] ?? time()) > 1800) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}

$_SESSION["last_activity"] = time();

if (($_SESSION["role"] ?? "") !== "student") {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | StudentHub</title>
<link rel="stylesheet" href="CSS/style.css">
</head>
<body>
<main class="login-page">
<div class="login-card">
<h1>Student Dashboard</h1>
<p>Welcome, <?php echo htmlspecialchars($_SESSION["fname"]); ?>!</p>
<p>You have logged in successfully.</p>
<p>Email: <?php echo htmlspecialchars($_SESSION["email"]); ?></p>
<p>Role: Student</p>
<a class="primary-btn" href="logout.php">Logout</a>
</div>
</main>
</body>
</html>
