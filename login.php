<?php
session_start();
include 'config.php';

$patch_admin = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255)
)";
try { mysqli_query($conn, $patch_admin); } catch (Exception $e) {}

$check_admin = mysqli_query($conn, "SELECT COUNT(*) as c FROM admins");
if($check_admin && mysqli_fetch_assoc($check_admin)['c'] == 0) {
    $hashed = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO admins (username, password) VALUES ('admin', '$hashed')");
}

$check_demo_student = @mysqli_query($conn, "SELECT id FROM students WHERE student_id='S26-0001'");
if($check_demo_student && mysqli_num_rows($check_demo_student) == 0) {
    mysqli_query($conn, "INSERT INTO students (student_id, first_name, last_name, email, course, year_level, department, status) VALUES ('S26-0001', 'Alex', 'Mercer', 'alex.m@campus.edu', 'BSCS', '2A', 'Computer Studies', 'Enrolled')");
}

if(isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student_portal.php");
        exit();
    } else {
        header("Location: index.php");
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $portal = $_POST['portal_type'] ?? 'student';

    if ($portal === 'student') {
        if ($username === 'student001' && $password === 'student123') {
            $_SESSION['auth'] = true;
            $_SESSION['role'] = 'student';
            $_SESSION['user_id'] = 'S26-0001';
            $_SESSION['full_name'] = 'Alex Mercer';
            header("Location: student_portal.php");
            exit();
        } else {
            $error = 'Invalid Student ID or Passphrase.';
        }
    } 
    elseif ($portal === 'admin') {
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['auth'] = true;
            $_SESSION['role'] = 'admin';
            $_SESSION['user_id'] = 'SYS-01';
            $_SESSION['full_name'] = 'System Administrator';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid Executive Credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Matrix | Authenticate</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --theme-primary: #4f46e5;
            --theme-primary-hover: #4338ca;
            --theme-glow: rgba(79, 70, 229, 0.4);
            --bg-base: #0f172a;
            --bg-surface: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-subtle: rgba(255, 255, 255, 0.1);
            --error-color: #ef4444;
            --success-color: #10b981;
        }

        body.admin-active {
            --theme-primary: #10b981;
            --theme-primary-hover: #059669;
            --theme-glow: rgba(16, 185, 129, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        ::selection {
            background: var(--theme-primary);
            color: #fff;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-base);
            color: var(--text-main);
            overflow: hidden;
            position: relative;
        }

        .ambient-mesh {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
            background: 
                radial-gradient(circle at 15% 50%, var(--theme-glow), transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(56, 189, 248, 0.15), transparent 50%);
            transition: background 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .ambient-mesh::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
            opacity: 0.4;
        }

        .auth-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 48px;
            background: var(--bg-surface);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border-subtle);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.05) inset;
            transform-style: preserve-3d;
            perspective: 1000px;
            animation: formEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes formEntrance {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .header-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-mark {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--theme-primary), var(--theme-primary-hover));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            box-shadow: 0 10px 25px -5px var(--theme-glow);
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        body.admin-active .logo-mark {
            transform: rotateY(180deg) scale(1.05);
            border-radius: 50%;
        }

        .icon-inner {
            transition: 0.6s;
        }

        body.admin-active .icon-inner {
            transform: rotateY(-180deg);
        }

        .title {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(1.8rem, 5vw, 2.2rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .segmented-control {
            display: flex;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 6px;
            margin-bottom: 32px;
            position: relative;
            border: 1px solid var(--border-subtle);
        }

        .segment-slider {
            position: absolute;
            top: 6px;
            left: 6px;
            width: calc(50% - 6px);
            height: calc(100% - 12px);
            background: var(--theme-primary);
            border-radius: 12px;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), background 0.4s;
            box-shadow: 0 4px 12px var(--theme-glow);
            z-index: 1;
        }

        .segment-btn {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            z-index: 2;
            transition: color 0.3s;
            user-select: none;
        }

        .segment-btn.active {
            color: #fff;
        }

        .floating-input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .floating-input-group input {
            width: 100%;
            background: rgba(0, 0, 0, 0.2);
            border: 2px solid transparent;
            color: var(--text-main);
            padding: 20px 16px 12px;
            font-size: 1.05rem;
            border-radius: 16px;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .floating-input-group label {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--text-muted);
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-weight: 500;
        }

        .floating-input-group input:focus,
        .floating-input-group input:valid {
            border-color: var(--theme-primary);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 4px var(--theme-glow), inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .floating-input-group input:focus ~ label,
        .floating-input-group input:valid ~ label {
            top: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--theme-primary);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.2s;
            padding: 8px;
        }

        .password-toggle:hover {
            color: var(--text-main);
        }

        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            user-select: none;
        }

        .custom-checkbox input {
            display: none;
        }

        .cb-box {
            width: 20px;
            height: 20px;
            border: 2px solid var(--text-muted);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            position: relative;
        }

        .custom-checkbox input:checked ~ .cb-box {
            background: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .cb-box::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #fff;
            font-size: 0.7rem;
            opacity: 0;
            transform: scale(0.5);
            transition: 0.2s;
        }

        .custom-checkbox input:checked ~ .cb-box::after {
            opacity: 1;
            transform: scale(1);
        }

        .forgot-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            position: relative;
            transition: 0.3s;
        }

        .forgot-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: var(--theme-primary);
            transition: width 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--text-main);
        }

        .forgot-link:hover::after {
            width: 100%;
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--theme-primary), var(--theme-primary-hover));
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 20px -5px var(--theme-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px var(--theme-glow);
        }

        .submit-btn:active {
            transform: translateY(1px);
        }

        .ripple {
            position: absolute;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: rippleEffect 0.6s linear;
            pointer-events: none;
        }

        @keyframes rippleEffect {
            to { transform: scale(4); opacity: 0; }
        }

        .error-card {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 16px;
            border-radius: 12px;
            color: #fca5a5;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            animation: errorShake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes errorShake {
            10%, 90% { transform: translate3d(-2px, 0, 0); }
            20%, 80% { transform: translate3d(4px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
            40%, 60% { transform: translate3d(6px, 0, 0); }
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .submit-btn.loading .btn-text,
        .submit-btn.loading .btn-icon {
            display: none;
        }

        .submit-btn.loading .spinner {
            display: block;
        }

        @media (max-width: 480px) {
            .auth-container { padding: 32px 24px; border-radius: 24px; }
            .logo-mark { width: 64px; height: 64px; font-size: 1.5rem; margin-bottom: 20px;}
        }
    </style>
</head>
<body>

    <div class="ambient-mesh"></div>

    <div class="auth-container" id="authCard">
        
        <div class="header-section">
            <div class="logo-mark">
                <i class="fas fa-layer-group icon-inner" id="mainIcon"></i>
            </div>
            <h1 class="title" id="mainTitle">Scholar Portal</h1>
            <p class="subtitle" id="mainSub">Secure access to your academic matrix.</p>
        </div>

        <div class="segmented-control">
            <div class="segment-slider" id="segmentSlider"></div>
            <div class="segment-btn active" id="btnStudent" onclick="togglePortal('student')">Student</div>
            <div class="segment-btn" id="btnAdmin" onclick="togglePortal('admin')">Executive</div>
        </div>

        <?php if($error): ?>
            <div class="error-card">
                <i class="fas fa-fingerprint"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" onsubmit="handleLoading()">
            <input type="hidden" name="portal_type" id="portalType" value="student">
            
            <div class="floating-input-group">
                <input type="text" name="username" id="inpUser" required autocomplete="off">
                <label id="lblUser">Network ID</label>
            </div>
            
            <div class="floating-input-group">
                <input type="password" name="password" id="inpPass" required autocomplete="off">
                <label>Passphrase</label>
                <i class="far fa-eye password-toggle" onclick="toggleVisibility(this, 'inpPass')"></i>
            </div>

            <div class="options-row">
                <label class="custom-checkbox">
                    <input type="checkbox" name="remember">
                    <div class="cb-box"></div>
                    Keep me connected
                </label>
                <a href="#" class="forgot-link">Recover keys?</a>
            </div>

            <button type="submit" class="submit-btn" id="btnSubmit">
                <span class="btn-text" id="btnText">Authenticate</span>
                <i class="fas fa-arrow-right btn-icon" id="btnArrow"></i>
                <div class="spinner"></div>
            </button>
        </form>

    </div>

    <script>
        function togglePortal(type) {
            const body = document.body;
            const slider = document.getElementById('segmentSlider');
            const btnS = document.getElementById('btnStudent');
            const btnA = document.getElementById('btnAdmin');
            const pInput = document.getElementById('portalType');
            const icon = document.getElementById('mainIcon');
            const title = document.getElementById('mainTitle');
            const sub = document.getElementById('mainSub');
            const lblUser = document.getElementById('lblUser');
            const btnText = document.getElementById('btnText');

            pInput.value = type;

            if (type === 'admin') {
                body.classList.add('admin-active');
                slider.style.transform = 'translateX(100%)';
                btnS.classList.remove('active');
                btnA.classList.add('active');
                
                setTimeout(() => { icon.className = 'fas fa-shield-halved icon-inner'; }, 300);
                title.innerText = 'Command Center';
                sub.innerText = 'Authorized personnel access only.';
                lblUser.innerText = 'Executive ID';
                btnText.innerText = 'Initialize Session';
            } else {
                body.classList.remove('admin-active');
                slider.style.transform = 'translateX(0)';
                btnA.classList.remove('active');
                btnS.classList.add('active');
                
                setTimeout(() => { icon.className = 'fas fa-layer-group icon-inner'; }, 300);
                title.innerText = 'Scholar Portal';
                sub.innerText = 'Secure access to your academic matrix.';
                lblUser.innerText = 'Network ID';
                btnText.innerText = 'Authenticate';
            }
        }

        function toggleVisibility(icon, targetId) {
            const input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function handleLoading() {
            const btn = document.getElementById('btnSubmit');
            btn.classList.add('loading');
        }

        document.getElementById('btnSubmit').addEventListener('click', function(e) {
            let x = e.clientX - e.target.getBoundingClientRect().left;
            let y = e.clientY - e.target.getBoundingClientRect().top;
            let ripples = document.createElement('span');
            ripples.style.left = x + 'px';
            ripples.style.top = y + 'px';
            ripples.classList.add('ripple');
            this.appendChild(ripples);
            setTimeout(() => { ripples.remove() }, 600);
        });

        document.addEventListener('mousemove', (e) => {
            const card = document.getElementById('authCard');
            const xAxis = (window.innerWidth / 2 - e.pageX) / 50;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 50;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });

        document.addEventListener('mouseleave', () => {
            const card = document.getElementById('authCard');
            card.style.transform = `rotateY(0deg) rotateX(0deg)`;
            card.style.transition = `transform 0.5s ease`;
            setTimeout(() => { card.style.transition = `none`; }, 500);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentType = document.getElementById('portalType').value;
            togglePortal(currentType);
            
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                if(input.value) input.classList.add('valid');
                input.addEventListener('input', () => {
                    if(input.value) input.classList.add('valid');
                    else input.classList.remove('valid');
                });
            });
        });
    </script>
</body>
</html>