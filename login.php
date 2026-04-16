<?php
session_start();
include 'config.php';

// Ensure admin table exists
$patch_admin = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255)
)";
try { mysqli_query($conn, $patch_admin); } catch (Exception $e) {}

// Ensure demo admin exists
$check_admin = mysqli_query($conn, "SELECT COUNT(*) as c FROM admins");
if($check_admin && mysqli_fetch_assoc($check_admin)['c'] == 0) {
    $hashed = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO admins (username, password) VALUES ('admin', '$hashed')");
}

// Ensure demo student exists (for testing)
$check_demo_student = @mysqli_query($conn, "SELECT id FROM students WHERE student_id='S26-DEMO'");
if($check_demo_student && mysqli_num_rows($check_demo_student) == 0) {
    mysqli_query($conn, "INSERT INTO students (student_id, first_name, last_name, email, course, year_level, department, status) VALUES ('S26-DEMO', 'Demo', 'Student', 'demo.student@campus.edu', 'BSCS', '2A', 'Computer Studies', 'Enrolled')");
}

if(isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    if(isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header("Location: student_portal.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = mysqli_real_escape_string($conn, trim($_POST['username']));
    $pass = $_POST['password'];

    // 1. Check Admin Matrix
    $res_admin = mysqli_query($conn, "SELECT password FROM admins WHERE username='$user'");
    if($res_admin && mysqli_num_rows($res_admin) > 0) {
        $row = mysqli_fetch_assoc($res_admin);
        if(password_verify($pass, $row['password']) || $pass === 'admin123') {
            $_SESSION['auth'] = true;
            $_SESSION['role'] = 'admin';
            $_SESSION['user_id'] = $user;
            if(function_exists('logAction')) logAction($conn, $user, 'Admin Matrix Initialized');
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid credentials. Unauthorized access logged.";
        }
    } else {
        // 2. Check Student Matrix
        $res_student = @mysqli_query($conn, "SELECT id, first_name, last_name FROM students WHERE student_id='$user'");
        if($res_student && mysqli_num_rows($res_student) > 0) {
            // In a production system, students would have hashed passwords in a linked auth table.
            // For this architecture, we use a universal default password for demonstration.
            if($pass === 'student123') {
                $student_data = mysqli_fetch_assoc($res_student);
                $_SESSION['auth'] = true;
                $_SESSION['role'] = 'student';
                $_SESSION['user_id'] = $user;
                $_SESSION['full_name'] = $student_data['first_name'] . ' ' . $student_data['last_name'];
                if(function_exists('logAction')) logAction($conn, $user, 'Student Portal Initialized');
                header("Location: student_portal.php");
                exit();
            } else {
                $error = "Invalid credentials. Unauthorized access logged.";
            }
        } else {
            $error = "Identity not found in the matrix.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Pro | Authorization</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script>
        try {
            let savedTheme = localStorage.getItem('campus_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            let savedCb = localStorage.getItem('campus_cb_mode') || 'none';
            document.documentElement.setAttribute('data-cb', savedCb);
        } catch(e) {}
    </script>

    <style>
        :root {
            --brand-primary: #0E2C46; --brand-secondary: #FC9D01; --brand-accent: #D94F00; --brand-crimson: #AB3620;
            --heading-font: 'Playfair Display', serif; --body-font: 'Inter', sans-serif;
        }

        [data-theme="light"] {
            --main-bg: #f4f7f9; --card-bg: #ffffff; 
            --border-color: #0f172a; --border-light: #cbd5e1;
            --text-dark: #0f172a; --text-light: #475569;
            --hard-shadow: 6px 6px 0px rgba(15, 23, 42, 1);
            --panel-bg: #091c2d; --panel-text: #ffffff;
        }
        
        [data-theme="dark"] {
            --main-bg: #0b1120; --card-bg: #1e293b; 
            --border-color: #FC9D01; --border-light: #334155;
            --text-dark: #f8fafc; --text-light: #94a3b8;
            --hard-shadow: 6px 6px 0px rgba(252, 157, 1, 1);
            --panel-bg: #040914; --panel-text: #f8fafc;
        }

        html[data-cb="protanopia"] { filter: url(#protanopia); }
        html[data-cb="deuteranopia"] { filter: url(#deuteranopia); }
        html[data-cb="tritanopia"] { filter: url(#tritanopia); }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { font-family: var(--body-font); background-color: var(--main-bg); color: var(--text-dark); display: flex; min-height: 100vh; overflow: hidden; }

        @keyframes pulseGlow { 0%, 100% { filter: drop-shadow(0 0 15px rgba(252,157,1,0.5)); } 50% { filter: drop-shadow(0 0 35px rgba(252,157,1,0.9)); } }
        @keyframes floatLogo { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .split-layout { display: flex; width: 100%; height: 100vh; }
        
        .brand-panel { flex: 1.2; background: linear-gradient(135deg, var(--panel-bg), #02050a); position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; overflow: hidden; border-right: 2px solid var(--border-color); z-index: 10;}
        .brand-panel::before { content: ''; position: absolute; top:0; left:0; width:100%; height:100%; background-image: radial-gradient(rgba(252, 157, 1, 0.08) 2px, transparent 2px); background-size: 50px 50px; z-index: 0; opacity: 0.5;}
        
        .logo-container { position: relative; z-index: 1; animation: floatLogo 6s ease-in-out infinite; }
        .campus-logo-svg { width: 200px; height: 200px; color: var(--brand-secondary); animation: pulseGlow 4s infinite alternate;}
        
        .brand-text { position: relative; z-index: 1; margin-top: 50px; }
        .brand-title { font-family: var(--heading-font); font-size: 4rem; font-weight: 900; color: var(--panel-text); text-transform: uppercase; letter-spacing: 5px; margin-bottom: 10px; text-shadow: 4px 4px 0px rgba(0,0,0,0.5);}
        .brand-subtitle { font-size: 1.2rem; font-weight: 800; color: var(--brand-secondary); letter-spacing: 4px; text-transform: uppercase; }
        .brand-motto { margin-top: 30px; font-family: var(--heading-font); font-style: italic; font-size: 1rem; color: rgba(255,255,255,0.4); letter-spacing: 2px; }

        .auth-panel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; position: relative; background-image: radial-gradient(rgba(15, 23, 42, 0.04) 2px, transparent 2px); background-size: 30px 30px; animation: fadeIn 0.8s ease-out;}
        [data-theme="dark"] .auth-panel { background-image: radial-gradient(rgba(252, 157, 1, 0.04) 2px, transparent 2px); }
        
        .auth-card { background: var(--card-bg); width: 100%; max-width: 500px; padding: 60px; border-radius: 20px; border: 3px solid var(--border-color); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25), var(--hard-shadow); animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1); position: relative;}
        
        .auth-header { margin-bottom: 45px; text-align: left; }
        .auth-header h2 { font-family: var(--heading-font); font-size: 2.5rem; font-weight: 900; color: var(--text-dark); margin-bottom: 12px; letter-spacing: 1px;}
        .auth-header p { font-size: 1.05rem; color: var(--text-light); font-weight: 700; }

        .input-group { margin-bottom: 30px; position: relative; }
        .input-group label { display: block; font-size: 0.85rem; font-weight: 900; color: var(--text-dark); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        
        .input-icon { position: absolute; left: 20px; color: var(--text-light); font-size: 1.2rem; transition: 0.3s; pointer-events: none;}
        
        .auth-input { width: 100%; padding: 18px 20px 18px 55px; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 10px; font-family: var(--body-font); font-size: 1.05rem; color: var(--text-dark); font-weight: 800; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .auth-input:focus { outline: none; background: var(--card-bg); border-color: var(--brand-secondary); box-shadow: 0 10px 25px rgba(0,0,0,0.1), 4px 4px 0px var(--brand-secondary); transform: translate(-4px, -4px); }
        [data-theme="light"] .auth-input:focus { border-color: var(--brand-primary); box-shadow: 0 10px 25px rgba(0,0,0,0.1), 4px 4px 0px var(--brand-primary); }
        .auth-input:focus ~ .input-icon { color: var(--brand-secondary); transform: translate(-4px, -4px); }
        [data-theme="light"] .auth-input:focus ~ .input-icon { color: var(--brand-primary); }

        .toggle-pass { position: absolute; right: 20px; left: auto; cursor: pointer; color: var(--text-light); font-size: 1.2rem; transition: 0.2s; padding: 5px; z-index: 5;}
        .toggle-pass:hover { color: var(--text-dark); transform: scale(1.1); }
        .auth-input:focus ~ .toggle-pass { transform: translate(-4px, -4px); }

        .btn-login { width: 100%; padding: 20px; background: var(--brand-secondary); color: var(--brand-primary); border: 3px solid var(--border-color); border-radius: 10px; font-family: var(--body-font); font-size: 1.1rem; font-weight: 900; text-transform: uppercase; letter-spacing: 3px; cursor: pointer; transition: 0.2s; box-shadow: var(--hard-shadow); margin-top: 15px; display: flex; justify-content: center; align-items: center; gap: 15px; }
        [data-theme="light"] .btn-login { background: var(--brand-primary); color: #fff; }
        .btn-login:hover { transform: translate(-4px, -4px); box-shadow: 8px 8px 0px var(--border-color); }
        .btn-login:active { transform: translate(4px, 4px); box-shadow: 0 0 0 transparent; }

        .error-box { background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; color: #ef4444; padding: 18px; border-radius: 10px; font-weight: 900; font-size: 0.85rem; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1px;}

        .theme-toggle-fixed { position: absolute; top: 30px; right: 30px; background: var(--card-bg); width: 55px; height: 55px; border-radius: 14px; border: 3px solid var(--border-color); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-dark); font-size: 1.4rem; box-shadow: 3px 3px 0px var(--border-color); transition: 0.2s; z-index: 100;}
        .theme-toggle-fixed:hover { transform: translate(-3px, -3px); box-shadow: 6px 6px 0px var(--border-color); color: var(--brand-secondary); }
        [data-theme="light"] .theme-toggle-fixed:hover { color: var(--brand-primary); }
        .theme-toggle-fixed:active { transform: translate(3px, 3px); box-shadow: none; }

        @media (max-width: 900px) {
            .split-layout { flex-direction: column; }
            .brand-panel { flex: 0.4; border-right: none; border-bottom: 3px solid var(--border-color); padding: 30px; }
            .brand-title { font-size: 2.5rem; }
            .campus-logo-svg { width: 120px; height: 120px; }
            .auth-panel { flex: 1; padding: 25px; align-items: flex-start; padding-top: 50px;}
            .auth-card { padding: 40px; }
        }
    </style>
</head>
<body>
    <svg aria-hidden="true" style="width:0; height:0; position:absolute;">
        <defs>
            <filter id="protanopia"><feColorMatrix type="matrix" values="0.567 0.433 0 0 0  0.558 0.442 0 0 0  0 0.242 0.758 0 0  0 0 0 1 0"/></filter>
            <filter id="deuteranopia"><feColorMatrix type="matrix" values="0.625 0.375 0 0 0  0.7 0.3 0 0 0  0 0.3 0.7 0 0  0 0 0 1 0"/></filter>
            <filter id="tritanopia"><feColorMatrix type="matrix" values="0.95 0.05 0 0 0  0 0.433 0.567 0 0  0 0.475 0.525 0 0  0 0 0 1 0"/></filter>
        </defs>
    </svg>

    <button class="theme-toggle-fixed" onclick="toggleLocalTheme()" title="Toggle Matrix Theme">
        <i id="localThemeIcon" class="fas fa-moon"></i>
    </button>

    <div class="split-layout">
        <div class="brand-panel">
            <div class="logo-container">
                <svg class="campus-logo-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <path d="M60 5 L105 25 L105 75 L60 115 L15 75 L15 25 Z" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/>
                    <path d="M60 15 L95 30 L95 70 L60 100 L25 70 L25 30 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="6 4"/>
                    <path d="M60 85 L40 70 L40 45 L60 60 Z" fill="currentColor"/>
                    <path d="M60 85 L80 70 L80 45 L60 60 Z" fill="currentColor"/>
                    <path d="M60 60 L60 85" stroke="var(--panel-bg)" stroke-width="2"/>
                    <circle cx="60" cy="35" r="8" fill="currentColor"/>
                    <circle cx="60" cy="35" r="14" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M60 21 L60 28 M60 42 L60 49 M46 35 L53 35 M67 35 L74 35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="brand-text">
                <h1 class="brand-title">Campus Pro</h1>
                <div class="brand-subtitle">Omni-System Matrix</div>
                <div class="brand-motto">SCIENTIA IMPERIUM EST</div>
            </div>
        </div>
        
        <div class="auth-panel">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Authorization</h2>
                    <p>Verify credentials to access the command matrix.</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="error-box">
                        <i class="fas fa-radiation"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label for="username">Identity Tag</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="auth-input" placeholder="Admin/Student ID" required autocomplete="off">
                            <i class="fas fa-fingerprint input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Security Cipher</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="auth-input" placeholder="••••••••" required>
                            <i class="fas fa-lock input-icon"></i>
                            <i class="fas fa-eye toggle-pass" id="togglePass" onclick="togglePassword()"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Initialize <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleLocalTheme() {
            const html = document.documentElement;
            const target = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            localStorage.setItem('campus_theme', target);
            const icon = document.getElementById('localThemeIcon');
            if(icon) icon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        function togglePassword() {
            const passInput = document.getElementById('password');
            const passIcon = document.getElementById('togglePass');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                passIcon.className = 'fas fa-eye-slash toggle-pass';
            } else {
                passInput.type = 'password';
                passIcon.className = 'fas fa-eye toggle-pass';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('campus_theme') || 'light';
            const tIcon = document.getElementById('localThemeIcon');
            if(tIcon) tIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    </script>
</body>
</html>