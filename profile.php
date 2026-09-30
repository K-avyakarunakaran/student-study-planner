<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Profile';
$error = '';
$success = '';

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $enrollment = trim($_POST['enrollment_no'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $college = trim($_POST['college'] ?? '');

        if ($name === '' || $email === '') {
            $error = 'Name and email are required.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ?, enrollment_no = ?, semester = ?, branch = ?, college = ? WHERE id = ?');
            $stmt->bind_param('ssssissi', $name, $email, $phone, $enrollment, $semester, $branch, $college, $user_id);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = 'Profile updated successfully.';
                $user = ['name' => $name, 'email' => $email, 'phone' => $phone, 'enrollment_no' => $enrollment, 'semester' => $semester, 'branch' => $branch, 'college' => $college];
            } else {
                $error = 'Could not update profile.';
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="fas fa-user-circle text-primary"></i> Profile</h3>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Enrollment No.</label>
                        <input type="text" class="form-control" name="enrollment_no" value="<?php echo htmlspecialchars($user['enrollment_no'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Semester</label>
                        <select class="form-control" name="semester">
                            <option value="">Select</option>
                            <?php for ($s = 1; $s <= 6; $s++): ?>
                                <option value="<?php echo $s; ?>" <?php echo ($user['semester'] ?? '') == $s ? 'selected' : ''; ?>>Semester <?php echo $s; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" name="branch" value="<?php echo htmlspecialchars($user['branch'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">College</label>
                        <input type="text" class="form-control" name="college" value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
