<?php
include 'db.php';
$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['password']) {
            if ($row['role'] === 'student' && $row['is_approved'] == 0) {
                $error = "Your account has not been approved by the Admin yet. You can check your status using your Student ID.";
            } else {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role'] = $row['role'];

                if ($row['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: student/index.php");
                }
                exit();
            }
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Username not registered!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Library System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>📚 Library Login</h2>
        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
        <form action="" method="POST">
            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" name="login" class="btn-primary">Login</button>
        </form>
        <div class="link-text">
            Don't have an account? <a href="register.php">Register</a><br>
            <a href="check_status.php">🔍 Check Approval Status</a>
        </div>
    </div>
</div>

</body>
</html>