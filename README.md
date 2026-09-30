# Student Study Planner and Progress Tracker

A responsive web application for BCA students to manage:
- login and registration
- dashboard statistics
- subjects and topics
- study planner
- tasks
- progress tracking
- timetable
- goals
- notes
- reminders
- profile
- logout

## Tech Stack
- PHP 8.2
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap 5

## Structure
- `config/database.php`
- `sql/database.sql`
- `index.php`
- `login.php`
- `register.php`
- `logout.php`
- `dashboard.php`
- `subjects.php`
- `planner.php`
- `tasks.php`
- `progress.php`
- `timetable.php`
- `goals.php`
- `notes.php`
- `reminders.php`
- `profile.php`
- `includes/header.php`
- `includes/sidebar.php`
- `includes/footer.php`
- `css/style.css`
- `js/script.js`

## Setup
1. Start Apache and MySQL in XAMPP.
2. Create a MySQL database named `student_study_planner`.
3. Import `sql/database.sql`.
4. Put this project inside `htdocs`.
5. Open `http://localhost/student-study-planner` in the browser.

## Database config
Edit `config/database.php` if your MySQL credentials are different.

## Default login
After registration, use your student email and password to login.

## Notes
This project is built for a BCA final-year demonstration and uses PHP sessions plus prepared statements for safe database access.
