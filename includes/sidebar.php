<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="bg-dark text-white" style="min-width: 240px; min-height: calc(100vh - 56px);">
    <div class="p-3">
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line me-2"></i> Dashboard
            </a>
            <a href="subjects.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'subjects.php' ? 'active' : ''; ?>">
                <i class="fas fa-book me-2"></i> Subjects
            </a>
            <a href="planner.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'planner.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt me-2"></i> Study Planner
            </a>
            <a href="tasks.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'tasks.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks me-2"></i> Tasks
            </a>
            <a href="progress.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'progress.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie me-2"></i> Progress
            </a>
            <a href="timetable.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                <i class="fas fa-clock me-2"></i> Timetable
            </a>
            <a href="goals.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'goals.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullseye me-2"></i> Goals
            </a>
            <a href="notes.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'notes.php' ? 'active' : ''; ?>">
                <i class="fas fa-sticky-note me-2"></i> Notes
            </a>
            <a href="reminders.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'reminders.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell me-2"></i> Reminders
            </a>
            <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-white <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>
</aside>
<div class="flex-grow-1 p-4 bg-light" style="min-height: calc(100vh - 56px);">
