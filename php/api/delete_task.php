<?php
require_once __DIR__ . '/../controllers/TaskController.php';

$controller = new TaskController();

if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if(!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Task ID is required"]);
        exit();
    }
    
    $result = $controller->deleteTask($id);
    
    if(isset($result['code']) && $result['code'] === 403) {
        http_response_code(403);
    } else if(isset($result['error'])) {
        http_response_code(404);
    } else {
        http_response_code(200);
    }
    
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>