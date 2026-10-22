
<?php
require_once "db.php";

// Insert a student using a prepared statement
$sql = "INSERT INTO students
        (name, email, mobile)
        VALUES (:name, :email, :mobile)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":name" => "Test Student",
    ":email" => "teststudent2@example.com",
    ":mobile" => "9876543210"
]);

echo "Student inserted successfully!";
?>
