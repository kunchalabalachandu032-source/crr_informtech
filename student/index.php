<?php
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once "../database/database.php";

// Base64 Logo Encoding from admin folder
$logoSrc = "";
$logoFile = BASE_DIR . '/admin/logo.png';
if (file_exists($logoFile)) {
    $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
}

// Base64 College Photo Encoding from admin folder
$bgSrc = "";
$bgFile = BASE_DIR . '/admin/college_bg.jpg';
if (file_exists($bgFile)) {
    $bgSrc = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgFile));
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = db_escape($_POST['year'] ?? '2');
    $section = db_escape($_POST['section'] ?? 'IT2A');

    $_SESSION['student_year'] = $year;
    $_SESSION['student_section'] = $section;

    header("Location: dashboard.php?year=" . urlencode($year) . "&section=" . urlencode($section));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRR-INFORMTECH | Student Academic Portal</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        html, body { width: 100%; height: 100%; }
        body {
            /* Fallback gradient background */
            background: linear-gradient(135deg, #0f172a, #1e293b, #334155);
            <?php if (!empty($bgSrc)): ?>
            background-image: url('<?php echo $bgSrc; ?>');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
            <?php endif; ?>
            width: 100vw; height: 100vh; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 0; overflow-x: hidden;
        }

        .login-container { width: 100%; height: 100%; max-width: 100%; }
        .login-card {
            background: transparent; border-radius: 0;
            width: 100%; height: 100%; padding: 40px 30px;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
        }
        .login-card form { width: 100%; max-width: 450px; }

        /* Letterhead style header */
        .letterhead {
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 18px; max-width: 720px; width: 100%;
        }
        .logo-wrapper {
            width: 180px; height: 180px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-img {
            max-width: 100%; max-height: 100%; object-fit: contain;
            filter: drop-shadow(0 0 12px rgba(255,255,255,0.8));
        }

        .letterhead-text { text-align: left; }
        .letterhead-text h1 {
            font-size: 24px; font-weight: 800; color: #ffffff;
            margin-bottom: 4px; line-height: 1.2;
            text-shadow: 0 2px 6px rgba(0,0,0,0.8);
        }
        .letterhead-text h1 .autonomous { color: #ff2d2d; }
        .letterhead-text p {
            font-size: 14px; font-weight: 700; color: #ffffff;
            margin-bottom: 2px; text-shadow: 0 2px 6px rgba(0,0,0,0.8);
        }

        .logo-area h2 { font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 2px; text-align: center; text-shadow: 0 2px 6px rgba(0,0,0,0.8); }
        .logo-area h5 { font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 6px; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; text-shadow: 0 2px 6px rgba(0,0,0,0.8); }
        .logo-area p { font-size: 14px; font-weight: 700; color: #38bdf8; margin-bottom: 25px; text-align: center; text-transform: uppercase; letter-spacing: 1px; text-shadow: 0 2px 6px rgba(0,0,0,0.8); }

        .form-label { font-size: 14px; font-weight: 700; color: #ffffff; text-shadow: 0 2px 6px rgba(0,0,0,0.8); }
        .form-select { border-radius: 10px; padding: 12px; font-size: 14px; font-weight: 600; border: 1px solid #cbd5e1; }
        .form-select:focus { box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2); border-color: #0284c7; }

        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            border: none; border-radius: 12px; padding: 14px; font-weight: 700; font-size: 16px; transition: all 0.3s ease;
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
        }
        .btn-primary:hover { background: linear-gradient(135deg, #0369a1, #075985); transform: translateY(-2px); box-shadow: 0 10px 24px rgba(2, 132, 199, 0.5); }
        .footer { margin-top: 25px; text-align: center; font-size: 13px; font-weight: 700; color: #ffffff; text-shadow: 0 2px 6px rgba(0,0,0,0.8); }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">

        <div class="letterhead">
            <div class="logo-wrapper">
                <?php if (!empty($logoSrc)): ?>
                    <img src="<?php echo $logoSrc; ?>" class="logo-img" alt="College Logo">
                <?php else: ?>
                    <div style="color: #ef4444; font-size: 11px; text-align: center;">Image File Not Found</div>
                <?php endif; ?>
            </div>
            <div class="letterhead-text">
                <h1>SIR C R REDDY COLLEGE OF ENGINEERING <span class="autonomous">(Autonomous)</span></h1>
                <p>ELURU, ANDHRA PRADESH 534007</p>
                <p>(Approved by AICTE, New Delhi | Affiliated to JNTUK, Kakinada)</p>
            </div>
        </div>

        <div class="logo-area">
            <h2>CRR-INFORMTECH</h2>
            <h5>Department of Information Technology</h5>
            <p><i class="fa-solid fa-graduation-cap me-2"></i>Student Academic Portal</p>
        </div>

        <form method="POST" action="">
            <!-- Academic Year Dropdown Restricted ONLY to 2nd Year and 3rd Year -->
            <div class="mb-3">
                <label class="form-label"><i class="fa-solid fa-layer-group me-1 text-info"></i> Select Academic Year</label>
                <select name="year" id="yearSelect" class="form-select" onchange="updateSections()" required>
                    <option value="2" selected>2nd Year</option>
                    <option value="3">3rd Year</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="fa-solid fa-users-rectangle me-1 text-info"></i> Select Class Section</label>
                <select name="section" id="sectionSelect" class="form-select" required>
                    <option value="IT2A" selected>Section IT-2A</option>
                    <option value="IT2B">Section IT-2B</option>
                    <option value="IT2C">Section IT-2C</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-door-open me-2"></i> Enter Student Portal
            </button>
        </form>

        <div class="footer">
            © 2026 CRR-INFORMTECH. All Rights Reserved.
        </div>

    </div>
</div>

<script>
    const sectionData = {
        '2': ['IT2A', 'IT2B', 'IT2C'],
        '3': ['IT3A', 'IT3B', 'IT3C']
    };

    function updateSections() {
        const year = document.getElementById('yearSelect').value;
        const sectionSelect = document.getElementById('sectionSelect');
        sectionSelect.innerHTML = '';

        const sections = sectionData[year] || ['IT2A', 'IT2B', 'IT2C'];
        sections.forEach(sec => {
            const opt = document.createElement('option');
            opt.value = sec;
            opt.textContent = 'Section ' + sec;
            sectionSelect.appendChild(opt);
        });
    }
</script>

</body>
</html>
