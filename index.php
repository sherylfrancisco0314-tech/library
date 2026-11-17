<?php
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = '';

// Update System Settings (Max Borrow Limit)
if (isset($_POST['update_limit'])) {
    $new_limit = intval($_POST['max_borrow_limit']);
    if ($new_limit > 0) {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'max_borrow_limit'");
        $stmt->bind_param("s", $new_limit);
        $stmt->execute();
        $msg = "Max borrow limit updated successfully!";
    }
}

// Add New Book
if (isset($_POST['add_book'])) {
    $isbn = trim($_POST['isbn']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    $copies = intval($_POST['total_copies']);

    $stmt = $conn->prepare("INSERT INTO books (isbn, title, author, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $isbn, $title, $author, $category, $copies, $copies);
    if ($stmt->execute()) {
        $msg = "Book added successfully!";
    } else {
        $msg = "An error occurred while adding the book.";
    }
}

// Get current max borrow limit
$limit_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'max_borrow_limit'");
$current_limit = $limit_query->fetch_assoc()['setting_value'] ?? 3;

// Search Logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR category LIKE ? OR isbn LIKE ? ORDER BY id DESC");
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
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
    <title>Admin Dashboard - Library System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    <h2>🛠️ Admin Dashboard</h2>
    <div>
        <a href="index.php" class="text-yellow">Manage Books</a>
        <a href="requests.php" class="text-yellow ml-15">Borrow Requests & Users</a>
        <a href="print_report.php" class="text-yellow ml-15">Print Report</a>
        <a href="../logout.php" class="text-red ml-15">Logout</a>
    </div>
</div>

<div class="main-container">
    <?php if(!empty($msg)) echo "<div class='alert-success'>$msg</div>"; ?>

    <div class="admin-wrapper">
        <div class="admin-card flex-1">
            <!-- Set Borrow Limit Form -->
            <h3>⚙️ System Settings</h3>
            <form action="" method="POST" class="mb-30">
                <div class="form-group">
                    <label>Max Books Per Student</label>
                    <input type="number" name="max_borrow_limit" class="form-control" min="1" value="<?php echo htmlspecialchars($current_limit); ?>" required>
                </div>
                <button type="submit" name="update_limit" class="btn-sm btn-primary btn-block">Save Limit</button>
            </form>

            <hr class="mb-20">

            <!-- Add Book Form -->
            <h3>Add New Book</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Total Copies</label>
                    <input type="number" name="total_copies" class="form-control" min="1" required>
                </div>

                <button type="submit" name="add_book" class="btn-sm btn-success btn-block">Add Book</button>
            </form>
        </div>

        <div class="admin-card flex-2">
            <h3>Book Inventory</h3>

            <form action="" method="GET" class="search-form mt-15">
                <input type="text" name="search" class="search-input" placeholder="Search title, author, category, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
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
                        <th>Available / Total</th>
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
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No books found in inventory.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>