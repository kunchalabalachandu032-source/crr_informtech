<?php
session_start();
require_once "../database/database.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $old_pass = db_escape($_POST['old_password'] ?? '');
        $new_pass = db_escape($_POST['new_password'] ?? '');
        $confirm_pass = db_escape($_POST['confirm_password'] ?? '');

        if (!empty($new_pass) && $new_pass === $confirm_pass) {
            // Update password in database `admins` / `managers` if present
            @mysqli_query($conn, "UPDATE admins SET password='$new_pass' WHERE username='$username'");
            @mysqli_query($conn, "UPDATE managers SET password='$new_pass' WHERE role='admin'");

            $success_msg = "Admin Password updated successfully to <b>$new_pass</b>!";
        } else {
            $error_msg = "New Passwords do not match!";
        }
    }
}

// Logo Loader
$possibleLogos = ['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.jpeg.jpeg', 'logo.png.png', 'cr_reddy.jpeg'];
$logoSrc = "";
foreach ($possibleLogos as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $data = file_get_contents(__DIR__ . '/' . $file);
        $logoSrc = 'data:image/' . $type . ';base64,' . base64_encode($data);
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Settings | CRR-INFORMTECH</title>

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background: #eef2f9; }

.sidebar {
    position: fixed; left: 0; top: 0; width: 275px; height: 100vh;
    background: linear-gradient(180deg, #0f2b46 0%, #0d6efd 55%, #0369a1 100%);
    color: white; padding-top: 25px; box-shadow: 4px 0 20px rgba(0,0,0,0.18);
    z-index: 100; overflow-y: auto;
}
.sidebar::-webkit-scrollbar { width: 5px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.25); border-radius: 10px; }

.sidebar-header { text-align: center; padding: 0 15px 22px 15px; border-bottom: 1px solid rgba(255,255,255,0.18); margin-bottom: 18px; }
.logo-box {
    width: 75px; height: 75px; border-radius: 50%; margin: 0 auto 12px auto;
    background: #ffffff; padding: 6px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 18px rgba(0,0,0,0.25), 0 0 0 3px rgba(255,255,255,0.3);
}
.logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 50%; }

.sidebar-header h4 { font-weight: 800; font-size: 18px; margin: 0; letter-spacing: 0.5px; }
.sidebar-header small { font-size: 11px; opacity: 0.85; text-transform: uppercase; }

.sidebar a {
    display: flex; align-items: center; gap: 12px; padding: 12px 25px; margin: 3px 12px;
    border-radius: 10px; color: rgba(255,255,255,0.92); text-decoration: none; font-size: 13.5px; font-weight: 500; transition: all 0.25s ease;
}
.sidebar a i { width: 18px; text-align: center; }
.sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; transform: translateX(4px); }
.sidebar a.active { box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-weight: 700; }
.sidebar a.logout-btn { margin-top: 24px; margin-bottom: 30px; color: #ffd966; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 18px; border-radius: 0; }

.main { margin-left: 275px; padding: 35px; }
.header-box { background: #ffffff; padding: 25px 30px; border-radius: 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.05); margin-bottom: 30px; }
.card-box { background: #ffffff; padding: 30px; border-radius: 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.05); margin-bottom: 25px; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-box">
            <?php if (!empty($logoSrc)): ?>
                <img src="<?php echo $logoSrc; ?>" alt="College Logo">
            <?php else: ?>
                <i class="fa-solid fa-graduation-cap text-primary fs-3"></i>
            <?php endif; ?>
        </div>
        <h4>CRR INFORMTECH</h4>
        <small>Department of IT</small>
    </div>

    <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="years.php"><i class="fa-solid fa-calendar-days"></i> Academic Years</a>
    <a href="sections.php?year=2"><i class="fa-solid fa-school"></i> Sections (2nd Year)</a>
    <a href="sections.php?year=3"><i class="fa-solid fa-school"></i> Sections (3rd Year)</a>
    <a href="manage_cr.php"><i class="fa-solid fa-user-gear"></i> Manage CR Accounts</a>
    <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty Management</a>
    <a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="dashboard.php?logout=true" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">

    <div class="header-box">
        <h3 class="fw-bold mb-1">Admin Portal Settings ⚙️</h3>
        <p class="text-muted mb-0" style="font-size: 14px;">Manage Admin security, credentials, and portal system settings</p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-box">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-key text-primary me-2"></i> Change Admin Password</h5>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label font-semibold" style="font-size: 13px;">Current Admin Username</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold" style="font-size: 13px;">New Password *</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label font-semibold" style="font-size: 13px;">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                    </div>
                    <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 600;">
                        <i class="fa-solid fa-save me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-box">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-sliders text-success me-2"></i> Portal System Info</h5>
                <ul class="list-group list-group-flush" style="font-size: 13.5px;">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="fa-solid fa-building-columns text-primary me-2"></i> Institution</span>
                        <strong class="text-dark">Sir C.R. Reddy College of Engineering</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="fa-solid fa-laptop-code text-success me-2"></i> Department</span>
                        <strong class="text-dark">Information Technology (IT)</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="fa-solid fa-database text-warning me-2"></i> Database Status</span>
                        <span class="badge bg-success">Connected (crr_informtech)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="fa-solid fa-server text-info me-2"></i> Server Port</span>
                        <span class="badge bg-primary">Port 8080 Active</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
