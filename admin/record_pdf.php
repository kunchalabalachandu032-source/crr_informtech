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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $exp_no = intval($_POST['exp_no'] ?? 1);
        $title = db_escape($_POST['title'] ?? '');
        $file_path = "";

        if (isset($_FILES['record_file']) && $_FILES['record_file']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($_FILES['record_file']['name']);
            $targetDir = UPLOADS_DIR . '/records/';
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['record_file']['tmp_name'], $targetFile)) {
                $file_path = 'uploads/records/' . $fileName;
            }
        }

        if (!empty($title) && !empty($file_path)) {
            $query = "INSERT INTO lab_records (year, section, subject_name, experiment_no, title, file_path) 
                      VALUES ('$year', '$section', '$subject', $exp_no, '$title', '$file_path')";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Lab Record PDF for Experiment <b>#$exp_no</b> uploaded!";
            } else {
                $error_msg = "Error uploading record PDF: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Please provide experiment title and select a PDF file!";
        }
    }

    elseif ($action === 'delete') {
        $id = intval($_POST['record_id'] ?? 0);
        if ($id > 0) {
            $res = mysqli_query($conn, "SELECT file_path FROM lab_records WHERE id=$id");
            if ($r = mysqli_fetch_assoc($res)) {
                if (!empty($r['file_path']) && file_exists(BASE_DIR . '/' . $r['file_path'])) {
                    @unlink(BASE_DIR . '/' . $r['file_path']);
                }
            }
            mysqli_query($conn, "DELETE FROM lab_records WHERE id=$id");
            $success_msg = "Record PDF deleted!";
        }
    }
}

$rec_query = "SELECT * FROM lab_records WHERE year='$year' AND section='$section' AND subject_name='$subject' ORDER BY experiment_no ASC";
$rec_result = mysqli_query($conn, $rec_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record PDFs | <?php echo htmlspecialchars($subject); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background: #f4f6f9; }
.header { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; padding: 25px; text-align: center; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
.container-box { width: 90%; max-width: 1000px; margin: 30px auto; }
.card-rec { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
</style>
</head>
<body>

<div class="header">
    <h2>📘 Lab Record PDFs</h2>
    <p class="mb-0"><?php echo htmlspecialchars($subject); ?> (<?php echo htmlspecialchars($section); ?>)</p>
</div>

<div class="container-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="subject_details.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>&type=Lab" class="btn btn-secondary px-3" style="border-radius: 10px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addRecordModal" style="border-radius: 10px;">
            <i class="fa-solid fa-file-pdf me-2"></i> Upload Record PDF
        </button>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if ($rec_result && mysqli_num_rows($rec_result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($rec_result)): ?>
            <div class="card-rec d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="badge bg-danger mb-2"><i class="fa-solid fa-file-pdf me-1"></i> Exp #<?php echo $row['experiment_no']; ?></span>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> Uploaded on <?php echo date('d M Y, h:i A', strtotime($row['uploaded_at'])); ?></small>
                </div>
                <div class="d-flex gap-2">
                    <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
                        <i class="fa-solid fa-file-pdf me-1"></i> Open PDF
                    </a>
                    <form method="POST" action="" onsubmit="return confirm('Delete this Record PDF?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" style="border-radius: 8px;"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5 text-muted card-rec">
            <i class="fa-solid fa-file-pdf fs-1 mb-2 text-danger"></i>
            <h5>No Record PDFs Uploaded Yet</h5>
            <p>Click <b>Upload Record PDF</b> above to add official lab record files.</p>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title"><i class="fa-solid fa-file-pdf me-2"></i> Upload Record PDF</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Experiment Number *</label>
                        <input type="number" name="exp_no" class="form-control" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Experiment / Record Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Experiment 1 - Client Server Communication PDF" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PDF File *</label>
                        <input type="file" name="record_file" class="form-control" accept=".pdf" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-upload me-1"></i> Upload PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>