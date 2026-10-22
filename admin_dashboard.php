
<?php
session_start();
require_once "db_mysqli.php";

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

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: dashboard.php");
    exit;
}

// Dashboard statistics
$total_users = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
)->fetch_assoc()["total"];

$total_students = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
)->fetch_assoc()["total"];

$total_events = $conn->query(
    "SELECT COUNT(*) AS total FROM events"
)->fetch_assoc()["total"];

$total_registrations = $conn->query(
    "SELECT COUNT(*) AS total FROM registrations"
)->fetch_assoc()["total"];

// Latest registrations
$latest = $conn->query("
    SELECT s.name AS student_name,
           e.title AS event_title,
           r.registration_date
    FROM registrations r
    JOIN students s ON r.student_id = s.student_id
    JOIN events e ON r.event_id = e.event_id
    ORDER BY r.registration_date DESC
    LIMIT 5
");

// Latest audit logs
$logs = $conn->query("
    SELECT action, details, created_at
    FROM audit_logs
    ORDER BY log_id DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | StudentHub</title>
<link rel="stylesheet" href="CSS/style.css">

<style>
.dashboard {
    max-width: 1100px;
    margin: 30px auto;
    padding: 20px;
}
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin: 25px 0;
}
.stat-card, .section-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px #ddd;
    margin-bottom: 20px;
}
.stat-card h2 {
    margin: 10px 0;
    color: #2457a7;
}
.table-wrap {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}
th {
    background: #2457a7;
    color: white;
}
.menu {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 20px 0;
}
.menu a {
    padding: 10px 14px;
    background: #2457a7;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
</style>
</head>

<body>
<main class="dashboard">

<h1>Admin Dashboard</h1>
<p>Welcome, <?php echo htmlspecialchars($_SESSION["fname"] ?? "Admin"); ?>!</p>
<p>Role: Admin</p>

<nav class="menu">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="student_management.php">Manage Students</a>
    <a href="event_management.php">Manage Events</a>
    <a href="event_api.html">Events API</a>
    <a href="logout.php">Logout</a>
</nav>

<h2>Dashboard Statistics</h2>

<div class="stats">
    <div class="stat-card">
        <p>Total Users</p>
        <h2><?php echo $total_users; ?></h2>
    </div>

    <div class="stat-card">
        <p>Total Students</p>
        <h2><?php echo $total_students; ?></h2>
    </div>

    <div class="stat-card">
        <p>Total Events</p>
        <h2><?php echo $total_events; ?></h2>
    </div>

    <div class="stat-card">
        <p>Total Registrations</p>
        <h2><?php echo $total_registrations; ?></h2>
    </div>
</div>

<section class="section-card">
<h2>Latest Registrations</h2>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>Student Name</th>
    <th>Event Name</th>
    <th>Registration Date</th>
</tr>
</thead>
<tbody>
<?php if ($latest && $latest->num_rows > 0): ?>
    <?php while ($row = $latest->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row["student_name"]); ?></td>
        <td><?php echo htmlspecialchars($row["event_title"]); ?></td>
        <td><?php echo htmlspecialchars($row["registration_date"]); ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="3">No registrations found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>

<section class="section-card">
<h2>Recent Audit Logs</h2>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>Action</th>
    <th>Details</th>
    <th>Date and Time</th>
</tr>
</thead>
<tbody>
<?php if ($logs && $logs->num_rows > 0): ?>
    <?php while ($log = $logs->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($log["action"]); ?></td>
        <td><?php echo htmlspecialchars($log["details"] ?? ""); ?></td>
        <td><?php echo htmlspecialchars($log["created_at"]); ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="3">No audit logs recorded yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>

</main>
</body>
</html>
