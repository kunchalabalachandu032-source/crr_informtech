<?php
session_start();
require_once "../database/database.php";

// Session Auth Guard (Admin or CR)
if ((!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) && (!isset($_SESSION['cr_logged_in']) || $_SESSION['cr_logged_in'] !== true)) {
    header("Location: ../cr/login.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Dynamic Column Auto-Fix for `announcements` table
ensure_columns_exist('announcements', [
    'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'content' => "TEXT",
    'target_audience' => "VARCHAR(50) NOT NULL DEFAULT 'All'",
    'image_path' => "VARCHAR(255) DEFAULT NULL"
]);

// ------------------------------------------------------------------------
// ACTION HANDLERS (POST ANNOUNCEMENT, DELETE ANNOUNCEMENT)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. POST ANNOUNCEMENT (WITH TEXT & OPTIONAL POSTER PHOTO/FILE)
    if ($action === 'add') {
        $title = db_escape($_POST['title'] ?? '');
        $content = db_escape($_POST['content'] ?? '');
        $target_audience = db_escape($_POST['target_audience'] ?? 'All');
        $image_path = "";

        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($_FILES['poster']['name']));
            $targetDir = UPLOADS_DIR . '/announcements/';
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetFile)) {
                $image_path = 'uploads/announcements/' . $fileName;
            }
        }

        if (!empty($title)) {
            $query = "INSERT INTO announcements (title, content, target_audience, image_path) 
                      VALUES ('$title', '$content', '$target_audience', '$image_path')";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Announcement <b>$title</b> posted successfully! Popup notification activated for students.";
            } else {
                $error_msg = "Error posting announcement: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Please enter an Announcement Title!";
        }
    }

    // 2. DELETE ANNOUNCEMENT
    elseif ($action === 'delete') {
        $id = intval($_POST['announcement_id'] ?? 0);
        if ($id > 0) {
            $res = mysqli_query($conn, "SELECT image_path FROM announcements WHERE id=$id");
            if ($r = mysqli_fetch_assoc($res)) {
                if (!empty($r['image_path']) && file_exists(BASE_DIR . '/' . $r['image_path'])) {
                    @unlink(BASE_DIR . '/' . $r['image_path']);
                }
            }
            mysqli_query($conn, "DELETE FROM announcements WHERE id=$id");
            $success_msg = "Announcement deleted successfully!";
        }
    }
}

// Fetch all announcements from database
$ann_query = "SELECT * FROM announcements ORDER BY id DESC";
$ann_result = mysqli_query($conn, $ann_query);

// Base64 College Logo for Left Sidebar
$logo_base64 = "";
$logo_path = BASE_DIR . '/logo.png';
if (file_exists($logo_path)) {
    $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements | CRR-INFORMTECH</title>

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

/* ================= Left Sidebar ================= */
.sidebar {
    position: fixed; left: 0; top: 0; width: 270px; height: 100vh;
    background: linear-gradient(180deg, #0f2b46 0%, #0d6efd 100%);
    color: white; padding-top: 25px; box-shadow: 4px 0 20px rgba(0,0,0,0.08);
    z-index: 100; overflow-y: auto;
}

.sidebar-header {
    text-align: center; padding: 0 15px 22px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12); margin-bottom: 20px;
}

.sidebar-logo { width: 55px; height: 55px; border-radius: 50%; object-fit: contain; margin-bottom: 10px; }

.sidebar-header h4 { font-weight: 800; font-size: 18px; margin: 0; letter-spacing: 0.5px; }
.sidebar-header small { font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

.sidebar a {
    display: flex; align-items: center; gap: 14px; padding: 13px 25px;
    color: rgba(255, 255, 255, 0.85); text-decoration: none; font-size: 14px;
    font-weight: 500; transition: all 0.25s ease; border-left: 4px solid transparent;
}

.sidebar a:hover, .sidebar a.active {
    background: rgba(255, 255, 255, 0.14); color: #ffffff;
    border-left-color: #38bdf8; padding-left: 28px;
}

.sidebar a.logout-btn {
    margin-top: 30px; margin-bottom: 30px; color: #fcd34d;
    border-top: 1px solid rgba(255, 255, 255, 0.12); padding-top: 18px;
}

/* ================= Main Content Area ================= */
.main-content { margin-left: 270px; padding: 40px; }

.page-header-card {
    background: #ffffff; padding: 28px 32px; border-radius: 20px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 35px; flex-wrap: wrap; gap: 20px;
}

.page-header-card h2 { font-weight: 800; color: #1e293b; margin: 0; }
.page-header-card p { font-size: 13.5px; color: #64748b; margin: 0; }

.card-ann {
    background: #ffffff; border-radius: 20px; padding: 28px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 22px; transition: transform 0.25s ease, box-shadow 0.25s ease;
    display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;
    border-left: 5px solid #0d6efd;
}
.card-ann:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,0,0,0.08); }

.poster-preview-img {
    max-width: 220px; max-height: 160px; border-radius: 12px;
    object-fit: cover; border: 2px solid #e2e8f0; margin-top: 10px;
}
</style>
</head>

<body>

<!-- Left Sidebar Navigation -->
<div class="sidebar">
    <div class="sidebar-header">
        <?php if (!empty($logo_base64)): ?>
            <img src="<?php echo $logo_base64; ?>" class="sidebar-logo" alt="CRR Logo">
        <?php else: ?>
            <i class="fa-solid fa-graduation-cap fs-1 text-white mb-2"></i>
        <?php endif; ?>
        <h4>CRR INFORMTECH</h4>
        <small>Department of IT</small>
    </div>

    <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="years.php"><i class="fa-solid fa-calendar-days"></i> Academic Years</a>
    <a href="manage_cr.php"><i class="fa-solid fa-user-gear"></i> Manage CR Accounts</a>
    <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty Directory</a>
    <a href="announcements.php" class="active"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="dashboard.php?logout=true" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Main Content Area -->
<div class="main-content">

    <div class="page-header-card">
        <div>
            <h2><i class="fa-solid fa-bullhorn text-primary me-2"></i>Department Announcements</h2>
            <p>Post circulars, posters, and notices with automatic Student Portal Popup Notifications</p>
        </div>
        <button class="btn btn-primary px-4 py-2.5 fw-bold" data-bs-toggle="modal" data-bs-target="#addAnnModal" style="border-radius: 12px; box-shadow: 0 6px 18px rgba(13,110,253,0.25);">
            <i class="fa-solid fa-plus-circle me-2"></i> Post Announcement
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

    <?php if ($ann_result && mysqli_num_rows($ann_result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($ann_result)): 
            $img = $row['image_path'] ?? ($row['poster'] ?? '');
            $posted_time = $row['posted_at'] ?? ($row['created_at'] ?? 'now');
        ?>
            <div class="card-ann">
                <div style="max-width: 700px;">
                    <div class="mb-2">
                        <span class="badge bg-warning text-dark px-3 py-1 fw-bold" style="border-radius: 12px; font-size: 12px;">
                            <i class="fa-solid fa-users me-1"></i> Target: <?php echo htmlspecialchars($row['target_audience'] ?? 'All'); ?>
                        </span>
                    </div>

                    <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($row['title']); ?></h4>

                    <?php if (!empty($row['content'])): ?>
                        <p class="text-secondary mb-3" style="font-size: 14px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($img) && file_exists(BASE_DIR . '/' . $img)): ?>
                        <div class="mt-2 mb-3">
                            <a href="../<?php echo htmlspecialchars($img); ?>" target="_blank">
                                <img src="../<?php echo htmlspecialchars($img); ?>" class="poster-preview-img" alt="Announcement Poster">
                            </a>
                            <small class="d-block text-muted mt-1"><i class="fa-solid fa-magnifying-glass-plus me-1"></i> Click poster to view full size</small>
                        </div>
                    <?php endif; ?>

                    <small class="text-muted d-block mt-2"><i class="fa-regular fa-clock me-1"></i> Posted on <?php echo date('d M Y, h:i A', strtotime($posted_time)); ?></small>
                </div>

                <div class="ms-auto">
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="announcement_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold" style="border-radius: 10px;">
                            <i class="fa-solid fa-trash me-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm p-4">
            <i class="fa-solid fa-bullhorn fs-1 text-primary opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold text-dark">No Announcements Posted Yet</h5>
            <p class="text-muted mb-0">Click <b>Post Announcement</b> above to add notices, circulars, or posters.</p>
        </div>
    <?php endif; ?>

</div>

<!-- ADD ANNOUNCEMENT MODAL -->
<div class="modal fade" id="addAnnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-bullhorn me-2"></i> Create Department Announcement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Announcement Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Mid-1 Exam Schedule Released" required style="border-radius: 10px;">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Target Audience</label>
                            <select name="target_audience" class="form-select" style="border-radius: 10px;">
                                <option value="All">All Students & Faculty</option>
                                <option value="2nd Year">2nd Year Only</option>
                                <option value="3rd Year">3rd Year Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Content / Notice Details</label>
                        <textarea name="content" class="form-control" rows="4" placeholder="Enter notice details or circular text..." style="border-radius: 10px;"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Poster / Notice Image / PDF (Optional)</label>
                        <input type="file" name="poster" class="form-control" accept="image/*, .pdf" style="border-radius: 10px;">
                        <small class="text-muted d-block mt-1">Attach a Poster Photo (.png, .jpg) or Notice PDF. This will pop up on Student Portal!</small>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" style="border-radius: 10px;"><i class="fa-solid fa-paper-plane me-1"></i> Post & Activate Popup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>