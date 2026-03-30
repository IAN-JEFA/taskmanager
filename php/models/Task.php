<?php
require_once __DIR__ . '/../config/database.php';

class Task {
    private $conn;
    private $table_name = "tasks";
    
    public $id;
    public $title;
    public $due_date;
    public $priority;
    public $status;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Create task
    public function create() {
        // Check for duplicate
        $checkQuery = "SELECT id FROM " . $this->table_name . 
                      " WHERE title = :title AND due_date = :due_date";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":title", $this->title);
        $checkStmt->bindParam(":due_date", $this->due_date);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            return ["error" => "A task with the same title and due date already exists"];
        }
        
        // Create task
        $query = "INSERT INTO " . $this->table_name . 
                 " SET title=:title, due_date=:due_date, priority=:priority, status='pending'";
        
        $stmt = $this->conn->prepare($query);
        
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":due_date", $this->due_date);
        $stmt->bindParam(":priority", $this->priority);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return $this->getTaskById();
        }
        
        return ["error" => "Unable to create task"];
    }
    
    // Get task by ID
    public function getTaskById() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all tasks
    public function getAllTasks($status = null) {
        $query = "SELECT * FROM " . $this->table_name;
        
        if($status && in_array($status, ['pending', 'in_progress', 'done'])) {
            $query .= " WHERE status = :status";
            $query .= " ORDER BY FIELD(priority, 'high', 'medium', 'low'), due_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":status", $status);
        } else {
            $query .= " ORDER BY FIELD(priority, 'high', 'medium', 'low'), due_date ASC";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if(count($tasks) == 0) {
            return ["message" => "No tasks found", "tasks" => []];
        }
        
        return $tasks;
    }
    
    // Update task status
    public function updateStatus($id, $newStatus) {
        // Get current task
        $query = "SELECT status FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$task) {
            return ["error" => "Task not found"];
        }
        
        $currentStatus = $task['status'];
        
        // Status progression rules
        $progression = [
            'pending' => 'in_progress',
            'in_progress' => 'done',
            'done' => 'done'
        ];
        
        if($currentStatus === 'done') {
            return ["error" => "Cannot update status of completed task"];
        }
        
        if($progression[$currentStatus] !== $newStatus && $currentStatus !== $newStatus) {
            return ["error" => "Status can only progress from {$currentStatus} to {$progression[$currentStatus]}"];
        }
        
        // Update status
        $updateQuery = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(":status", $newStatus);
        $updateStmt->bindParam(":id", $id);
        
        if($updateStmt->execute()) {
            return ["message" => "Task status updated successfully", "id" => $id, "new_status" => $newStatus];
        }
        
        return ["error" => "Unable to update status"];
    }
    
    // Delete task
    public function deleteTask($id) {
        // Check if task is done
        $query = "SELECT status FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$task) {
            return ["error" => "Task not found"];
        }
        
        if($task['status'] !== 'done') {
            return ["error" => "Only completed (done) tasks can be deleted", "code" => 403];
        }
        
        // Delete task
        $deleteQuery = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->bindParam(":id", $id);
        
        if($deleteStmt->execute()) {
            return ["message" => "Task deleted successfully"];
        }
        
        return ["error" => "Unable to delete task"];
    }
    
    // Get daily report
    public function getDailyReport($date) {
        $query = "SELECT priority, status, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE due_date = :date 
                  GROUP BY priority, status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [
            'high' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'medium' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'low' => ['pending' => 0, 'in_progress' => 0, 'done' => 0]
        ];
        
        foreach($results as $result) {
            $summary[$result['priority']][$result['status']] = (int)$result['count'];
        }
        
        return [
            'date' => $date,
            'summary' => $summary,
            'total_tasks' => array_sum(array_map('array_sum', $summary))
        ];
    }
}
?>