// API Configuration
const API_BASE_URL = '/task-manager/php/api';

// State management
let currentFilter = 'all';

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    // Set minimum date for due date input
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('due_date').min = today;
    
    // Load tasks
    loadTasks();
    
    // Setup event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Form submission
    document.getElementById('taskForm').addEventListener('submit', createTask);
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.status;
            loadTasks();
        });
    });
    
    // Report button
    document.getElementById('reportBtn').addEventListener('click', showReport);
}

// Create Task
async function createTask(e) {
    e.preventDefault();
    
    const title = document.getElementById('title').value;
    const due_date = document.getElementById('due_date').value;
    const priority = document.getElementById('priority').value;
    
    if (!title || !due_date) {
        showToast('Please fill all required fields', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/create_task.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ title, due_date, priority })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast('Task created successfully!', 'success');
            document.getElementById('taskForm').reset();
            document.getElementById('due_date').min = new Date().toISOString().split('T')[0];
            loadTasks();
        } else {
            showToast(data.error || 'Failed to create task', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

// Load Tasks
async function loadTasks() {
    const tasksContainer = document.getElementById('tasksList');
    tasksContainer.innerHTML = '<div class="loading">Loading tasks...</div>';
    
    try {
        let url = `${API_BASE_URL}/list_tasks.php`;
        if (currentFilter !== 'all') {
            url += `?status=${currentFilter}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.message === 'No tasks found' || (Array.isArray(data) && data.length === 0)) {
            tasksContainer.innerHTML = `
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                    <p>No tasks found. Create your first task!</p>
                </div>
            `;
            return;
        }
        
        const tasks = Array.isArray(data) ? data : (data.tasks || []);
        renderTasks(tasks);
    } catch (error) {
        tasksContainer.innerHTML = '<div class="empty-state">Error loading tasks. Please refresh.</div>';
        showToast('Error loading tasks', 'error');
    }
}

// Render Tasks
function renderTasks(tasks) {
    const tasksContainer = document.getElementById('tasksList');
    
    if (tasks.length === 0) {
        tasksContainer.innerHTML = `
            <div class="empty-state">
                <p>No tasks match the filter</p>
            </div>
        `;
        return;
    }
    
    tasksContainer.innerHTML = tasks.map(task => `
        <div class="task-card" style="border-left-color: ${getPriorityColor(task.priority)}">
            <div class="task-title">${escapeHtml(task.title)}</div>
            <div class="task-details">
                <span class="badge priority-${task.priority}">${task.priority.toUpperCase()}</span>
                <span class="badge status-${task.status}">${formatStatus(task.status)}</span>
                <span>📅 Due: ${formatDate(task.due_date)}</span>
                <span>🕒 Created: ${formatDate(task.created_at)}</span>
            </div>
            <div class="task-actions">
                ${getProgressButton(task)}
                <button class="action-btn delete-btn" onclick="deleteTask(${task.id})" ${task.status !== 'done' ? 'disabled' : ''}>
                    🗑️ Delete
                </button>
            </div>
        </div>
    `).join('');
}

function getProgressButton(task) {
    if (task.status === 'done') {
        return '<button class="action-btn progress-btn" disabled>✓ Completed</button>';
    }
    
    const nextStatus = task.status === 'pending' ? 'in_progress' : 'done';
    const buttonText = task.status === 'pending' ? '▶ Start Progress' : '✔ Mark as Done';
    
    return `<button class="action-btn progress-btn" onclick="updateStatus(${task.id}, '${nextStatus}')">
        ${buttonText}
    </button>`;
}

// Update Status
async function updateStatus(id, newStatus) {
    try {
        const response = await fetch(`${API_BASE_URL}/update_status.php?id=${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: newStatus })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast('Task status updated!', 'success');
            loadTasks();
        } else {
            showToast(data.error || 'Failed to update status', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

// Delete Task
async function deleteTask(id) {
    if (!confirm('Are you sure you want to delete this task? Only completed tasks can be deleted.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/delete_task.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast('Task deleted successfully!', 'success');
            loadTasks();
        } else if (response.status === 403) {
            showToast(data.error || 'Only completed tasks can be deleted', 'error');
        } else {
            showToast(data.error || 'Failed to delete task', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

// Show Report
async function showReport() {
    const date = document.getElementById('reportDate').value;
    
    if (!date) {
        showToast('Please select a date', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/report.php?date=${date}`);
        const data = await response.json();
        
        if (response.ok) {
            displayReport(data);
        } else {
            showToast(data.error || 'Failed to load report', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

function displayReport(report) {
    const reportContent = document.getElementById('reportContent');
    const summary = report.summary;
    
    reportContent.innerHTML = `
        <div class="report-stats">
            <h3>📊 Report for ${report.date}</h3>
            <p>Total Tasks: ${report.total_tasks}</p>
            ${Object.entries(summary).map(([priority, stats]) => `
                <div class="priority-section">
                    <h3>${priority.toUpperCase()} Priority</h3>
                    <div class="status-stats">
                        <div class="stat-item">
                            <div class="stat-value">${stats.pending}</div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${stats.in_progress}</div>
                            <div class="stat-label">In Progress</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${stats.done}</div>
                            <div class="stat-label">Done</div>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    document.getElementById('reportModal').style.display = 'flex';
}

// Utility Functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatStatus(status) {
    return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function getPriorityColor(priority) {
    const colors = {
        high: '#c53030',
        medium: '#dd6b20',
        low: '#2f855a'
    };
    return colors[priority] || '#667eea';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Modal functions
function closeModal() {
    document.getElementById('reportModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reportModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}