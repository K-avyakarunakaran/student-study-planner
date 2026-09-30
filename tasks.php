<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Tasks';
$error = '';
$success = '';
$status_filter = $_GET['status'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_task') {
        $title = trim($_POST['task_title'] ?? '');
        $description = trim($_POST['task_description'] ?? '');
        $due_date = $_POST['due_date'] ?? null;
        $priority = $_POST['priority'] ?? 'Medium';
        $subject_id = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;

        if ($title === '') {
            $error = 'Task title is required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO tasks (user_id, task_title, task_description, due_date, priority, subject_id, status) VALUES (?, ?, ?, ?, ?, ?, "Pending")');
            $stmt->bind_param('isssii', $user_id, $title, $description, $due_date, $priority, $subject_id);
            if ($stmt->execute()) {
                $success = 'Task added successfully.';
            } else {
                $error = 'Could not add task.';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_status') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $stmt = $conn->prepare('UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('sii', $status, $task_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Task updated.';
        } else {
            $error = 'Could not update task.';
        }
        $stmt->close();
    }

    if ($action === 'delete_task') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $task_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Task deleted.';
        } else {
            $error = 'Could not delete task.';
        }
        $stmt->close();
    }
}

$query = 'SELECT t.id, t.task_title, t.task_description, t.due_date, t.priority, t.status, t.subject_id FROM tasks t WHERE t.user_id = ?';
$params = [$user_id];
$types = 'i';

if ($status_filter !== 'all') {
    $query .= ' AND t.status = ?';
    $params[] = ucfirst($status_filter);
    $types .= 's';
}

$query .= ' ORDER BY due_date IS NULL, due_date ASC';
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks = $stmt->get_result();
$stmt->close();

$subjectStmt = $conn->prepare('SELECT id, subject_name FROM subjects WHERE user_id = ? ORDER BY subject_name ASC');
$subjectStmt->bind_param('i', $user_id);
$subjectStmt->execute();
$subjects = $subjectStmt->get_result();
$subjectStmt->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-tasks text-primary"></i> Tasks</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">Add Task</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="tasks.php?status=all">All</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="tasks.php?status=pending">Pending</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'completed' ? 'active' : ''; ?>" href="tasks.php?status=completed">Completed</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'overdue' ? 'active' : ''; ?>" href="tasks.php?status=overdue">Overdue</a></li>
    </ul>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Task</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tasks->num_rows > 0): ?>
                        <?php while ($task = $tasks->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['task_title']); ?></strong>
                                    <?php if (!empty($task['task_description'])): ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars(substr($task['task_description'], 0, 60)); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $subName = '-';
                                    if (!empty($task['subject_id'])) {
                                        $subStmt = $conn->prepare('SELECT subject_name FROM subjects WHERE id = ? AND user_id = ?');
                                        $subStmt->bind_param('ii', $task['subject_id'], $user_id);
                                        $subStmt->execute();
                                        $subData = $subStmt->get_result()->fetch_assoc();
                                        $subName = $subData['subject_name'] ?? '-';
                                        $subStmt->close();
                                    }
                                    echo htmlspecialchars($subName);
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $task['priority'] === 'High' ? 'danger' : ($task['priority'] === 'Medium' ? 'warning text-dark' : 'info'); ?>"><?php echo htmlspecialchars($task['priority']); ?></span>
                                </td>
                                <td><?php echo !empty($task['due_date']) ? date('M d, Y', strtotime($task['due_date'])) : '-'; ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                        <select class="form-select form-select-sm" name="status" onchange="this.form.submit();">
                                            <option value="Pending" <?php echo $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Overdue" <?php echo $task['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete task?');">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No tasks found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_task">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="task_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="task_description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select class="form-control" name="subject_id">
                            <option value="">General</option>
                            <?php while ($row = $subjects->fetch_assoc()): ?>
                                <option value="<?php echo (int)$row['id']; ?>"><?php echo htmlspecialchars($row['subject_name']); ?></option>
                            <?php endwhile; ?>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
