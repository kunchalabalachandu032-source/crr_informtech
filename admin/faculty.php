<?php
session_start();
require_once "../database/database.php";

// Session Auth Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Dynamic Column Auto-Fix for `faculty` table
ensure_columns_exist('faculty', [
    'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'designation' => "VARCHAR(100) NOT NULL DEFAULT ''"
]);

// ------------------------------------------------------------------------
// ACTION HANDLERS (ADD, DELETE FACULTY MEMBER)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD FACULTY MEMBER (ONLY NAME & SUBJECT/DESIGNATION)
    if ($action === 'add') {
        $name = db_escape($_POST['name'] ?? '');
        $designation = db_escape($_POST['designation'] ?? '');

        if (!empty($name) && !empty($designation)) {
            $query = "INSERT INTO faculty (name, designation) VALUES ('$name', '$designation')";
            if (mysqli_query($conn, $query)) {
                $success_msg = "Faculty profile for <b>$name</b> added successfully!";
            } else {
                $error_msg = "Error adding faculty: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Please enter Faculty Name and Subject!";
        }
    }

    // 2. DELETE FACULTY MEMBER
    elseif ($action === 'delete') {
        $id = intval($_POST['faculty_id'] ?? 0);
        if ($id > 0) {
            mysqli_query($conn, "DELETE FROM faculty WHERE id=$id");
            $success_msg = "Faculty profile deleted successfully!";
        }
    }
}

// Fetch all faculty members from database
$fac_query = "SELECT * FROM faculty ORDER BY id DESC";
$fac_result = mysqli_query($conn, $fac_query);

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
<title>Faculty Directory | CRR-INFORMTECH</title>

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

.card-fac {
    background: #ffffff; border-radius: 20px; padding: 28px 22px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%;
    display: flex; flex-direction: column; justify-content: space-between;
    border-top: 4px solid #0d6efd;
}
.card-fac:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.08); }

.fac-avatar-placeholder {
    width: 75px; height: 75px; border-radius: 50%;
    background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0284c7;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 800; border: 3px solid #0d6efd;
    margin: 0 auto 16px auto; box-shadow: 0 6px 18px rgba(2,132,199,0.2);
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
    <a href="faculty.php" class="active"><i class="fa-solid fa-chalkboard-user"></i> Faculty Directory</a>
    <a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="dashboard.php?logout=true" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Main Content Area -->
<div class="main-content">

    <div class="page-header-card">
        <div>
            <h2><i class="fa-solid fa-chalkboard-user text-primary me-2"></i>Faculty Directory</h2>
            <p>Manage Information Technology department faculty members & subjects</p>
        </div>
        <button class="btn btn-primary px-4 py-2.5 fw-bold" data-bs-toggle="modal" data-bs-target="#addFacultyModal" style="border-radius: 12px; box-shadow: 0 6px 18px rgba(13,110,253,0.25);">
            <i class="fa-solid fa-user-plus me-2"></i> Add Faculty Member
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

    <div class="row g-4">
        <?php if ($fac_result && mysqli_num_rows($fac_result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($fac_result)): ?>
                <div class="col-md-4 col-lg-4">
                    <div class="card-fac text-center">
                        <div>
                            <div class="fac-avatar-placeholder">
                                <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                            </div>

                            <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <span class="badge bg-primary px-3 py-1.5 font-semibold" style="border-radius: 20px; font-size: 13px;">
                                <i class="fa-solid fa-book-open me-1"></i> <?php echo htmlspecialchars($row['designation']); ?>
                            </span>
                        </div>

                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this faculty member?');" class="mt-4">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="faculty_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 py-2 fw-semibold" style="border-radius: 10px;">
                                <i class="fa-solid fa-trash me-1"></i> Remove Faculty
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm p-4">
                <i class="fa-solid fa-chalkboard-user fs-1 text-primary opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">No Faculty Members Found</h5>
                <p class="text-muted mb-0">Click <b>Add Faculty Member</b> above to enter faculty name and subject.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ADD FACULTY MODAL (ONLY NAME & SUBJECT) -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Add New Faculty Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Faculty Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Dr. S. Krishna Rao" required style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject / Designation *</label>
                        <input type="text" name="designation" class="form-control" placeholder="e.g. Software Engineering / HOD" required style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" style="border-radius: 10px;"><i class="fa-solid fa-save me-1"></i> Save Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>