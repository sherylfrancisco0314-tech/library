<?php
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$requests = $conn->prepare("
    SELECT br.*, b.title, b.author 
    FROM borrow_requests br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = ? 
    ORDER BY br.id DESC
");
$requests->bind_param("i", $user_id);
$requests->execute();
$result = $requests->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Borrow Requests</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <h2>🎓 My Borrow Requests</h2>
    <div>
        <a href="index.php" class="text-yellow">Back to Catalog</a>
        <a href="../logout.php" class="text-red">Logout</a>
    </div>
</div>

<div class="main-container">
    <h3>📋 Status of My Requests</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Book Title</th>
                <th>Author</th>
                <th>Request Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['author']); ?></td>
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
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>