<?php
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';

// Get individual user's specific borrow limit set by Admin upon account approval
$user_limit_query = $conn->prepare("SELECT borrow_limit FROM users WHERE id = ?");
$user_limit_query->bind_param("i", $user_id);
$user_limit_query->execute();
$max_limit = intval($user_limit_query->get_result()->fetch_assoc()['borrow_limit'] ?? 3);

if (isset($_POST['request_borrow'])) {
    $book_id = $_POST['book_id'];

    $limit_check = $conn->prepare("SELECT COUNT(*) as active_count FROM borrow_requests WHERE user_id = ? AND status IN ('Pending', 'Approved')");
    $limit_check->bind_param("i", $user_id);
    $limit_check->execute();
    $active_count = $limit_check->get_result()->fetch_assoc()['active_count'];

    if ($active_count >= $max_limit) {
        $msg = "Borrow limit reached! You can only have a maximum of " . $max_limit . " active or pending book requests based on your account settings. Please return a book first.";
    } else {
        $check_stock = $conn->prepare("SELECT available_copies FROM books WHERE id = ?");
        $check_stock->bind_param("i", $book_id);
        $check_stock->execute();
        $res = $check_stock->get_result()->fetch_assoc();

        if ($res && $res['available_copies'] > 0) {
            $stmt = $conn->prepare("INSERT INTO borrow_requests (user_id, book_id, status) VALUES (?, ?, 'Pending')");
            $stmt->bind_param("ii", $user_id, $book_id);
            if ($stmt->execute()) {
                $msg = "Borrow request successfully submitted to Admin!";
            } else {
                $msg = "An error occurred while sending your request.";
            }
        } else {
            $msg = "Sorry, no copies available for this book.";
        }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR category LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query("SELECT * FROM books ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - Library Catalog</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <h2>🎓 Student Dashboard</h2>
    <div>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</span> 
        <a href="my_requests.php" class="text-yellow">My Requests</a> 
        <a href="../logout.php" class="text-red">Logout</a>
    </div>
</div>

<div class="main-container">
    <h3>📚 Available Book Catalog (Your Borrow Limit: <?php echo $max_limit; ?>)</h3>
    <?php if(!empty($msg)) echo "<div class='alert-success'>$msg</div>"; ?>

    <form action="" method="GET" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="Search by Title, Author, or Category..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-search">Search</button>
        <?php if(!empty($search)): ?>
            <a href="index.php" class="btn-sm reset-btn">Reset</a>
        <?php endif; ?>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>ISBN</th>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Available Copies</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($books->num_rows > 0): ?>
                <?php while($row = $books->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo $row['available_copies']; ?> / <?php echo $row['total_copies']; ?></td>
                    <td>
                        <?php if($row['available_copies'] > 0): ?>
                            <form action="" method="POST">
                                <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="request_borrow" class="btn-sm btn-success">Request Borrow</button>
                            </form>
                        <?php else: ?>
                            <span class="out-of-stock">Out of Stock</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No books found matching your criteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>