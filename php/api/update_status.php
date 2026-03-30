<?php
require_once __DIR__ . '/../controllers/TaskController.php';

$controller = new TaskController();

if($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if(!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Task ID is required"]);
        exit();
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    $result = $controller->updateTaskStatus($id, $data);
    
    $statusCode = isset($result['error']) ? 422 : 200;
    http_response_code($statusCode);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>