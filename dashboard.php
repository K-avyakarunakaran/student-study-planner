<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard';

$stmt = $conn->prepare('SELECT COUNT(*) AS total_subjects FROM subjects WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_subjects = $stmt->get_result()->fetch_assoc()['total_subjects'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total_tasks FROM tasks WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_tasks = $stmt->get_result()->fetch_assoc()['total_tasks'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS completed_tasks FROM tasks WHERE user_id = ? AND status = "Completed"');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$completed_tasks = $stmt->get_result()->fetch_assoc()['completed_tasks'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS pending_tasks FROM tasks WHERE user_id = ? AND status = "Pending"');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$pending_tasks = $stmt->get_result()->fetch_assoc()['pending_tasks'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total_topics, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS completed_topics FROM topics WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$topic_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total_topics = (int)($topic_data['total_topics'] ?? 0);
$completed_topics = (int)($topic_data['completed_topics'] ?? 0);
$topic_percent = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;

$overall_percent = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

$today = date('Y-m-d');
$stmt = $conn->prepare('SELECT sp.id, s.subject_name, sp.start_time, sp.end_time, sp.status FROM study_plans sp JOIN subjects s ON s.id = sp.subject_id WHERE sp.user_id = ? AND sp.study_date = ? ORDER BY sp.start_time ASC');
$stmt->bind_param('is', $user_id, $today);
$stmt->execute();
$today_schedule = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare('(SELECT "Exam" AS type, exam_name AS name, exam_date AS event_date FROM exams WHERE user_id = ? AND exam_date >= CURDATE() ORDER BY exam_date ASC LIMIT 5) UNION (SELECT "Assignment" AS type, assignment_name AS name, due_date AS event_date FROM assignments WHERE user_id = ? AND due_date >= CURDATE() ORDER BY due_date ASC LIMIT 5)');
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$reminders = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare('SELECT subject_name, COUNT(*) AS total, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS done FROM subjects s LEFT JOIN topics t ON t.subject_id = s.id WHERE s.user_id = ? GROUP BY s.id ORDER BY s.subject_name');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$subject_progress = $stmt->get_result();
$stmt->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-1"><i class="fas fa-chart-line text-primary"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h3>
            <p class="text-muted mb-0">Your study progress overview</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Subjects</div>
                            <h3 class="mb-0"><?php echo $total_subjects; ?></h3>
                        </div>
                        <i class="fas fa-book text-primary fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Tasks</div>
                            <h3 class="mb-0"><?php echo $total_tasks; ?></h3>
                        </div>
                        <i class="fas fa-tasks text-warning fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Completed</div>
                            <h3 class="mb-0"><?php echo $completed_tasks; ?></h3>
                        </div>
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Pending</div>
                            <h3 class="mb-0"><?php echo $pending_tasks; ?></h3>
                        </div>
                        <i class="fas fa-clock text-danger fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-pie text-primary"></i> Overall Progress</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h1 class="display-6 mb-0"><?php echo $overall_percent; ?>%</h1>
                    </div>
                    <div class="progress" style="height: 22px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $overall_percent; ?>%;"><?php echo $overall_percent; ?>%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap text-success"></i> Topic Progress</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h1 class="display-6 mb-0"><?php echo $topic_percent; ?>%</h1>
                    </div>
                    <div class="progress" style="height: 22px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $topic_percent; ?>%;"><?php echo $topic_percent; ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-calendar-day text-info"></i> Today’s Study Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if ($today_schedule->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($item = $today_schedule->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['subject_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($item['start_time'])); ?> - <?php echo date('h:i A', strtotime($item['end_time'])); ?></small>
                                        </div>
                                        <span class="badge <?php echo $item['status'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo htmlspecialchars($item['status']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No schedule assigned for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-bell text-danger"></i> Upcoming Exams & Assignments</h5>
                </div>
                <div class="card-body">
                    <?php if ($reminders->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($row = $reminders->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($row['event_date'])); ?></small>
                                        </div>
                                        <span class="badge <?php echo $row['type'] === 'Exam' ? 'bg-danger' : 'bg-info'; ?>"><?php echo $row['type']; ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No upcoming reminders.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-book text-primary"></i> Subject-wise Progress</h5>
                </div>
                <div class="card-body">
                    <?php if ($subject_progress->num_rows > 0): ?>
                        <?php while ($row = $subject_progress->fetch_assoc()): ?>
                            <?php $done = (int)($row['done'] ?? 0); $total = (int)($row['total'] ?? 0); $percent = $total > 0 ? round(($done / $total) * 100) : 0; ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between"><strong><?php echo htmlspecialchars($row['subject_name']); ?></strong><small><?php echo $done; ?>/<?php echo $total; ?></small></div>
                                <div class="progress" style="height: 18px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percent; ?>%;"><?php echo $percent; ?>%</div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Add subjects to view progress.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
