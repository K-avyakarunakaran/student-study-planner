<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Notes';
$error = '';
$success = '';
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $subject_id = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
        $title = trim($_POST['note_title'] ?? '');
        $content = trim($_POST['note_content'] ?? '');
        if ($title === '' || $content === '') {
            $error = 'Title and content are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO notes (user_id, subject_id, note_title, note_content) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $user_id, $subject_id, $title, $content);
            if ($stmt->execute()) {
                $success = 'Note saved successfully.';
            } else {
                $error = 'Could not save note.';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_note') {
        $note_id = (int)($_POST['note_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $note_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Note deleted.';
        } else {
            $error = 'Could not delete note.';
        }
        $stmt->close();
    }
}

$query = 'SELECT n.id, n.note_title, n.note_content, s.subject_name FROM notes n LEFT JOIN subjects s ON s.id = n.subject_id WHERE n.user_id = ?';
$params = [$user_id];
$types = 'i';
if ($search !== '') {
    $query .= ' AND (n.note_title LIKE ? OR n.note_content LIKE ?)';
    $like = '%'.$search.'%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
$query .= ' ORDER BY n.updated_at DESC';
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notes = $stmt->get_result();
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
        <h3 class="mb-0"><i class="fas fa-sticky-note text-primary"></i> Notes</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">Add Note</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <form method="GET" class="d-flex gap-2">
                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search notes...">
                <button class="btn btn-outline-primary" type="submit">Search</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($notes->num_rows > 0): ?>
            <?php while ($note = $notes->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($note['note_title']); ?></h5>
                            <form method="POST" onsubmit="return confirm('Delete this note?');">
                                <input type="hidden" name="action" value="delete_note">
                                <input type="hidden" name="note_id" value="<?php echo (int)$note['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($note['subject_name'])): ?><small class="text-muted d-block mb-2"><?php echo htmlspecialchars($note['subject_name']); ?></small><?php endif; ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($note['note_content'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info">No notes found.</div></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_note">
                <div class="modal-body">
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
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="note_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" rows="6" name="note_content" required></textarea>
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
