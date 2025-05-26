<?php
session_start();

// Database connection settings
$servername = "127.0.0.1";
$username = "root";
$password = ""; // Change if your MySQL root password is set
$dbname = "demoapp";

$error = "";
$reg_error = "";
$reg_success = "";

// Set the admin username, password, and admin code
$admin_username = "adminuser";
$admin_password = "adminpass";
$expected_admin_code = "ADMIN1234";

// LOGIN logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user = trim($_POST["username"] ?? "");
    $pass = trim($_POST["password"] ?? "");
    $admin_code = trim($_POST["admin_code"] ?? "");

    if (empty($user) || empty($pass)) {
        $error = "Username and password are required.";
    } else {
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // If admin login attempt
            if ($user === $admin_username) {
                if ($pass === $admin_password) {
                    if ($admin_code !== $expected_admin_code) {
                        $error = "Invalid admin code.";
                    } else {
                        $_SESSION['username'] = $user;
                        $_SESSION['role'] = 'admin';
                        header("Location: admindash.php");
                        exit;
                    }
                } else {
                    $error = "Invalid admin password.";
                }
            } else {
                // Regular user login
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $user);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check password (assuming plain text; use password_hash in production)
                if ($row && $row['password'] === $pass) {
                    $_SESSION['username'] = $user;
                    $_SESSION['role'] = $row['role'];
                    header("Location: welcome.php");
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            }
        } catch(PDOException $e) {
            $error = "Connection failed: " . $e->getMessage();
        }
    }
}

// REGISTRATION logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $reg_user = trim($_POST["reg_username"] ?? "");
    $reg_pass = trim($_POST["reg_password"] ?? "");
    $reg_email = trim($_POST["reg_email"] ?? "");

    if (empty($reg_user) || empty($reg_pass) || empty($reg_email)) {
        $reg_error = "Username, password, and email are required for registration.";
    } elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
        $reg_error = "Please enter a valid email address.";
    } else {
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username or email already exists in users or pendUser
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $reg_user, ':email' => $reg_email]);
            $exists_users = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM pendUser WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $reg_user, ':email' => $reg_email]);
            $exists_pend = $stmt->fetchColumn();

            if ($exists_users > 0 || $exists_pend > 0) {
                $reg_error = "Username or email already exists or is pending approval.";
            } else {
                // Insert into pendUser (store password as plain text for demo; use password_hash in production)
                $stmt = $conn->prepare("INSERT INTO pendUser (username, password, email) VALUES (:username, :password, :email)");
                $stmt->execute([
                    ':username' => $reg_user,
                    ':password' => $reg_pass,
                    ':email' => $reg_email
                ]);
                $reg_success = "Registration submitted! Please wait for admin approval.";
            }
        } catch(PDOException $e) {
            $reg_error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup Form</title>
    <link rel="stylesheet" href="SignUp_LogIn_Form.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="container<?php if (isset($_POST['register']) && !$reg_error) { echo ' active'; } ?>">
    <!-- Login Form -->
    <div class="form-box login">
        <form method="post" action="">
            <h1>Login</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="input-box">
                <input type="text" name="username" id="username" placeholder="Username" required onkeyup="toggleAdminCode()">
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <div class="input-box" id="admin_code_field" style="display:none;">
                <input type="text" name="admin_code" id="admin_code" placeholder="Admin Code">
                <i class='bx bxs-key'></i>
            </div>
            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>

    <!-- Registration Form -->
    <div class="form-box register">
        <form method="post" action="">
            <h1>Registration</h1>
            <?php if ($reg_error): ?>
                <div class="error-message"><?php echo htmlspecialchars($reg_error); ?></div>
            <?php elseif ($reg_success): ?>
                <div class="success-message"><?php echo htmlspecialchars($reg_success); ?></div>
            <?php endif; ?>
            <div class="input-box">
                <input type="text" name="reg_username" id="reg_username" placeholder="Username" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="email" name="reg_email" id="reg_email" placeholder="Email" required>
                <i class='bx bxs-envelope'></i>
            </div>
            <div class="input-box">
                <input type="password" name="reg_password" id="reg_password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <button type="submit" name="register" class="btn">Register</button>
        </form>
    </div>

    <!-- Toggle Panels -->
    <div class="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1>Hello, Welcome!</h1>
            <p>Don't have an account?</p>
            <button class="btn register-btn" type="button">Register</button>
        </div>
        <div class="toggle-panel toggle-right">
            <h1>Welcome Back!</h1>
            <p>Already have an account?</p>
            <button class="btn login-btn" type="button">Login</button>
        </div>
    </div>
</div>
<script src="53/SignUp_LogIn_Form.js"></script>
<script>
function toggleAdminCode() {
    var username = document.getElementById('username').value;
    var adminUsername = "<?php echo addslashes($admin_username); ?>";
    var adminCodeField = document.getElementById('admin_code_field');
    if (username === adminUsername) {
        adminCodeField.style.display = 'block';
    } else {
        adminCodeField.style.display = 'none';
    }
}
// Show admin code field if admin username is prefilled
window.onload = function() {
    toggleAdminCode();
};
</script>
</body>
</html>