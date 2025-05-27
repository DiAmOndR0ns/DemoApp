<?php 
// userlandpage.php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection (adjust as needed)
$dbConfig = [
    'host' => 'localhost',
    'name' => 'DemoApp',
    'user' => 'root',
    'pass' => ''
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Get placemarks added by this user in the last 1 month, grouped by color
$username = $_SESSION['username'];
$colorCounts = [];
$colorNames = [
    '#ff0000' => 'Red',
    '#ff5722' => 'Orange',
    '#2196f3' => 'Blue'
];
$colorOrder = array_keys($colorNames);

$stmt = $pdo->prepare("SELECT color, COUNT(*) as count FROM placemarks WHERE username = :username AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY color");
$stmt->execute([':username' => $username]);
$total = 0;
foreach ($colorOrder as $color) {
    $colorCounts[$color] = 0;
}
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $color = strtolower($row['color']);
    if (isset($colorCounts[$color])) {
        $colorCounts[$color] = (int)$row['count'];
        $total += (int)$row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Placemark Activity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #0078d7;
            --primary-light: #e3f2ff;
            --secondary: #2196f3;
            --accent: #ff5722;
            --text: #333;
            --text-light: #666;
            --bg: #f8fafc;
            --card-bg: #fff;
            --border-radius: 16px;
            --shadow: 0 4px 24px rgba(0, 120, 215, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .dashboard-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dashboard-header .usermap-link {
            color: var(--secondary);
            font-size: 1.8rem;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dashboard-header .usermap-link:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        .user-details {
            background: var(--primary-light);
            border-radius: calc(var(--border-radius) - 4px);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary);
        }

        .user-details h2 {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .user-details p {
            margin-bottom: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }

        .user-details strong {
            min-width: 80px;
            display: inline-block;
        }

        .chart-section {
            margin: 2rem 0;
        }

        .chart-title {
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text);
        }

        .chart-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: var(--primary-light);
            border-radius: calc(var(--border-radius) - 4px);
            padding: 1.5rem;
            flex-wrap: wrap;
        }

        .pie-legend {
            flex: 1;
            min-width: 200px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .legend-item:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.8);
            flex-shrink: 0;
        }

        .legend-text {
            flex: 1;
        }

        .total-count {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(0, 0, 0, 0.1);
            font-weight: 600;
            color: var(--primary);
            display: flex;
            justify-content: space-between;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent);
            font-weight: 600;
        }

        .btn-secondary:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1.5rem;
            }
            
            .chart-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .pie-legend {
                width: 100%;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>
                <i class="fas fa-user-circle"></i>
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
            </h1>
            <a class="usermap-link" href="usermap.php" title="Go to Map">
                <i class="fas fa-map-marked-alt"></i>
                <span class="link-text">View Map</span>
            </a>
        </div>
        
        <div class="user-details">
            <h2><i class="fas fa-id-card"></i> User Profile</h2>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <p><strong>Last Activity:</strong> <?php echo date('M j, Y g:i a'); ?></p>
        </div>
        
        <div class="chart-section">
            <h2 class="chart-title">
                <i class="fas fa-chart-pie"></i>
                Placemark Analytics (Last 30 Days)
            </h2>
            
            <div class="chart-container">
                <div style="width: 220px; height: 220px;">
                    <canvas id="placemarkPie"></canvas>
                </div>
                
                <div class="pie-legend">
                    <?php foreach ($colorOrder as $color): ?>
                        <div class="legend-item">
                            <span class="legend-color" style="background:<?= $color ?>"></span>
                            <span class="legend-text"><?= $colorNames[$color] ?></span>
                            <span class="legend-count"><b><?= $colorCounts[$color] ?></b></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="total-count">
                        <span>Total Placemarks:</span>
                        <span><?= $total ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-primary" id="backBtn" style="display:none;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <script>
        // Pie Chart Data from PHP
        const pieLabels = <?php echo json_encode(array_values($colorNames)); ?>;
        const pieData = <?php echo json_encode(array_values($colorCounts)); ?>;
        const pieColors = <?php echo json_encode(array_keys($colorNames)); ?>;
        const pieBorders = pieColors.map(color => `${color}80`);

        // Chart.js Pie Chart
        const ctx = document.getElementById('placemarkPie').getContext('2d');
        const placemarkPie = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: pieColors,
                    borderColor: pieBorders,
                    borderWidth: 2
                }]
            },
            options: {
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });

        // Back button logic for usermap.php
        if (window.location.pathname.includes('usermap.php')) {
            document.getElementById('backBtn').style.display = 'inline-flex';
            document.getElementById('backBtn').onclick = function() {
                window.location.href = 'userlandpage.php';
            };
        }
    </script>
</body>
</html>