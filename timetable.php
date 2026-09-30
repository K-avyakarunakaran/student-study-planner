<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Timetable';
$error = '';
$success = '';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_timetable') {
        $day = $_POST['day_of_week'] ?? '';
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $topic_id = !empty($_POST['topic_id']) ? (int)$_POST['topic_id'] : null;
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $location = trim($_POST['location'] ?? '');

        if ($day === '' || $subject_id <= 0 || $start_time === '' || $end_time === '') {
            $error = 'Day, subject and time are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO timetable (user_id, subject_id, topic_id, day_of_week, start_time, end_time, location) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iiissss', $user_id, $subject_id, $topic_id, $day, $start_time, $end_time, $location);
            if ($stmt->execute()) {
                $success = 'Timetable item added.';
            } else {
                $error = 'Could not add timetable item.';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_timetable') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM timetable WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);
        if ($stmt->execute()) {
            $success = 'Timetable entry removed.';
        } else {
            $error = 'Could not remove timetable item.';
        }
        $stmt->close();
    }
}

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
        <h3 class="mb-0"><i class="fas fa-clock text-primary"></i> Weekly Timetable</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">Add Schedule</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Day</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                        <th>Sunday</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="table-light">Schedule</th>
                        <?php foreach ($days as $day): ?>
                            <td>
                                <?php
                                $stmt = $conn->prepare('SELECT t.id, s.subject_name, tp.topic_name, t.start_time, t.end_time, t.location FROM timetable t LEFT JOIN subjects s ON s.id = t.subject_id LEFT JOIN topics tp ON tp.id = t.topic_id WHERE t.user_id = ? AND t.day_of_week = ? ORDER BY t.start_time ASC');
                                $stmt->bind_param('is', $user_id, $day);
                                $stmt->execute();
                                $entries = $stmt->get_result();
                                if ($entries->num_rows > 0):
                                    while ($entry = $entries->fetch_assoc()): ?>
                                        <div class="border rounded p-2 mb-2 bg-light">
                                            <strong><?php echo htmlspecialchars($entry['subject_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($entry['topic_name'] ?? 'General'); ?></small><br>
                                            <small><?php echo date('h:i A', strtotime($entry['start_time'])); ?> - <?php echo date('h:i A', strtotime($entry['end_time'])); ?></small>
                                            <?php if (!empty($entry['location'])): ?><br><small><?php echo htmlspecialchars($entry['location']); ?></small><?php endif; ?>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="action" value="delete_timetable">
                                                <input type="hidden" name="id" value="<?php echo (int)$entry['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger w-100" type="submit">Remove</button>
                                            </form>
                                        </div>
                                    <?php endwhile;
                                } else {
                                    echo '<span class="text-muted">No class</span>';
                                }
                                $stmt->close();
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Timetable Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_timetable">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Day</label>
                        <select class="form-control" name="day_of_week" required>
                            <?php foreach ($days as $day): ?>
                                <option value="<?php echo htmlspecialchars($day); ?>"><?php echo htmlspecialchars($day); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <label class="form-label">Topic</label>
                        <select class="form-control" name="topic_id">
                            <option value="">General</option>
                            <?php
                            $tstmt = $conn->prepare('SELECT id, topic_name FROM topics WHERE user_id = ? ORDER BY topic_name ASC');
                            $tstmt->bind_param('i', $user_id);
                            $tstmt->execute();
                            $tlist = $tstmt->get_result();
                            while ($t = $tlist->fetch_assoc()) {
                                echo '<option value="' . (int)$t['id'] . '">' . htmlspecialchars($t['topic_name']) . '</option>';
                            }
                            $tstmt->close();
                            ?>
                        </select>
                    </div>
                    <div class="row">
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
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="Classroom / Lab">
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
