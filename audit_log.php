
<?php
require_once "db_mysqli.php";

function logAction($action, $details = "")
{
    global $conn;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION["user_id"] ?? null;

    $sql = "INSERT INTO audit_logs (user_id, action, details)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("iss", $userId, $action, $details);

    $result = $stmt->execute();

    $stmt->close();

    return $result;
}
