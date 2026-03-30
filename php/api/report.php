<?php
require_once __DIR__ . '/../controllers/TaskController.php';

$controller = new TaskController();

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $result = $controller->getReport($date);
    
    http_response_code(isset($result['error']) ? 400 : 200);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>