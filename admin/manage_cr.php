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

// Dynamic Column Auto-Fix for `cr_accounts` & `crs` tables
ensure_columns_exist('cr_accounts', [
    'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'roll_number' => "VARCHAR(50) NOT NULL DEFAULT ''",
    'year' => "VARCHAR(10) NOT NULL DEFAULT '2'",
    'section' => "VARCHAR(10) NOT NULL DEFAULT 'IT2A'",
    'email' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'phone' => "VARCHAR(20) DEFAULT NULL",
    'password' => "VARCHAR(255) NOT NULL DEFAULT ''"
]);

ensure_columns_exist('crs', [
    'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'roll_number' => "VARCHAR(50) NOT NULL DEFAULT ''",
    'year' => "VARCHAR(10) NOT NULL DEFAULT '2'",
    'section' => "VARCHAR(10) NOT NULL DEFAULT 'IT2A'",
    'email' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'phone' => "VARCHAR(20) DEFAULT NULL",
    'password' => "VARCHAR(255) NOT NULL DEFAULT ''"
]);

// ------------------------------------------------------------------------
// ACTION HANDLERS (ADD CR ACCOUNT, DELETE CR ACCOUNT)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD NEW CR ACCOUNT (DYNAMIC SAVING TO DB)
    if ($action === 'add') {
        $name = db_escape($_POST['name'] ?? '');
        $roll_number = db_escape($_POST['roll_number'] ?? '');
        $year = db_escape($_POST['year'] ?? '2');
        $section = db_escape($_POST['section'] ?? 'IT2A');
        $email = db_escape($_POST['email'] ?? '');
        $phone = db_escape($_POST['phone'] ?? '');
        $password_raw = trim($_POST['password'] ?? '');

        if (!empty($name) && !empty($roll_number) && !empty($email) && !empty($password_raw)) {
            // Check if CR email or roll number already exists
            $check_res = mysqli_query($conn, "SELECT id FROM cr_accounts WHERE email='$email' OR roll_number='$roll_number'");
            if ($check_res && mysqli_num_rows($check_res) > 0) {
                $error_msg = "A CR account with this Email or Roll Number already exists!";
            } else {
                // Insert into primary table `cr_accounts`
                $query = "INSERT INTO cr_accounts (name, roll_number, year, section, email, phone, password) 
                          VALUES ('$name', '$roll_number', '$year', '$section', '$email', '$phone', '$password_raw')";
                
                if (mysqli_query($conn, $query)) {
                    // Sync to legacy tables safely
                    @mysqli_query($conn, "INSERT INTO crs (name, roll_number, year, section, email, phone, password) VALUES ('$name', '$roll_number', '$year', '$section', '$email', '$phone', '$password_raw')");
                    @mysqli_query($conn, "INSERT INTO cr_users (username, email, password, year, section) VALUES ('$name', '$email', '$password_raw', '$year', '$section')");
                    
                    $hashed_pwd = password_hash($password_raw, PASSWORD_DEFAULT);
                    @mysqli_query($conn, "INSERT INTO managers (username, email, password, role) VALUES ('$roll_number', '$email', '$hashed_pwd', 'cr')");

                    $success_msg = "CR Account for <b>$name</b> ($roll_number) created successfully!";
                } else {
                    $error_msg = "Database Error adding CR: " . mysqli_error($conn);
                }
            }
        } else {
            $error_msg = "Please fill in all required fields (Name, Roll No, Email, Password)!";
        }
    }

    // 2. DELETE CR ACCOUNT
    elseif ($action === 'delete') {
        $id = intval($_POST['cr_id'] ?? 0);
        if ($id > 0) {
            $get_cr = mysqli_query($conn, "SELECT email, roll_number FROM cr_accounts WHERE id=$id");
            if ($cr_data = mysqli_fetch_assoc($get_cr)) {
                $em = db_escape($cr_data['email']);
                $rn = db_escape($cr_data['roll_number']);
                @mysqli_query($conn, "DELETE FROM crs WHERE email='$em' OR roll_number='$rn'");
                @mysqli_query($conn, "DELETE FROM cr_users WHERE email='$em'");
                @mysqli_query($conn, "DELETE FROM managers WHERE email='$em' OR username='$rn'");
            }

            mysqli_query($conn, "DELETE FROM cr_accounts WHERE id=$id");
            $success_msg = "CR account deleted successfully!";
        }
    }
}

// Fetch all CR accounts from primary table `cr_accounts`
$cr_query = "SELECT * FROM cr_accounts ORDER BY id DESC";
$cr_result = mysqli_query($conn, $cr_query);

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
<title>Manage CR Accounts | CRR-INFORMTECH</title>

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
    background: #ffffff; padding: 30px 35px; border-radius: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 35px; flex-wrap: wrap; gap: 20px; position: relative; overflow: hidden;
}

.page-header-card::before {
    content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%;
    background: linear-gradient(180deg, #0d6efd, #0369a1);
}

.page-header-card h2 { font-weight: 800; color: #0f172a; margin: 0; font-size: 26px; }
.page-header-card p { font-size: 14px; color: #64748b; margin: 4px 0 0 0; }

.btn-add-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0369a1 100%);
    color: #ffffff; border: none; padding: 12px 28px; border-radius: 14px;
    font-weight: 700; font-size: 14.5px; transition: all 0.25s ease;
    box-shadow: 0 8px 20px rgba(13, 110, 253, 0.28);
    display: inline-flex; align-items: center; gap: 10px;
}

.btn-add-primary:hover {
    background: linear-gradient(135deg, #0a58ca 0%, #0284c7 100%);
    color: #ffffff; transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(13, 110, 253, 0.38);
}

.content-card {
    background: #ffffff; border-radius: 22px; padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05);
}

.table-custom-header {
    background: linear-gradient(120deg, #0f2b46 0%, #0d6efd 100%);
    color: white;
}

.table th {
    background: transparent; color: #ffffff; border: none;
    font-weight: 700; font-size: 13.5px; letter-spacing: 0.5px; text-transform: uppercase;
}

.table td { font-size: 14px; vertical-align: middle; padding: 16px 14px; }

.cr-avatar {
    width: 42px; height: 42px; border-radius: 12px;
    background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0284c7;
    font-weight: 800; font-size: 16px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.15);
}

.badge-roll {
    background: #e0f2fe; color: #0369a1; border: 1px solid rgba(3, 105, 161, 0.2);
    font-family: monospace; font-size: 13.5px; font-weight: 700; padding: 6px 12px; border-radius: 10px;
}

.badge-sec {
    background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;
    font-size: 12.5px; font-weight: 600; padding: 6px 14px; border-radius: 20px;
}

.btn-delete-action {
    background: #fff1f2; color: #e11d48; border: 1px solid rgba(225, 29, 72, 0.2);
    padding: 8px 18px; border-radius: 10px; font-weight: 600; font-size: 13px;
    transition: all 0.25s ease; display: inline-flex; align-items: center; gap: 6px;
}

.btn-delete-action:hover {
    background: #e11d48; color: #ffffff;
    box-shadow: 0 6px 16px rgba(225, 29, 72, 0.3); transform: translateY(-1px);
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
    <a href="manage_cr.php" class="active"><i class="fa-solid fa-user-gear"></i> Manage CR Accounts</a>
    <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty Directory</a>
    <a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="dashboard.php?logout=true" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Main Content Area -->
<div class="main-content">

    <div class="page-header-card">
        <div>
            <h2><i class="fa-solid fa-users-gear text-primary me-2"></i>Manage CR Accounts</h2>
            <p>Create & manage Class Representative (CR) login accounts</p>
        </div>
        <button class="btn-add-primary" data-bs-toggle="modal" data-bs-target="#addCrModal">
            <i class="fa-solid fa-user-plus"></i> Add New CR Account
        </button>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-address-book text-primary me-2"></i> Registered Class Representatives</h5>
        <div class="table-responsive rounded-4 border overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-custom-header">
                    <tr>
                        <th class="py-3 px-4">CR Name</th>
                        <th class="py-3">Roll Number</th>
                        <th class="py-3">Year / Section</th>
                        <th class="py-3">Email Address</th>
                        <th class="py-3">Phone Number</th>
                        <th class="py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cr_result && mysqli_num_rows($cr_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($cr_result)): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="cr-avatar">
                                            <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                        </div>
                                        <b class="text-dark fs-6"><?php echo htmlspecialchars($row['name']); ?></b>
                                    </div>
                                </td>
                                <td><span class="badge-roll"><?php echo htmlspecialchars($row['roll_number']); ?></span></td>
                                <td><span class="badge-sec">Year <?php echo htmlspecialchars($row['year']); ?> - <?php echo htmlspecialchars($row['section']); ?></span></td>
                                <td><i class="fa-solid fa-envelope me-2 text-primary opacity-75"></i> <?php echo htmlspecialchars($row['email']); ?></td>
                                <td><i class="fa-solid fa-phone me-2 text-success opacity-75"></i> <?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : 'N/A'; ?></td>
                                <td class="text-center">
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this CR account?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cr_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-delete-action">
                                            <i class="fa-solid fa-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-user-gear fs-1 text-primary opacity-50 mb-3 d-block"></i>
                                <h5>No CR Accounts Found</h5>
                                <p class="mb-0">Click <b>Add New CR Account</b> above to register a Class Representative.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ADD CR ACCOUNT MODAL -->
<div class="modal fade" id="addCrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f2b46, #0d6efd); border-top-left-radius: 24px; border-top-right-radius: 24px; padding: 20px 28px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Add New CR Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">CR Full Name *</label>
                        <input type="text" name="name" class="form-control form-control-lg fs-6" placeholder="e.g. K. Rajesh" required style="border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Roll Number / CR ID *</label>
                        <input type="text" name="roll_number" class="form-control form-control-lg fs-6" placeholder="e.g. 21B91A1201" required style="border-radius: 12px;">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-dark">Year *</label>
                            <select name="year" class="form-select form-select-lg fs-6" style="border-radius: 12px;">
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-dark">Section *</label>
                            <select name="section" class="form-select form-select-lg fs-6" style="border-radius: 12px;">
                                <option value="IT2A">IT2A</option>
                                <option value="IT2B">IT2B</option>
                                <option value="IT2C">IT2C</option>
                                <option value="IT3A">IT3A</option>
                                <option value="IT3B">IT3B</option>
                                <option value="IT3C">IT3C</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Email Address *</label>
                        <input type="email" name="email" class="form-control form-control-lg fs-6" placeholder="e.g. crit2a@crr.ac.in" required style="border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-lg fs-6" placeholder="e.g. 9876543210" style="border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Password *</label>
                        <input type="password" name="password" class="form-control form-control-lg fs-6" placeholder="Enter Login Password" required style="border-radius: 12px;">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; padding: 18px 28px;">
                    <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius: 12px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold" style="border-radius: 12px; background: linear-gradient(135deg, #0d6efd, #0369a1); border: none;">
                        <i class="fa-solid fa-user-plus me-1"></i> Save CR Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>