<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Reminders';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_exam') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $exam_name = trim($_POST['exam_name'] ?? '');
        $exam_date = $_POST['exam_date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if ($subject_id <= 0 || $exam_name === '' || $exam_date === '') {
            $error = 'Exam subject, name and date are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO exams (user_id, exam_name, subject_id, exam_date, description) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('isiss', $user_id, $exam_name, $subject_id, $exam_date, $description);
            if ($stmt->execute()) {
                $success = 'Exam reminder added.';
            } else {
                $error = 'Could not add exam reminder.';
            }
            $stmt->close();
        }
    }

    if ($action === 'add_assignment') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $assignment_name = trim($_POST['assignment_name'] ?? '');
        $due_date = $_POST['due_date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if ($subject_id <= 0 || $assignment_name === '' || $due_date === '') {
            $error = 'Assignment subject, name and due date are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO assignments (user_id, assignment_name, subject_id, due_date, description) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('isiss', $user_id, $assignment_name, $subject_id, $due_date, $description);
            if ($stmt->execute()) {
                $success = 'Assignment reminder added.';
            } else {
                $error = 'Could not add assignment reminder.';
            }
            $stmt->close();
        }
    }
}

$subjectStmt = $conn->prepare('SELECT id, subject_name FROM subjects WHERE user_id = ? ORDER BY subject_name ASC');
$subjectStmt->bind_param('i', $user_id);
$subjectStmt->execute();
$subjects = $subjectStmt->get_result();
$subjectStmt->close();

$examStmt = $conn->prepare('SELECT e.id, e.exam_name, e.exam_date, e.description, s.subject_name FROM exams e LEFT JOIN subjects s ON s.id = e.subject_id WHERE e.user_id = ? ORDER BY e.exam_date ASC');
$examStmt->bind_param('i', $user_id);
$examStmt->execute();
$exams = $examStmt->get_result();
$examStmt->close();

$assignmentStmt = $conn->prepare('SELECT a.id, a.assignment_name, a.due_date, a.description, s.subject_name FROM assignments a LEFT JOIN subjects s ON s.id = a.subject_id WHERE a.user_id = ? ORDER BY a.due_date ASC');
$assignmentStmt->bind_param('i', $user_id);
$assignmentStmt->execute();
$assignments = $assignmentStmt->get_result();
$assignmentStmt->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-bell text-primary"></i> Exam & Assignment Reminders</h3>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between">
                    <h5 class="mb-0">Exams</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">Add</button>
                </div>
                <div class="card-body">
                    <?php if ($exams->num_rows > 0): ?>
                        <?php while ($exam = $exams->fetch_assoc()): ?>
                            <div class="border rounded p-3 mb-3">
                                <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($exam['subject_name']); ?></small>
                                <p class="mb-1 mt-2"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></p>
                                <?php if (!empty($exam['description'])): ?><p class="mb-0 text-muted"><?php echo htmlspecialchars($exam['description']); ?></p><?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No exam reminders.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between">
                    <h5 class="mb-0">Assignments</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">Add</button>
                </div>
                <div class="card-body">
                    <?php if ($assignments->num_rows > 0): ?>
                        <?php while ($item = $assignments->fetch_assoc()): ?>
                            <div class="border rounded p-3 mb-3">
                                <strong><?php echo htmlspecialchars($item['assignment_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($item['subject_name']); ?></small>
                                <p class="mb-1 mt-2"><strong>Due:</strong> <?php echo date('M d, Y', strtotime($item['due_date'])); ?></p>
                                <?php if (!empty($item['description'])): ?><p class="mb-0 text-muted"><?php echo htmlspecialchars($item['description']); ?></p><?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No assignment reminders.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addExamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Exam Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_exam">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Exam Name</label>
                        <input type="text" class="form-control" name="exam_name" required>
                    </div>
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
                        <label class="form-label">Exam Date</label>
                        <input type="date" class="form-control" name="exam_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="description"></textarea>
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

<div class="modal fade" id="addAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Assignment Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_assignment">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Assignment Name</label>
                        <input type="text" class="form-control" name="assignment_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select class="form-control" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php
                            $s2 = $conn->prepare('SELECT id, subject_name FROM subjects WHERE user_id = ? ORDER BY subject_name ASC');
                            $s2->bind_param('i', $user_id);
                            $s2->execute();
                            $subs2 = $s2->get_result();
                            while ($row = $subs2->fetch_assoc()) {
                                echo '<option value="' . (int)$row['id'] . '">' . htmlspecialchars($row['subject_name']) . '</option>';
                            }
                            $s2->close();
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="description"></textarea>
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
