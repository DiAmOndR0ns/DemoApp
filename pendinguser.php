<?php
session_start();

// Only allow access if logged in as admin
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demoapp";

$error = "";
$success = "";

// Approve user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve_username'])) {
    $approve_username = trim($_POST['approve_username']);
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get user data from pendUser
        $stmt = $pdo->prepare("SELECT * FROM pendUser WHERE username = :username");
        $stmt->execute([':username' => $approve_username]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pending) {
            // Insert into users table (default role: user)
            $insert = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (:username, :password, 'user', :email)");
            $insert->execute([
                ':username' => $pending['username'],
                ':password' => $pending['password'],
                ':email' => $pending['email']
            ]);
            // Remove from pendUser
            $delete = $pdo->prepare("DELETE FROM pendUser WHERE username = :username");
            $delete->execute([':username' => $approve_username]);
            $success = "User '{$approve_username}' approved and added to users.";
        } else {
            $error = "Pending user not found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Reject user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reject_username'])) {
    $reject_username = trim($_POST['reject_username']);
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $delete = $pdo->prepare("DELETE FROM pendUser WHERE username = :username");
        $delete->execute([':username' => $reject_username]);
        $success = "User '{$reject_username}' rejected and removed from pending list.";
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Fetch all pending users (now including email)
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pendingUsers = $pdo->query("SELECT username, email, requested_at FROM pendUser ORDER BY requested_at ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendingUsers = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending User Registrations</title>
    <style>
        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .container {
            max-width: 600px;
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
        .approve-btn {
            background: #4caf50;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .approve-btn:hover {
            background: #388e3c;
        }
        .reject-btn {
            background: #f44336;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .reject-btn:hover {
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
    <h2>Pending User Registrations</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <table>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Requested At</th>
            <th>Action</th>
        </tr>
        <?php if (empty($pendingUsers)): ?>
            <tr>
                <td colspan="4" style="text-align:center;color:#888;">No pending registrations.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($pendingUsers as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['requested_at']); ?></td>
                    <td>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="approve_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            <button class="approve-btn" type="submit">Approve</button>
                        </form>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="reject_username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            <button class="reject-btn" type="submit">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>
</body>
</html>