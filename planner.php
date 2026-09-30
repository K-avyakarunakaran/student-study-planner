<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Study Planner';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_plan') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $topic_id = !empty($_POST['topic_id']) ? (int)$_POST['topic_id'] : null;
        $study_date = $_POST['study_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $priority = $_POST['priority'] ?? 'Medium';
        $description = trim($_POST['description'] ?? '');

        if ($subject_id <= 0 || $study_date === '' || $start_time === '' || $end_time === '') {
            $error = 'Subject, date and times are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO study_plans (user_id, subject_id, topic_id, study_date, start_time, end_time, priority, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Pending")');
            $stmt->bind_param('iiisssss', $user_id, $subject_id, $topic_id, $study_date, $start_time, $end_time, $priority, $description);
            if ($stmt->execute()) {
                $success = 'Study plan added successfully.';
            } else {
                $error = 'Could not add study plan.';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_plan') {
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $stmt = $conn->prepare('UPDATE study_plans SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('sii', $status, $plan_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Study plan updated.';
        } else {
            $error = 'Could not update study plan.';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare('SELECT sp.id, sp.study_date, sp.start_time, sp.end_time, sp.priority, sp.description, sp.status, s.subject_name, t.topic_name FROM study_plans sp LEFT JOIN subjects s ON s.id = sp.subject_id LEFT JOIN topics t ON t.id = sp.topic_id WHERE sp.user_id = ? ORDER BY sp.study_date DESC, sp.start_time ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$plans = $stmt->get_result();
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
        <h3 class="mb-0"><i class="fas fa-calendar-alt text-primary"></i> Study Planner</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">Add Plan</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($plans->num_rows > 0): ?>
            <?php while ($plan = $plans->fetch_assoc()): ?>
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($plan['subject_name']); ?></h5>
                            <span class="badge bg-<?php echo $plan['priority'] === 'High' ? 'danger' : ($plan['priority'] === 'Medium' ? 'warning text-dark' : 'info'); ?>"><?php echo htmlspecialchars($plan['priority']); ?></span>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Topic:</strong> <?php echo htmlspecialchars($plan['topic_name'] ?? 'General'); ?></p>
                            <p class="mb-2"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($plan['study_date'])); ?></p>
                            <p class="mb-2"><strong>Time:</strong> <?php echo date('h:i A', strtotime($plan['start_time'])); ?> - <?php echo date('h:i A', strtotime($plan['end_time'])); ?></p>
                            <?php if (!empty($plan['description'])): ?><p class="text-muted"><?php echo htmlspecialchars($plan['description']); ?></p><?php endif; ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="update_plan">
                                <input type="hidden" name="plan_id" value="<?php echo (int)$plan['id']; ?>">
                                <select class="form-select" name="status" onchange="this.form.submit();">
                                    <option value="Pending" <?php echo $plan['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Completed" <?php echo $plan['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $plan['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No study plans added yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Study Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_plan">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select class="form-control" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php while ($row = $subjects->fetch_assoc()): ?>
                                <option value="<?php echo (int)$row['id']; ?>"><?php echo htmlspecialchars($row['subject_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Topic</label>
                        <select class="form-control" name="topic_id">
                            <option value="">General</option>
                            <?php
                            $topStmt = $conn->prepare('SELECT id, topic_name FROM topics WHERE user_id = ? ORDER BY topic_name ASC');
                            $topStmt->bind_param('i', $user_id);
                            $topStmt->execute();
                            $topicsList = $topStmt->get_result();
                            while ($top = $topicsList->fetch_assoc()) {
                                echo '<option value="' . (int)$top['id'] . '">' . htmlspecialchars($top['topic_name']) . '</option>';
                            }
                            $topStmt->close();
                            ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Study Date</label>
                            <input type="date" class="form-control" name="study_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-control" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
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
