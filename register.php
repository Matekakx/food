 <?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name    = trim($_POST['full_name']);
    $email        = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password     = trim($_POST['password']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO customers (full_name, email, phone_number, password)
         VALUES (?, ?, ?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param("ssss", $full_name, $email, $phone_number, $hashedPassword);
        $stmt->execute();
        $stmt->close();
        header("Location: login.php?registered=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
    <link rel="stylesheet" href="style.css">

    <script>
    function validateForm() {

        let full_name = document.getElementById("full_name").value.trim();
        let email = document.getElementById("email").value.trim();
        let phoneInput = document.getElementById("phone_number");
        let phone_number = phoneInput.value.trim();
        let password = document.getElementById("password").value;
        let confirm_password = document.getElementById("confirm_password").value;

        // Regex patterns
        let tzWithCode = /^\+255[67]\d{8}$/; // +2556xxxxxxxx or +2557xxxxxxxx
        let tzLocal = /^0[67]\d{8}$/;        // 06xxxxxxxx or 07xxxxxxxx

        if (full_name === "") {
            alert("Full name is required");
            return false;
        }

        if (email === "" || !email.includes("@")) {
            alert("Enter a valid email address");
            return false;
        }

        // Phone number validation + normalization
        if (tzLocal.test(phone_number)) {
            // Convert 06/07 to +255
            phone_number = "+255" + phone_number.substring(1);
            phoneInput.value = phone_number;
        } else if (!tzWithCode.test(phone_number)) {
            alert("Phone number must be 06XXXXXXXX, 07XXXXXXXX or +2556/7XXXXXXXX");
            return false;
        }

        if (password.length < 8) {
            alert("Password must be at least 8 characters");
            return false;
        }

        if (password !== confirm_password) {
            alert("Passwords do not match");
            return false;
        }

        return true; // allow submit
    }
    </script>
</head>

<body>

<div class="form-container">

    <h2>Customer Registration</h2>

    <form method="POST" action="" onsubmit="return validateForm();">

        <input type="text" id="full_name" name="full_name"
               placeholder="Full Name">

        <input type="email" id="email" name="email"
               placeholder="Email">

        <input type="text" id="phone_number" name="phone_number"
               placeholder="Phone Number (06/07 or +255...)">

        <input type="password" id="password" name="password"
               placeholder="Password">

        <input type="password" id="confirm_password"
               placeholder="Confirm Password">

        <button type="submit">Register</button>

    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>

</div>

</body>
</html>
