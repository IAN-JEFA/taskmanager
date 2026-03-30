<?php
require_once __DIR__ . '/../controllers/TaskController.php';

$controller = new TaskController();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $result = $controller->createTask($data);
    
    http_response_code(isset($result['error']) ? 422 : 201);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>