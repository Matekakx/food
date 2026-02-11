 <?php
// adminlogin.php
session_start();
include "connection.php";

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admindashboard.php");
    exit();
}

$error = "";

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Hardcoded admin credentials (in production, use hashed passwords from database)
    $admin_username = 'admin';
    $admin_password = 'admin@123';

    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_email'] = 'admin@matekakx.com';
        header("Location: admindashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MATEKAKX DELICIOUS </title>
    <link rel="stylesheet" href="adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="login-container">
    <!-- Login Header -->
    <div class="login-header">
        <h1>
            <i class="fas fa-lock"></i> Admin Portal
        </h1>
        <p>Matekakx Delicious Foods - Administrative Dashboard</p>
    </div>

    <!-- Error Message -->
    <?php if ($error): ?>
        <div class="error-message show">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">
                <i class="fas fa-user"></i> Username
            </label>
            <div class="input-group">
                <span class="input-icon">
                    <i class="fas fa-user-shield"></i>
                </span>
<input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
            </div>
        </div>

        <div class="form-group">
            <label for="password">
                <i class="fas fa-lock"></i> Password
            </label>
            <div class="input-group">
                <span class="input-icon">
                    <i class="fas fa-key"></i>
                </span>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password" 
                    required
                    autocomplete="current-password"
                >
            </div>
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login to Dashboard</span>
        </button>
    </form>

    <!-- Demo Info -->
 

    <!-- Login Footer -->
    <div class="login-footer">
        <p>
            <i class="fas fa-lock"></i>
            Design || Developed by Julius Matekele
        </p>
    </div>
</div>

</body>
</html>