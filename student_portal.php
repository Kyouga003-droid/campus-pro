<?php
include 'student_header.php';
include 'config.php';

$sid = $_SESSION['user_id'];

// Fetch secure student data
$stu_res = @mysqli_query($conn, "SELECT * FROM students WHERE student_id='$sid'");
$student = $stu_res ? mysqli_fetch_assoc($stu_res) : null;

// Fetch secure financial data
$fin_res = @mysqli_query($conn, "SELECT SUM(net_amount - amount_paid) as deficit FROM billing WHERE student_id='$sid' AND status!='Paid'");
$deficit = $fin_res ? floatval(mysqli_fetch_assoc($fin_res)['deficit']) : 0;

// Greeting Logic
$hour = date('H');
if ($hour < 12) $greeting = "Good Morning";
elseif ($hour < 17) $greeting = "Good Afternoon";
else $greeting = "Good Evening";
?>

<style>
    .student-hero { background: linear-gradient(135deg, var(--card-bg), var(--sub-menu-bg)); border: 2px solid var(--border-color); border-radius: 20px; padding: 50px; margin-bottom: 40px; box-shadow: var(--soft-shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; position: relative; overflow: hidden;}
    .student-hero::after { content: '\f19d'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: -5%; top: -20%; font-size: 15rem; color: var(--text-dark); opacity: 0.03; pointer-events: none;}
    
    .sh-welcome { font-family: var(--heading-font); font-size: 2.8rem; font-weight: 900; color: var(--text-dark); margin-bottom: 10px; line-height: 1.1; letter-spacing: 1px;}
    .sh-sub { font-size: 1.1rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 2px;}
    
    .sh-stats { display: flex; gap: 30px; }
    .sh-stat-box { text-align: right; }
    .sh-stat-lbl { font-size: 0.8rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;}
    .sh-stat-val { font-size: 1.5rem; font-weight: 900; color: var(--brand-secondary); font-family: monospace;}
    [data-theme="light"] .sh-stat-val { color: var(--brand-primary); }

    .hub-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; }
    @media (max-width: 1000px) { .hub-grid { grid-template-columns: 1fr; } }

    .widget-title { font-size: 1.2rem; text-transform: uppercase; font-family: var(--heading-font); display: flex; align-items: center; gap: 15px; margin-bottom: 25px; color: var(--text-dark); padding-bottom: 15px; border-bottom: 1px solid var(--border-light); font-weight: 900; letter-spacing: 1px; }
    .widget-title .w-icon { width: 40px; height: 40px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;}

    .schedule-list { display: flex; flex-direction: column; gap: 15px; }
    .sched-item { display: flex; align-items: center; gap: 20px; padding: 20px; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 12px; box-shadow: 2px 2px 0px rgba(0,0,0,0.05); transition: 0.2s;}
    .sched-item:hover { border-color: var(--brand-secondary); transform: translateX(5px); box-shadow: var(--hard-shadow);}
    [data-theme="light"] .sched-item:hover { border-color: var(--brand-primary); }
    .sched-time { width: 120px; font-weight: 900; font-family: monospace; color: var(--brand-secondary); font-size: 1.1rem; border-right: 2px solid var(--border-light); padding-right: 20px;}
    [data-theme="light"] .sched-time { color: var(--brand-primary); }
    .sched-details { flex: 1; }
    .sched-course { font-size: 1.1rem; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; text-transform: uppercase;}
    .sched-meta { font-size: 0.85rem; font-weight: 600; color: var(--text-light); display: flex; gap: 15px;}
    .sched-meta i { color: var(--text-dark); opacity: 0.5;}

    .alert-box { background: rgba(239, 68, 68, 0.05); border: 2px dashed #ef4444; border-radius: 12px; padding: 25px; text-align: center; margin-bottom: 30px;}
    .alert-box h4 { color: #ef4444; font-family: var(--heading-font); font-weight: 900; font-size: 1.3rem; margin-bottom: 10px; text-transform: uppercase;}
    .alert-box p { color: var(--text-dark); font-weight: 600; font-size: 0.9rem; margin-bottom: 15px;}
    
    .btn-pay { display: inline-block; background: #ef4444; color: #fff; font-weight: 900; padding: 12px 25px; border-radius: 8px; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; border: 2px solid #ef4444; transition: 0.2s; box-shadow: 2px 2px 0px rgba(239,68,68,0.3);}
    .btn-pay:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px rgba(239,68,68,0.5); }
    .btn-pay:active { transform: translate(2px, 2px); box-shadow: none; }
</style>

<div class="student-hero">
    <div>
        <h1 class="sh-welcome"><?= $greeting ?>, <?= htmlspecialchars($student['first_name'] ?? 'Scholar') ?>.</h1>
        <div class="sh-sub">Academic Term 2026 • <?= htmlspecialchars($student['department'] ?? 'General Registry') ?></div>
    </div>
    <div class="sh-stats">
        <div class="sh-stat-box">
            <div class="sh-stat-lbl">Program</div>
            <div class="sh-stat-val"><?= htmlspecialchars($student['course'] ?? 'N/A') ?></div>
        </div>
        <div class="sh-stat-box">
            <div class="sh-stat-lbl">Year Level</div>
            <div class="sh-stat-val"><?= htmlspecialchars($student['year_level'] ?? 'N/A') ?></div>
        </div>
        <div class="sh-stat-box">
            <div class="sh-stat-lbl">Standing</div>
            <div class="sh-stat-val" style="color:#10b981;"><?= htmlspecialchars($student['status'] ?? 'Active') ?></div>
        </div>
    </div>
</div>

<div class="hub-grid">
    
    <div class="card" style="margin:0;">
        <h3 class="widget-title">
            <div class="w-icon"><i class="fas fa-calendar-day" style="color:var(--brand-secondary);"></i></div> Today's Schedule
        </h3>
        <div class="schedule-list">
            <?php
            // In a full production build, this would query a complex mapping table matching students to specific class IDs.
            // For this architectural demonstration, we provide a structured layout of what the student sees.
            ?>
            <div class="sched-item">
                <div class="sched-time">09:00 AM</div>
                <div class="sched-details">
                    <div class="sched-course">Advanced Programming Logic</div>
                    <div class="sched-meta">
                        <span><i class="fas fa-door-open"></i> Lab IT-401</span>
                        <span><i class="fas fa-user-tie"></i> Dr. Turing</span>
                    </div>
                </div>
            </div>
            
            <div class="sched-item">
                <div class="sched-time">11:30 AM</div>
                <div class="sched-details">
                    <div class="sched-course">Database Architecture</div>
                    <div class="sched-meta">
                        <span><i class="fas fa-door-open"></i> Room CR-302</span>
                        <span><i class="fas fa-user-tie"></i> Prof. Codd</span>
                    </div>
                </div>
            </div>

            <div class="sched-item" style="opacity: 0.6;">
                <div class="sched-time">02:00 PM</div>
                <div class="sched-details">
                    <div class="sched-course">Free Block / Study Period</div>
                    <div class="sched-meta">
                        <span><i class="fas fa-book-reader"></i> Library Recommended</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:40px;">
        
        <div class="card" style="margin:0; padding:30px;">
            <h3 class="widget-title" style="margin-bottom:15px; border:none;">
                <div class="w-icon"><i class="fas fa-coins" style="color:var(--brand-crimson);"></i></div> Financial Status
            </h3>
            
            <?php if($deficit > 0): ?>
                <div class="alert-box">
                    <h4>Action Required</h4>
                    <p>You have an outstanding tuition balance.</p>
                    <div style="font-size:2rem; font-family:monospace; font-weight:900; color:var(--text-dark); margin-bottom:20px;">₱<?= number_format($deficit, 2) ?></div>
                    <a href="#" class="btn-pay"><i class="fas fa-credit-card" style="margin-right:8px;"></i> Access Ledger</a>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding: 20px;">
                    <i class="fas fa-check-circle" style="font-size:3.5rem; color:#10b981; margin-bottom:15px;"></i>
                    <h4 style="font-family:var(--heading-font); font-size:1.3rem; font-weight:900; color:var(--text-dark); text-transform:uppercase; margin-bottom:5px;">Accounts Settled</h4>
                    <p style="font-size:0.9rem; color:var(--text-light); font-weight:600;">You have no pending financial obligations.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin:0; padding:30px; background:var(--sub-menu-bg);">
            <h3 class="widget-title" style="border:none; margin-bottom:10px;">
                <div class="w-icon"><i class="fas fa-bullhorn" style="color:var(--text-dark);"></i></div> Campus Notices
            </h3>
            <div style="font-size:0.85rem; font-weight:600; color:var(--text-light); line-height:1.5;">
                <strong>Enrollment Note:</strong> Final dropping of subjects without penalty is scheduled for Friday. Ensure your matrix is correct.
            </div>
        </div>

    </div>

</div>

</body>
</html>