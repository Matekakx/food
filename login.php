 <?php
session_start();
include "db.php";

$message = "";
$successMessage = "";

/* Show registration success message */
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $successMessage = "You have registered successfully. Please login.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $message = "Email and Password are required";
    } else {

        $stmt = $conn->prepare(
            "SELECT customer_id, full_name, password
             FROM customers
             WHERE email = ?"
        );

        if (!$stmt) {
            $message = "Prepare failed: " . $conn->error;
        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {

                if (password_verify($password, $user['password'])) {

                    $_SESSION['customer_id']   = $user['customer_id'];
                    $_SESSION['customer_name'] = $user['full_name'];

                    header("Location: customerdashboard.php");
                    exit;

                } else {
                    $message = "Invalid password";
                }

            } else {
                $message = "User not found";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-container">

    <h2>User Login</h2>

    <?php if (!empty($successMessage)): ?>
        <div class="message-success">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="message-error">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>
    </form>

    <p>New user? <a href="register.php">Register</a></p>

</div>

</body>
</html>
