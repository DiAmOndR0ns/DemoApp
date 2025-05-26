<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = ""; // Change if your MySQL root password is set
$dbname = "demoapp";
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Table</title>
    <style>
        body {
            background: linear-gradient(120deg, #e0eafc 0%, #cfdef3 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 36px 32px 28px 32px;
        }
        h2 {
            text-align: center;
            color: #0078d7;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            background: #f9fbfd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,120,215,0.05);
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
        }
        th {
            background: #0078d7;
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.5px;
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
        .no-users {
            text-align: center;
            color: #d8000c;
            font-size: 18px;
            margin-top: 30px;
        }
        .footer {
            text-align: center;
            color: #aaa;
            font-size: 13px;
            margin-top: 24px;
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
    <h2>Users Table</h2>
    <?php
    try {
        // Create PDO connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        // Set PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to select all users
        $sql = "SELECT * FROM users";
        $stmt = $conn->query($sql);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            // Output data as a table
            echo "<table>";
            // Output table headers
            echo "<tr>";
            foreach (array_keys($rows[0]) as $header) {
                echo "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . "</th>";
            }
            echo "</tr>";
            // Output table rows
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='no-users'>No users found.</div>";
        }
    } catch(PDOException $e) {
        echo "<div class='no-users'>Connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
    <div class="footer">
        &copy; <?php echo date('Y'); ?> DemoApp Users
    </div>
</div>
</body>
</html>