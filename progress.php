<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Progress';

$stmt = $conn->prepare('SELECT s.subject_name, COUNT(t.id) AS total_topics, SUM(CASE WHEN t.is_completed = 1 THEN 1 ELSE 0 END) AS completed_topics FROM subjects s LEFT JOIN topics t ON t.subject_id = s.id WHERE s.user_id = ? GROUP BY s.id ORDER BY s.subject_name ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$subjectProgress = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total_topics, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS completed_topics FROM topics WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$overall = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_topics = (int)($overall['total_topics'] ?? 0);
$completed_topics = (int)($overall['completed_topics'] ?? 0);
$overall_percent = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-chart-pie text-primary"></i> Progress Tracker</h3>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Total Topics</div>
                    <h2 class="mb-0"><?php echo $total_topics; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Completed Topics</div>
                    <h2 class="mb-0 text-success"><?php echo $completed_topics; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Overall %</div>
                    <h2 class="mb-0 text-primary"><?php echo $overall_percent; ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Subject-wise Progress</h5>
        </div>
        <div class="card-body">
            <?php if ($subjectProgress->num_rows > 0): ?>
                <?php while ($row = $subjectProgress->fetch_assoc()): ?>
                    <?php $done = (int)($row['completed_topics'] ?? 0); $total = (int)($row['total_topics'] ?? 0); $percent = $total > 0 ? round(($done / $total) * 100) : 0; ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong>
                            <small><?php echo $done; ?>/<?php echo $total; ?></small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%;"><?php echo $percent; ?>%</div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted mb-0">No subject progress available yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
