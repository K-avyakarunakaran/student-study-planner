<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $enrollment = trim($_POST['enrollment_no'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $college = trim($_POST['college'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone, enrollment_no, semester, branch, college) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssssss', $name, $email, $hashed, $phone, $enrollment, $semester, $branch, $college);

            if ($stmt->execute()) {
                $success = 'Registration successful. Please login.';
                header('Refresh: 1.5; url=login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Student Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-box {
            background: white;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 650px;
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .register-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .register-body {
            padding: 30px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border: none;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i></h1>
            <h1>Create Account</h1>
            <p class="mb-0">Student Study Planner</p>
        </div>
        <div class="register-body">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Enrollment No.</label>
                        <input type="text" class="form-control" name="enrollment_no">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Semester</label>
                        <select class="form-control" name="semester">
                            <option value="">Select</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" name="branch" placeholder="BCA">
                    </div>
                    <div class="col-12">
                        <label class="form-label">College</label>
                        <input type="text" class="form-control" name="college">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-4">Register</button>
            </form>
            <div class="mt-3 text-center">
                <span>Already have account? </span>
                <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>
