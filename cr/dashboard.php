<?php
// FORCE NO BROWSER CACHING FOR CR DASHBOARD
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";

// Fail-safe Auth Guard for CR Portal (redirects to login with logout parameter to break any loops)
if (empty($_SESSION['cr_logged_in'])) {
    header("Location: login.php?logout=true");
    exit();
}

// Handle Logout Action
if (isset($_GET['logout'])) {
    unset($_SESSION['cr_logged_in']);
    unset($_SESSION['cr_id']);
    unset($_SESSION['cr_name']);
    unset($_SESSION['cr_roll']);
    unset($_SESSION['cr_year']);
    unset($_SESSION['cr_section']);
    session_destroy();
    header("Location: login.php?logout=true");
    exit();
}

// Extract assigned CR session details
$cr_name = !empty($_SESSION['cr_name']) ? $_SESSION['cr_name'] : (!empty($_SESSION['cr_roll']) ? $_SESSION['cr_roll'] : 'Class Representative');
$cr_roll = $_SESSION['cr_roll'] ?? '';

// Allow dynamic section selection via GET param or Session
$selected_year = db_escape($_GET['year'] ?? ($_SESSION['cr_year'] ?? ''));
$selected_section = db_escape($_GET['section'] ?? ($_SESSION['cr_section'] ?? ''));

// Clean numeric year
$clean_year = preg_replace('/[^0-9]/', '', $selected_year);

// ------------------------------------------------------------------------
// DYNAMIC DATABASE QUERY (FETCH ALL ADMIN SUBJECTS & SECTIONS LIVE)
// ------------------------------------------------------------------------
// Fetch distinct sections created by Admin for dropdown selector
$sections_query = mysqli_query($conn, "SELECT DISTINCT year, section FROM subjects ORDER BY year ASC, section ASC");

// Build dynamic subject query based on Admin entries in `subjects`
if (!empty($selected_section)) {
    $sub_query = "SELECT * FROM subjects WHERE (LOWER(section) = LOWER('$selected_section') OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER('$selected_section'), '-', '')) ORDER BY subject_type ASC, id ASC";
} elseif (!empty($selected_year)) {
    $sub_query = "SELECT * FROM subjects WHERE (year='$selected_year' OR year='$clean_year' OR year LIKE '%$clean_year%') ORDER BY subject_type ASC, id ASC";
} else {
    // If no specific section filtered, load ALL subjects created by Admin across all years!
    $sub_query = "SELECT * FROM subjects ORDER BY year ASC, section ASC, subject_type ASC, id ASC";
}

$sub_result = mysqli_query($conn, $sub_query);

// Fallback: If specific section query returns zero results, load ALL subjects added by Admin
if ((!$sub_result || mysqli_num_rows($sub_result) == 0) && (!empty($selected_section) || !empty($selected_year))) {
    $sub_query = "SELECT * FROM subjects ORDER BY year ASC, section ASC, subject_type ASC, id ASC";
    $sub_result = mysqli_query($conn, $sub_query);
}

$total_subjects = ($sub_result) ? mysqli_num_rows($sub_result) : 0;

// Fetch active assignments count
$ass_res = @mysqli_query($conn, "SELECT COUNT(*) as total FROM assignments");
$total_assignments = ($ass_res && $row = mysqli_fetch_assoc($ass_res)) ? intval($row['total']) : 0;

// Fetch class works count
$work_res = @mysqli_query($conn, "SELECT COUNT(*) as total FROM class_works");
$total_works = ($work_res && $row = mysqli_fetch_assoc($work_res)) ? intval($row['total']) : 0;

// Fetch Department Announcements posted by Admin
$ann_query = "SELECT * FROM announcements ORDER BY id DESC LIMIT 5";
$ann_result = mysqli_query($conn, $ann_query);

// Base64 College Logo for Left Sidebar
$logo_base64 = "";
$logo_path = BASE_DIR . '/admin/logo.png';
if (file_exists($logo_path)) {
    $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CR Portal Dashboard | CRR-INFORMTECH</title>

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

/* ================= Left Sidebar Navigation (Matching Admin Panel) ================= */
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

.welcome-header-card {
    background: #ffffff; padding: 32px 36px; border-radius: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 35px; flex-wrap: wrap; gap: 20px; position: relative; overflow: hidden;
}

.welcome-header-card::before {
    content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%;
    background: linear-gradient(180deg, #0d6efd, #0369a1);
}

.welcome-header-card h2 { font-weight: 800; color: #0f172a; margin: 0; font-size: 26px; }
.welcome-header-card p { font-size: 14px; color: #64748b; margin: 4px 0 0 0; }

.stat-card {
    background: #ffffff; border-radius: 20px; padding: 25px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.3s ease; display: flex; align-items: center; gap: 20px;
}
.stat-card:hover { transform: translateY(-5px); }

.stat-icon {
    width: 60px; height: 60px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center; font-size: 24px;
}

.card-subject {
    background: #ffffff; border-radius: 22px; padding: 28px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%;
    display: flex; flex-direction: column; justify-content: space-between;
    border-top: 4px solid #0d6efd;
}
.card-subject:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.08); }

.badge-type { width: fit-content; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; }
.badge-theory { background: #e0f2fe; color: #0284c7; }
.badge-lab { background: #fef3c7; color: #d97706; }

.notice-box {
    background: #ffffff; border-radius: 18px; padding: 22px;
    border-left: 5px solid #0d6efd; margin-bottom: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.04);
}
</style>
</head>

<body>

<!-- Left Sidebar Navigation (Matching Admin Panel) -->
<div class="sidebar">
    <div class="sidebar-header">
        <?php if (!empty($logo_base64)): ?>
            <img src="<?php echo $logo_base64; ?>" class="sidebar-logo" alt="CRR Logo">
        <?php else: ?>
            <i class="fa-solid fa-graduation-cap fs-1 text-white mb-2"></i>
        <?php endif; ?>
        <h4>CRR INFORMTECH</h4>
        <small>Class Representative</small>
    </div>

    <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard Overview</a>
    <a href="../admin/years.php" target="_blank"><i class="fa-solid fa-layer-group"></i> Academic Years</a>
    <a href="#subjectsSection"><i class="fa-solid fa-book-open"></i> Subjects & Resources</a>
    <a href="#announcementsSection"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="../student/index.php" target="_blank"><i class="fa-solid fa-eye"></i> Student Portal View</a>
    <a href="dashboard.php?logout=true" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Main Content Area -->
<div class="main-content">

    <!-- WELCOME HEADER CARD -->
    <div class="welcome-header-card">
        <div>
            <div class="d-flex gap-2 mb-2">
                <span class="badge bg-primary px-3 py-1.5 font-semibold" style="border-radius: 20px; font-size: 12px;">
                    <i class="fa-solid fa-user-gear me-1"></i> Class Representative Portal
                </span>
                <?php if (!empty($selected_section)): ?>
                    <span class="badge bg-dark px-3 py-1.5 font-semibold" style="border-radius: 20px; font-size: 12px;">
                        Section <?php echo htmlspecialchars($selected_section); ?>
                    </span>
                <?php endif; ?>
            </div>
            <h2>Welcome back, <?php echo htmlspecialchars($cr_name); ?>! 👋</h2>
            <p>Admin Database Live View: <b class="text-primary font-bold">Showing All Admin Created Academic Subjects & Resources</b></p>
        </div>
        
        <!-- DYNAMIC SECTION SELECTOR DROPDOWN -->
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold text-muted" style="font-size: 13px;">Filter Section:</label>
            <select class="form-select form-select-sm fw-bold border-primary" style="border-radius: 10px; min-width: 150px;" onchange="location = this.value;">
                <option value="dashboard.php" <?php echo empty($_GET['section']) ? 'selected' : ''; ?>>All Admin Sections</option>
                <?php 
                if ($sections_query && mysqli_num_rows($sections_query) > 0) {
                    while ($sec = mysqli_fetch_assoc($sections_query)) {
                        $s_val = $sec['section'];
                        $y_val = $sec['year'];
                        if (empty($s_val)) continue;
                        $sel = (strtolower($selected_section) === strtolower($s_val)) ? 'selected' : '';
                        echo "<option value='dashboard.php?year=".urlencode($y_val)."&section=".urlencode($s_val)."' $sel>Year $y_val - Section $s_val</option>";
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <!-- STATS CARDS GRID -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_subjects; ?></h3>
                    <small class="text-muted font-semibold">Total Admin Subjects</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_assignments; ?></h3>
                    <small class="text-muted font-semibold">Active Assignments</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-laptop-code"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_works; ?></h3>
                    <small class="text-muted font-semibold">Class Works Uploaded</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ANNOUNCEMENTS SECTION -->
    <?php if ($ann_result && mysqli_num_rows($ann_result) > 0): ?>
        <div class="mb-5" id="announcementsSection">
            <h4 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-bullhorn text-primary me-2"></i> Department Circulars & Announcements</h4>
            <?php while ($ann = mysqli_fetch_assoc($ann_result)): 
                $img = $ann['image_path'] ?? ($ann['poster'] ?? '');
            ?>
                <div class="notice-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($ann['title']); ?></h5>
                        <span class="badge bg-warning text-dark px-3 py-1 fw-bold" style="border-radius: 12px; font-size: 11px;">Target: <?php echo htmlspecialchars($ann['target_audience'] ?? 'All'); ?></span>
                    </div>

                    <?php if (!empty($ann['content'])): ?>
                        <p class="text-secondary mb-2" style="font-size: 14px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($img) && file_exists(BASE_DIR . '/' . $img)): ?>
                        <div class="my-2">
                            <a href="../<?php echo htmlspecialchars($img); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                                <i class="fa-solid fa-image me-1"></i> View Attachment Poster
                            </a>
                        </div>
                    <?php endif; ?>

                    <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> Posted on <?php echo date('d M Y, h:i A', strtotime($ann['posted_at'] ?? 'now')); ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- DYNAMIC ADMIN SUBJECTS & LABS GRID -->
    <div class="d-flex justify-content-between align-items-center mb-3" id="subjectsSection">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-book-open-reader text-primary me-2"></i> Admin Academic Subjects & Resources
        </h4>
        <span class="badge bg-primary px-3 py-1.5 fs-6" style="border-radius: 12px;">
            <?php echo !empty($selected_section) ? "Section $selected_section" : "All Sections"; ?>
        </span>
    </div>
    <p class="text-muted mb-4" style="font-size: 13.5px;">Click on any subject below to manage or view class works, assignments, mid marks, important questions, and lab materials added by Admin.</p>

    <div class="row g-4">
        <?php if ($sub_result && mysqli_num_rows($sub_result) > 0): ?>
            <?php while ($sub = mysqli_fetch_assoc($sub_result)): 
                $s_year = $sub['year'] ?? '2';
                $s_sec = $sub['section'] ?? 'IT2A';
            ?>
                <div class="col-md-4">
                    <div class="card-subject">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge-type <?php echo (strtolower($sub['subject_type'])==='theory'||empty($sub['subject_type']))?'badge-theory':'badge-lab'; ?>">
                                    <i class="fa-solid <?php echo (strtolower($sub['subject_type'])==='theory'||empty($sub['subject_type']))?'fa-book':'fa-flask'; ?> me-1"></i> <?php echo htmlspecialchars($sub['subject_type'] ?? 'Theory'); ?>
                                </span>
                                <span class="badge bg-secondary text-white px-2 py-1" style="border-radius: 8px; font-size: 11px;">
                                    Year <?php echo htmlspecialchars($s_year); ?> - <?php echo htmlspecialchars($s_sec); ?>
                                </span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($sub['subject_name']); ?></h5>
                            <p class="text-muted" style="font-size: 13px;">Class works, assignments, mid marks & lab records.</p>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <a href="subject_details.php?year=<?php echo urlencode($s_year); ?>&section=<?php echo urlencode($s_sec); ?>&subject=<?php echo urlencode($sub['subject_name']); ?>&type=<?php echo urlencode($sub['subject_type'] ?? 'Theory'); ?>" 
                               class="btn btn-primary btn-sm w-100 py-2.5 fw-bold" style="border-radius: 10px; box-shadow: 0 4px 14px rgba(13,110,253,0.25);">
                                <i class="fa-solid fa-folder-open me-1"></i> Open Subject Resources
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm p-4">
                <i class="fa-solid fa-folder-open fs-1 text-primary opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">No Subjects Created in Database Yet</h5>
                <p class="text-muted mb-0">Ask Administrator to add subjects in Admin Panel under Academic Years section.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- FOOTER -->
<footer class="text-center py-4 text-muted border-top bg-white mt-5" style="font-size: 13px;">
    © 2026 Sir C.R. Reddy College of Engineering - Department of Information Technology (CRR-INFORMTECH)
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>