
<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "../db_mysqli.php";

function respond($status, $message, $data = null) {
    http_response_code($status);
    echo json_encode([
        "success" => $status >= 200 && $status < 300,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

if (!isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin") {
    respond(403, "Admin access required.");
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $search = trim($_GET["search"] ?? "");

    if ($search !== "") {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare(
            "SELECT event_id, title, event_date, venue, category, status
             FROM events
             WHERE title LIKE ? OR venue LIKE ? OR category LIKE ?
             ORDER BY event_id DESC"
        );
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query(
            "SELECT event_id, title, event_date, venue, category, status
             FROM events ORDER BY event_id DESC"
        );
    }

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    if (isset($stmt)) {
        $stmt->close();
    }

    respond(200, "Events fetched successfully.", $events);
}

if ($method !== "POST") {
    header("Allow: GET, POST");
    respond(405, "Method not allowed.");
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond(400, "Invalid JSON request.");
}

$action = $input["action"] ?? "";
$id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

if ($action === "add" || $action === "update") {
    $title = trim($input["title"] ?? "");
    $date = trim($input["event_date"] ?? "");
    $venue = trim($input["venue"] ?? "");
    $category = trim($input["category"] ?? "General");
    $description = trim($input["description"] ?? "");
    $status = $input["status"] ?? "Open";

    if ($title === "" || $date === "" || $venue === "" ||
        $category === "") {
        respond(422, "Title, date, venue and category are required.");
    }

    $parsedDate = DateTime::createFromFormat("Y-m-d", $date);
    if (!$parsedDate || $parsedDate->format("Y-m-d") !== $date) {
        respond(422, "Enter a valid event date.");
    }

    if (!in_array($status, ["Open", "Closed"], true)) {
        respond(422, "Status must be Open or Closed.");
    }

    if (strlen($title) > 150 || strlen($venue) > 150 ||
        strlen($category) > 100) {
        respond(422, "One or more fields are too long.");
    }

    if ($action === "add") {
        $stmt = $conn->prepare(
            "INSERT INTO events
             (title, event_date, venue, description, category, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssss", $title, $date, $venue,
            $description, $category, $status
        );

        if (!$stmt->execute()) {
            $stmt->close();
            respond(500, "Could not add event.");
        }

        $newId = $conn->insert_id;
        $stmt->close();
        respond(201, "Event added successfully.", ["event_id" => $newId]);
    }

    if (!$id || $id < 1) {
        respond(422, "A valid event ID is required.");
    }

    $stmt = $conn->prepare(
        "UPDATE events
         SET title=?, event_date=?, venue=?, description=?,
             category=?, status=?
         WHERE event_id=?"
    );
    $stmt->bind_param(
        "ssssssi", $title, $date, $venue,
        $description, $category, $status, $id
    );

    if (!$stmt->execute()) {
        $stmt->close();
        respond(500, "Could not update event.");
    }

    $changed = $stmt->affected_rows;
    $stmt->close();

    if ($changed === 0) {
        $check = $conn->prepare(
            "SELECT event_id FROM events WHERE event_id=?"
        );
        $check->bind_param("i", $id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            respond(404, "Event not found.");
        }
    }

    respond(200, "Event updated successfully.");
}

if ($action === "delete") {
    if (!$id || $id < 1) {
        respond(422, "A valid event ID is required.");
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE event_id=?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        $stmt->close();
        respond(500, "Could not delete event. Check related registrations.");
    }

    $deleted = $stmt->affected_rows;
    $stmt->close();

    if (!$deleted) {
        respond(404, "Event not found.");
    }

    respond(200, "Event deleted successfully.");
}

respond(400, "Unknown action.");
?>
