<?php
/**
 * Tasks Page - Student Study Planner
 * Manage study tasks with priorities and due dates
 */

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Tasks";
$error = '';
$success = '';
$filter_status = $_GET['status'] ?? 'all';

// Handle add/update/delete task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_task') {
        $task_title = trim($_POST['task_title'] ?? '');
        $task_description = trim($_POST['task_description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? 'Medium';
        $subject_id = $_POST['subject_id'] ?? null;
        $status = 'Pending';
        
        if (empty($task_title)) {
            $error = "Task title is required!";
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_title, task_description, due_date, priority, subject_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssi", $user_id, $task_title, $task_description, $due_date, $priority, $subject_id, $status);
            
            if ($stmt->execute()) {
                $success = "Task added successfully!";
            } else {
                $error = "Failed to add task!";
            }
            $stmt->close();
        }
    }
    
    // Handle update task status
    elseif ($_POST['action'] == 'update_task_status') {
        $task_id = $_POST['task_id'] ?? 0;
        $new_status = $_POST['new_status'] ?? 'Pending';
        
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_status, $task_id, $user_id);
        
        if ($stmt->execute()) {
            $success = "Task status updated!";
        } else {
            $error = "Failed to update task!";
        }
        $stmt->close();
    }
    
    // Handle delete task
    elseif ($_POST['action'] == 'delete_task') {
        $task_id = $_POST['task_id'] ?? 0;
        
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        
        if ($stmt->execute()) {
            $success = "Task deleted successfully!";
        } else {
            $error = "Failed to delete task!";
        }
        $stmt->close();
    }
}

// Build query based on filter
$query = "SELECT id, task_title, task_description, due_date, priority, status, subject_id FROM tasks WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filter_status != 'all') {
    $query .= " AND status = ?";
    $params[] = ucfirst($filter_status);
    $types .= "s";
}

$query .= " ORDER BY due_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks = $stmt->get_result();
$stmt->close();

// Get subjects for dropdown
$stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE user_id = ? ORDER BY subject_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjects = $stmt->get_result();
$stmt->close();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3"><i class="fas fa-tasks text-primary"></i> Tasks</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                    <i class="fas fa-plus"></i> Add Task
                </button>
            </div>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_status == 'all' ? 'active' : ''; ?>" href="?status=all">All Tasks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_status == 'pending' ? 'active' : ''; ?>" href="?status=pending">
                        <span class="badge bg-warning">Pending</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_status == 'completed' ? 'active' : ''; ?>" href="?status=completed">
                        <span class="badge bg-success">Completed</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_status == 'overdue' ? 'active' : ''; ?>" href="?status=overdue">
                        <span class="badge bg-danger">Overdue</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Tasks List -->
    <div class="row">
        <div class="col-12">
            <?php if ($tasks->num_rows > 0): ?>
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Task Title</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($task = $tasks->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($task['task_title']); ?></strong>
                                            <?php if (!empty($task['task_description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($task['task_description'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($task['subject_id']) {
                                                $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ?");
                                                $stmt->bind_param("i", $task['subject_id']);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                    $subj = $result->fetch_assoc();
                                                    echo htmlspecialchars($subj['subject_name']);
                                                }
                                                $stmt->close();
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $task['priority'] == 'High' ? 'danger' : ($task['priority'] == 'Medium' ? 'warning' : 'info'); ?>">
                                                <?php echo htmlspecialchars($task['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($task['due_date'])): ?>
                                                <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_task_status">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit();" style="width: auto;">
                                                    <option value="Pending" <?php echo $task['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Completed" <?php echo $task['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="Overdue" <?php echo $task['status'] == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                                                <input type="hidden" name="action" value="delete_task">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No tasks found. <a href="#" data-bs-toggle="modal" data-bs-target="#addTaskModal" class="alert-link">Add a task</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="mb-3">
                        <label class="form-label">Task Title *</label>
                        <input type="text" class="form-control" name="task_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="task_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select class="form-control" name="subject_id">
                            <option value="">-- Select Subject --</option>
                            <?php
                            // Refetch subjects
                            $stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE user_id = ? ORDER BY subject_name");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $subjects_temp = $stmt->get_result();
                            while ($subj = $subjects_temp->fetch_assoc()) {
                                echo "<option value='" . $subj['id'] . "'>" . htmlspecialchars($subj['subject_name']) . "</option>";
                            }
                            $stmt->close();
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-control" name="priority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
