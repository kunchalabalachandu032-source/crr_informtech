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

// Dynamic Column Auto-Fix for `important_questions` table
ensure_columns_exist('important_questions', [
    'year' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'section' => "VARCHAR(10) NOT NULL DEFAULT ''",
    'subject_name' => "VARCHAR(150) NOT NULL DEFAULT ''",
    'unit_number' => "VARCHAR(50) DEFAULT NULL",
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'questions_text' => "TEXT",
    'file_path' => "VARCHAR(255) DEFAULT NULL"
]);

// ------------------------------------------------------------------------
// ACTION HANDLERS (ADD, DELETE IMPORTANT QUESTIONS)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD IMPORTANT QUESTIONS (TEXT AND/OR FILE ATTACHMENT)
    if ($action === 'add') {
        $unit_no = db_escape($_POST['unit_no'] ?? $_POST['unit_number'] ?? '');
        $title = db_escape($_POST['title'] ?? '');
        $questions_text = db_escape($_POST['questions_text'] ?? '');
        $file_path = "";

        if (isset($_FILES['question_file']) && $_FILES['question_file']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($_FILES['question_file']['name']));
            $targetDir = UPLOADS_DIR . '/important_questions/';
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['question_file']['tmp_name'], $targetFile)) {
                $file_path = 'uploads/important_questions/' . $fileName;
            }
        }

        if (!empty($title)) {
            $query = "INSERT INTO important_questions (year, section, subject_name, unit_number, title, questions_text, file_path) 
                      VALUES ('$year', '$section', '$subject', '$unit_no', '$title', '$questions_text', '$file_path')";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Important Questions <b>$title</b> added successfully!";
            } else {
                $error_msg = "Error adding questions: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Please enter a Title for the questions!";
        }
    }

    // 2. DELETE IMPORTANT QUESTIONS
    elseif ($action === 'delete') {
        $id = intval($_POST['question_id'] ?? 0);
        if ($id > 0) {
            $res = mysqli_query($conn, "SELECT file_path FROM important_questions WHERE id=$id");
            if ($r = mysqli_fetch_assoc($res)) {
                if (!empty($r['file_path']) && file_exists(BASE_DIR . '/' . $r['file_path'])) {
                    @unlink(BASE_DIR . '/' . $r['file_path']);
                }
            }
            mysqli_query($conn, "DELETE FROM important_questions WHERE id=$id");
            $success_msg = "Questions entry deleted successfully!";
        }
    }
}

// Safe Fetch Query with column check fallback
$q_query = "SELECT * FROM important_questions WHERE year='$year' AND section='$section' AND subject_name='$subject' ORDER BY id DESC";
$chk_sub_col = @mysqli_query($conn, "SHOW COLUMNS FROM `important_questions` LIKE 'subject_name'");
if (!$chk_sub_col || mysqli_num_rows($chk_sub_col) == 0) {
    $q_query = "SELECT * FROM important_questions WHERE year='$year' AND section='$section' ORDER BY id DESC";
}
$q_result = mysqli_query($conn, $q_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Important Questions | <?php echo htmlspecialchars($subject); ?></title>

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

.card-q {
    background: #ffffff; border-radius: 20px; padding: 26px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 22px; transition: transform 0.25s ease, box-shadow 0.25s ease;
    display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;
    border-left: 5px solid #0d6efd;
}
.card-q:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,0,0,0.08); }

.q-icon-badge {
    width: 52px; height: 52px; border-radius: 14px; background: #e0f2fe; color: #0284c7;
    display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}

.questions-box {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px; font-size: 13.5px; color: #334155; margin-top: 10px;
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
    <h1>📚 <?php echo htmlspecialchars($subject); ?> Important Questions</h1>
    <p>Unit-wise question banks, question paper PDFs, and images</p>
</div>

<div class="main-container">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Posted Important Questions</h4>
            <small class="text-muted">Managed by Department Administrator</small>
        </div>
        <button class="btn btn-primary px-4 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addQModal" style="border-radius: 12px; box-shadow: 0 6px 18px rgba(13,110,253,0.25);">
            <i class="fa-solid fa-plus-circle me-2"></i> Add Important Questions
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

    <?php if ($q_result && mysqli_num_rows($q_result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($q_result)): 
            $unit = $row['unit_number'] ?? ($row['unit_no'] ?? '');
            $q_text = $row['questions_text'] ?? '';
            $f_path = $row['file_path'] ?? '';
            $ext = !empty($f_path) ? strtolower(pathinfo($f_path, PATHINFO_EXTENSION)) : '';
        ?>
            <div class="card-q">
                <div class="d-flex align-items-start gap-3 flex-grow-1">
                    <div class="q-icon-badge">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div style="max-width: 650px;">
                        <?php if (!empty($unit)): ?>
                            <span class="badge bg-primary text-white mb-2 px-3 py-1 fw-bold" style="border-radius: 12px; font-size: 11.5px;">
                                <?php echo htmlspecialchars($unit); ?>
                            </span>
                        <?php endif; ?>

                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>

                        <!-- QUESTIONS TEXT -->
                        <?php if (!empty($q_text)): ?>
                            <div class="questions-box">
                                <i class="fa-solid fa-align-left text-primary me-1"></i>
                                <b>Questions:</b>
                                <div class="mt-1"><?php echo nl2br(htmlspecialchars($q_text)); ?></div>
                            </div>
                        <?php endif; ?>

                        <small class="text-secondary d-block mt-2"><i class="fa-regular fa-clock me-1"></i> Posted on <?php echo date('d M Y, h:i A', strtotime($row['created_at'] ?? $row['uploaded_at'] ?? 'now')); ?></small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap ms-auto mt-2 mt-md-0">
                    <?php if (!empty($f_path)): ?>
                        <!-- VIEW ATTACHMENT -->
                        <a href="../<?php echo htmlspecialchars($f_path); ?>" target="_blank" class="btn btn-sm btn-outline-primary px-3 py-2 fw-semibold" style="border-radius: 10px;">
                            <i class="fa-solid <?php echo (in_array($ext, ['png','jpg','jpeg'])) ? 'fa-image' : 'fa-file-pdf'; ?> me-1"></i> View File
                        </a>
                        <!-- DOWNLOAD ATTACHMENT -->
                        <a href="../<?php echo htmlspecialchars($f_path); ?>" download class="btn btn-sm btn-success px-3 py-2 fw-semibold" style="border-radius: 10px;">
                            <i class="fa-solid fa-download me-1"></i> Download
                        </a>
                    <?php endif; ?>

                    <!-- DELETE BUTTON -->
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete these questions?');" class="m-0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="question_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger px-3 py-2 fw-semibold" style="border-radius: 10px;">
                            <i class="fa-solid fa-trash me-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm p-4">
            <i class="fa-solid fa-graduation-cap fs-1 text-primary opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold text-dark">No Important Questions Added Yet</h5>
            <p class="text-muted mb-0">Click <b>Add Important Questions</b> above to post question text, PDFs, or images.</p>
        </div>
    <?php endif; ?>

</div>

<!-- ADD IMPORTANT QUESTIONS MODAL -->
<div class="modal fade" id="addQModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Add Important Questions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Unit Number / Topic</label>
                            <input type="text" name="unit_no" class="form-control" placeholder="e.g. Unit 1 & 2" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Mid-1 Most Important Questions" required style="border-radius: 10px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Questions Text (Write or Paste Text)</label>
                        <textarea name="questions_text" class="form-control" rows="4" placeholder="1. Explain software engineering principles?&#10;2. Define Agile methodology..." style="border-radius: 10px;"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Attach Question Paper / PDF / Image (Optional)</label>
                        <input type="file" name="question_file" class="form-control" accept=".pdf, .docx, .doc, image/*" style="border-radius: 10px;">
                        <small class="text-muted d-block mt-1">Supports PDF (.pdf), Word (.docx), or Photo Images (.jpg, .png) from Mobile/PC</small>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" style="border-radius: 10px;"><i class="fa-solid fa-save me-1"></i> Save Questions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>