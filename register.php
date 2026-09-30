<?php
/**
 * Registration Page - Student Study Planner
 * Handles new student registration with validation
 */

session_start();
require_once 'config/database.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $enrollment_no = trim($_POST['enrollment_no'] ?? '');
    $semester = $_POST['semester'] ?? '';
    $branch = trim($_POST['branch'] ?? '');
    $college = trim($_POST['college'] ?? '');
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, email, and password are required!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, enrollment_no, semester, branch, college) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisss", $name, $email, $hashed_password, $phone, $enrollment_no, $semester, $branch, $college);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login...";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Registration failed. Please try again!";
            }
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .register-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .register-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        .register-body {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .row-cols-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 576px) {
            .row-cols-2 {
                grid-template-columns: 1fr;
            }
            .register-body {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-book-reader"></i></h1>
            <h1>Study Planner</h1>
            <p>Create Your Account</p>
        </div>
        
        <div class="register-body">
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
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" class="form-control" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" class="form-control" name="phone" placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-id-card"></i> Enrollment Number</label>
                    <input type="text" class="form-control" name="enrollment_no" placeholder="Enter enrollment number">
                </div>
                
                <div class="row-cols-2">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-graduation-cap"></i> Semester</label>
                        <select class="form-control" name="semester">
                            <option value="">Select Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-code"></i> Branch</label>
                        <select class="form-control" name="branch">
                            <option value="">Select Branch</option>
                            <option value="BCA">BCA</option>
                            <option value="B.Tech">B.Tech</option>
                            <option value="B.Sc">B.Sc</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-university"></i> College Name</label>
                    <input type="text" class="form-control" name="college" placeholder="Enter college name">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter password (min 6 characters)" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login Here</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
