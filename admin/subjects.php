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

if (empty($year) || empty($section)) {
    header("Location: years.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Ensure `subject_type` column exists in table dynamically
$chk_col = @mysqli_query($conn, "SHOW COLUMNS FROM `subjects` LIKE 'subject_type'");
if ($chk_col && mysqli_num_rows($chk_col) == 0) {
    @mysqli_query($conn, "ALTER TABLE `subjects` ADD COLUMN `subject_type` VARCHAR(20) NOT NULL DEFAULT 'Theory'");
}

// ------------------------------------------------------------------------
// ACTION HANDLERS (ADD, EDIT, DELETE SUBJECT)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD SUBJECT
    if ($action === 'add') {
        $subject_name = db_escape($_POST['subject_name'] ?? '');
        $subject_type = db_escape($_POST['subject_type'] ?? 'Theory');

        if (!empty($subject_name)) {
            $query = "INSERT INTO subjects (year, section, subject_name, subject_type) 
                      VALUES ('$year', '$section', '$subject_name', '$subject_type')";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Subject <b>$subject_name</b> added successfully!";
            } else {
                $error_msg = "Error adding subject: " . mysqli_error($conn);
            }
        }
    }

    // 2. EDIT SUBJECT
    elseif ($action === 'edit') {
        $id = intval($_POST['subject_id'] ?? 0);
        $subject_name = db_escape($_POST['subject_name'] ?? '');
        $subject_type = db_escape($_POST['subject_type'] ?? 'Theory');

        if ($id > 0 && !empty($subject_name)) {
            $query = "UPDATE subjects SET subject_name='$subject_name', subject_type='$subject_type' WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Subject details updated successfully!";
            } else {
                $error_msg = "Error updating subject: " . mysqli_error($conn);
            }
        }
    }

    // 3. DELETE SUBJECT
    elseif ($action === 'delete') {
        $id = intval($_POST['subject_id'] ?? 0);
        if ($id > 0) {
            $query = "DELETE FROM subjects WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Subject deleted successfully!";
            } else {
                $error_msg = "Error deleting subject: " . mysqli_error($conn);
            }
        }
    }
}

// Safe query execution with fallback ORDER BY
$sub_query = "SELECT * FROM subjects WHERE year='$year' AND section='$section' ORDER BY id ASC";
$chk_type_col = @mysqli_query($conn, "SHOW COLUMNS FROM `subjects` LIKE 'subject_type'");
if ($chk_type_col && mysqli_num_rows($chk_type_col) > 0) {
    $sub_query = "SELECT * FROM subjects WHERE year='$year' AND section='$section' ORDER BY subject_type ASC, id ASC";
}
$sub_result = mysqli_query($conn, $sub_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($section); ?> Subjects | CRR-INFORMTECH</title>

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background: #f4f6f9; }

.header {
    background: linear-gradient(120deg, #0f2b46 0%, #0d6efd 60%, #0369a1 100%);
    color: white; padding: 30px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
    box-shadow: 0 8px 24px rgba(13,110,253,0.18); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
}

.main-card {
    background: white; border-radius: 18px; padding: 30px; box-shadow: 0 6px 18px rgba(0,0,0,0.05); margin-top: -20px;
}

.subject-card {
    background: #ffffff; border-radius: 16px; padding: 22px; border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 4px 14px rgba(0,0,0,0.04); transition: transform 0.25s ease, box-shadow 0.25s ease;
    height: 100%; display: flex; flex-direction: column; justify-content: space-between;
}
.subject-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }

.badge-type { font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; }
.badge-theory { background: #e0f2fe; color: #0284c7; }
.badge-lab { background: #fef3c7; color: #d97706; }
</style>
</head>

<body>

<div class="container py-4">
    <div class="header mb-4">
        <div>
            <a href="sections.php?year=<?php echo urlencode($year); ?>" class="btn btn-sm btn-light mb-2 fw-semibold" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Sections
            </a>
            <h2 class="fw-bold mb-0">Year <?php echo htmlspecialchars($year); ?> - Section <?php echo htmlspecialchars($section); ?> Subjects</h2>
            <small class="opacity-75">Manage Theory and Lab Subjects for CR & Student Portals</small>
        </div>
        <button class="btn btn-light px-4 py-2 text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addSubjectModal" style="border-radius: 10px;">
            <i class="fa-solid fa-plus me-1"></i> Add New Subject
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

    <div class="main-card">
        <div class="row g-4">
            <?php 
            if ($sub_result && mysqli_num_rows($sub_result) > 0):
                while ($sub = mysqli_fetch_assoc($sub_result)):
                    $sub_type = $sub['subject_type'] ?? 'Theory';
                    $badge_class = (strtolower($sub_type) === 'lab') ? 'badge-lab' : 'badge-theory';
                    $icon = (strtolower($sub_type) === 'lab') ? 'fa-flask' : 'fa-book';
            ?>
                <div class="col-md-4">
                    <div class="subject-card">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge-type <?php echo $badge_class; ?>">
                                    <i class="fa-solid <?php echo $icon; ?> me-1"></i> <?php echo htmlspecialchars($sub_type); ?>
                                </span>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary border-0 edit-btn me-1" 
                                        data-id="<?php echo $sub['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($sub['subject_name']); ?>"
                                        data-type="<?php echo htmlspecialchars($sub_type); ?>"
                                        data-bs-toggle="modal" data-bs-target="#editSubjectModal">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger border-0 delete-btn" 
                                        data-id="<?php echo $sub['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($sub['subject_name']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#deleteSubjectModal">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($sub['subject_name']); ?></h5>
                        </div>

                        <a href="subject_details.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($sub['subject_name']); ?>&type=<?php echo urlencode($sub_type); ?>" 
                           class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 10px;">
                           Open Resources <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fa-solid fa-folder-open fs-1 mb-3 d-block text-secondary"></i>
                    <h5>No subjects added yet for Year <?php echo htmlspecialchars($year); ?> (Section <?php echo htmlspecialchars($section); ?>).</h5>
                    <p class="mb-0">Click <b>Add New Subject</b> to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODALS -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title"><i class="fa-solid fa-plus me-2"></i> Add New Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Subject Name *</label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g. Java Programming / DBMS Lab" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Subject Type *</label>
                        <select name="subject_type" class="form-select" required>
                            <option value="Theory">Theory</option>
                            <option value="Lab">Lab</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-success text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i> Edit Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="subject_id" id="edit_sub_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Subject Name *</label>
                        <input type="text" name="subject_name" id="edit_sub_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Subject Type *</label>
                        <select name="subject_type" id="edit_sub_type" class="form-select" required>
                            <option value="Theory">Theory</option>
                            <option value="Lab">Lab</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-arrows-rotate me-1"></i> Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i> Delete Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="subject_id" id="delete_sub_id">
                <div class="modal-body p-3 text-center">
                    <p class="mb-0" style="font-size: 13.5px;">Are you sure you want to delete <b id="delete_sub_name"></b>?</p>
                </div>
                <div class="modal-footer bg-light justify-content-center py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_sub_id').value = this.dataset.id;
        document.getElementById('edit_sub_name').value = this.dataset.name;
        document.getElementById('edit_sub_type').value = this.dataset.type;
    });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_sub_id').value = this.dataset.id;
        document.getElementById('delete_sub_name').textContent = this.dataset.name;
    });
});
</script>
</body>
</html>