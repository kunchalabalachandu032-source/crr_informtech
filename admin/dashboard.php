<?php
session_start();
require_once "../database/database.php";

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Session Auth Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';

// Dynamic Database Live Counts
$cr_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM cr_accounts");
if ($r = mysqli_fetch_assoc($res)) { $cr_count = $r['cnt']; }

$sub_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM subjects");
if ($r = mysqli_fetch_assoc($res)) { $sub_count = $r['cnt']; }

$fac_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM faculty");
if ($r = mysqli_fetch_assoc($res)) { $fac_count = $r['cnt']; }

$ann_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM announcements");
if ($r = mysqli_fetch_assoc($res)) { $ann_count = $r['cnt']; }

// Automatic Multi-Extension Check for Logo (Base64 encoding bypasses path issues)
$possibleLogos = [
    'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.jpeg.jpeg', 
    'logo.png.png', 'cr_reddy.jpeg'
];

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
<title>CRR-INFORMTECH | Admin Dashboard</title>

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

/* ================= Sidebar ================= */
.sidebar {
    position: fixed; left: 0; top: 0; width: 275px; height: 100vh;
    background: linear-gradient(180deg, #0f2b46 0%, #0d6efd 55%, #0369a1 100%);
    color: white; padding-top: 25px; box-shadow: 4px 0 20px rgba(0,0,0,0.18);
    z-index: 100; overflow-y: auto;
}
.sidebar::-webkit-scrollbar { width: 5px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.25); border-radius: 10px; }

.sidebar-header {
    text-align: center; padding: 0 15px 22px 15px; border-bottom: 1px solid rgba(255,255,255,0.18); margin-bottom: 18px;
}

/* College Logo Container */
.logo-box {
    width: 75px; height: 75px; border-radius: 50%; margin: 0 auto 12px auto;
    background: #ffffff; padding: 6px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 18px rgba(0,0,0,0.25), 0 0 0 3px rgba(255,255,255,0.3);
    transition: transform 0.3s ease;
}
.logo-box:hover { transform: scale(1.05); }
.logo-box img {
    max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 50%;
}

.sidebar-header h4 { font-weight: 800; font-size: 18px; margin: 0; letter-spacing: 0.5px; }
.sidebar-header small { font-size: 11px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px; }

.sidebar a {
    display: flex; align-items: center; gap: 12px; padding: 12px 25px; margin: 3px 12px;
    border-radius: 10px;
    color: rgba(255,255,255,0.92); text-decoration: none; font-size: 13.5px; font-weight: 500; transition: all 0.25s ease;
}
.sidebar a i { width: 18px; text-align: center; }
.sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; transform: translateX(4px); }
.sidebar a.active { box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-weight: 700; }
.sidebar a.logout-btn { margin-top: 24px; margin-bottom: 30px; color: #ffd966; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 18px; border-radius: 0; }
.sidebar a.logout-btn:hover { background: rgba(220, 53, 69, 0.25); color: #ff6b6b; transform: none; }

/* ================= Main Area ================= */
.main { margin-left: 275px; padding: 35px; }

.welcome-banner {
    background: linear-gradient(120deg, #0f2b46 0%, #0d6efd 60%, #0369a1 100%);
    color: #fff;
    border-radius: 20px; padding: 32px 36px; box-shadow: 0 12px 32px rgba(13,110,253,0.22);
    margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;
    position: relative; overflow: hidden;
}
.welcome-banner::after {
    content: "\f19d"; font-family: "Font Awesome 6 Free"; font-weight: 900;
    position: absolute; right: 25px; top: 50%; transform: translateY(-50%);
    font-size: 130px; opacity: 0.08; color: #ffffff; pointer-events: none;
}
.welcome-banner h3 { font-weight: 800; font-size: 26px; }
.welcome-banner p { opacity: 0.9; font-size: 14px; margin-bottom: 0; }

.welcome-banner .btn-light-cta {
    background: #ffffff; color: #0d6efd; border: none; border-radius: 12px;
    padding: 12px 24px; font-weight: 700; font-size: 14px; transition: all 0.25s ease;
    box-shadow: 0 6px 16px rgba(0,0,0,0.15); display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
    position: relative; z-index: 2;
}
.welcome-banner .btn-light-cta:hover { background: #0d6efd; color: #fff; transform: translateY(-2px); box-shadow: 0 10px 22px rgba(0,0,0,0.25); }

/* ================= Stat Cards ================= */
.stat-card {
    background: #ffffff; border-radius: 20px; padding: 26px; box-shadow: 0 8px 24px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex; align-items: center; gap: 20px;
    border: 1px solid rgba(0,0,0,0.04);
    border-bottom: 4px solid #0d6efd; position: relative; overflow: hidden;
}
.stat-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.1); }

.stat-icon {
    width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    box-shadow: 0 8px 18px rgba(13,110,253,0.35);
}
.stat-card.card-green { border-bottom-color: #198754; }
.stat-card.card-green .stat-icon { background: linear-gradient(135deg, #20c997, #198754); box-shadow: 0 8px 18px rgba(25,135,84,0.35); }
.stat-card.card-orange { border-bottom-color: #fd7e14; }
.stat-card.card-orange .stat-icon { background: linear-gradient(135deg, #ffb347, #fd7e14); box-shadow: 0 8px 18px rgba(253,126,20,0.35); }
.stat-card.card-purple { border-bottom-color: #6f42c1; }
.stat-card.card-purple .stat-icon { background: linear-gradient(135deg, #a06cd5, #6f42c1); box-shadow: 0 8px 18px rgba(111,66,193,0.35); }

.stat-card h6 { color: #6c757d; font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.stat-card h3 { font-weight: 800; font-size: 30px; color: #1e293b; margin: 0; }

/* Quick Actions Section */
.section-title { font-weight: 700; font-size: 18px; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.quick-card {
    background: #ffffff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    text-decoration: none; color: #334155; display: flex; align-items: center; gap: 15px;
    transition: all 0.25s ease; border: 1px solid rgba(0,0,0,0.04);
}
.quick-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); color: #0d6efd; }
.quick-icon {
    width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7;
    display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
}
.quick-card:hover .quick-icon { background: #0d6efd; color: #ffffff; }
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

    <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="years.php"><i class="fa-solid fa-calendar-days"></i> Academic Years</a>
    <a href="sections.php?year=2"><i class="fa-solid fa-school"></i> Sections (2nd Year)</a>
    <a href="sections.php?year=3"><i class="fa-solid fa-school"></i> Sections (3rd Year)</a>
    <a href="manage_cr.php"><i class="fa-solid fa-user-gear"></i> Manage CR Accounts</a>
    <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty Management</a>
    <a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="dashboard.php?logout=true" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">

    <div class="welcome-banner">
        <div>
            <h3 class="mb-1">Welcome, <?php echo htmlspecialchars(ucfirst($username)); ?>! 👋</h3>
            <p>Sir C.R. Reddy College of Engineering - IT Department Portal</p>
        </div>
        <a href="years.php" class="btn-light-cta">
            <i class="fa-solid fa-folder-open me-1"></i> Explore Academic Years
        </a>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-user-gear"></i></div>
                <div>
                    <h6>Total CRs</h6>
                    <h3><?php echo $cr_count; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card card-green">
                <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
                <div>
                    <h6>Active Subjects</h6>
                    <h3><?php echo $sub_count; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card card-orange">
                <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <div>
                    <h6>IT Faculty</h6>
                    <h3><?php echo $fac_count; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card card-purple">
                <div class="stat-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <div>
                    <h6>Announcements</h6>
                    <h3><?php echo $ann_count; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACCESS MODULES -->
    <div class="section-title">
        <i class="fa-solid fa-rocket text-primary"></i> Quick Management Access
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <a href="manage_cr.php" class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-user-gear"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Manage CR Accounts</h6>
                    <small class="text-muted">Add, Edit, & Reset CR Logins</small>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="years.php" class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Academic Curriculum</h6>
                    <small class="text-muted">Browse 2nd & 3rd Year Subjects</small>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="faculty.php" class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Faculty Management</h6>
                    <small class="text-muted">IT Department Directory</small>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="announcements.php" class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Announcements</h6>
                    <small class="text-muted">Post Department Circulars</small>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="../student/index.php" target="_blank" class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-eye"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Student Portal View</h6>
                    <small class="text-muted">Preview Live Student Website</small>
                </div>
            </a>
        </div>
    </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>