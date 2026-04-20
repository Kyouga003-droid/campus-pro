<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { 
    header("Location: login.php"); 
    exit(); 
}

$current_page = basename($_SERVER['PHP_SELF']);
$student_id = $_SESSION['user_id'];
$student_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Student';

$page_titles = [
    'student_portal.php' => 'Hub',
    'student_grades.php' => 'Academics',
    'student_billing.php' => 'Finances',
    'student_library.php' => 'Library',
    'student_schedule.php' => 'Schedule',
    'student_clearance.php' => 'Clearance'
];
$page_title = $page_titles[$current_page] ?? 'Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Pro | <?= htmlspecialchars($page_title) ?></title>
    
    <script>
        try {
            let savedTheme = localStorage.getItem('campus_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        } catch(e) {}
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-secondary: #3b82f6;
            --body-font: 'Inter', sans-serif;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        [data-theme="light"] {
            --main-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --main-bg: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --border-light: #1e293b;
            --text-dark: #f8fafc;
            --text-light: #94a3b8;
            --nav-bg: rgba(30, 41, 59, 0.85);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px -1px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--body-font);
            background-color: var(--main-bg);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.3s, color 0.3s;
            overflow-x: hidden;
        }

        .student-nav {
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0 40px;
            height: 75px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 800;
            font-size: 1.3rem;
            letter-spacing: -0.5px;
        }

        .nav-brand i {
            color: var(--brand-secondary);
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-link {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link i {
            font-size: 1.1rem;
            opacity: 0.7;
            transition: 0.2s;
        }

        .nav-link:hover {
            color: var(--text-dark);
            background: var(--border-light);
        }

        .nav-link.active {
            background: var(--brand-secondary);
            color: #fff;
        }

        .nav-link.active i {
            opacity: 1;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 1.1rem;
            outline: none;
        }

        .action-btn:hover {
            color: var(--text-dark);
            border-color: var(--text-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--card-bg);
            padding: 6px 20px 6px 6px;
            border-radius: 30px;
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            text-decoration: none;
            transition: 0.2s;
            margin-left: 10px;
        }

        .student-profile:hover {
            border-color: var(--brand-secondary);
            box-shadow: var(--shadow-sm);
        }

        .sp-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--brand-secondary), #8b5cf6);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .sp-info {
            display: flex;
            flex-direction: column;
        }

        .sp-name {
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .sp-id {
            font-size: 0.7rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .content-area {
            padding: 40px;
            max-width: 1500px;
            margin: 0 auto;
            width: 100%;
            flex: 1;
            animation: fadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1100px) {
            .nav-links { display: none; }
            .student-nav { padding: 0 20px; }
            .content-area { padding: 20px; }
        }
    </style>
</head>
<body>

<nav class="student-nav">
    <a href="student_portal.php" class="nav-brand">
        <i class="fas fa-layer-group"></i> CampusPro
    </a>
    
    <div class="nav-links">
        <a href="student_portal.php" class="nav-link <?= ($current_page == 'student_portal.php') ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Hub
        </a>
        <a href="student_schedule.php" class="nav-link <?= ($current_page == 'student_schedule.php') ? 'active' : '' ?>">
            <i class="far fa-calendar-alt"></i> Schedule
        </a>
        <a href="student_grades.php" class="nav-link <?= ($current_page == 'student_grades.php') ? 'active' : '' ?>">
            <i class="fas fa-award"></i> Academics
        </a>
        <a href="student_billing.php" class="nav-link <?= ($current_page == 'student_billing.php') ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i> Finances
        </a>
        <a href="student_clearance.php" class="nav-link <?= ($current_page == 'student_clearance.php') ? 'active' : '' ?>">
            <i class="fas fa-check-circle"></i> Clearance
        </a>
    </div>
    
    <div class="nav-actions">
        <button class="action-btn" title="Search"><i class="fas fa-search"></i></button>
        
        <button class="action-btn" title="Notifications" style="position:relative;">
            <i class="far fa-bell"></i>
            <span style="position:absolute; top:-2px; right:-2px; width:12px; height:12px; background:var(--danger); border-radius:50%; border:2px solid var(--card-bg);"></span>
        </button>
        
        <button class="action-btn" onclick="toggleStudentTheme()" title="Toggle Theme">
            <i id="stuThemeIcon" class="fas fa-moon"></i>
        </button>
        
        <div style="position: relative;">
            <div class="student-profile" onclick="document.getElementById('spDropdown').classList.toggle('show')" style="cursor:pointer;">
                <div class="sp-avatar">
                    <?= substr(explode(' ', $student_name)[0], 0, 1) ?><?= isset(explode(' ', $student_name)[1]) ? substr(explode(' ', $student_name)[1], 0, 1) : '' ?>
                </div>
                <div class="sp-info">
                    <span class="sp-name"><?= htmlspecialchars($student_name) ?></span>
                    <span class="sp-id"><?= htmlspecialchars($student_id) ?></span>
                </div>
            </div>
            
            <div id="spDropdown" style="display:none; position:absolute; top:60px; right:0; background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px; box-shadow:var(--shadow-md); width:200px; flex-direction:column; overflow:hidden; z-index:1000;">
                <a href="student_profile.php" style="padding:15px 20px; color:var(--text-dark); text-decoration:none; font-size:0.9rem; font-weight:500; border-bottom:1px solid var(--border-light); display:flex; align-items:center; gap:10px;"><i class="fas fa-user-cog"></i> Profile Settings</a>
                <a href="logout.php" style="padding:15px 20px; color:var(--danger); text-decoration:none; font-size:0.9rem; font-weight:500; display:flex; align-items:center; gap:10px;"><i class="fas fa-sign-out-alt"></i> Secure Logout</a>
            </div>
        </div>
    </div>
</nav>

<main class="content-area">

<script>
function toggleStudentTheme() {
    const html = document.documentElement;
    const target = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', target);
    localStorage.setItem('campus_theme', target);
    const icon = document.getElementById('stuThemeIcon');
    if (icon) icon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('campus_theme') || 'light';
    const icon = document.getElementById('stuThemeIcon');
    if (icon) icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});

document.addEventListener('click', (e) => {
    const drop = document.getElementById('spDropdown');
    const prof = document.querySelector('.student-profile');
    if(drop && drop.style.display === 'flex' && !drop.contains(e.target) && !prof.contains(e.target)) {
        drop.style.display = 'none';
    }
});

const ogToggle = document.querySelector('.student-profile');
if(ogToggle) {
    ogToggle.onclick = (e) => {
        e.preventDefault();
        const d = document.getElementById('spDropdown');
        d.style.display = d.style.display === 'none' ? 'flex' : 'none';
    };
}
</script>