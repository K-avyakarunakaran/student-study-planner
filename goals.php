<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Goals';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_goal') {
        $goal_title = trim($_POST['goal_title'] ?? '');
        $goal_type = $_POST['goal_type'] ?? 'Daily';
        $target_hours = (int)($_POST['target_hours'] ?? 1);
        $target_date = $_POST['target_date'] ?? date('Y-m-d');

        if ($goal_title === '') {
            $error = 'Goal title is required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO goals (user_id, goal_title, goal_type, target_hours, target_date, status) VALUES (?, ?, ?, ?, ?, "Active")');
            $stmt->bind_param('isiss', $user_id, $goal_title, $goal_type, $target_hours, $target_date);
            if ($stmt->execute()) {
                $success = 'Goal created successfully.';
            } else {
                $error = 'Could not create goal.';
            }
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare('SELECT * FROM goals WHERE user_id = ? ORDER BY target_date ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$goals = $stmt->get_result();
$stmt->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-bullseye text-primary"></i> Goals</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">Add Goal</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($goals->num_rows > 0): ?>
            <?php while ($goal = $goals->fetch_assoc()): ?>
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($goal['goal_title']); ?></h5>
                            <span class="badge bg-<?php echo $goal['goal_type'] === 'Daily' ? 'info' : ($goal['goal_type'] === 'Weekly' ? 'success' : 'warning text-dark'); ?>"><?php echo htmlspecialchars($goal['goal_type']); ?></span>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Target date:</strong> <?php echo date('M d, Y', strtotime($goal['target_date'])); ?></p>
                            <p class="mb-2"><strong>Target hours:</strong> <?php echo (int)$goal['target_hours']; ?> hrs</p>
                            <p class="mb-3"><strong>Status:</strong> <?php echo htmlspecialchars($goal['status']); ?></p>
                            <div class="progress" style="height: 22px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $goal['status'] === 'Completed' ? '100%' : '50%'; ?>;"><?php echo $goal['status'] === 'Completed' ? '100%' : '50%'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info">No goals yet.</div></div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_goal">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Goal Title</label>
                        <input type="text" class="form-control" name="goal_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Goal Type</label>
                        <select class="form-control" name="goal_type">
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Hours</label>
                        <input type="number" class="form-control" name="target_hours" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Date</label>
                        <input type="date" class="form-control" name="target_date" value="<?php echo date('Y-m-d'); ?>">
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
