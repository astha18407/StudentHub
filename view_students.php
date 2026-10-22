
<?php
require_once "db.php";

$sql = "SELECT student_id, name, email, mobile FROM students";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$students = $stmt->fetchAll();

echo "<h2>Student List</h2>";

foreach ($students as $student) {
    echo "ID: " . htmlspecialchars((string)$student["student_id"]) . "<br>";
    echo "Name: " . htmlspecialchars($student["name"]) . "<br>";
    echo "Email: " . htmlspecialchars($student["email"]) . "<br>";
    echo "Mobile: " . htmlspecialchars($student["mobile"] ?? "") . "<br><hr>";
}
?>
