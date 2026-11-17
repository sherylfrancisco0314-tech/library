<?php
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Student Registration Approvals with Custom Limit
if (isset($_POST['approve_user'])) {
    $u_id = $_POST['user_id'];
    $borrow_limit = intval($_POST['borrow_limit']);

    if ($borrow_limit > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_approved = 1, borrow_limit = ? WHERE id = ?");
        $stmt->bind_param("ii", $borrow_limit, $u_id);
        $stmt->execute();
    }
    header("Location: requests.php");
    exit();
}

if (isset($_GET['user_action']) && isset($_GET['user_id'])) {
    $u_action = $_GET['user_action'];
    $u_id = $_GET['user_id'];

    if ($u_action == 'reject_user') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $u_id);
        $stmt->execute();
    }
    header("Location: requests.php");
    exit();
}

// Borrow Request Approvals with Custom Due Date
if (isset($_POST['approve_request'])) {
    $req_id = $_POST['req_id'];
    $due_date = $_POST['due_date'];

    if (!empty($due_date)) {
        $q = $conn->prepare("SELECT book_id FROM borrow_requests WHERE id = ?");
        $q->bind_param("i", $req_id);
        $q->execute();
        $book = $q->get_result()->fetch_assoc();
        $book_id = $book['book_id'];

        $borrow_date = date('Y-m-d');

        $stmt = $conn->prepare("UPDATE borrow_requests SET status = 'Approved', borrow_date = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $borrow_date, $due_date, $req_id);
        $stmt->execute();

        $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE id = $book_id");
    }
    header("Location: requests.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $req_id = $_GET['id'];

    if ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE borrow_requests SET status = 'Rejected' WHERE id = ?");
        $stmt->bind_param("i", $req_id);
        $stmt->execute();

    } elseif ($action == 'return') {
        $q = $conn->prepare("SELECT book_id FROM borrow_requests WHERE id = ?");
        $q->bind_param("i", $req_id);
        $q->execute();
        $book = $q->get_result()->fetch_assoc();
        $book_id = $book['book_id'];

        $return_date = date('Y-m-d');
        $stmt = $conn->prepare("UPDATE borrow_requests SET status = 'Returned', return_date = ? WHERE id = ?");
        $stmt->bind_param("si", $return_date, $req_id);
        $stmt->execute();

        $conn->query("UPDATE books SET available_copies = available_copies + 1 WHERE id = $book_id");
    }

    header("Location: requests.php");
    exit();
}

$pending_users = $conn->query("SELECT * FROM users WHERE role = 'student' AND is_approved = 0 ORDER BY id DESC");

$requests = $conn->query("
    SELECT br.*, u.fullname, u.student_id, b.title 
    FROM borrow_requests br 
    JOIN users u ON br.user_id = u.id 
    JOIN books b ON br.book_id = b.id 
    ORDER BY br.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Requests & Approvals - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <h2>🛠️ Admin Approvals</h2>
    <div>
        <a href="index.php" class="text-yellow">Manage Books</a>
        <a href="requests.php" class="text-yellow ml-15">Borrow Requests & Users</a>
        <a href="print_report.php" class="text-yellow ml-15">Print Report</a>
        <a href="../logout.php" class="text-red ml-15">Logout</a>
    </div>
</div>

<div class="main-container">
    <h3>👤 Pending Student Registrations</h3>
    <table class="data-table mb-30">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Registration Date</th>
                <th>Action & Set Borrow Limit</th>
            </tr>
        </thead>
        <tbody>
            <?php if($pending_users->num_rows > 0): ?>
                <?php while($user = $pending_users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo $user['created_at']; ?></td>
                    <td>
                        <form action="" method="POST" class="inline-form">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <label style="font-size: 13px; font-weight: bold;">Limit:</label>
                            <input type="number" name="borrow_limit" class="input-limit" min="1" value="3" required>
                            <button type="submit" name="approve_user" class="btn-sm btn-approve btn-inline">Approve Account</button>
                        </form>
                        <a href="requests.php?user_action=reject_user&user_id=<?php echo $user['id']; ?>" class="btn-sm btn-reject">Reject</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No pending student registrations.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>📋 Student Book Requests & Transactions</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Book Title</th>
                <th>Request Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['request_date']; ?></td>
                <td><?php echo $row['due_date'] ? $row['due_date'] : 'N/A'; ?></td>
                <td>
                    <?php 
                        $status = $row['status'];
                        $class = '';
                        if($status == 'Pending') $class = 'status-pending';
                        elseif($status == 'Approved') $class = 'status-approved';
                        elseif($status == 'Rejected') $class = 'status-rejected';
                        elseif($status == 'Returned') $class = 'status-returned';
                    ?>
                    <span class="<?php echo $class; ?>"><?php echo $status; ?></span>
                </td>
                <td>
                    <?php if($status == 'Pending'): ?>
                        <form action="" method="POST" class="inline-form">
                            <input type="hidden" name="req_id" value="<?php echo $row['id']; ?>">
                            <input type="date" name="due_date" class="date-input" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                            <button type="submit" name="approve_request" class="btn-sm btn-approve btn-inline">Approve</button>
                        </form>
                        <a href="requests.php?action=reject&id=<?php echo $row['id']; ?>" class="btn-sm btn-reject">Reject</a>
                    <?php elseif($status == 'Approved'): ?>
                        <a href="requests.php?action=return&id=<?php echo $row['id']; ?>" class="btn-sm btn-return">Mark as Returned</a>
                    <?php else: ?>
                        <span class="text-muted">Completed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>