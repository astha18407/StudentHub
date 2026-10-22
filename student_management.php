
<?php
session_start();
require_once "db_mysqli.php";
require_once "audit_log.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Please log in with an admin account.");
}

$message = "";
$messageType = "success";

// Delete student
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"])) {
    $id = filter_var($_POST["delete_id"], FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            logAction("DELETE STUDENT", "Deleted student ID: " . $id);
            $message = "Student deleted successfully.";
        } else {
            $message = "Student not found or already deleted.";
            $messageType = "error";
        }

        $stmt->close();
    } else {
        $message = "Invalid student ID.";
        $messageType = "error";
    }
}

// Add or update student
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_student"])) {
    $id = filter_var($_POST["student_id"] ?? "0", FILTER_VALIDATE_INT);
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");

    if (
        $id === false || $id < 0 ||
        $name === "" || strlen($name) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 100 ||
        !preg_match('/^[0-9]{10}$/', $mobile) ||
        $course === "" || strlen($course) > 100
    ) {
        $message = "Enter valid details. Mobile must contain 10 digits.";
        $messageType = "error";
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare(
                "UPDATE students SET name=?, email=?, mobile=?, course=? WHERE student_id=?"
            );

            $stmt->bind_param("ssssi", $name, $email, $mobile, $course, $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows >= 0) {
                    logAction("UPDATE STUDENT", "Updated student ID: " . $id);
                    $message = "Student updated successfully.";
                } else {
                    $message = "Could not update student.";
                    $messageType = "error";
                }
            } else {
                $message = "Could not update student.";
                $messageType = "error";
            }
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO students (name, email, mobile, course) VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param("ssss", $name, $email, $mobile, $course);

            if ($stmt->execute()) {
                $newId = $conn->insert_id;

                logAction("ADD STUDENT", "Added student ID: " . $newId);

                $message = "Student added successfully.";
            } else {
                $message = "Could not add student. Check whether the email already exists.";
                $messageType = "error";
            }
        }

        $stmt->close();
    }
}

// Search and filter
$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");

$sql = "SELECT student_id, name, email, mobile, course FROM students WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR course LIKE ?)";
    $term = "%" . $search . "%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;

    $types .= "sss";
}

if ($courseFilter !== "") {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
    $types .= "s";
}

$sql .= " ORDER BY student_id DESC";

$stmt = $conn->prepare($sql);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$courseResult = $conn->query(
    "SELECT DISTINCT course FROM students ORDER BY course"
);

// Load student for editing
$editStudent = null;

if (isset($_GET["edit"])) {
    $editId = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        $editStmt = $conn->prepare(
            "SELECT student_id, name, email, mobile, course FROM students WHERE student_id=?"
        );

        $editStmt->bind_param("i", $editId);
        $editStmt->execute();

        $editStudent = $editStmt->get_result()->fetch_assoc();

        $editStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Management - StudentHub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f5fa;
            margin: 0;
            padding: 25px;
            color: #222;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1, h2 {
            color: #183b70;
        }

        .panel {
            background: white;
            padding: 20px;
            margin-bottom: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000012;
        }

        input, select, button, .button {
            padding: 10px;
            margin: 5px 3px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        button, .button {
            background: #1769aa;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .delete {
            background: #c62828;
        }

        .edit {
            background: #25804b;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .success {
            background: #d9f5df;
            color: #155724;
        }

        .error {
            background: #ffe0e0;
            color: #8b0000;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #183b70;
            color: white;
        }

        tr:nth-child(even) {
            background: #f7f9fc;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Student Management</h1>
    <p>Add, view, edit, delete, search and filter student records.</p>

    <?php if ($message !== ""): ?>
        <div class="message <?= $messageType === "error" ? "error" : "success" ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2><?= $editStudent ? "Edit Student" : "Add New Student" ?></h2>

        <form method="POST" action="student_management.php">
            <input type="hidden" name="student_id"
                   value="<?= htmlspecialchars((string)($editStudent["student_id"] ?? "0")) ?>">

            <input type="text" name="name" placeholder="Student Name"
                   maxlength="100" required
                   value="<?= htmlspecialchars($editStudent["name"] ?? "") ?>">

            <input type="email" name="email" placeholder="Email"
                   maxlength="100" required
                   value="<?= htmlspecialchars($editStudent["email"] ?? "") ?>">

            <input type="tel" name="mobile" placeholder="10-digit Mobile"
                   pattern="[0-9]{10}" maxlength="10" required
                   value="<?= htmlspecialchars($editStudent["mobile"] ?? "") ?>">

            <input type="text" name="course" placeholder="Course (e.g. CSE)"
                   maxlength="100" required
                   value="<?= htmlspecialchars($editStudent["course"] ?? "") ?>">

            <button type="submit" name="save_student">
                <?= $editStudent ? "Update Student" : "Add Student" ?>
            </button>

            <?php if ($editStudent): ?>
                <a class="button" href="student_management.php">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <h2>Search and Filter Students</h2>

        <form method="GET" action="student_management.php">
            <input type="text" name="search"
                   placeholder="Search name, email or course"
                   value="<?= htmlspecialchars($search) ?>">

            <select name="course">
                <option value="">All Courses</option>

                <?php while ($c = $courseResult->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c["course"]) ?>"
                        <?= $courseFilter === $c["course"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($c["course"]) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Search / Filter</button>

            <a class="button" href="student_management.php">Reset</a>
        </form>
    </div>

    <div class="panel">
        <h2>Student Records (<?= $result->num_rows ?>)</h2>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Course</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                <?php if ($result->num_rows > 0): ?>

                    <?php while ($student = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$student["student_id"] ?></td>
                            <td><?= htmlspecialchars($student["name"]) ?></td>
                            <td><?= htmlspecialchars($student["email"]) ?></td>
                            <td><?= htmlspecialchars($student["mobile"]) ?></td>
                            <td><?= htmlspecialchars($student["course"]) ?></td>

                            <td>
                                <a class="button edit"
                                   href="student_management.php?edit=<?= (int)$student["student_id"] ?>">
                                    Edit
                                </a>

                                <form method="POST" action="student_management.php"
                                      style="display:inline"
                                      onsubmit="return confirm('Are you sure you want to delete this student?');">

                                    <input type="hidden" name="delete_id"
                                           value="<?= (int)$student["student_id"] ?>">

                                    <button type="submit" class="delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6">No student records found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>

</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
