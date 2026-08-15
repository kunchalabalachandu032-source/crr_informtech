<?php
// ========================================================================
// DATABASE CONNECTION & AUTOMATIC MULTI-TABLE COMPATIBILITY (crr_informtech)
// ========================================================================

$host = "localhost";
$user = "root";
$password = "";

// Primary Database Name matching user's phpMyAdmin (with underscore)
$database = "crr_informtech";

// Connect to MySQL Server
$conn = @mysqli_connect($host, $user, $password);

if (!$conn) {
    die("<div style='padding:20px; color:red; font-family:sans-serif;'>
        <h3>Database Connection Error</h3>
        <p>Could not connect to MySQL server at <b>$host</b>. Please check if MySQL (XAMPP/WAMP) is running.</p>
        </div>");
}

// Select Database (or create if not exists)
if (!mysqli_select_db($conn, $database)) {
    if (!mysqli_select_db($conn, "crrinformtech")) {
        mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_select_db($conn, $database);
    } else {
        $database = "crrinformtech";
    }
}

// Helper function to execute safe string escaping
function db_escape($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Global Upload Directory helper
define('BASE_DIR', __DIR__ . '/..');
define('UPLOADS_DIR', BASE_DIR . '/uploads');

// Ensure upload subfolders exist
$upload_folders = ['works', 'assignments', 'mid_marks', 'important_questions', 'observations', 'records', 'faculty', 'announcements'];
foreach ($upload_folders as $folder) {
    $path = UPLOADS_DIR . '/' . $folder;
    if (!file_exists($path)) {
        @mkdir($path, 0777, true);
    }
}

// ------------------------------------------------------------------------
// UNIVERSAL AUTO MIGRATION & COLUMN SYNC
// ------------------------------------------------------------------------

function ensure_columns_exist($table_name, $required_columns) {
    global $conn;
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `$table_name` (`id` INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    foreach ($required_columns as $col => $definition) {
        $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE '$col'");
        if ($chk && mysqli_num_rows($chk) == 0) {
            // Check for common column aliases
            if ($col === 'subject_name') {
                $chk_alt = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'subject'");
                if ($chk_alt && mysqli_num_rows($chk_alt) > 0) {
                    @mysqli_query($conn, "ALTER TABLE `$table_name` CHANGE COLUMN `subject` `subject_name` VARCHAR(150) NOT NULL");
                    continue;
                }
            }
            if ($col === 'roll_number') {
                $chk_alt = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'roll_no'");
                if ($chk_alt && mysqli_num_rows($chk_alt) > 0) {
                    @mysqli_query($conn, "ALTER TABLE `$table_name` CHANGE COLUMN `roll_no` `roll_number` VARCHAR(50) DEFAULT NULL");
                    continue;
                }
            }
            if ($col === 'unit_number') {
                $chk_alt = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'unit_no'");
                if ($chk_alt && mysqli_num_rows($chk_alt) > 0) {
                    @mysqli_query($conn, "ALTER TABLE `$table_name` CHANGE COLUMN `unit_no` `unit_number` VARCHAR(50) DEFAULT NULL");
                    continue;
                }
            }
            if ($col === 'photo_path') {
                $chk_alt = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'photo'");
                if ($chk_alt && mysqli_num_rows($chk_alt) > 0) {
                    @mysqli_query($conn, "ALTER TABLE `$table_name` CHANGE COLUMN `photo` `photo_path` VARCHAR(255) DEFAULT NULL");
                    continue;
                }
            }
            if ($col === 'image_path') {
                $chk_alt = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'poster'");
                if ($chk_alt && mysqli_num_rows($chk_alt) > 0) {
                    @mysqli_query($conn, "ALTER TABLE `$table_name` CHANGE COLUMN `poster` `image_path` VARCHAR(255) DEFAULT NULL");
                    continue;
                }
            }
            @mysqli_query($conn, "ALTER TABLE `$table_name` ADD COLUMN `$col` $definition");
        }
    }
}

// 1. cr_accounts & crs sync
ensure_columns_exist('cr_accounts', [
    'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'roll_number' => "VARCHAR(50) NOT NULL DEFAULT ''",
    'year' => "VARCHAR(10) NOT NULL DEFAULT '2'",
    'section' => "VARCHAR(10) NOT NULL DEFAULT 'IT2A'",
    'email' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'phone' => "VARCHAR(20) DEFAULT NULL",
    'password' => "VARCHAR(255) NOT NULL DEFAULT ''"
]);

ensure_columns_exist('crs', [
    'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'roll_number' => "VARCHAR(50) NOT NULL DEFAULT ''",
    'year' => "VARCHAR(10) NOT NULL DEFAULT '2'",
    'section' => "VARCHAR(10) NOT NULL DEFAULT 'IT2A'",
    'email' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'phone' => "VARCHAR(20) DEFAULT NULL",
    'password' => "VARCHAR(255) NOT NULL DEFAULT ''"
]);

// 2. subjects
ensure_columns_exist('subjects', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'subject_type' => "VARCHAR(20) NOT NULL DEFAULT 'Theory'"
]);

// 3. class_works
ensure_columns_exist('class_works', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'description' => "TEXT",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// 4. assignments
ensure_columns_exist('assignments', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'description' => "TEXT",
    'due_date' => "DATE DEFAULT NULL",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// 5. mid_marks
ensure_columns_exist('mid_marks', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'title' => "VARCHAR(255) DEFAULT NULL",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// 6. important_questions
ensure_columns_exist('important_questions', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'unit_number' => "VARCHAR(50) DEFAULT NULL",
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'questions_text' => "TEXT",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// 7. lab_observations
ensure_columns_exist('lab_observations', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'experiment_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// 8. lab_records
ensure_columns_exist('lab_records', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// 9. faculty
ensure_columns_exist('faculty', [
    'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'designation' => "VARCHAR(100) NOT NULL DEFAULT ''"
]);

// 10. announcements
ensure_columns_exist('announcements', [
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'content' => "TEXT",
    'target_audience' => "VARCHAR(50) NOT NULL DEFAULT 'All'",
    'image_path' => "VARCHAR(255) DEFAULT NULL"
]);

// Seed default Admin
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@mysqli_query($conn, "INSERT IGNORE INTO `admins` (username, password) VALUES ('admin', 'admin123')");
?>