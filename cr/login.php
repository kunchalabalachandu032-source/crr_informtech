<?php
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";

$error = "";

// Handle explicit logout parameter
if (isset($_GET['logout'])) {
    unset($_SESSION['cr_logged_in']);
    unset($_SESSION['cr_id']);
    unset($_SESSION['cr_name']);
    unset($_SESSION['cr_roll']);
    unset($_SESSION['cr_year']);
    unset($_SESSION['cr_section']);
    session_destroy();
    session_start();
}

// Redirect to dashboard ONLY if valid CR session exists and not logging out
if (!empty($_SESSION['cr_logged_in']) && !isset($_GET['logout'])) {
    header("Location: dashboard.php");
    exit();
}

// ------------------------------------------------------------------------
// CR LOGIN AUTHENTICATION HANDLER
// ------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = db_escape(trim($_POST['username'] ?? ($_POST['email'] ?? '')));
    $password = trim($_POST['password'] ?? '');

    if (!empty($login_input) && !empty($password)) {
        $cr_user = null;
        $esc_pwd = db_escape($password);

        // 1. Query primary `cr_accounts` table
        $q1 = mysqli_query($conn, "SELECT * FROM cr_accounts WHERE (LOWER(email) = LOWER('$login_input') OR LOWER(roll_number) = LOWER('$login_input') OR LOWER(name) = LOWER('$login_input')) AND (password='$esc_pwd' OR password=MD5('$esc_pwd'))");
        if ($q1 && mysqli_num_rows($q1) > 0) {
            $cr_user = mysqli_fetch_assoc($q1);
        }

        // 2. Query `managers` table (Role = 'cr')
        if (!$cr_user) {
            $q2 = mysqli_query($conn, "SELECT * FROM managers WHERE (LOWER(email) = LOWER('$login_input') OR LOWER(username) = LOWER('$login_input')) AND role='cr'");
            if ($q2 && mysqli_num_rows($q2) > 0) {
                while ($mgr = mysqli_fetch_assoc($q2)) {
                    if (password_verify($password, $mgr['password']) || $password === $mgr['password']) {
                        $cr_user = $mgr;
                        break;
                    }
                }
            }
        }

        // 3. Query legacy `crs` table
        if (!$cr_user) {
            $q3 = mysqli_query($conn, "SELECT * FROM crs WHERE (LOWER(email) = LOWER('$login_input') OR LOWER(roll_number) = LOWER('$login_input') OR LOWER(name) = LOWER('$login_input')) AND (password='$esc_pwd' OR password=MD5('$esc_pwd'))");
            if ($q3 && mysqli_num_rows($q3) > 0) {
                $cr_user = mysqli_fetch_assoc($q3);
            }
        }

        // SUCCESSFUL AUTHENTICATION - SET SESSION
        if ($cr_user) {
            $_SESSION['cr_logged_in'] = true;
            $_SESSION['cr_id'] = $cr_user['id'] ?? 1;
            $_SESSION['cr_name'] = $cr_user['name'] ?? $cr_user['username'] ?? 'Class Representative';
            $_SESSION['cr_roll'] = $cr_user['roll_number'] ?? $cr_user['username'] ?? '';
            $_SESSION['cr_year'] = $cr_user['year'] ?? '';
            $_SESSION['cr_section'] = $cr_user['section'] ?? '';

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid Credentials! Only Admin created CR accounts are allowed to log in.";
        }
    } else {
        $error = "Please enter both Username and Password!";
    }
}

// Base64 Logo Encoding
$logoSrc = "";
$logoFile = BASE_DIR . '/admin/logo.png';
if (file_exists($logoFile)) {
    $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
}

// Base64 College Photo Encoding
$bgSrc = "";
$bgFile = BASE_DIR . '/admin/college_bg.jpg';
if (file_exists($bgFile)) {
    $bgSrc = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgFile));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRR-INFORMTECH | Class Representative Login Portal</title>
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
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            <?php if (!empty($bgSrc)): ?>
            background-image: url('<?php echo $bgSrc; ?>');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
            <?php endif; ?>
            width: 100vw;
            height: 100vh;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            overflow-x: hidden;
        }

        .login-container { width: 100%; height: 100%; max-width: 100%; }
        .login-card {
            background: transparent;
            border-radius: 0;
            width: 100%;
            height: 100%;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .login-card form { width: 100%; max-width: 420px; }

        /* Letterhead style header */
        .letterhead {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 18px;
            max-width: 720px;
            width: 100%;
        }
        .logo-wrapper {
            width: 180px; height: 180px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-img {
            max-width: 100%; max-height: 100%; object-fit: contain;
            animation: logoGlow 2s ease-in-out infinite;
        }
        @keyframes logoGlow {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(255,255,255,0.85)) drop-shadow(0 0 6px rgba(2,132,199,0.7)); }
            50% { filter: drop-shadow(0 0 28px rgba(255,255,255,1)) drop-shadow(0 0 16px rgba(2,132,199,1)); }
        }

        /* Glowing text style */
        .glow-text {
            animation: textGlow 2s ease-in-out infinite;
        }
        @keyframes textGlow {
            0%, 100% { text-shadow: 0 0 6px rgba(255,255,255,0.6), 0 0 3px rgba(2,132,199,0.6), 0 2px 6px rgba(0,0,0,0.7); }
            50% { text-shadow: 0 0 16px rgba(255,255,255,1), 0 0 10px rgba(2,132,199,1), 0 2px 6px rgba(0,0,0,0.7); }
        }

        .letterhead-text { text-align: left; }
        .letterhead-text h1 {
            font-size: 24px; font-weight: 800; color: #ffffff;
            margin-bottom: 4px; line-height: 1.2;
        }
        .letterhead-text h1 .autonomous { color: #ff2d2d; }
        .letterhead-text p {
            font-size: 14px; font-weight: 700; color: #ffffff;
            margin-bottom: 2px;
        }

        .logo-area h2 { font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 2px; text-align: center; }
        .logo-area h5 { font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 6px; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; }
        .logo-area p { font-size: 13px; font-weight: 700; color: #ffffff; margin-bottom: 25px; text-align: center; }

        .form-label { font-size: 14px; font-weight: 700; color: #ffffff; }
        .form-control { border-radius: 10px; padding: 12px; font-size: 14px; border: 1px solid #cbd5e1; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2); border-color: #0284c7; }
        .form-check-label { font-size: 14px; font-weight: 700; color: #ffffff; cursor: pointer; }
        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            border: none; border-radius: 10px; padding: 12px; font-weight: 700; font-size: 15px; transition: all 0.3s ease;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #0369a1, #075985); transform: translateY(-1px); }
        .footer { margin-top: 25px; text-align: center; font-size: 13px; font-weight: 700; color: #ffffff; }
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
                <h1 class="glow-text">SIR C R REDDY COLLEGE OF ENGINEERING <span class="autonomous">(Autonomous)</span></h1>
                <p class="glow-text">ELURU, ANDHRA PRADESH 534007</p>
                <p class="glow-text">(Approved by AICTE, New Delhi | Affiliated to JNTUK, Kakinada)</p>
            </div>
        </div>

        <div class="logo-area">
            <h2 class="glow-text">CRR-INFORMTECH</h2>
            <h5 class="glow-text">Department of Information Technology</h5>
            <p class="glow-text">Class Representative Login Portal</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger py-2 text-center" style="font-size: 13px; width: 100%; max-width: 420px;">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label glow-text">Username / Email / Roll No</label>
                <input type="text" name="username" class="form-control" placeholder="Enter Admin Created Username or Email" required autocomplete="off">
            </div>

            <div class="mb-3">
                <label class="form-label glow-text">Password</label>
                <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter Password" required>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="showPasswordCheckbox">
                <label class="form-check-label glow-text" for="showPasswordCheckbox">
                    Show Password
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Login
            </button>
        </form>

        <div class="footer glow-text">
            © 2026 CRR-INFORMTECH. All Rights Reserved.
        </div>

    </div>
</div>

<script>
    document.getElementById('showPasswordCheckbox').addEventListener('change', function() {
        const passwordInput = document.getElementById('passwordInput');
        passwordInput.type = this.checked ? 'text' : 'password';
    });
</script>

</body>
</html>