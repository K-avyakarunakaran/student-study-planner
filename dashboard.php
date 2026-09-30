<?php
/**
 * Dashboard Page - Student Study Planner
 * Shows overall statistics and quick overview of student's academic progress
 */

session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Dashboard";

// Get dashboard statistics
// Total Subjects
$stmt = $conn->prepare("SELECT COUNT(*) as total_subjects FROM subjects WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjects_result = $stmt->get_result();
$total_subjects = $subjects_result->fetch_assoc()['total_subjects'];
$stmt->close();

// Total Tasks
$stmt = $conn->prepare("SELECT COUNT(*) as total_tasks FROM tasks WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
$total_tasks = $tasks_result->fetch_assoc()['total_tasks'];
$stmt->close();

// Completed Tasks
$stmt = $conn->prepare("SELECT COUNT(*) as completed_tasks FROM tasks WHERE user_id = ? AND status = 'Completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();
$completed_tasks = $completed_result->fetch_assoc()['completed_tasks'];
$stmt->close();

// Pending Tasks
$stmt = $conn->prepare("SELECT COUNT(*) as pending_tasks FROM tasks WHERE user_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_tasks = $pending_result->fetch_assoc()['pending_tasks'];
$stmt->close();

// Overdue Tasks
$stmt = $conn->prepare("SELECT COUNT(*) as overdue_tasks FROM tasks WHERE user_id = ? AND status = 'Overdue'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overdue_result = $stmt->get_result();
$overdue_tasks = $overdue_result->fetch_assoc()['overdue_tasks'];
$stmt->close();

// Overall progress percentage
$progress_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Get today's study schedule
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT sp.id, s.subject_name, sp.start_time, sp.end_time, sp.status FROM study_plans sp JOIN subjects s ON sp.subject_id = s.id WHERE sp.user_id = ? AND sp.study_date = ? ORDER BY sp.start_time");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_schedule = $stmt->get_result();
$stmt->close();

// Get upcoming exams/assignments
$stmt = $conn->prepare("(SELECT 'Exam' as type, exam_name as name, subject_id, exam_date as date FROM exams WHERE user_id = ? AND exam_date >= CURDATE()) UNION (SELECT 'Assignment' as type, assignment_name as name, subject_id, due_date as date FROM assignments WHERE user_id = ? AND due_date >= CURDATE() AND status != 'Graded') ORDER BY date LIMIT 5");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$upcoming = $stmt->get_result();
$stmt->close();

// Get total topics and completed topics
$stmt = $conn->prepare("SELECT COUNT(*) as total_topics, SUM(CASE WHEN is_completed = TRUE THEN 1 ELSE 0 END) as completed_topics FROM topics WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$topics_result = $stmt->get_result();
$topics_data = $topics_result->fetch_assoc();
$total_topics = $topics_data['total_topics'] ?? 0;
$completed_topics = $topics_data['completed_topics'] ?? 0;
$stmt->close();

// Get subject-wise completion
$stmt = $conn->prepare("SELECT s.subject_name, COUNT(t.id) as total, SUM(CASE WHEN t.is_completed = TRUE THEN 1 ELSE 0 END) as completed FROM subjects s LEFT JOIN topics t ON s.id = t.subject_id WHERE s.user_id = ? GROUP BY s.id ORDER BY s.subject_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subject_progress = $stmt->get_result();
$stmt->close();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3">
                <i class="fas fa-chart-line text-primary"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
            </h1>
            <p class="text-muted">Here's your academic progress overview for today</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #667eea;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-2">Total Subjects</h6>
                            <h2 class="text-primary mb-0"><?php echo $total_subjects; ?></h2>
                        </div>
                        <i class="fas fa-book fa-3x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #f59e0b;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-2">Total Tasks</h6>
                            <h2 class="text-warning mb-0"><?php echo $total_tasks; ?></h2>
                        </div>
                        <i class="fas fa-tasks fa-3x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #10b981;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-2">Completed Tasks</h6>
                            <h2 class="text-success mb-0"><?php echo $completed_tasks; ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ef4444;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-2">Pending Tasks</h6>
                            <h2 class="text-danger mb-0"><?php echo $pending_tasks; ?></h2>
                        </div>
                        <i class="fas fa-clock fa-3x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress and Topics Section -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-pie text-primary"></i> Overall Progress</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div style="font-size: 48px; font-weight: bold; color: #667eea;"><?php echo $progress_percentage; ?>%</div>
                        <p class="text-muted">of tasks completed</p>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progress_percentage; ?>%;" aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $progress_percentage; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="fas fa-graduation-cap text-success"></i> Topics Progress</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <p class="text-muted mb-1">Total Topics</p>
                                <h3 class="text-primary"><?php echo $total_topics; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <p class="text-muted mb-1">Completed</p>
                                <h3 class="text-success"><?php echo $completed_topics; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0; ?>%;" aria-valuenow="<?php echo $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule and Upcoming Events -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="fas fa-calendar-alt text-info"></i> Today's Study Schedule</h5>
                </div>
                <div class="card-body">
                    <?php if ($today_schedule->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($schedule = $today_schedule->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($schedule['subject_name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $schedule['status'] == 'Completed' ? 'success' : 'warning'; ?>">
                                            <?php echo $schedule['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-3x text-muted mb-2"></i>
                            <p class="text-muted">No classes scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="fas fa-bell text-danger"></i> Upcoming Exams & Assignments</h5>
                </div>
                <div class="card-body">
                    <?php if ($upcoming->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($event = $upcoming->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($event['name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $event['type'] == 'Exam' ? 'danger' : 'info'; ?>">
                                            <?php echo $event['type']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-muted mb-2"></i>
                            <p class="text-muted">No upcoming exams or assignments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Subject-wise Progress -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="fas fa-book text-primary"></i> Subject-wise Progress</h5>
                </div>
                <div class="card-body">
                    <?php if ($subject_progress->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Topics Completed</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($subject = $subject_progress->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo $subject['completed'] ?? 0; ?> / <?php echo $subject['total'] ?? 0; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $subject['total'] > 0 ? round(($subject['completed'] / $subject['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $subject['total'] > 0 ? round(($subject['completed'] / $subject['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $subject['total'] > 0 ? round(($subject['completed'] / $subject['total']) * 100) : 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                            <p class="text-muted">No subjects added yet. <a href="subjects.php">Add a subject</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
