<?php
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once "database/database.php";

// Base64 Logo Encoding
$logoSrc = "";
$logoFile = __DIR__ . '/admin/logo.png';
if (file_exists($logoFile)) {
    $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
}

// Base64 College Photo Encoding
$bgSrc = "";
$bgFile = __DIR__ . '/admin/college_bg.jpg';
if (file_exists($bgFile)) {
    $bgSrc = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgFile));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRR-INFORMTECH | Department of Information Technology</title>
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
            width: 100vw; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 30px 20px; overflow-x: hidden;
        }

        .main-card {
            background: transparent; border-radius: 0;
            width: 100%; max-width: 1050px;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
        }

        /* Letterhead style header */
        .letterhead {
            display: flex; align-items: center; justify-content: center; gap: 20px;
            margin-bottom: 25px; max-width: 850px; width: 100%; text-align: center;
        }
        .logo-wrapper {
            width: 150px; height: 150px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-img {
            max-width: 100%; max-height: 100%; object-fit: contain;
            filter: drop-shadow(0 0 14px rgba(255,255,255,0.85));
        }

        .letterhead-text { text-align: left; }
        .letterhead-text h1 {
            font-size: 26px; font-weight: 800; color: #ffffff;
            margin-bottom: 4px; line-height: 1.2; text-shadow: 0 2px 8px rgba(0,0,0,0.85);
        }
        .letterhead-text h1 .autonomous { color: #ff2d2d; }
        .letterhead-text p {
            font-size: 14px; font-weight: 700; color: #ffffff;
            margin-bottom: 2px; text-shadow: 0 2px 8px rgba(0,0,0,0.85);
        }

        .dept-title-box { text-align: center; margin-bottom: 40px; }
        .dept-title-box h2 { font-size: 28px; font-weight: 800; color: #ffffff; text-shadow: 0 2px 8px rgba(0,0,0,0.85); margin-bottom: 2px; }
        .dept-title-box h4 { font-size: 18px; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 1.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.85); margin-bottom: 0; }

        /* PORTAL INTEGRATED SELECTION CARDS */
        .portal-card {
            background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 24px;
            padding: 35px 25px; text-align: center; color: white;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            height: 100%; display: flex; flex-direction: column; justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .portal-card:hover {
            transform: translateY(-8px); border-color: #38bdf8;
            box-shadow: 0 18px 40px rgba(2, 132, 199, 0.45);
        }

        .portal-icon {
            width: 70px; height: 70px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px auto; font-size: 28px;
        }

        .icon-student { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 2px solid #38bdf8; }
        .icon-cr { background: rgba(251, 191, 36, 0.2); color: #fbbf24; border: 2px solid #fbbf24; }
        .icon-admin { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 2px solid #f87171; }

        .portal-card h4 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .portal-card p { font-size: 13.5px; opacity: 0.85; margin-bottom: 25px; line-height: 1.5; }

        .btn-enter-student {
            background: linear-gradient(135deg, #0284c7, #0369a1); color: white;
            border: none; border-radius: 12px; padding: 12px; font-weight: 700; font-size: 15px; text-decoration: none; display: block;
        }
        .btn-enter-cr {
            background: linear-gradient(135deg, #d97706, #b45309); color: white;
            border: none; border-radius: 12px; padding: 12px; font-weight: 700; font-size: 15px; text-decoration: none; display: block;
        }
        .btn-enter-admin {
            background: linear-gradient(135deg, #dc2626, #991b1b); color: white;
            border: none; border-radius: 12px; padding: 12px; font-weight: 700; font-size: 15px; text-decoration: none; display: block;
        }

        .footer { margin-top: 40px; text-align: center; font-size: 13px; font-weight: 700; color: #ffffff; text-shadow: 0 2px 6px rgba(0,0,0,0.85); }
    </style>
</head>
<body>

<div class="main-card">

    <!-- LETTERHEAD HEADER -->
    <div class="letterhead">
        <div class="logo-wrapper">
            <?php if (!empty($logoSrc)): ?>
                <img src="<?php echo $logoSrc; ?>" class="logo-img" alt="College Logo">
            <?php endif; ?>
        </div>
        <div class="letterhead-text">
            <h1>SIR C R REDDY COLLEGE OF ENGINEERING <span class="autonomous">(Autonomous)</span></h1>
            <p>ELURU, ANDHRA PRADESH 534007</p>
            <p>(Approved by AICTE, New Delhi | Affiliated to JNTUK, Kakinada)</p>
        </div>
    </div>

    <div class="dept-title-box">
        <h2>CRR-INFORMTECH</h2>
        <h4>Department of Information Technology — Unified Academic Portal</h4>
    </div>

    <!-- 3 INTEGRATED PORTALS SELECTION GRID -->
    <div class="row g-4 w-100 justify-content-center">
        
        <!-- 1. STUDENT ACADEMIC PORTAL -->
        <div class="col-md-4">
            <div class="portal-card">
                <div>
                    <div class="portal-icon icon-student">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h4>Student Academic Portal</h4>
                    <p>Access 2nd & 3rd Year class notes, assignments, mid marks, question banks, and announcements.</p>
                </div>
                <a href="student/index.php" class="btn-enter-student">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Enter Student Portal
                </a>
            </div>
        </div>

        <!-- 2. CLASS REPRESENTATIVE (CR) PORTAL -->
        <div class="col-md-4">
            <div class="portal-card">
                <div>
                    <div class="portal-icon icon-cr">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <h4>CR Portal</h4>
                    <p>Manage section class works, assignments, mid marks, and lab manuals for assigned class.</p>
                </div>
                <a href="cr/login.php" class="btn-enter-cr">
                    <i class="fa-solid fa-lock me-1"></i> CR Login Portal
                </a>
            </div>
        </div>

        <!-- 3. DEPARTMENT ADMIN PORTAL -->
        <div class="col-md-4">
            <div class="portal-card">
                <div>
                    <div class="portal-icon icon-admin">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h4>Department Admin Portal</h4>
                    <p>Complete control to manage subjects, CR accounts, faculty, and department circulars.</p>
                </div>
                <a href="admin/login.php" class="btn-enter-admin">
                    <i class="fa-solid fa-shield-halved me-1"></i> Admin Login Portal
                </a>
            </div>
        </div>

    </div>

    <div class="footer">
        © 2026 CRR-INFORMTECH — Sir C.R. Reddy College of Engineering. All Rights Reserved.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>