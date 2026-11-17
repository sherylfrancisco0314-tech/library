<?php
include 'db.php';

$search_id = '';
$user_data = null;
$searched = false;

if (isset($_GET['student_id'])) {
    $search_id = trim($_GET['student_id']);
    if (!empty($search_id)) {
        $stmt = $conn->prepare("SELECT fullname, student_id, is_approved FROM users WHERE student_id = ? AND role = 'student'");
        $stmt->bind_param("s", $search_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $searched = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Account Status - Library System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>🔍 Account Status</h2>
        <form action="" method="GET">
            <label>Enter Student ID</label>
            <input type="text" name="student_id" required placeholder="e.g. 2026-0001" value="<?php echo htmlspecialchars($search_id); ?>">
            <button type="submit" class="btn-primary">Check Status</button>
        </form>

        <?php if ($searched): ?>
            <?php if ($user_data): ?>
                <?php if ($user_data['is_approved'] == 1): ?>
                    <div class="status-box approved">
                        🟢 Account is approved, <?php echo htmlspecialchars($user_data['fullname']); ?>!<br>
                        <a href="login.php" class="text-yellow">Login here now</a>
                    </div>
                <?php else: ?>
                    <div class="status-box pending">
                        🟡 Hi <?php echo htmlspecialchars($user_data['fullname']); ?>, your account is currently pending Admin approval. Please check again later.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="status-box not-found">
                    🔴 No account found for this Student ID or registration was rejected.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="link-text">
            <a href="login.php">Back to Login</a> | <a href="register.php">Register</a>
        </div>
    </div>
</div>

</body>
</html>