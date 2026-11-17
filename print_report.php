<?php
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'books';

$total_books = $conn->query("SELECT SUM(total_copies) as total FROM books")->fetch_assoc()['total'] ?? 0;
$total_borrowed = $conn->query("SELECT COUNT(*) as total FROM borrow_requests WHERE status = 'Approved'")->fetch_assoc()['total'] ?? 0;
$total_returned = $conn->query("SELECT COUNT(*) as total FROM borrow_requests WHERE status = 'Returned'")->fetch_assoc()['total'] ?? 0;
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND is_approved = 1")->fetch_assoc()['total'] ?? 0;

if ($type == 'borrowed') {
    $report_data = $conn->query("
        SELECT br.*, u.fullname, u.student_id, b.title, b.isbn 
        FROM borrow_requests br 
        JOIN users u ON br.user_id = u.id 
        JOIN books b ON br.book_id = b.id 
        WHERE br.status = 'Approved' 
        ORDER BY br.id DESC
    ");
} elseif ($type == 'returned') {
    $report_data = $conn->query("
        SELECT br.*, u.fullname, u.student_id, b.title, b.isbn 
        FROM borrow_requests br 
        JOIN users u ON br.user_id = u.id 
        JOIN books b ON br.book_id = b.id 
        WHERE br.status = 'Returned' 
        ORDER BY br.id DESC
    ");
} else {
    $report_data = $conn->query("SELECT * FROM books ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Inventory & Transaction Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="body-white">

<div class="navbar">
    <h2>🖨️ Report Module</h2>
    <div>
        <a href="index.php" class="text-yellow">Back to Dashboard</a>
    </div>
</div>

<div class="main-container">
    <div class="filter-card">
        <form action="" method="GET" class="filter-form">
            <label class="form-label">Select Report Type:</label>
            <select name="type" class="select-input" onchange="this.form.submit()">
                <option value="books" <?php if($type == 'books') echo 'selected'; ?>>Book Inventory List</option>
                <option value="borrowed" <?php if($type == 'borrowed') echo 'selected'; ?>>Currently Borrowed Books</option>
                <option value="returned" <?php if($type == 'returned') echo 'selected'; ?>>Returned Books Log</option>
                <option value="overall" <?php if($type == 'overall') echo 'selected'; ?>>Overall Summary Statistics</option>
            </select>
            <button type="button" onclick="window.print()" class="btn-sm btn-success print-btn">🖨️ Print / Save PDF</button>
        </form>
    </div>

    <div class="print-header">
        <h2>📚 College Library</h2>
        <p>
            <?php 
                if($type == 'borrowed') echo "Currently Borrowed Books Report";
                elseif($type == 'returned') echo "Returned Books History Report";
                elseif($type == 'overall') echo "Overall Library System Summary";
                else echo "Complete Book Inventory Report";
            ?>
        </p>
        <p class="report-date">Date Generated: <?php echo date('F d, Y - h:i A'); ?></p>
    </div>

    <?php if($type == 'overall'): ?>
        <div class="summary-grid">
            <div class="summary-box">
                <h4>Total Book Copies</h4>
                <p><?php echo $total_books; ?></p>
            </div>
            <div class="summary-box">
                <h4>Active Borrowed Books</h4>
                <p><?php echo $total_borrowed; ?></p>
            </div>
            <div class="summary-box">
                <h4>Total Returned Books</h4>
                <p><?php echo $total_returned; ?></p>
            </div>
            <div class="summary-box">
                <h4>Approved Students</h4>
                <p><?php echo $total_students; ?></p>
            </div>
        </div>
    <?php elseif($type == 'borrowed' || $type == 'returned'): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>ID</th>
                    <th>Book Title</th>
                    <th>Borrow Date</th>
                    <th><?php echo ($type == 'borrowed') ? 'Due Date' : 'Return Date'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if($report_data->num_rows > 0): ?>
                    <?php while($row = $report_data->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo $row['borrow_date']; ?></td>
                        <td><?php echo ($type == 'borrowed') ? $row['due_date'] : $row['return_date']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No records found for the selected report type.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Available / Total Copies</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $report_data->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo $row['available_copies']; ?> / <?php echo $row['total_copies']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>