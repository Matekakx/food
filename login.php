 <?php
session_start();
include "connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM customers WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['logged_in'] = true;
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['fullname'];
            $_SESSION['customer_email'] = $user['email'];

            header("Location: customerdashboard.php");
            exit();
        } else {
            $error = "Wrong password.";
        }
    } else {
        $error = "Account not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Login</title>
    <link rel="stylesheet" href="customerstyle.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Welcome Back</h1>
        <p>Login to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Email Field -->
        <div class="form-group">
            <label for="email">
                <i class="fas fa-envelope"></i> Email Address
            </label>
            <div class="input-group">
                <span class="input-icon">
                     
                </span>
                <input 
                    type="email" id="email"name="email" placeholder="Enter your email" required autocomplete="email"
                >
            </div>
        </div>

        <!-- Password Field -->
        <div class="form-group">
            <label for="password">
                <i class="fas fa-lock"></i> Password
            </label>
            <div class="input-group">
                <span class="input-icon">
                    
                </span>
                <input type="password" id="password"name="password" placeholder="Enter your password" required
                    autocomplete="current-password"
                >
            </div>
        </div>

        <button type="submit">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="toggle-form">
        <p>Don't have an account? <a href="register.php"><i class="fas fa-user-plus"></i> Register</a></p>
    </div>
</div>

</body>
</html>