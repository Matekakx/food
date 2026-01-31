 <?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $confirmpassword  = trim($_POST['confirm_password'] ?? '');

    //check password if match
    
    if ($password != $confirm_password) {
        echo "<script> alert('password do not match'); </script>";
    }

    
        // Check if email exists
        $check = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email already exists";
            $check->close();
        } else {

            $check->close();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO customers (full_name, email, phone_number, password)
                 VALUES (?, ?, ?, ?)"
            );

            if (!$stmt) {
                $message = "Prepare failed: " . $conn->error;
            } else {

                $stmt->bind_param("ssss", $full_name, $email, $phone_number, $hashedPassword);

                if ($stmt->execute()) {
                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $message = "Insert error: " . $stmt->error;
                }

                $stmt->close();
            }
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
            let full_name= document.getElementById("full_name").value;
            let email= document.getElementById("email").value;
            let phone_number= document.getElementById("phone_number").value;
            let password= document.getElementById("password").value;
            let confirm_password= document.getElementById("confirm_password").value;
        }

    
        if (full_name ==) {
            alert("Full name must be filled out");
            return false;
        }

        if (email ==) {
            alert("email must be filled out");
            return false;
        }

        if (!email.includes("@"))  {
            alert("Please enter a valid email");
            return false;
        }

        if (password.lenght <8) {
            alert("Password musgt be atleast 8 characters");
            return false;

            alert("Form submitted successfully!");
            return true;
        }
</script>
</head>
<body>

<div class="form-container">

    <h2>Customer Registration</h2>

      
    <form method="POST" action="">
        <input type="text" name="full_name" placeholder="Full Name" required>

        <input type="email" name="email" placeholder="Email" required>

        <input type="text" name="phone_number" placeholder="Phone Number" required>

        <input type="password" name="password" placeholder="Password" required>

        <input type="password" name="confirm_Password" placeholder="confirm Password" required>

        <button type="submit" name ="register" value="Register"></button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>

</div>

</body>
</html>
