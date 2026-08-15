<?php
// FORCE NO BROWSER CACHING
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once "../database/database.php";

$year = db_escape($_GET['year'] ?? '2');
$section = db_escape($_GET['section'] ?? 'IT2A');
$subject = db_escape($_GET['subject'] ?? '');
$type = db_escape($_GET['type'] ?? 'Theory');

// Numeric year
$clean_year = preg_replace('/[^0-9]/', '', $year);
if (empty($clean_year)) $clean_year = '2';

// Fetch resources for this subject from database (Read-Only View)
$works_q = @mysqli_query($conn, "SELECT * FROM class_works WHERE (year='$year' OR year='$clean_year') AND (LOWER(section)=LOWER('$section') OR REPLACE(LOWER(section),'-','')=REPLACE(LOWER('$section'),'-','')) AND LOWER(subject_name)=LOWER('$subject') ORDER BY id DESC");
$assign_q = @mysqli_query($conn, "SELECT * FROM assignments WHERE (year='$year' OR year='$clean_year') AND (LOWER(section)=LOWER('$section') OR REPLACE(LOWER(section),'-','')=REPLACE(LOWER('$section'),'-','')) AND LOWER(subject_name)=LOWER('$subject') ORDER BY id DESC");
$mid_q = @mysqli_query($conn, "SELECT * FROM mid_marks WHERE (year='$year' OR year='$clean_year') AND (LOWER(section)=LOWER('$section') OR REPLACE(LOWER(section),'-','')=REPLACE(LOWER('$section'),'-','')) AND LOWER(subject_name)=LOWER('$subject') ORDER BY id DESC");
$imp_q = @mysqli_query($conn, "SELECT * FROM important_questions WHERE (year='$year' OR year='$clean_year') AND (LOWER(section)=LOWER('$section') OR REPLACE(LOWER(section),'-','')=REPLACE(LOWER('$section'),'-','')) AND LOWER(subject_name)=LOWER('$subject') ORDER BY id DESC");

// Base64 College Logo for Transparent Watermark Background
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
<title><?php echo htmlspecialchars($subject); ?> | Student Resources View</title>

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body {
    background: #0f172a;
    color: #ffffff;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* ================= TRANSPARENT COLLEGE LOGO WATERMARK BACKGROUND ================= */
.watermark-bg {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 750px; height: 750px;
    background-image: url('<?php echo $logo_base64; ?>');
    background-repeat: no-repeat;
    background-position: center center;
    background-size: contain;
    opacity: 0.08; /* Transparent watermark emblem */
    pointer-events: none;
    z-index: 0;
}

.header-box {
    background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,0.12);
    padding: 35px 20px; text-align: center; position: relative; z-index: 10;
}

.btn-back {
    background: rgba(255, 255, 255, 0.12); color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 12px;
    padding: 8px 20px; font-size: 13.5px; font-weight: 600; text-decoration: none;
    transition: all 0.25s ease; position: absolute; left: 25px; top: 25px;
}
.btn-back:hover { background: #ffffff; color: #0284c7; }

.container-main { max-width: 1100px; margin: 40px auto 60px auto; padding: 0 20px; position: relative; z-index: 10; }

.resource-card {
    background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(10px);
    border-radius: 22px; padding: 28px; border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 30px;
}

.btn-view-pdf {
    background: linear-gradient(135deg, #0284c7, #0369a1); color: white;
    border: none; border-radius: 10px; padding: 8px 18px; font-weight: 700; font-size: 13px;
    text-decoration: none; transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 6px;
}
.btn-view-pdf:hover { background: linear-gradient(135deg, #0369a1, #075985); color: white; transform: translateY(-2px); }
</style>
</head>

<body>

<!-- Transparent Emblem Logo Watermark Background -->
<div class="watermark-bg"></div>

<!-- HEADER -->
<div class="header-box">
    <a href="dashboard.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>" class="btn-back">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Student Portal
    </a>

    <span class="badge bg-info text-dark px-3 py-1 mb-2 font-semibold" style="border-radius: 20px; font-size: 12px;">
        Year <?php echo htmlspecialchars($clean_year); ?> — Section <?php echo htmlspecialchars($section); ?> (<?php echo htmlspecialchars($type); ?>)
    </span>
    <h1 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($subject); ?></h1>
    <p class="text-info font-semibold mb-0" style="font-size: 14px;">Sir C.R. Reddy College of Engineering — Department of IT (Student View)</p>
</div>

<div class="container-main">

    <!-- 1. CLASS WORKS / LECTURE NOTES -->
    <div class="resource-card">
        <h4 class="fw-bold text-white mb-3"><i class="fa-solid fa-file-lines text-info me-2"></i> Class Works & Lecture Notes</h4>
        <?php if ($works_q && mysqli_num_rows($works_q) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" style="background: transparent;">
                    <thead>
                        <tr class="text-info">
                            <th>Topic / Title</th>
                            <th>File Attachment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($w = mysqli_fetch_assoc($works_q)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($w['title'] ?? 'Lecture Note'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars(basename($w['file_path'] ?? 'file')); ?></span></td>
                                <td>
                                    <?php if (!empty($w['file_path']) && file_exists(BASE_DIR . '/' . $w['file_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($w['file_path']); ?>" target="_blank" class="btn-view-pdf">
                                            <i class="fa-solid fa-eye"></i> View & Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 12px;">No File</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-light opacity-75 mb-0"><i class="fa-solid fa-info-circle me-1 text-info"></i> No class works uploaded for this subject yet.</p>
        <?php endif; ?>
    </div>

    <!-- 2. ASSIGNMENTS -->
    <div class="resource-card">
        <h4 class="fw-bold text-white mb-3"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Student Assignments</h4>
        <?php if ($assign_q && mysqli_num_rows($assign_q) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-warning">
                            <th>Assignment Title</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($a = mysqli_fetch_assoc($assign_q)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($a['title'] ?? 'Assignment'); ?></td>
                                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($a['due_date'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <?php if (!empty($a['file_path']) && file_exists(BASE_DIR . '/' . $a['file_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($a['file_path']); ?>" target="_blank" class="btn-view-pdf">
                                            <i class="fa-solid fa-eye"></i> View & Download PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 12px;">View Only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-light opacity-75 mb-0"><i class="fa-solid fa-info-circle me-1 text-warning"></i> No assignments posted for this subject yet.</p>
        <?php endif; ?>
    </div>

    <!-- 3. MID MARKS -->
    <div class="resource-card">
        <h4 class="fw-bold text-white mb-3"><i class="fa-solid fa-chart-column text-success me-2"></i> Mid Marks Sheets</h4>
        <?php if ($mid_q && mysqli_num_rows($mid_q) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-success">
                            <th>Exam Title</th>
                            <th>Marks File / Sheet</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = mysqli_fetch_assoc($mid_q)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($m['exam_name'] ?? 'Mid Marks Sheet'); ?></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars(basename($m['file_path'] ?? 'marksheet')); ?></span></td>
                                <td>
                                    <?php if (!empty($m['file_path']) && file_exists(BASE_DIR . '/' . $m['file_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn-view-pdf">
                                            <i class="fa-solid fa-eye"></i> View Marks Sheet
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-light opacity-75 mb-0"><i class="fa-solid fa-info-circle me-1 text-success"></i> Mid marks sheets not published yet.</p>
        <?php endif; ?>
    </div>

    <!-- 4. IMPORTANT QUESTIONS -->
    <div class="resource-card">
        <h4 class="fw-bold text-white mb-3"><i class="fa-solid fa-graduation-cap text-info me-2"></i> Important Questions & Question Banks</h4>
        <?php if ($imp_q && mysqli_num_rows($imp_q) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-info">
                            <th>Question Bank / Unit Title</th>
                            <th>Attachment File</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($iq = mysqli_fetch_assoc($imp_q)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($iq['title'] ?? 'Important Questions'); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars(basename($iq['file_path'] ?? 'qb')); ?></span></td>
                                <td>
                                    <?php if (!empty($iq['file_path']) && file_exists(BASE_DIR . '/' . $iq['file_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($iq['file_path']); ?>" target="_blank" class="btn-view-pdf">
                                            <i class="fa-solid fa-eye"></i> View & Download Question Bank
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-light opacity-75 mb-0"><i class="fa-solid fa-info-circle me-1 text-info"></i> Important question banks not published yet.</p>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
