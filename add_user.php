
<?php
session_start();

// Only allow access if logged in as admin-----------------------------------------------------------------------------------------------------
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "demoapp";
$error = "";
$success = "";

// Handle Add User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $dbusername, $dbpassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username already exists.";
            } else {
                // Insert user (password stored as plain text for demo; use password_hash in production)
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $password,
                    ':role' => $role
                ]);
                $success = "User added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Delete User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_user'])) {
    $delete_username = trim($_POST['delete_username'] ?? '');
    if ($delete_username !== "") {
        try {
            $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $dbusername, $dbpassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prevent admin from deleting themselves
            if ($delete_username === $_SESSION['username']) {
                $error = "You cannot delete your own admin account while logged in.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE username = :username");
                $stmt->execute([':username' => $delete_username]);
                if ($stmt->rowCount() > 0) {
                    $success = "User '$delete_username' deleted successfully!";
                } else {
                    $error = "User not found or could not be deleted.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "No user selected for deletion.";
    }
}

// Fetch all users for display
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $users = $pdo->query("SELECT username, role FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add/Delete User - Admin</title>
    <style>
        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 32px 28px 24px 28px;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        h2 {
            text-align: center;
            color: #0078d7;
            margin-bottom: 24px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #444;
            font-weight: 500;
        }
        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #f9f9f9;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="password"]:focus, select:focus {
            border: 1.5px solid #0078d7;
            outline: none;
            background: #fff;
        }
        .error-message {
            color: #d8000c;
            background: #ffd2d2;
            border: 1px solid #d8000c;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 18px;
            text-align: center;
        }
        .success-message {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 18px;
            text-align: center;
        }
        button[type="submit"] {
            width: 100%;
            background: #0078d7;
            color: #fff;
            border: none;
            padding: 12px 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #005fa3;
        }
        .user-list {
            margin-top: 32px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #f9fbfd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,120,215,0.05);
        }
        th, td {
            padding: 10px 8px;
            text-align: left;
        }
        th {
            background: #0078d7;
            color: #fff;
            font-weight: 600;
            border-bottom: 2px solid #005fa3;
        }
        tr:nth-child(even) {
            background: #f1f7fb;
        }
        tr:hover {
            background: #e6f0fa;
            transition: background 0.2s;
        }
        td {
            color: #333;
        }
        .delete-btn {
            background: #f44336;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .delete-btn:hover {
            background: #c62828;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 18px;
            color: #0078d7;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid transparent;
            transition: color 0.2s, border-bottom 0.2s;
        }
        .back-link:hover {
            color: #005fa3;
            border-bottom: 1px solid #0078d7;
        }
    </style>
</head>
<body>
<div class="container">
    <a class="back-link" href="admindash.php">&larr; Back to Dashboard</a>
    <h2>Add New User</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label for="role">Role</label>
        <select name="role" id="role">
            <option value="user">User</option>
        </select>

        <button type="submit" name="add_user">Add User</button>
    </form>

    <div class="user-list">
        <h2>Existing Users</h2>
        <form method="post" action="">
            <table>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                        <td>
                            <?php if ($user['username'] !== $_SESSION['username']): ?>
                                <button class="delete-btn" type="submit" name="delete_user" value="1" onclick="return confirm('Delete user <?php echo htmlspecialchars($user['username']); ?>?');">
                                    Delete
                                </button>
                                <input type="hidden" name="delete_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            <?php else: ?>
                                <span style="color:#aaa;">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </form>
    </div>
</div>
</body>
</html>