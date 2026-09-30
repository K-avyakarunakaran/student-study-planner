<?php
/**
 * Sidebar Include - Student Study Planner
 * Navigation sidebar for all dashboard pages
 */

// Determine current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<nav class="sidebar bg-dark text-white p-0" style="width: 250px; position: fixed; height: calc(100vh - 56px); top: 56px; left: 0; overflow-y: auto;">
    <div class="nav flex-column p-3">
        <!-- Dashboard -->
        <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        
        <!-- Subjects -->
        <a href="subjects.php" class="nav-link <?php echo $current_page == 'subjects.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-book"></i> Subjects
        </a>
        
        <!-- Study Planner -->
        <a href="planner.php" class="nav-link <?php echo $current_page == 'planner.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-calendar-alt"></i> Study Planner
        </a>
        
        <!-- Tasks -->
        <a href="tasks.php" class="nav-link <?php echo $current_page == 'tasks.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-tasks"></i> Tasks
        </a>
        
        <!-- Progress -->
        <a href="progress.php" class="nav-link <?php echo $current_page == 'progress.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-chart-pie"></i> Progress
        </a>
        
        <!-- Timetable -->
        <a href="timetable.php" class="nav-link <?php echo $current_page == 'timetable.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-clock"></i> Timetable
        </a>
        
        <!-- Goals -->
        <a href="goals.php" class="nav-link <?php echo $current_page == 'goals.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-target"></i> Goals
        </a>
        
        <!-- Notes -->
        <a href="notes.php" class="nav-link <?php echo $current_page == 'notes.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-sticky-note"></i> Notes
        </a>
        
        <!-- Reminders -->
        <a href="reminders.php" class="nav-link <?php echo $current_page == 'reminders.php' ? 'active bg-primary' : 'text-white'; ?> mb-2 rounded">
            <i class="fas fa-bell"></i> Reminders
        </a>
    </div>
</nav>

<style>
    .sidebar {
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar .nav-link {
        transition: all 0.3s ease;
        padding: 12px 15px;
    }
    
    .sidebar .nav-link:hover {
        background-color: #007bff;
        transform: translateX(5px);
    }
    
    .sidebar .nav-link.active {
        background-color: #007bff !important;
        box-shadow: inset 4px 0 0 #fff;
    }
    
    .main-content {
        margin-left: 250px;
        flex: 1;
        padding: 30px;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
            width: 200px;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
        }
    }
</style>

<!-- Main Content Wrapper -->
<div class="main-content">
