<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Authentication required"]);
    exit;
}

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
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, lng, lat, color, username, created_at FROM placemarks ORDER BY created_at DESC");
    $placemarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($placemarks);
    exit;
}

if ($method === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON data", "received" => $json]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO placemarks (id, lng, lat, color, username, created_at) VALUES (:id, :lng, :lat, :color, :username, :created_at)");
    $count = 0;
    $errors = [];
    foreach ($data as $pm) {
        if (!isset($pm['id'], $pm['lng'], $pm['lat'], $pm['color'])) {
            $errors[] = "Missing required fields in placemark";
            continue;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $pm['color'])) {
            $errors[] = "Invalid color format: " . $pm['color'];
            continue;
        }
        try {
            $result = $stmt->execute([
                ':id' => $pm['id'],
                ':lng' => $pm['lng'],
                ':lat' => $pm['lat'],
                ':color' => $pm['color'],
                ':username' => $_SESSION['username'],
                ':created_at' => $pm['createdAt'] ?? date('Y-m-d H:i:s')
            ]);
            $count += $stmt->rowCount();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if ($count > 0) {
        echo json_encode(["success" => true, "saved" => $count]);
    } else {
        http_response_code(400);
        echo json_encode([
            "error" => "No valid placemarks provided",
            "details" => $errors,
            "data_received" => $data
        ]);
    }
    exit;
}

if ($method === 'DELETE') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Placemark ID required"]);
        exit;
    }

    // Only allow deletion if the placemark belongs to the user
    $stmt = $pdo->prepare("DELETE FROM placemarks WHERE id = :id AND username = :username");
    $stmt->execute([
        ':id' => $data['id'],
        ':username' => $_SESSION['username']
    ]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Delete failed or not allowed"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
exit;