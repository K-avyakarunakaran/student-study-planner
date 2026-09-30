<?php
/**
 * Subjects Page - Student Study Planner
 * Allows students to manage subjects and topics
 */

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Subjects";
$error = '';
$success = '';

// Handle add subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_subject') {
        $subject_name = trim($_POST['subject_name'] ?? '');
        $subject_code = trim($_POST['subject_code'] ?? '');
        $credits = $_POST['credits'] ?? 0;
        $professor_name = trim($_POST['professor_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($subject_name)) {
            $error = "Subject name is required!";
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_name, subject_code, credits, professor_name, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issis", $user_id, $subject_name, $subject_code, $credits, $professor_name, $description);
            
            if ($stmt->execute()) {
                $success = "Subject added successfully!";
            } else {
                $error = "Failed to add subject!";
            }
            $stmt->close();
        }
    }
    
    // Handle add topic
    elseif ($_POST['action'] == 'add_topic') {
        $subject_id = $_POST['subject_id'] ?? 0;
        $topic_name = trim($_POST['topic_name'] ?? '');
        $topic_description = trim($_POST['topic_description'] ?? '');
        
        if (empty($topic_name) || $subject_id == 0) {
            $error = "Topic name and subject are required!";
        } else {
            $stmt = $conn->prepare("INSERT INTO topics (subject_id, user_id, topic_name, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $subject_id, $user_id, $topic_name, $topic_description);
            
            if ($stmt->execute()) {
                $success = "Topic added successfully!";
            } else {
                $error = "Failed to add topic!";
            }
            $stmt->close();
        }
    }
    
    // Handle delete subject
    elseif ($_POST['action'] == 'delete_subject') {
        $subject_id = $_POST['subject_id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $subject_id, $user_id);
        
        if ($stmt->execute()) {
            $success = "Subject deleted successfully!";
        } else {
            $error = "Failed to delete subject!";
        }
        $stmt->close();
    }
    
    // Handle toggle topic completion
    elseif ($_POST['action'] == 'toggle_topic') {
        $topic_id = $_POST['topic_id'] ?? 0;
        $current_status = $_POST['current_status'] ?? 0;
        $new_status = $current_status == 1 ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE topics SET is_completed = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $new_status, $topic_id, $user_id);
        
        if ($stmt->execute()) {
            $success = "Topic status updated!";
        } else {
            $error = "Failed to update topic!";
        }
        $stmt->close();
    }
}

// Get all subjects
$stmt = $conn->prepare("SELECT id, subject_name, subject_code, credits, professor_name, description FROM subjects WHERE user_id = ? ORDER BY subject_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjects = $stmt->get_result();
$stmt->close();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3"><i class="fas fa-book text-primary"></i> Subjects</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="fas fa-plus"></i> Add Subject
                </button>
            </div>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($subjects->num_rows > 0): ?>
        <div class="row">
            <?php while ($subject = $subjects->fetch_assoc()): ?>
                <?php
                // Get topics for this subject
                $stmt = $conn->prepare("SELECT id, topic_name, is_completed FROM topics WHERE subject_id = ? ORDER BY topic_name");
                $stmt->bind_param("i", $subject['id']);
                $stmt->execute();
                $topics = $stmt->get_result();
                $stmt->close();
                ?>
                
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                    <?php if (!empty($subject['subject_code'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this subject?');">
                                    <input type="hidden" name="action" value="delete_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($subject['professor_name'])): ?>
                                <p class="mb-2"><strong>Professor:</strong> <?php echo htmlspecialchars($subject['professor_name']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($subject['description'])): ?>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($subject['description']); ?></p>
                            <?php endif; ?>
                            
                            <h6 class="mt-3 mb-2">Topics (<?php echo $topics->num_rows; ?>)</h6>
                            
                            <?php if ($topics->num_rows > 0): ?>
                                <div class="list-group list-group-sm list-group-flush">
                                    <?php while ($topic = $topics->fetch_assoc()): ?>
                                        <form method="POST" class="list-group-item d-flex justify-content-between align-items-center">
                                            <input type="hidden" name="action" value="toggle_topic">
                                            <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $topic['is_completed']; ?>">
                                            
                                            <label class="mb-0 flex-grow-1" style="cursor: pointer;">
                                                <input type="checkbox" <?php echo $topic['is_completed'] ? 'checked' : ''; ?> onchange="this.form.submit();" style="cursor: pointer;">
                                                <span class="ms-2 <?php echo $topic['is_completed'] ? 'text-muted text-decoration-line-through' : ''; ?>">
                                                    <?php echo htmlspecialchars($topic['topic_name']); ?>
                                                </span>
                                            </label>
                                            <?php if ($topic['is_completed']): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php endif; ?>
                                        </form>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small mb-0">No topics added yet</p>
                            <?php endif; ?>
                            
                            <button class="btn btn-sm btn-outline-primary mt-3 w-100" data-bs-toggle="modal" data-bs-target="#addTopicModal<?php echo $subject['id']; ?>">
                                <i class="fas fa-plus"></i> Add Topic
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Add Topic Modal -->
                <div class="modal fade" id="addTopicModal<?php echo $subject['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Topic to <?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="add_topic">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                    
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
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Topic</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No subjects added yet. <a href="#" data-bs-toggle="modal" data-bs-target="#addSubjectModal" class="alert-link">Add your first subject</a>
        </div>
    <?php endif; ?>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_subject">
                    
                    <div class="mb-3">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" class="form-control" name="subject_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject Code</label>
                        <input type="text" class="form-control" name="subject_code" placeholder="e.g., CS101">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Credits</label>
                        <input type="number" class="form-control" name="credits" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Professor Name</label>
                        <input type="text" class="form-control" name="professor_name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
