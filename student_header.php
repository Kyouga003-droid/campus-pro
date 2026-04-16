<?php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Pro | Student Portal</title>
    
    <script>
        try {
            let savedTheme = localStorage.getItem('campus_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        } catch(e) {}
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --brand-primary: #0E2C46; --brand-secondary: #FC9D01; --brand-accent: #D94F00; --brand-crimson: #AB3620;
            --heading-font: 'Playfair Display', serif; --body-font: 'Inter', sans-serif;
        }

        [data-theme="light"] {
            --main-bg: #f4f7f9; --card-bg: #ffffff; 
            --border-color: #0f172a; --border-light: #cbd5e1;
            --text-dark: #0f172a; --text-light: #475569; --text-inverse: #ffffff;
            --hard-shadow: 4px 4px 0px rgba(15, 23, 42, 1); --soft-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
            --bg-grid: rgba(15, 23, 42, 0.05); --nav-bg: #091c2d;
        }
        
        [data-theme="dark"] {
            --main-bg: #0b1120; --card-bg: #1e293b; 
            --border-color: #FC9D01; --border-light: #334155;
            --text-dark: #f8fafc; --text-light: #94a3b8; --text-inverse: #0f172a;
            --hard-shadow: 4px 4px 0px rgba(252, 157, 1, 1); --soft-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            --bg-grid: rgba(252, 157, 1, 0.05); --nav-bg: #040914;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { font-family: var(--body-font); background-color: var(--main-bg); background-image: radial-gradient(var(--bg-grid) 2px, transparent 2px); background-size: 30px 30px; color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; transition: background-color 0.3s ease, color 0.3s ease; }
        
        .student-nav { background: var(--nav-bg); padding: 0 50px; height: 80px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); position: sticky; top: 0; z-index: 100; box-shadow: var(--soft-shadow);}
        
        .nav-brand { display: flex; align-items: center; gap: 15px; color: var(--brand-secondary); text-decoration: none; font-family: var(--heading-font); font-weight: 900; font-size: 1.5rem; letter-spacing: 1px; text-transform: uppercase;}
        .nav-brand svg { width: 35px; height: 35px; }
        
        .nav-links { display: flex; gap: 10px; align-items: center;}
        .nav-link { color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; padding: 10px 20px; border-radius: 8px; border: 2px solid transparent; transition: 0.2s;}
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);}
        .nav-link.active { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary); box-shadow: 2px 2px 0px var(--brand-primary);}
        [data-theme="light"] .nav-link.active { box-shadow: 2px 2px 0px var(--text-dark); }

        .nav-actions { display: flex; align-items: center; gap: 15px; }
        .theme-btn { background: transparent; border: 2px solid rgba(255,255,255,0.2); color: #fff; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;}
        .theme-btn:hover { border-color: var(--brand-secondary); color: var(--brand-secondary); transform: translateY(-2px);}
        
        .student-profile { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.05); padding: 6px 15px 6px 6px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); color: #fff; text-decoration: none; transition: 0.2s;}
        .student-profile:hover { border-color: var(--brand-secondary); background: rgba(252, 157, 1, 0.05);}
        .sp-avatar { width: 32px; height: 32px; background: var(--brand-secondary); color: var(--brand-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.9rem;}
        [data-theme="light"] .sp-avatar { background: var(--brand-primary); color: #fff; }
        .sp-info { display: flex; flex-direction: column;}
        .sp-name { font-size: 0.8rem; font-weight: 800; line-height: 1.2;}
        .sp-id { font-size: 0.65rem; font-family: monospace; color: var(--brand-secondary);}

        .content-area { padding: 50px; max-width: 1400px; width: 100%; margin: 0 auto; flex: 1; animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        .card { background: var(--card-bg); padding: 40px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); margin-bottom: 40px; border-radius: 16px;} 
    </style>
</head>
<body>
    <nav class="student-nav">
        <a href="student_portal.php" class="nav-brand">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <polygon points="50,5 95,25 95,75 50,95 5,75 5,25" fill="none" stroke="currentColor" stroke-width="4"/>
                <polygon points="50,15 85,32 85,68 50,85 15,68 15,32" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="50" cy="50" r="10" fill="currentColor"/>
            </svg>
            Campus Pro
        </a>
        
        <div class="nav-links">
            <a href="student_portal.php" class="nav-link <?= ($current_page=='student_portal.php')?'active':'' ?>"><i class="fas fa-home" style="margin-right:8px;"></i> Hub</a>
            <a href="#" class="nav-link"><i class="fas fa-book" style="margin-right:8px;"></i> Academics</a>
            <a href="#" class="nav-link"><i class="fas fa-file-invoice-dollar" style="margin-right:8px;"></i> Finances</a>
        </div>

        <div class="nav-actions">
            <button class="theme-btn" onclick="toggleStudentTheme()" title="Toggle Theme"><i id="stuThemeIcon" class="fas fa-moon"></i></button>
            <a href="#" class="student-profile">
                <div class="sp-avatar"><i class="fas fa-user"></i></div>
                <div class="sp-info">
                    <span class="sp-name"><?= htmlspecialchars($student_name) ?></span>
                    <span class="sp-id"><?= htmlspecialchars($student_id) ?></span>
                </div>
            </a>
            <a href="logout.php" class="theme-btn" style="border-color: rgba(239,68,68,0.3); color: #ef4444;" title="Disconnect"><i class="fas fa-power-off"></i></a>
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
            if(icon) icon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('campus_theme') || 'light';
            const tIcon = document.getElementById('stuThemeIcon');
            if(tIcon) tIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    </script>