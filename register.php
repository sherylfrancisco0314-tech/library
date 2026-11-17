<?php
include 'db.php';
$msg = '';

if (isset($_POST['register'])) {
    $student_id = trim($_POST['student_id']);
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = 'student';
    $is_approved = 0;

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $msg = "Username is already taken!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (student_id, fullname, username, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $student_id, $fullname, $username, $password, $role, $is_approved);
        if ($stmt->execute()) {
            $msg = "Registration successful! Please wait for Admin approval before logging in.";
        } else {
            $msg = "An error occurred during registration.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>🎓 Student Sign Up</h2>
        <?php if(!empty($msg)) echo "<div class='error-msg'>$msg</div>"; ?>
        <form action="" method="POST">
            <label>Student ID</label>
            <input type="text" name="student_id" required placeholder="e.g. 2026-0001">

            <label>Full Name</label>
            <input type="text" name="fullname" required placeholder="Juan Dela Cruz">

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" name="register" class="btn-success">Register Account</button>
        </form>
        <div class="link-text">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

</body>
</html>