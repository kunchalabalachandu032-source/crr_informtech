<?php
session_start();
require_once "../database/database.php";

// Auth guard for CR or Admin
if ((!isset($_SESSION['cr_logged_in']) || $_SESSION['cr_logged_in'] !== true) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header("Location: login.php");
    exit();
}

$year = db_escape($_GET['year'] ?? '');
$section = db_escape($_GET['section'] ?? '');
$subject = db_escape($_GET['subject'] ?? '');
$type = db_escape($_GET['type'] ?? 'Theory');

if (empty($year) || empty($section) || empty($subject)) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($subject); ?> Resources | CR Portal</title>

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

.header-box {
    background: linear-gradient(120deg, #0f2b46 0%, #0d6efd 60%, #0369a1 100%);
    color: white; padding: 40px 20px; text-align: center;
    border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;
    box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2); position: relative;
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

.badge-type-pill {
    display: inline-block; background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3); color: #ffffff;
    font-size: 12px; font-weight: 600; padding: 4px 14px; border-radius: 20px; margin-bottom: 8px;
}

.container-box { max-width: 1100px; margin: 50px auto 40px auto; padding: 0 20px; }

.resources-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px;
}

.card-resource-clean {
    background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 22px; padding: 35px 25px; text-align: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
}

.card-resource-clean::before {
    content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
    background: linear-gradient(90deg, #0d6efd, #0369a1);
}

.card-resource-clean:hover { transform: translateY(-8px); box-shadow: 0 16px 36px rgba(0, 0, 0, 0.1); }

.resource-icon-box {
    width: 75px; height: 75px; border-radius: 50%; margin: 0 auto 20px auto;
    background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center;
    font-size: 32px; box-shadow: 0 6px 18px rgba(2, 132, 199, 0.18); transition: transform 0.3s ease;
}

.card-resource-clean:hover .resource-icon-box {
    transform: scale(1.08); background: linear-gradient(135deg, #0d6efd, #0a58ca); color: #ffffff;
}

.card-resource-clean h4 { font-weight: 800; font-size: 20px; color: #1e293b; margin-bottom: 8px; }
.card-resource-clean p { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 25px; }

.btn-open-clean {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    text-decoration: none; background: linear-gradient(135deg, #0d6efd, #0369a1);
    color: white; padding: 12px 28px; border-radius: 12px; font-weight: 700;
    font-size: 14.5px; width: 100%; transition: all 0.25s ease; box-shadow: 0 6px 18px rgba(13, 110, 253, 0.22);
}

.btn-open-clean:hover {
    background: linear-gradient(135deg, #0a58ca, #0284c7); color: white;
    box-shadow: 0 10px 24px rgba(13, 110, 253, 0.35); transform: translateY(-2px);
}
</style>
</head>

<body>

<div class="header-box">
    <div class="back-top">
        <a href="dashboard.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <span class="badge-type-pill">
        Year <?php echo htmlspecialchars($year); ?> | Section <?php echo htmlspecialchars($section); ?> (<?php echo htmlspecialchars($type); ?>)
    </span>
    <h1><?php echo htmlspecialchars($subject); ?></h1>
    <p>Class Representative Academic Management | CRR-INFORMTECH</p>
</div>

<div class="container-box">
    <div class="resources-grid">

        <?php if (strtolower($type) === 'lab'): ?>

            <!-- LAB RESOURCE 1: OBSERVATION PROGRAMS -->
            <div class="card-resource-clean">
                <div>
                    <div class="resource-icon-box">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <h4>Observation Programs</h4>
                    <p>Manage and upload Lab experiment codes & program files.</p>
                </div>
                <a href="../admin/observations.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>" class="btn-open-clean">
                    <i class="fa-solid fa-folder-open"></i> Open Observations
                </a>
            </div>

            <!-- LAB RESOURCE 2: RECORD PDF -->
            <div class="card-resource-clean">
                <div>
                    <div class="resource-icon-box">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <h4>Lab Record PDFs</h4>
                    <p>Upload and view Official Lab Record PDFs & Manuals.</p>
                </div>
                <a href="../admin/record_pdf.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>" class="btn-open-clean">
                    <i class="fa-solid fa-folder-open"></i> Open Lab Records
                </a>
            </div>

        <?php else: ?>

            <!-- THEORY RESOURCE 1: WORKS -->
            <div class="card-resource-clean">
                <div>
                    <div class="resource-icon-box">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <h4>Class Works</h4>
                    <p>Lecture notes, daily class materials, and topic documents.</p>
                </div>
                <a href="../admin/works.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>" class="btn-open-clean">
                    <i class="fa-solid fa-folder-open"></i> Open Class Works
                </a>
            </div>

            <!-- THEORY RESOURCE 2: ASSIGNMENTS -->
            <div class="card-resource-clean">
                <div>
                    <div class="resource-icon-box">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <h4>Assignments</h4>
                    <p>Post student assignment questions, due dates & PDFs.</p>
                </div>
                <a href="../admin/assignments.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>" class="btn-open-clean">
                    <i class="fa-solid fa-folder-open"></i> Open Assignments
                </a>
            </div>

            <!-- THEORY RESOURCE 3: MID MARKS -->
            <div class="card-resource-clean">
                <div>
                    <div class="resource-icon-box">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>
                    <h4>Mid Marks</h4>
                    <p>Manage Mid-1 and Mid-2 student examination marks.</p>
                </div>
                <a href="../admin/mid_marks.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>" class="btn-open-clean">
                    <i class="fa-solid fa-folder-open"></i> Open Mid Marks
                </a>
            </div>

            <!-- THEORY RESOURCE 4: IMPORTANT QUESTIONS -->
            <div class="card-resource-clean">
                <div>
                    <div class="resource-icon-box">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h4>Important Questions</h4>
                    <p>Unit-wise question banks, previous papers & PDFs.</p>
                </div>
                <a href="../admin/important_questions.php?year=<?php echo urlencode($year); ?>&section=<?php echo urlencode($section); ?>&subject=<?php echo urlencode($subject); ?>" class="btn-open-clean">
                    <i class="fa-solid fa-folder-open"></i> Open Important Questions
                </a>
            </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
