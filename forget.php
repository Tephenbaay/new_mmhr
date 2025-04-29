<?php
include 'config.php';

$step = 1; // default step

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["email"])) {
        $email = $_POST["email"];
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $step = 2;
        } else {
            $error = "No account found with that email.";
        }
        $stmt->close();
    } elseif (isset($_POST["new_password"]) && isset($_POST["email_hidden"])) {
        $new_password = password_hash($_POST["new_password"], PASSWORD_BCRYPT);
        $email_hidden = $_POST["email_hidden"];

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email_hidden);

        if ($stmt->execute()) {
            echo "<script>alert('Password reset successful!'); window.location.href='index.php';</script>";
        } else {
            $error = "Failed to update password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Forgot Password</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <form method="POST" action="">
            <label for="email">Enter your email:</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Continue</button>
        </form>

        <?php elseif ($step === 2): ?>
        <form method="POST" action="">
            <input type="hidden" name="email_hidden" value="<?php echo htmlspecialchars($email); ?>">
            <label for="new_password">Enter new password:</label>
            <input type="password" id="new_password" name="new_password" required>
            <button type="submit">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
