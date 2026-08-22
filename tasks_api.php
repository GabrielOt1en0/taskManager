<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && ($_GET['action'] ?? '') === 'fetch') {
    $stmt = $pdo->prepare("SELECT id, task_name AS name, duration, status FROM tasks WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, task_name, duration, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$userId, $data['name'], $data['duration']]);
        echo json_encode(["id" => $pdo->lastInsertId()]);
    } elseif ($action === 'update_status') {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['status'], $data['id'], $userId]);
        echo json_encode(["success" => true]);
    } elseif ($action === 'clear_status') {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE status = ? AND user_id = ?");
        $stmt->execute([$data['status'], $userId]);
        echo json_encode(["success" => true]);
    }
    exit;
}
?>