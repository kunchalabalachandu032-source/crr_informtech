<?php
session_start();
require_once "../database/database.php";

// Session Auth Guard (Admin or CR)
if ((!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) && (!isset($_SESSION['cr_logged_in']) || $_SESSION['cr_logged_in'] !== true)) {
    header("Location: ../cr/login.php");
    exit();
}

$year = db_escape($_GET['year'] ?? '');
$section = db_escape($_GET['section'] ?? '');
$subject = db_escape($_GET['subject'] ?? '');

if (empty($year) || empty($section) || empty($subject)) {
    header("Location: years.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Dynamic Column Auto-Fix for `assignments` table
$chk_name_col = @mysqli_query($conn, "SHOW COLUMNS FROM `assignments` LIKE 'subject_name'");
if ($chk_name_col && mysqli_num_rows($chk_name_col) == 0) {
    $chk_sub_col = @mysqli_query($conn, "SHOW COLUMNS FROM `assignments` LIKE 'subject'");
    if ($chk_sub_col && mysqli_num_rows($chk_sub_col) > 0) {
        @mysqli_query($conn, "ALTER TABLE `assignments` CHANGE COLUMN `subject` `subject_name` VARCHAR(150) NOT NULL");
    } else {
        @mysqli_query($conn, "ALTER TABLE `assignments` ADD COLUMN `subject_name` VARCHAR(150) NOT NULL");
    }
}

// ------------------------------------------------------------------------
// ACTION HANDLERS (ADD, DELETE ASSIGNMENT)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD ASSIGNMENT (WITH FILE UPLOAD & DUE DATE)
    if ($action === 'add') {
        $title = db_escape($_POST['title'] ?? '');
        $description = db_escape($_POST['description'] ?? '');
        $due_date = db_escape($_POST['due_date'] ?? '');
        $file_path = "";

        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($_FILES['assignment_file']['name']));
            $targetDir = UPLOADS_DIR . '/assignments/';
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetFile)) {
                $file_path = 'uploads/assignments/' . $fileName;
            }
        }

        if (!empty($title)) {
            $query = "INSERT INTO assignments (year, section, subject_name, title, description, due_date, file_path) 
                      VALUES ('$year', '$section', '$subject', '$title', '$description', '$due_date', '$file_path')";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Assignment <b>$title</b> posted successfully!";
            } else {
                $error_msg = "Error adding assignment: " . mysqli_error($conn);
            }
        }
    }

    // 2. DELETE ASSIGNMENT
    elseif ($action === 'delete') {
        $id = intval($_POST['assignment_id'] ?? 0);
        if ($id > 0) {
            $res = mysqli_query($conn, "SELECT file_path FROM assignments WHERE id=$id");
            if ($r = mysqli_fetch_assoc($res)) {
                if (!empty($r['file_path']) && file_exists(BASE_DIR . '/' . $r['file_path'])) {
                    @unlink(BASE_DIR . '/' . $r['file_path']);
                }
            }
            mysqli_query($conn, "DELETE FROM assignments WHERE id=$id");
            $success_msg = "Assignment deleted successfully!";
        }
    }
}

// Safe Fetch Assignments Query with fallback column match
$assign_query = "SELECT * FROM assignments WHERE year='$year' AND section='$section' AND subject_name='$subject' ORDER BY id DESC";
$chk_col_exists = @mysqli_query($conn, "SHOW COLUMNS FROM `assignments` LIKE 'subject_name'");
if (!$chk_col_exists || mysqli_num_rows($chk_col_exists) == 0) {
    $assign_query = "SELECT * FROM assignments WHERE year='$year' AND section='$section' ORDER BY id DESC";
}
$assign_result = mysqli_query($conn, $assign_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assignments | <?php echo htmlspecialchars($subject); ?></title>

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body {
    background: #eef2f9;
    background-image: 
        radial-gradient(at 10% 10%, rgba(13, 110, 253, 0.05) 0px, transparent 50%),
        radial-gradient(at 90% 90%, rgba(3, 105, 161, 0.05) 0px, transparent 50%);
    min-height: 100vh;
}

/* ================= Header ================= */
.header-box {
    background: linear-gradient(120deg, #0f2b46 0%, #0d6efd 60%, #0369a1 100%);
    color: white; padding: 40px 20px; text-align: center;
    border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;
    box-shadow: 0 10px 30px rgba(13,110,253,0.2); position: relative;
}

.header-box h1 { font-weight: 800; font-size: 30px; margin-bottom: 6px; }
.header-box p { font-size: 14px; opacity: 0.9; margin: 0; letter-spacing: 0.5px; }

.back-top { position: absolute; left: 25px; top: 30px; }
.btn-back {
    background: rgba(255, 255, 255, 0.18); color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 12px;
    padding: 8px 20px; font-size: 13.5px; font-weight: 600; text-decoration: none;
    transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 8px;
}
.btn-back:hover { background: #ffffff; color: #0d6efd; }

.badge-pill {
    display: inline-block; background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3); color: #ffffff;
    font-size: 12px; font-weight: 600; padding: 4px 14px; border-radius: 20px; margin-bottom: 8px;
}

/* ================= Main Container ================= */
.main-container { max-width: 1050px; margin: 40px auto; padding: 0 20px; }

.card-assign {
    background: #ffffff; border-radius: 20px; padding: 26px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 22px; transition: transform 0.25s ease, box-shadow 0.25s ease;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
    border-left: 5px solid #0d6efd;
}
.card-assign:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,0,0,0.08); }

.assign-icon-badge {
    width: 52px; height: 52px; border-radius: 14px; background: #e0f2fe; color: #0284c7;
    display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}
</style>
</head>

<body>

<div class="header-box">
    <div class="back-top">
        <a href="subject_details.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>&type=Theory" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Subject Details
        </a>
    </div>

    <span class="badge-pill">Year <?php echo htmlspecialchars($year); ?> | Section <?php echo htmlspecialchars($section); ?></span>
    <h1>📝 <?php echo htmlspecialchars($subject); ?> Assignments</h1>
    <p>Post student assignments, submission due dates, and PDF question papers</p>
</div>

<div class="main-container">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Posted Assignments</h4>
            <small class="text-muted">Managed by Department Administrator</small>
        </div>
        <button class="btn btn-primary px-4 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addAssignmentModal" style="border-radius: 12px; box-shadow: 0 6px 18px rgba(13,110,253,0.25);">
            <i class="fa-solid fa-plus-circle me-2"></i> Add New Assignment
        </button>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($assign_result && mysqli_num_rows($assign_result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($assign_result)): ?>
            <div class="card-assign">
                <div class="d-flex align-items-start gap-3">
                    <div class="assign-icon-badge">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>
                        <?php if (!empty($row['description'])): ?>
                            <p class="text-muted mb-2" style="font-size: 13.5px;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-3 flex-wrap">
                            <small class="text-danger fw-bold"><i class="fa-solid fa-calendar-day me-1"></i> Due Date: <?php echo !empty($row['due_date']) ? date('d M Y', strtotime($row['due_date'])) : 'N/A'; ?></small>
                            <small class="text-secondary"><i class="fa-regular fa-clock me-1"></i> Posted on <?php echo date('d M Y', strtotime($row['uploaded_at'])); ?></small>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($row['file_path'])): ?>
                        <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary px-3 py-2 fw-semibold" style="border-radius: 10px;">
                            <i class="fa-solid fa-file-arrow-down me-1"></i> Download PDF
                        </a>
                    <?php endif; ?>

                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this assignment?');" class="m-0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="assignment_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger px-3 py-2 fw-semibold" style="border-radius: 10px;">
                            <i class="fa-solid fa-trash me-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm p-4">
            <i class="fa-solid fa-file-signature fs-1 text-primary opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold text-dark">No Assignments Uploaded Yet</h5>
            <p class="text-muted mb-0">Click <b>Add New Assignment</b> above to create an assignment task.</p>
        </div>
    <?php endif; ?>

</div>

<!-- ADD ASSIGNMENT MODAL -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Create New Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assignment Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Assignment 1 - Neural Networks" required style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Instructions / Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Write question details or submission guidelines..." style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Submission Due Date</label>
                        <input type="date" name="due_date" class="form-control" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question Paper / PDF Attachment</label>
                        <input type="file" name="assignment_file" class="form-control" style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" style="border-radius: 10px;"><i class="fa-solid fa-paper-plane me-1"></i> Post Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>