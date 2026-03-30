<?php
require_once __DIR__ . '/../controllers/TaskController.php';

$controller = new TaskController();

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $result = $controller->listTasks($status);
    
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>