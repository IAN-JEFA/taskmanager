-- Create database
CREATE DATABASE IF NOT EXISTS task_manager;
USE task_manager;

-- Create tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    due_date DATE NOT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL,
    status ENUM('pending', 'in_progress', 'done') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_task_per_day (title, due_date)
);

-- Insert sample data
INSERT INTO tasks (title, due_date, priority, status) VALUES
('Complete project documentation', CURDATE() + INTERVAL 2 DAY, 'high', 'pending'),
('Review pull requests', CURDATE() + INTERVAL 1 DAY, 'medium', 'in_progress'),
('Update website content', CURDATE() + INTERVAL 3 DAY, 'low', 'pending'),
('Fix critical bug', CURDATE(), 'high', 'done'),
('Team meeting', CURDATE() + INTERVAL 1 DAY, 'medium', 'pending');