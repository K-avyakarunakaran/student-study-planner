<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Subjects';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_subject') {
        $subject_name = trim($_POST['subject_name'] ?? '');
        $subject_code = trim($_POST['subject_code'] ?? '');
        $credits = (int)($_POST['credits'] ?? 0);
        $professor_name = trim($_POST['professor_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($subject_name === '') {
            $error = 'Subject name is required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO subjects (user_id, subject_name, subject_code, credits, professor_name, description) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ississ', $user_id, $subject_name, $subject_code, $credits, $professor_name, $description);
            if ($stmt->execute()) {
                $success = 'Subject added successfully.';
            } else {
                $error = 'Could not add subject.';
            }
            $stmt->close();
        }
    }

    if ($action === 'add_topic') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $topic_name = trim($_POST['topic_name'] ?? '');
        $topic_description = trim($_POST['topic_description'] ?? '');

        if ($subject_id <= 0 || $topic_name === '') {
            $error = 'Subject and topic name are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO topics (subject_id, user_id, topic_name, description) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $subject_id, $user_id, $topic_name, $topic_description);
            if ($stmt->execute()) {
                $success = 'Topic added successfully.';
            } else {
                $error = 'Could not add topic.';
            }
            $stmt->close();
        }
    }

    if ($action === 'toggle_topic') {
        $topic_id = (int)($_POST['topic_id'] ?? 0);
        $current = (int)($_POST['current_status'] ?? 0);
        $new = $current ? 0 : 1;

        $stmt = $conn->prepare('UPDATE topics SET is_completed = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('iii', $new, $topic_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Topic status updated.';
        } else {
            $error = 'Could not update topic.';
        }
        $stmt->close();
    }

    if ($action === 'delete_subject') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM subjects WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $subject_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Subject deleted.';
        } else {
            $error = 'Could not delete subject.';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare('SELECT id, subject_name, subject_code, credits, professor_name, description FROM subjects WHERE user_id = ? ORDER BY subject_name ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$subjects = $stmt->get_result();
$stmt->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-book text-primary"></i> Subjects</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($subjects->num_rows > 0): ?>
            <?php while ($subject = $subjects->fetch_assoc()): ?>
                <?php
                $sub_id = (int)$subject['id'];
                $topicStmt = $conn->prepare('SELECT id, topic_name, is_completed FROM topics WHERE subject_id = ? AND user_id = ? ORDER BY topic_name ASC');
                $topicStmt->bind_param('ii', $sub_id, $user_id);
                $topicStmt->execute();
                $topics = $topicStmt->get_result();
                $topicStmt->close();
                ?>
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                <?php if (!empty($subject['subject_code'])): ?><small class="text-muted"><?php echo htmlspecialchars($subject['subject_code']); ?></small><?php endif; ?>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this subject?');">
                                <input type="hidden" name="action" value="delete_subject">
                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($subject['professor_name'])): ?><p class="mb-2"><strong>Professor:</strong> <?php echo htmlspecialchars($subject['professor_name']); ?></p><?php endif; ?>
                            <?php if (!empty($subject['description'])): ?><p class="text-muted"><?php echo htmlspecialchars($subject['description']); ?></p><?php endif; ?>
                            <div class="mt-3 mb-2">
                                <strong>Topics</strong>
                            </div>
                            <?php if ($topics->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($topic = $topics->fetch_assoc()): ?>
                                        <form method="POST" class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                            <input type="hidden" name="action" value="toggle_topic">
                                            <input type="hidden" name="topic_id" value="<?php echo (int)$topic['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo (int)$topic['is_completed']; ?>">
                                            <label class="mb-0">
                                                <input type="checkbox" <?php echo (int)$topic['is_completed'] ? 'checked' : ''; ?> onchange="this.form.submit();">
                                                <span class="ms-2 <?php echo (int)$topic['is_completed'] ? 'text-decoration-line-through text-muted' : ''; ?>"><?php echo htmlspecialchars($topic['topic_name']); ?></span>
                                            </label>
                                            <?php if ((int)$topic['is_completed']): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php endif; ?>
                                        </form>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No topics added.</p>
                            <?php endif; ?>
                            <button class="btn btn-outline-primary mt-3 w-100" data-bs-toggle="modal" data-bs-target="#topicModal<?php echo $sub_id; ?>">Add Topic</button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="topicModal<?php echo $sub_id; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Topic</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_topic">
                                <input type="hidden" name="subject_id" value="<?php echo $sub_id; ?>">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Topic Name</label>
                                        <input type="text" class="form-control" name="topic_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="topic_description" rows="3"></textarea>
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
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No subjects yet. Add your first one.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_subject">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" class="form-control" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code</label>
                        <input type="text" class="form-control" name="subject_code">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credits</label>
                        <input type="number" class="form-control" name="credits" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Professor</label>
                        <input type="text" class="form-control" name="professor_name">
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
