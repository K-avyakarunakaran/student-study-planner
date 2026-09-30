<?php
/**
 * Progress Tracker Page - Student Study Planner
 * Displays subject-wise progress, completed topics, and overall completion summary
 */

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Progress Tracker";

// Subject-wise progress summary
$stmt = $conn->prepare("SELECT s.subject_name, COUNT(t.id) as total_topics, SUM(CASE WHEN t.is_completed = 1 THEN 1 ELSE 0 END) as completed_topics FROM subjects s LEFT JOIN topics t ON s.id = t.subject_id WHERE s.user_id = ? GROUP BY s.id ORDER BY s.subject_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress_summary = $stmt->get_result();
$stmt->close();

// Overall stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_topics, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_topics FROM topics WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overall = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_topics = $overall['total_topics'] ?? 0;
$completed_topics = $overall['completed_topics'] ?? 0;
$overall_percentage = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-chart-pie text-primary"></i> Progress Tracker</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Topics</h6>
                    <h2 class="text-primary mb-0"><?php echo $total_topics; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted">Completed Topics</h6>
                    <h2 class="text-success mb-0"><?php echo $completed_topics; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted">Overall Completion</h6>
                    <h2 class="text-warning mb-0"><?php echo $overall_percentage; ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-bar text-primary"></i> Subject-wise Progress</h5>
                </div>
                <div class="card-body">
                    <?php if ($progress_summary->num_rows > 0): ?>
                        <?php while ($row = $progress_summary->fetch_assoc()): ?>
                            <?php
                            $completed = (int)($row['completed_topics'] ?? 0);
                            $total = (int)($row['total_topics'] ?? 0);
                            $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                            ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong>
                                    <small><?php echo $completed; ?> / <?php echo $total; ?> topics</small>
                                </div>
                                <div class="progress" style="height: 22px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percent; ?>%;">
                                        <?php echo $percent; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book-open fa-3x text-muted mb-2"></i>
                            <p>No subject progress available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-circle-info text-info"></i> Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Completed</span>
                            <strong class="text-success"><?php echo $completed_topics; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Remaining</span>
                            <strong class="text-warning"><?php echo $total_topics - $completed_topics; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Completion</span>
                            <strong class="text-primary"><?php echo $overall_percentage; ?>%</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
