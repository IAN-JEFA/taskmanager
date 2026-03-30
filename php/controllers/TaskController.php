<?php
require_once __DIR__ . '/../models/Task.php';

class TaskController {
    private $task;
    
    public function __construct() {
        $this->task = new Task();
    }
    
    public function createTask($data) {
        // Validate input
        if(!isset($data['title']) || empty($data['title'])) {
            return ["error" => "Title is required"];
        }
        
        if(!isset($data['due_date']) || empty($data['due_date'])) {
            return ["error" => "Due date is required"];
        }
        
        // Check if due date is today or later
        $today = date('Y-m-d');
        if($data['due_date'] < $today) {
            return ["error" => "Due date must be today or later"];
        }
        
        if(!isset($data['priority']) || !in_array($data['priority'], ['low', 'medium', 'high'])) {
            return ["error" => "Priority must be low, medium, or high"];
        }
        
        $this->task->title = $data['title'];
        $this->task->due_date = $data['due_date'];
        $this->task->priority = $data['priority'];
        
        return $this->task->create();
    }
    
    public function listTasks($status = null) {
        return $this->task->getAllTasks($status);
    }
    
    public function updateTaskStatus($id, $data) {
        if(!isset($data['status']) || empty($data['status'])) {
            return ["error" => "Status is required"];
        }
        
        if(!in_array($data['status'], ['pending', 'in_progress', 'done'])) {
            return ["error" => "Invalid status"];
        }
        
        return $this->task->updateStatus($id, $data['status']);
    }
    
    public function deleteTask($id) {
        return $this->task->deleteTask($id);
    }
    
    public function getReport($date = null) {
        if(!$date) {
            $date = date('Y-m-d');
        }
        
        // Validate date format
        if(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            return ["error" => "Invalid date format. Use YYYY-MM-DD"];
        }
        
        return $this->task->getDailyReport($date);
    }
}
?>