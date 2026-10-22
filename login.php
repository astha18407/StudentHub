
<?php
session_start();
require_once "db_mysqli.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
    exit("Please enter a valid email and password. <a href='login.html'>Go back</a>");
}

$stmt = $conn->prepare(
    "SELECT user_id, fname, lname, email, password_hash, role
     FROM users WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
    $conn->close();
    exit("Invalid email or password. <a href='login.html'>Try again</a>");
}

session_regenerate_id(true);

$_SESSION["user_id"] = $user["user_id"];
$_SESSION["fname"] = $user["fname"];
$_SESSION["lname"] = $user["lname"];
$_SESSION["email"] = $user["email"];
$_SESSION["role"] = $user["role"];
$_SESSION["last_activity"] = time();

$stmt = $conn->prepare(
    "UPDATE users SET last_login = NOW() WHERE user_id = ?"
);
$stmt->bind_param("i", $user["user_id"]);
$stmt->execute();
$stmt->close();
$conn->close();

if ($user["role"] === "admin") {
    header("Location: admin_dashboard.php");
} else {
    header("Location: dashboard.php");
}
exit;
?>
