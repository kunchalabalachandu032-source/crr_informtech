<?php
// FORCE NO BROWSER CACHING FOR LIVE REAL-TIME DATA
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once "../database/database.php";

// Extract selected Academic Year & Section from URL or Session
$selected_year = db_escape($_GET['year'] ?? ($_SESSION['student_year'] ?? '2'));
$selected_section = db_escape($_GET['section'] ?? ($_SESSION['student_section'] ?? 'IT2A'));

// Restrict year to ONLY 2 or 3
$clean_year = preg_replace('/[^0-9]/', '', $selected_year);
if ($clean_year !== '3') $clean_year = '2';

$year_title = ($clean_year === '3') ? '3rd Year' : '2nd Year';

// ------------------------------------------------------------------------
// 1. LIVE MYSQL QUERY: SUBJECTS FOR SELECTED YEAR & SECTION ONLY
// ------------------------------------------------------------------------
$sub_query = "SELECT * FROM subjects WHERE (year='$clean_year' OR year LIKE '%$clean_year%') AND (LOWER(section) = LOWER('$selected_section') OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER('$selected_section'), '-', '')) ORDER BY subject_type ASC, id ASC";
$sub_result = mysqli_query($conn, $sub_query);

// Fallback if specific section string mismatch: load subjects for that clean year
if (!$sub_result || mysqli_num_rows($sub_result) == 0) {
    $sub_query = "SELECT * FROM subjects WHERE (year='$clean_year' OR year LIKE '%$clean_year%') ORDER BY subject_type ASC, id ASC";
    $sub_result = mysqli_query($conn, $sub_query);
}

// ------------------------------------------------------------------------
// 2. LIVE MYSQL QUERY: CLASS REPRESENTATIVES (CRs) FOR SELECTED SECTION ONLY
// ------------------------------------------------------------------------
$cr_list = [];
$q_cr1 = @mysqli_query($conn, "SELECT name, roll_number, email, phone, year, section FROM cr_accounts WHERE (year='$clean_year' OR year LIKE '%$clean_year%') AND (LOWER(section)=LOWER('$selected_section') OR REPLACE(LOWER(section), '-', '')=REPLACE(LOWER('$selected_section'), '-', ''))");
if ($q_cr1 && mysqli_num_rows($q_cr1) > 0) {
    while ($r = mysqli_fetch_assoc($q_cr1)) {
        $cr_list[] = $r;
    }
}

// Check legacy `crs` table
$q_cr2 = @mysqli_query($conn, "SELECT name, roll_number, email, phone, year, section FROM crs WHERE (year='$clean_year' OR year LIKE '%$clean_year%') AND (LOWER(section)=LOWER('$selected_section') OR REPLACE(LOWER(section), '-', '')=REPLACE(LOWER('$selected_section'), '-', ''))");
if ($q_cr2 && mysqli_num_rows($q_cr2) > 0) {
    while ($r = mysqli_fetch_assoc($q_cr2)) {
        $exists = false;
        foreach ($cr_list as $existing) {
            if (strtolower($existing['roll_number']) === strtolower($r['roll_number'])) { $exists = true; break; }
        }
        if (!$exists) $cr_list[] = $r;
    }
}

// Check `managers` table fallback
if (empty($cr_list)) {
    $q_cr3 = @mysqli_query($conn, "SELECT username as name, username as roll_number, email, '' as phone, '$clean_year' as year, '$selected_section' as section FROM managers WHERE role='cr'");
    if ($q_cr3 && mysqli_num_rows($q_cr3) > 0) {
        while ($r = mysqli_fetch_assoc($q_cr3)) {
            $cr_list[] = $r;
        }
    }
}

// ------------------------------------------------------------------------
// 3. LIVE MYSQL QUERY: FACULTY DIRECTORY
// ------------------------------------------------------------------------
$fac_query = "SELECT * FROM faculty ORDER BY id DESC LIMIT 6";
$fac_result = mysqli_query($conn, $fac_query);

// ------------------------------------------------------------------------
// 4. LIVE MYSQL QUERY: ANNOUNCEMENTS & POPUP NOTIFICATION
// ------------------------------------------------------------------------
$ann_query = "SELECT * FROM announcements WHERE (target_audience='All' OR target_audience='$year_title' OR target_audience LIKE '%$clean_year%') ORDER BY id DESC LIMIT 5";
$ann_result = mysqli_query($conn, $ann_query);

// Latest announcement for auto popup modal on page load
$latest_ann_query = "SELECT * FROM announcements WHERE (target_audience='All' OR target_audience='$year_title' OR target_audience LIKE '%$clean_year%') ORDER BY id DESC LIMIT 1";
$latest_ann_result = mysqli_query($conn, $latest_ann_query);
$latest_ann = ($latest_ann_result && mysqli_num_rows($latest_ann_result) > 0) ? mysqli_fetch_assoc($latest_ann_result) : null;

// Base64 College Logo for Left Sidebar & Transparent Watermark Background
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
<title>CRR-INFORMTECH | Student Portal - <?php echo htmlspecialchars($year_title); ?></title>

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body {
    background: #f1f5f9;
    color: #0f172a;
    min-height: 100vh;
    display: flex;
    position: relative;
    overflow-x: hidden;
}

/* ================= CLEARLY VISIBLE TRANSPARENT COLLEGE LOGO WATERMARK BACKGROUND ================= */
.watermark-bg {
    position: fixed; top: 50%; left: calc(50% + 140px);
    transform: translate(-50%, -50%);
    width: 680px; height: 680px;
    background-image: url('<?php echo $logo_base64; ?>');
    background-repeat: no-repeat;
    background-position: center center;
    background-size: contain;
    opacity: 0.16; /* Clearly visible transparent emblem logo */
    pointer-events: none;
    z-index: 0;
}

/* ================= LEFT SIDEBAR NAVIGATION PANEL ================= */
.left-sidebar {
    width: 280px; height: 100vh; position: fixed; top: 0; left: 0;
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff; padding: 28px 20px; z-index: 100;
    box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    display: flex; flex-direction: column; justify-content: space-between;
}

.sidebar-logo-box {
    text-align: center; padding-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.12);
}
.sidebar-logo { width: 75px; height: 75px; border-radius: 50%; object-fit: contain; margin-bottom: 10px; }
.sidebar-logo-box h5 { font-size: 16px; font-weight: 800; margin-bottom: 2px; color: #ffffff; }
.sidebar-logo-box small { font-size: 11px; color: #38bdf8; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

.class-badge {
    background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3);
    color: #38bdf8; border-radius: 12px; padding: 8px 12px; font-size: 12px; font-weight: 700;
    text-align: center; margin: 18px 0;
}

.sidebar-menu { list-style: none; padding: 0; margin: 0; }
.sidebar-menu li { margin-bottom: 10px; }

/* LEFT SIDE BUTTON STYLES */
.sidebar-btn {
    width: 100%; display: flex; align-items: center; gap: 12px;
    padding: 13px 18px; border-radius: 14px; border: none;
    background: rgba(255, 255, 255, 0.06); color: rgba(255, 255, 255, 0.85);
    font-weight: 700; font-size: 14px; transition: all 0.25s ease;
    cursor: pointer; text-align: left;
}
.sidebar-btn:hover { background: rgba(255, 255, 255, 0.15); color: #38bdf8; }
.sidebar-btn.active {
    background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff;
    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.4);
}

.exit-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    background: #ef4444; color: white; border-radius: 12px; font-weight: 700;
    padding: 11px; font-size: 13.5px; text-decoration: none; transition: all 0.25s ease;
}
.exit-btn:hover { background: #dc2626; color: white; }

/* ================= MAIN CONTENT AREA (RIGHT SIDE) ================= */
.main-content {
    margin-left: 280px; width: calc(100% - 280px);
    padding: 35px 40px; position: relative; z-index: 10;
}

/* TOP HEADER CARD WITH PROMINENT BOLD DEPARTMENT TITLE */
.top-header-card {
    background: #ffffff; border-radius: 20px; padding: 24px 30px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.06);
    margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;
}
.dept-heading {
    font-size: 26px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px;
}

/* DYNAMIC VIEW SECTIONS */
.view-section { display: none; }
.view-section.active-view { display: block; animation: fadeIn 0.3s ease-in-out; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ================= CARDS ================= */
.card-subject {
    background: #ffffff; border-radius: 20px; padding: 26px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease; height: 100%;
    display: flex; flex-direction: column; justify-content: space-between;
    border-top: 4px solid #0284c7;
}
.card-subject:hover { transform: translateY(-5px); box-shadow: 0 14px 30px rgba(0,0,0,0.08); }

.badge-type { width: fit-content; padding: 5px 12px; border-radius: 16px; font-weight: 700; font-size: 11.5px; }
.badge-theory { background: #e0f2fe; color: #0284c7; }
.badge-lab { background: #fef3c7; color: #d97706; }

.card-cr {
    background: #ffffff; border-radius: 20px; padding: 24px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.06);
    border-left: 5px solid #0284c7; height: 100%;
}

.fac-card {
    background: #ffffff; border-radius: 20px; padding: 22px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.06);
    display: flex; align-items: center; gap: 18px; height: 100%;
}
.fac-img { width: 65px; height: 65px; border-radius: 50%; object-fit: cover; border: 3px solid #0284c7; }

.notice-box {
    background: #ffffff; border-radius: 18px; padding: 24px;
    border-left: 5px solid #ffc107; margin-bottom: 18px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);
}

.poster-modal-img {
    max-width: 100%; max-height: 420px; border-radius: 16px; object-fit: contain; border: 1px solid #e2e8f0;
}
</style>
</head>

<body>

<!-- CLEARLY VISIBLE TRANSPARENT EMBLEM LOGO IN BACKGROUND -->
<div class="watermark-bg"></div>

<!-- LEFT SIDEBAR NAVIGATION PANEL (CONTAINING LEFT SIDE BUTTONS) -->
<div class="left-sidebar">
    <div>
        <div class="sidebar-logo-box">
            <?php if (!empty($logo_base64)): ?>
                <img src="<?php echo $logo_base64; ?>" class="sidebar-logo" alt="College Emblem Logo">
            <?php else: ?>
                <i class="fa-solid fa-graduation-cap fs-1 text-info mb-2"></i>
            <?php endif; ?>
            <h5>SIR C R REDDY</h5>
            <small>Student Academic Portal</small>
        </div>

        <div class="class-badge">
            <i class="fa-solid fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($year_title); ?> — Section <?php echo htmlspecialchars($selected_section); ?>
        </div>

        <!-- LEFT SIDE BUTTONS: SUBJECTS, CRS, FACULTY, ANNOUNCEMENTS -->
        <ul class="sidebar-menu">
            <li>
                <button type="button" class="sidebar-btn active" onclick="switchView('subjectsView', this)">
                    <i class="fa-solid fa-book-open"></i> Subjects & Labs
                </button>
            </li>
            <li>
                <button type="button" class="sidebar-btn" onclick="switchView('crsView', this)">
                    <i class="fa-solid fa-user-shield"></i> Class Representatives
                </button>
            </li>
            <li>
                <button type="button" class="sidebar-btn" onclick="switchView('facultyView', this)">
                    <i class="fa-solid fa-chalkboard-user"></i> Faculty Directory
                </button>
            </li>
            <li>
                <button type="button" class="sidebar-btn" onclick="switchView('announcementsView', this)">
                    <i class="fa-solid fa-bullhorn"></i> Announcements
                </button>
            </li>
        </ul>
    </div>

    <div class="pt-3 border-top border-secondary">
        <a href="index.php" class="exit-btn">
            <i class="fa-solid fa-house"></i> Change Class / Exit
        </a>
    </div>
</div>

<!-- MAIN CONTENT AREA (RIGHT SIDE) -->
<div class="main-content">

    <!-- TOP HEADER CARD WITH PROMINENT BOLD DEPARTMENT HEADING -->
    <div class="top-header-card">
        <div>
            <h2 class="dept-heading">DEPARTMENT OF INFORMATION TECHNOLOGY</h2>
            <p class="text-muted mb-0" style="font-size: 13.5px;">Sir C.R. Reddy College of Engineering (Autonomous) — Student Academic Resources</p>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-6" style="border-radius: 12px;">
                <?php echo htmlspecialchars($year_title); ?> — Section <?php echo htmlspecialchars($selected_section); ?>
            </span>
        </div>
    </div>

    <!-- 1. ACADEMIC SUBJECTS & LABS VIEW -->
    <div id="subjectsView" class="view-section active-view">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-book-open-reader text-primary me-2"></i> Academic Subjects & Labs</h4>
            <span class="badge bg-primary px-3 py-1.5 fs-6" style="border-radius: 10px;"><?php echo htmlspecialchars($year_title); ?> — Section <?php echo htmlspecialchars($selected_section); ?></span>
        </div>
        <p class="text-muted mb-4" style="font-size: 13.5px;">Click <b>View & Download Resources</b> on any subject below to open class works, assignments, mid marks, and important questions for <b>Section <?php echo htmlspecialchars($selected_section); ?></b>.</p>

        <div class="row g-4">
            <?php if ($sub_result && mysqli_num_rows($sub_result) > 0): ?>
                <?php while ($sub = mysqli_fetch_assoc($sub_result)): 
                    $s_type = $sub['subject_type'] ?? 'Theory';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card-subject">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge-type <?php echo (strtolower($s_type)==='theory')?'badge-theory':'badge-lab'; ?>">
                                        <i class="fa-solid <?php echo (strtolower($s_type)==='theory')?'fa-book':'fa-flask'; ?> me-1"></i> <?php echo htmlspecialchars($s_type); ?>
                                    </span>
                                    <span class="badge bg-light text-dark border px-2 py-1" style="border-radius: 8px; font-size: 11px;">
                                        Section <?php echo htmlspecialchars($selected_section); ?>
                                    </span>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($sub['subject_name']); ?></h5>
                                <p class="text-muted" style="font-size: 13px;">Class works, assignments, mid marks & lab manuals.</p>
                            </div>
                            <div class="mt-4 pt-3 border-top">
                                <a href="subject_details.php?year=<?php echo urlencode($clean_year); ?>&section=<?php echo urlencode($selected_section); ?>&subject=<?php echo urlencode($sub['subject_name']); ?>&type=<?php echo urlencode($s_type); ?>" 
                                   class="btn btn-primary btn-sm w-100 py-2.5 fw-bold" style="border-radius: 10px; box-shadow: 0 4px 14px rgba(13,110,253,0.25);">
                                    <i class="fa-solid fa-eye me-1"></i> View & Download Resources
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm p-4 border">
                    <i class="fa-solid fa-folder-open fs-1 text-primary opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Subjects Published for Section <?php echo htmlspecialchars($selected_section); ?> Yet</h5>
                    <p class="text-muted mb-0">Check back soon for updated class materials.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. CLASS REPRESENTATIVES (CRs) VIEW -->
    <div id="crsView" class="view-section">
        <h4 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-user-shield text-info me-2"></i> Class Representatives (CRs) — Section <?php echo htmlspecialchars($selected_section); ?></h4>
        <div class="row g-4">
            <?php if (!empty($cr_list)): ?>
                <?php foreach ($cr_list as $cr): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card-cr">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-4">
                                    <i class="fa-solid fa-id-badge"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($cr['name'] ?? 'Class Representative'); ?></h6>
                                    <span class="badge bg-primary px-2 py-0.5" style="font-size: 11px;">Roll: <?php echo htmlspecialchars($cr['roll_number'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="pt-2 border-top text-muted" style="font-size: 13px;">
                                <?php if (!empty($cr['email'])): ?>
                                    <p class="mb-1"><i class="fa-regular fa-envelope me-2 text-primary"></i> <?php echo htmlspecialchars($cr['email']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($cr['phone'])): ?>
                                    <p class="mb-0"><i class="fa-solid fa-phone me-2 text-success"></i> <?php echo htmlspecialchars($cr['phone']); ?></p>
                                <?php else: ?>
                                    <p class="mb-0 text-muted"><i class="fa-solid fa-user-tag me-2 text-secondary"></i> Assigned CR for <?php echo htmlspecialchars($selected_section); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="bg-white p-4 text-center rounded-4 border shadow-sm text-muted">
                        <i class="fa-solid fa-user-slash fs-3 mb-2 opacity-50"></i>
                        <h6>No CR Details Assigned for Section <?php echo htmlspecialchars($selected_section); ?> Yet</h6>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. IT DEPARTMENT FACULTY DIRECTORY VIEW -->
    <div id="facultyView" class="view-section">
        <h4 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-chalkboard-user text-primary me-2"></i> IT Department Faculty Directory</h4>
        <div class="row g-4">
            <?php if ($fac_result && mysqli_num_rows($fac_result) > 0): ?>
                <?php while ($fac = mysqli_fetch_assoc($fac_result)): 
                    $f_img = !empty($fac['image_path']) && file_exists(BASE_DIR . '/' . $fac['image_path']) ? '../' . $fac['image_path'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="fac-card">
                            <img src="<?php echo htmlspecialchars($f_img); ?>" class="fac-img" alt="Faculty Image">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($fac['name']); ?></h6>
                                <p class="text-primary mb-1 fw-semibold" style="font-size: 12.5px;"><?php echo htmlspecialchars($fac['subject'] ?? ($fac['designation'] ?? 'Department Faculty')); ?></p>
                                <small class="text-muted"><i class="fa-solid fa-graduation-cap me-1"></i> Department of IT</small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4 bg-white rounded-4 border text-muted">
                    No faculty directory entries found.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4. DEPARTMENT ANNOUNCEMENTS VIEW -->
    <div id="announcementsView" class="view-section">
        <h4 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-bullhorn text-warning me-2"></i> Department Announcements & Circulars</h4>
        <?php if ($ann_result && mysqli_num_rows($ann_result) > 0): ?>
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
        <?php else: ?>
            <div class="bg-white p-4 text-center rounded-4 border text-muted">
                No active department announcements.
            </div>
        <?php endif; ?>
    </div>

    <footer class="text-center py-4 text-muted border-top mt-5" style="font-size: 13px;">
        © 2026 Sir C.R. Reddy College of Engineering — Department of Information Technology (CRR-INFORMTECH)
    </footer>

</div>

<!-- LATEST ANNOUNCEMENT POPUP NOTIFICATION MODAL -->
<?php if ($latest_ann): 
    $ann_img = $latest_ann['image_path'] ?? ($latest_ann['poster'] ?? '');
?>
<div class="modal fade" id="announcementPopupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 24px; overflow: hidden; border: none;">
            <div class="modal-header bg-primary text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-bullhorn text-warning me-2 fs-4"></i> Department Circular & Notice
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h4 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($latest_ann['title']); ?></h4>
                
                <?php if (!empty($latest_ann['content'])): ?>
                    <p class="text-secondary text-start mb-4" style="font-size: 14.5px; line-height: 1.7; background: #f8fafc; padding: 18px; border-radius: 14px; border-left: 4px solid #0d6efd;">
                        <?php echo nl2br(htmlspecialchars($latest_ann['content'])); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($ann_img) && file_exists(BASE_DIR . '/' . $ann_img)): ?>
                    <div class="mb-3">
                        <img src="../<?php echo htmlspecialchars($ann_img); ?>" class="poster-modal-img shadow-sm" alt="Circular Attachment Poster">
                    </div>
                    <a href="../<?php echo htmlspecialchars($ann_img); ?>" target="_blank" class="btn btn-outline-primary fw-bold px-4 py-2" style="border-radius: 10px;">
                        <i class="fa-solid fa-download me-1"></i> Open Full Attachment
                    </a>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light p-3">
                <small class="text-muted me-auto"><i class="fa-regular fa-clock me-1"></i> Posted on <?php echo date('d M Y, h:i A', strtotime($latest_ann['posted_at'] ?? 'now')); ?></small>
                <button type="button" class="btn btn-primary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius: 10px;">Got It</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function switchView(viewId, btnElement) {
        // Hide all view sections
        const views = document.querySelectorAll('.view-section');
        views.forEach(v => v.classList.remove('active-view'));

        // Deactivate all sidebar buttons
        const btns = document.querySelectorAll('.sidebar-btn');
        btns.forEach(b => b.classList.remove('active'));

        // Activate target view section & sidebar button
        document.getElementById(viewId).classList.add('active-view');
        btnElement.classList.add('active');
    }

    <?php if ($latest_ann): ?>
    document.addEventListener("DOMContentLoaded", function() {
        var annModal = new bootstrap.Modal(document.getElementById('announcementPopupModal'));
        annModal.show();
    });
    <?php endif; ?>
</script>

</body>
</html>
