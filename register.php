 <?php
session_start();
include "connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $gender   = $_POST['gender'];
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    }
    // Validate password length
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }
    // Validate fullname
    elseif (empty($fullname)) {
        $error = "Full name is required!";
    }
    // Validate gender
    elseif (empty($gender)) {
        $error = "Please select a gender!";
    }
    else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO customers (fullname, email, gender, phone, address, password)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssss", $fullname, $email, $gender, $phone, $address, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                // Check for duplicate email error
                if (strpos($stmt->error, 'Duplicate entry') !== false) {
                    $error = "Email already exists!";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Customer</title>
    <link rel="stylesheet" href="customerstyle.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Create Account</h1>
        <p>Register as a new customer</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Full Name Field -->
        <div class="form-group">
            <label for="fullname">
                <i class="fas fa-user"></i> Full Name
            </label>
            <div class="input-group">
                <span class="input-icon">
                    
                </span>
                <input 
                    type="text" 
                    id="fullname"
                    name="fullname" 
                    placeholder="Enter your full name" 
                    required
                    autocomplete="name"
                    value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>"
                >
            </div>
        </div>

        <!-- Email Field -->
        <div class="form-group">
            <label for="email">
                <i class="fas fa-envelope"></i> Email Address
            </label>
            <div class="input-group">
                <span class="input-icon">
                    
                </span>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    placeholder="Enter your email" 
                    required
                    autocomplete="email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>
        </div>

        <!-- Gender Field -->
        <div class="form-group">
            <label for="gender">
                <i class="fas fa-venus-mars"></i> Gender
            </label>
            <div class="input-group">
                <span class="input-icon">
                     
                </span>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <!-- Phone Field -->
        <div class="form-group">
            <label for="phone">
                <i class="fas fa-phone"></i> Phone Number
            </label>
            <div class="input-group">
                <span class="input-icon">
                   
                </span>
                <input 
                    type="tel" 
                    id="phone"
                    name="phone" 
                    placeholder="Enter your phone number"
                    autocomplete="tel"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                >
            </div>
        </div>

        <!-- Address Field -->
        <div class="form-group">
            <label for="address">
                <i class="fas fa-map-marker-alt"></i> Address
            </label>
            <div class="input-group">
                <span class="input-icon">
                   
                </span>
                <input 
                    type="text" 
                    id="address"
                    name="address" 
                    placeholder="Enter your address"
                    autocomplete="street-address"
                    value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"
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
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="Enter a secure password (min. 8 characters)" 
                    required
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>
        </div>

        <!-- Confirm Password Field -->
        <div class="form-group">
            <label for="confirm_password">
                <i class="fas fa-lock"></i> Confirm Password
            </label>
            <div class="input-group">
                <span class="input-icon">
                     
                </span>
                <input 
                    type="password" 
                    id="confirm_password"
                    name="confirm_password" 
                    placeholder="Re-enter your password" 
                    required
                    autocomplete="new-password"
                    minlength="6"
                >
            </div>
        </div>

        <button type="submit">
            <i class="fas fa-user-check"></i> Register
        </button>
    </form>

    <div class="toggle-form">
        <p>Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></p>
    </div>
</div>

</body>
</html>