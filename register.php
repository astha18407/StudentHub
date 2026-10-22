
<?php
require_once "db_mysqli.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit;
}

$fname = trim($_POST["fname"] ?? "");
$lname = trim($_POST["lname"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm"] ?? "";
$terms = $_POST["terms"] ?? "";

$errors = [];

if ($fname === "" || $lname === "") {
    $errors[] = "First and last name are required.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email address.";
}

if (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain 10 digits.";
}

if ($course === "" || $year === "" || $gender === "") {
    $errors[] = "Please complete all selections.";
}

if (strlen($password) < 8) {
    $errors[] = "Password must contain at least 8 characters.";
}

if ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
}

if ($terms === "") {
    $errors[] = "Please accept the terms and conditions.";
}

if (!empty($errors)) {
    echo "<h2>Registration Failed</h2><ul>";

    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }

    echo "</ul><a href='register.html'>Go Back</a>";
    exit;
}

$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    echo "<h2>Registration Failed</h2>";
    echo "<p>This email is already registered.</p>";
    echo "<a href='register.html'>Go Back</a>";
    exit;
}

$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users
    (fname, lname, email, mobile, course, year, gender, password_hash)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "ssssssss",
    $fname,
    $lname,
    $email,
    $mobile,
    $course,
    $year,
    $gender,
    $passwordHash
);

if ($stmt->execute()) {
    echo "<h2>Registration Successful!</h2>";
    echo "<p>Your details have been saved to the database.</p>";
    echo "<a href='login.html'>Go to Login</a>";
} else {
    echo "<h2>Registration Failed</h2>";
    echo "<p>Please try again with valid details.</p>";
    echo "<a href='register.html'>Go Back</a>";
}

$stmt->close();
$conn->close();
?>
