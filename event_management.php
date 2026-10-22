
<?php
session_start();
require_once "db_mysqli.php";
require_once "audit_log.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Please log in with an admin account.");
}

$uploadDir = __DIR__ . "/uploads/event_posters/";
$uploadUrl = "uploads/event_posters/";
$maxSize = 2 * 1024 * 1024;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function removePoster($filename) {
    $path = __DIR__ . "/uploads/event_posters/" . basename($filename);
    if ($filename !== "" && is_file($path)) {
        unlink($path);
    }
}

function savePoster($field, $uploadDir, $maxSize) {
    if (!isset($_FILES[$field]) ||
        $_FILES[$field]["error"] === UPLOAD_ERR_NO_FILE) {
        return ["path" => null, "error" => null];
    }

    $file = $_FILES[$field];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return ["path" => null, "error" => "Poster upload failed."];
    }

    if ($file["size"] > $maxSize) {
        return ["path" => null, "error" => "Poster must be 2 MB or smaller."];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime]) || !getimagesize($file["tmp_name"])) {
        return [
            "path" => null,
            "error" => "Only JPG, PNG and WEBP images are allowed."
        ];
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];

    if (!move_uploaded_file(
        $file["tmp_name"],
        $uploadDir . $filename
    )) {
        return ["path" => null, "error" => "Could not save the poster."];
    }

    return ["path" => $filename, "error" => null];
}

function redirectMessage($message) {
    header("Location: event_management.php?msg=" . urlencode($message));
    exit;
}

$error = "";
$editEvent = null;

/* Add, update and delete */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $id = filter_var($_POST["event_id"] ?? "", FILTER_VALIDATE_INT);

        if (!$id) {
            redirectMessage("Invalid event ID.");
        }

        $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $oldEvent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$oldEvent) {
            redirectMessage("Event not found.");
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            if (!empty($oldEvent["poster"])) {
                removePoster($oldEvent["poster"]);
            }

            logAction("DELETE EVENT", "Deleted event ID: " . $id);

            redirectMessage("Event deleted successfully.");
        }

        redirectMessage("Could not delete the event.");
    }

    if ($action === "save") {
        $id = (int)($_POST["event_id"] ?? 0);
        $title = trim($_POST["title"] ?? "");
        $eventDate = trim($_POST["event_date"] ?? "");
        $venue = trim($_POST["venue"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $status = $_POST["status"] ?? "Open";

        if ($title === "" || $eventDate === "" ||
            $venue === "" || $category === "") {
            $error = "Please fill all required fields.";
        } elseif (!in_array($status, ["Open", "Closed"], true)) {
            $error = "Invalid event status.";
        } else {
            $date = DateTime::createFromFormat("Y-m-d", $eventDate);

            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                $error = "Please enter a valid event date.";
            }
        }

        $oldPoster = null;

        if ($error === "" && $id > 0) {
            $stmt = $conn->prepare(
                "SELECT poster FROM events WHERE event_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existing) {
                $error = "Event not found.";
            } else {
                $oldPoster = $existing["poster"];
            }
        }

        $newPoster = null;

        if ($error === "") {
            $upload = savePoster("poster", $uploadDir, $maxSize);
            $newPoster = $upload["path"];
            $error = $upload["error"] ?? "";
        }

        if ($error === "") {
            $poster = $newPoster ?? $oldPoster;

            if ($id > 0) {
                $stmt = $conn->prepare(
                    "UPDATE events
                     SET title=?, event_date=?, venue=?, description=?,
                         category=?, status=?, poster=?
                     WHERE event_id=?"
                );

                $stmt->bind_param(
                    "sssssssi",
                    $title, $eventDate, $venue, $description,
                    $category, $status, $poster, $id
                );
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO events
                     (title, event_date, venue, description, category, status, poster)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssss",
                    $title, $eventDate, $venue, $description,
                    $category, $status, $newPoster
                );
            }

            if ($stmt->execute()) {
                $savedEventId = $id > 0 ? $id : $conn->insert_id;
                $stmt->close();

                if ($newPoster !== null && !empty($oldPoster)) {
                    removePoster($oldPoster);
                }

                if ($id > 0) {
                    logAction("UPDATE EVENT", "Updated event ID: " . $savedEventId);
                } else {
                    logAction("ADD EVENT", "Added event ID: " . $savedEventId);
                }

                redirectMessage(
                    $id > 0
                        ? "Event updated successfully."
                        : "Event added successfully."
                );
            } else {
                if ($newPoster !== null) {
                    removePoster($newPoster);
                }
                $stmt->close();
                $error = "Database error. Could not save the event.";
            }
        }
    }
}

/* Load event for editing */
if (isset($_GET["edit"]) && !$error) {
    $id = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editEvent = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

/* Search and filters */
$search = trim($_GET["search"] ?? "");
$filterCategory = trim($_GET["category"] ?? "");
$filterStatus = $_GET["status"] ?? "";

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (title LIKE ? OR venue LIKE ? OR category LIKE ?)";
    $like = "%" . $search . "%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

if ($filterCategory !== "") {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= "s";
}

if (in_array($filterStatus, ["Open", "Closed"], true)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

$sql .= " ORDER BY event_date DESC";

$stmt = $conn->prepare($sql);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$events = $stmt->get_result();

$categoryResult = $conn->query(
    "SELECT DISTINCT category FROM events ORDER BY category"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Management - StudentHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            margin: 0;
            padding: 25px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }
        h1, h2 { color: #243b64; }
        input, textarea, select, button {
            padding: 10px;
            margin: 5px 0;
            box-sizing: border-box;
            max-width: 100%;
        }
        input:not([type="file"]), textarea, select { width: 100%; }
        button, .btn {
            background: #2459a9;
            color: white;
            border: 0;
            padding: 10px 14px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .delete { background: #c62828; }
        .message {
            background: #e4f7e8;
            padding: 12px;
            margin: 12px 0;
        }
        .error {
            background: #ffe5e5;
            padding: 12px;
            margin: 12px 0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .full { grid-column: 1 / -1; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th { background: #eaf0fa; }
        img.poster { width: 90px; height: 65px; object-fit: cover; }
        .filters {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
        }
        @media (max-width: 750px) {
            .form-grid, .filters { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>StudentHub Event Management</h1>
    <p>
        <a class="btn" href="admin_dashboard.php">Back to Admin Dashboard</a>
    </p>

    <?php if (isset($_GET["msg"])): ?>
        <div class="message"><?= clean($_GET["msg"]) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error"><?= clean($error) ?></div>
    <?php endif; ?>

    <h2><?= $editEvent ? "Edit Event" : "Add New Event" ?></h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="event_id"
               value="<?= clean($editEvent["event_id"] ?? 0) ?>">

        <div class="form-grid">
            <div>
                <label>Event Title *</label>
                <input type="text" name="title" maxlength="150" required
                       value="<?= clean($editEvent["title"] ?? "") ?>">
            </div>

            <div>
                <label>Event Date *</label>
                <input type="date" name="event_date" required
                       value="<?= clean($editEvent["event_date"] ?? "") ?>">
            </div>

            <div>
                <label>Venue *</label>
                <input type="text" name="venue" maxlength="150" required
                       value="<?= clean($editEvent["venue"] ?? "") ?>">
            </div>

            <div>
                <label>Category *</label>
                <input type="text" name="category" maxlength="100" required
                       placeholder="Workshop, Seminar, Sports..."
                       value="<?= clean($editEvent["category"] ?? "General") ?>">
            </div>

            <div>
                <label>Status</label>
                <select name="status">
                    <option value="Open"
                        <?= ($editEvent["status"] ?? "Open") === "Open"
                            ? "selected" : "" ?>>Open</option>
                    <option value="Closed"
                        <?= ($editEvent["status"] ?? "") === "Closed"
                            ? "selected" : "" ?>>Closed</option>
                </select>
            </div>

            <div>
                <label>Poster (JPG, PNG, WEBP; max 2 MB)</label>
                <input type="file" name="poster"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <?php if (!empty($editEvent["poster"])): ?>
                    <p>Current poster:</p>
                    <img class="poster"
                         src="<?= clean($uploadUrl . basename($editEvent["poster"])) ?>"
                         alt="Current event poster">
                <?php endif; ?>
            </div>

            <div class="full">
                <label>Description</label>
                <textarea name="description" rows="3"><?= clean(
                    $editEvent["description"] ?? ""
                ) ?></textarea>
            </div>
        </div>

        <p>
            <button type="submit">
                <?= $editEvent ? "Update Event" : "Add Event" ?>
            </button>
            <?php if ($editEvent): ?>
                <a class="btn" href="event_management.php">Cancel Edit</a>
            <?php endif; ?>
        </p>
    </form>

    <hr>
    <h2>View Events</h2>

    <form method="GET" class="filters">
        <input type="text" name="search" placeholder="Search title, venue, category"
               value="<?= clean($search) ?>">

        <select name="category">
            <option value="">All Categories</option>
            <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                <option value="<?= clean($cat["category"]) ?>"
                    <?= $filterCategory === $cat["category"]
                        ? "selected" : "" ?>>
                    <?= clean($cat["category"]) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="status">
            <option value="">All Statuses</option>
            <option value="Open" <?= $filterStatus === "Open" ? "selected" : "" ?>>
                Open
            </option>
            <option value="Closed" <?= $filterStatus === "Closed" ? "selected" : "" ?>>
                Closed
            </option>
        </select>

        <button type="submit">Search / Filter</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Poster</th>
            <th>Title</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Category</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($events->num_rows === 0): ?>
            <tr><td colspan="8">No events found.</td></tr>
        <?php else: ?>
            <?php while ($event = $events->fetch_assoc()): ?>
                <tr>
                    <td><?= clean($event["event_id"]) ?></td>
                    <td>
                        <?php if (!empty($event["poster"])): ?>
                            <img class="poster"
                                 src="<?= clean($uploadUrl . basename($event["poster"])) ?>"
                                 alt="Event poster">
                        <?php else: ?>
                            No poster
                        <?php endif; ?>
                    </td>
                    <td><?= clean($event["title"]) ?></td>
                    <td><?= clean($event["event_date"]) ?></td>
                    <td><?= clean($event["venue"]) ?></td>
                    <td><?= clean($event["category"]) ?></td>
                    <td><?= clean($event["status"]) ?></td>
                    <td>
                        <a class="btn"
                           href="event_management.php?edit=<?= (int)$event["event_id"] ?>">
                            Edit
                        </a>

                        <form method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this event?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="event_id"
                                   value="<?= (int)$event["event_id"] ?>">
                            <button type="submit" class="delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
