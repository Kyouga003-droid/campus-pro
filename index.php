<?php 
include 'config.php'; 

if(isset($_GET['clear_logs']) && $_GET['clear_logs'] == 'true') {
    mysqli_query($conn, "TRUNCATE TABLE system_logs");
    header("Location: index.php");
    exit();
}

$patch_queries = [
    "ALTER TABLE students ADD COLUMN department VARCHAR(50)",
    "ALTER TABLE billing ADD COLUMN fee_type VARCHAR(100)"
];
foreach($patch_queries as $q) { try { mysqli_query($conn, $q); } catch (Exception $e) {} }

include 'header.php';

$active_alert_res = @mysqli_query($conn, "SELECT alert_type, severity, location, timestamp FROM emergency_alerts WHERE status='Active' ORDER BY timestamp DESC LIMIT 1");
$has_alert = $active_alert_res && mysqli_num_rows($active_alert_res) > 0;
$alert_data = $has_alert ? mysqli_fetch_assoc($active_alert_res) : null;

$hour = date('H');
if ($hour < 12) $greeting = "Good Morning";
elseif ($hour < 17) $greeting = "Good Afternoon";
else $greeting = "Good Evening";

$student_count = getCount($conn, 'students');
$employee_count = getCount($conn, 'employees');
$visitor_count = getCount($conn, 'visitors');
$class_count = getCount($conn, 'classes');
$book_count = getCount($conn, 'library_catalog');

$dept_counts = [];
$res_dept = mysqli_query($conn, "SELECT department, COUNT(*) as c FROM students WHERE status='Enrolled' GROUP BY department");
if($res_dept) {
    while($row = mysqli_fetch_assoc($res_dept)){ if(!empty($row['department'])) $dept_counts[$row['department']] = $row['c']; }
}
$dept_labels = json_encode(array_keys($dept_counts));
$dept_data = json_encode(array_values($dept_counts));

$fee_counts = [];
$res_fee = mysqli_query($conn, "SELECT fee_type, SUM(amount) as total FROM billing WHERE status='Paid' GROUP BY fee_type");
if($res_fee) {
    while($row = mysqli_fetch_assoc($res_fee)){ if(!empty($row['fee_type'])) $fee_counts[$row['fee_type']] = $row['total']; }
}
$fee_labels = json_encode(array_keys($fee_counts));
$fee_data = json_encode(array_values($fee_counts));
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800;900&display=swap');

    .alert-strip { background: rgba(239, 68, 68, 0.08); border: 2px solid #ef4444; border-left: 8px solid #ef4444; border-radius: 10px; padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.15); animation: slideDown 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    
    .alert-strip-left { display: flex; align-items: center; gap: 20px; }
    .alert-pulse-icon { font-size: 2rem; color: #ef4444; animation: pulseGlowRed 2s infinite alternate; }
    @keyframes pulseGlowRed { 0% { filter: drop-shadow(0 0 2px rgba(239,68,68,0.4)); transform: scale(0.95); } 100% { filter: drop-shadow(0 0 12px rgba(239,68,68,0.9)); transform: scale(1.05); } }
    
    .alert-text-wrapper { display: flex; flex-direction: column; }
    .alert-title { font-family: var(--heading-font); font-size: 1.2rem; font-weight: 900; color: var(--text-dark); text-transform: uppercase; letter-spacing: 1px; }
    .alert-subtitle { font-size: 0.9rem; font-weight: 700; color: #ef4444; }

    .hero-banner { position: relative; min-height: 260px; border-radius: 16px; box-shadow: var(--soft-shadow); margin-bottom: 50px; background: var(--sub-menu-bg); border: 2px solid var(--border-color); overflow: hidden; display: flex; align-items: center; justify-content: center; text-align: center; }
    .olympus-art { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; color: var(--text-dark); }
    
    .hero-content { position: relative; z-index: 2; background: var(--card-bg); padding: 45px 80px; border: 2px solid var(--brand-secondary); box-shadow: inset 0 0 0 6px var(--card-bg), inset 0 0 0 8px var(--brand-secondary), 0 20px 40px rgba(0,0,0,0.15); animation: fadeIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); margin: 30px 0; }
    [data-theme="light"] .hero-content { border-color: var(--brand-primary); box-shadow: inset 0 0 0 6px var(--card-bg), inset 0 0 0 8px var(--brand-primary), 0 20px 40px rgba(14, 44, 70, 0.1); }
    
    .hero-title { font-family: 'Cinzel', serif; font-size: 3.4rem; font-weight: 900; margin-bottom: 10px; line-height: 1.1; color: var(--text-dark); text-transform: uppercase; letter-spacing: 2px;}
    .hero-subtitle { font-weight: 900; font-size: 1.05rem; letter-spacing: 5px; text-transform: uppercase; color: var(--brand-secondary); }
    [data-theme="light"] .hero-subtitle { color: var(--brand-primary); }

    .matrix-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 25px; margin-bottom: 45px; }
    
    .rpg-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 30px 20px; display: flex; flex-direction: column; align-items: center; text-align: center; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; text-decoration: none; box-shadow: var(--soft-shadow); }
    .rpg-card:hover { transform: translateY(-8px); box-shadow: var(--hard-shadow), var(--soft-shadow); }
    
    .rpg-icon-wrap { font-size: 2.8rem; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; transition: 0.3s; color: currentColor; background: var(--main-bg); border-radius: 20px; border: 2px solid var(--border-color); box-shadow: 2px 2px 0px var(--border-color);}
    .rpg-card:hover .rpg-icon-wrap { transform: scale(1.1) translateY(-5px); box-shadow: 4px 4px 0px var(--border-color); }
    
    .rpg-title { font-family: var(--heading-font); font-weight: 900; font-size: 1.2rem; color: var(--text-dark); margin-bottom: 5px; letter-spacing: 1px; text-transform: uppercase; }
    .rpg-metric { font-family: var(--body-font); font-weight: 900; font-size: 2.5rem; color: var(--text-dark); margin-bottom: 15px; line-height: 1; }
    
    .rpg-sub { font-size: 0.8rem; color: var(--text-light); font-weight: 800; letter-spacing: 0.5px; padding-top: 15px; border-top: 2px solid var(--border-light); width: 100%; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; }
    
    .rpg-badge { position: absolute; top: 15px; right: 15px; font-size: 0.7rem; font-weight: 900; padding: 6px 12px; border-radius: 20px; background: var(--main-bg); text-transform: uppercase; border: 2px solid currentColor; box-shadow: 2px 2px 0px currentColor;}
    .rpg-live-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; animation: pulse-dot 2s infinite; background: currentColor; }
    @keyframes pulse-dot { 0% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(currentColor, 0.7); } 50% { opacity: 0.5; transform: scale(1.3); box-shadow: 0 0 0 6px rgba(currentColor, 0); } 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(currentColor, 0); } }

    .c-blue { color: #3b82f6; } .c-purple { color: #8b5cf6; } .c-red { color: #ef4444; } .c-green { color: #10b981; } .c-cyan { color: #06b6d4; }

    .widget-title { font-size: 1.3rem; text-transform: uppercase; font-family: var(--heading-font); display: flex; align-items: center; gap: 15px; margin-bottom: 25px; color: var(--text-dark); padding-bottom: 15px; border-bottom: 1px solid var(--border-light); font-weight: 900; letter-spacing: 1.5px; }
    .widget-title .icon-box { border: 1px solid var(--border-color); border-radius: 8px; width: 45px; height: 45px; display: flex; justify-content: center; align-items: center; font-size: 1.3rem;}
    
    .scroll-list { display: flex; flex-direction: column; gap: 12px; max-height: 280px; overflow-y: auto; padding-right: 5px; }

    .log-item { display: flex; flex-direction: column; text-decoration: none; padding: 20px 25px; margin-bottom: 15px; background: var(--sub-menu-bg); border: 2px solid var(--border-color); transition: all 0.3s ease; position: relative; overflow: hidden; border-radius: 12px; box-shadow: 2px 2px 0px rgba(0,0,0,0.05);}
    .log-item:hover { border-color: var(--brand-secondary); transform: translateX(6px); box-shadow: var(--hard-shadow); }
    [data-theme="light"] .log-item:hover { border-color: var(--brand-primary); }
    .log-item .log-time { font-size: 0.8rem; font-weight: 800; color: var(--text-light); margin-bottom: 8px; letter-spacing: 0.5px; display:flex; justify-content: space-between;}
    .log-hover-action { position: absolute; right: -100px; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, var(--brand-secondary), var(--brand-accent)); color: #fff; padding: 10px 20px; font-weight: 900; font-size: 0.85rem; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); opacity: 0; border-radius: 8px; border: 2px solid var(--border-color); box-shadow: 2px 2px 0px var(--border-color);}
    [data-theme="light"] .log-hover-action { background: linear-gradient(135deg, var(--brand-primary), #1a4971); box-shadow: 2px 2px 0px var(--border-color); }
    .log-item:hover .log-hover-action { right: 25px; opacity: 1; }

    .cal-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); transition: 0.3s; box-shadow: var(--soft-shadow); display: flex; flex-direction: column; border-radius: 16px; height: max-content; }
    .cal-month-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-weight: 900; font-size: 1.2rem; color: var(--text-dark); padding: 0 5px; font-family: var(--heading-font); text-transform: uppercase; letter-spacing: 1px;}
    .cal-btn { background: var(--sub-menu-bg); border: 2px solid var(--border-color); border-radius: 8px; color: var(--text-dark); font-size: 1rem; width: 35px; height: 35px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; box-shadow: 2px 2px 0px var(--border-color); }
    .cal-btn:hover { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--brand-secondary); }
    [data-theme="light"] .cal-btn:hover { background: var(--brand-primary); color: #fff; border-color: var(--brand-primary); box-shadow: 4px 4px 0px var(--brand-primary); }
    .cal-btn:active { transform: translate(2px, 2px); box-shadow: none; }

    .calendar-grid { display: grid !important; grid-template-columns: repeat(7, 1fr) !important; gap: 4px; text-align: center; width: 100%; }
    .calendar-day-name { font-size: 0.75rem; color: var(--text-dark); font-weight: 900; padding-bottom: 8px; border-bottom: 2px solid var(--border-light); margin-bottom: 8px; text-transform: uppercase;}
    .calendar-day { font-family: var(--body-font); padding: 0; transition: 0.2s; cursor: pointer; font-weight: 700; font-size: 0.95rem; color: var(--text-dark); background: transparent; position: relative; border-radius: 50%; width: 35px; height: 35px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 2px solid transparent;}
    .calendar-day:hover { background: var(--bg-grid); color: var(--brand-secondary); transform: scale(1.1); border-color: var(--border-color); box-shadow: 2px 2px 0px var(--border-color);}
    [data-theme="light"] .calendar-day:hover { color: var(--brand-primary); }
    .calendar-day.today { background: linear-gradient(135deg, #ef4444, #b91c1c); color: #fff; font-weight: 900; border-color: var(--brand-crimson); box-shadow: 2px 2px 0px rgba(171,54,32,0.3); transform: scale(1.05); }
    .calendar-day.empty { opacity: 0; pointer-events: none; border:none;}
    .calendar-day.has-event::after { content: ''; position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 6px; height: 6px; background: var(--brand-secondary); border-radius: 50%; border: 1px solid var(--card-bg);}
    [data-theme="light"] .calendar-day.has-event::after { background: var(--brand-primary); }
    
    .cal-footer { display: flex; justify-content: space-between; border-top: 2px solid var(--border-light); margin-top: 15px; padding-top: 15px; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;}
    .cal-footer a { color: #3b82f6; text-decoration: none; padding: 6px 12px; border-radius: 8px; transition: 0.2s; border: 2px solid transparent; }
    .cal-footer a:hover { background: rgba(59,130,246,0.1); border-color: #3b82f6; }
    
    .ops-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .ops-btn { background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px 10px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-decoration: none; color: var(--text-dark); transition: 0.2s; }
    .ops-btn i { font-size: 1.8rem; color: var(--text-light); transition: 0.2s; }
    .ops-btn span { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
    .ops-btn:hover { border-color: var(--brand-secondary); transform: translateY(-4px); box-shadow: var(--hard-shadow); }
    [data-theme="light"] .ops-btn:hover { border-color: var(--brand-primary); }
    .ops-btn:hover i { color: var(--brand-secondary); transform: scale(1.1); }
    [data-theme="light"] .ops-btn:hover i { color: var(--brand-primary); }

    .list-row { display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); text-decoration: none; transition: 0.2s; }
    .list-row:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px rgba(0,0,0,0.05); border-color: var(--brand-secondary); }
    [data-theme="light"] .list-row:hover { border-color: var(--brand-primary); box-shadow: 4px 4px 0px rgba(14, 44, 70, 0.1); }
</style>

<?php if($has_alert): ?>
<div class="alert-strip">
    <div class="alert-strip-left">
        <i class="fas fa-broadcast-tower alert-pulse-icon"></i>
        <div class="alert-text-wrapper">
            <span class="alert-title">Active Crisis Telemetry: <?= htmlspecialchars($alert_data['alert_type']) ?></span>
            <span class="alert-subtitle"><?= htmlspecialchars($alert_data['severity']) ?> detected in <?= htmlspecialchars($alert_data['location']) ?>. Awaiting executive directives.</span>
        </div>
    </div>
    <a href="disasters.php" class="btn-primary" style="background:#ef4444; border-color:#ef4444; color:#fff; padding: 12px 25px; margin: 0;"><i class="fas fa-shield-alt"></i> Open Matrix</a>
</div>
<?php endif; ?>

<div class="hero-banner">
    <svg class="olympus-art" viewBox="0 0 1000 240" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <pattern id="meander-flat" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
            <path d="M0,60 L0,0 L60,0 L60,45 L15,45 L15,15 L45,15 L45,30 L30,30 L30,60" fill="none" stroke="currentColor" stroke-width="2" opacity="0.08"/>
        </pattern>
        <rect x="0" y="0" width="100%" height="100%" fill="url(#meander-flat)"/>
    </svg>
    <div class="hero-content">
        <h1 class="hero-title"><?= $greeting ?>, Admin.</h1>
        <p class="hero-subtitle">Executive Command & Central Intelligence</p>
    </div>
</div>

<div class="matrix-grid">
    <a href="students.php" class="rpg-card c-blue">
        <span class="rpg-badge" style="color: #3b82f6; border-color: #3b82f6;">Active</span>
        <div class="rpg-icon-wrap"><i class="fas fa-user-graduate"></i></div>
        <div class="rpg-title">Students</div>
        <div class="rpg-metric" style="color: var(--text-dark);"><?= $student_count ?></div>
        <div class="rpg-sub"><span><span class="rpg-live-dot"></span>Online: 82%</span> <span><i class="fas fa-arrow-right"></i></span></div>
    </a>
  
    <a href="employees.php" class="rpg-card c-purple">
        <span class="rpg-badge" style="color: #8b5cf6; border-color: #8b5cf6;">Staff</span>
        <div class="rpg-icon-wrap"><i class="fas fa-user-tie"></i></div>
        <div class="rpg-title">Faculty</div>
        <div class="rpg-metric" style="color: var(--text-dark);"><?= $employee_count ?></div>
        <div class="rpg-sub"><span><i class="fas fa-chalkboard-teacher"></i> 14 Teaching</span> <span><i class="fas fa-arrow-right"></i></span></div>
    </a>
    
    <a href="classes.php" class="rpg-card c-cyan">
        <span class="rpg-badge" style="color: #06b6d4; border-color: #06b6d4;">Sessions</span>
        <div class="rpg-icon-wrap"><i class="fas fa-chalkboard"></i></div>
        <div class="rpg-title">Classes</div>
        <div class="rpg-metric" style="color: var(--text-dark);"><?= $class_count ?></div>
        <div class="rpg-sub"><span><span class="rpg-live-dot"></span>3 Live Now</span> <span><i class="fas fa-arrow-right"></i></span></div>
    </a>
    
    <a href="inventory.php" class="rpg-card c-green">
        <span class="rpg-badge" style="color: #10b981; border-color: #10b981;">Assets</span>
        <div class="rpg-icon-wrap"><i class="fas fa-boxes"></i></div>
        <div class="rpg-title">Inventory</div>
        <div class="rpg-metric" style="color: var(--text-dark);">Safe</div>
        <div class="rpg-sub"><span>No Alerts</span> <span><i class="fas fa-arrow-right"></i></span></div>
    </a>
    
    <a href="visitors.php" class="rpg-card c-red">
        <span class="rpg-badge" style="color: #ef4444; border-color: #ef4444;">Temp</span>
        <div class="rpg-icon-wrap"><i class="fas fa-id-card-clip"></i></div>
        <div class="rpg-title">Visitors</div>
        <div class="rpg-metric" style="color: var(--text-dark);"><?= $visitor_count ?></div>
        <div class="rpg-sub"><span><i class="fas fa-clock"></i> 2 Expiring</span> <span><i class="fas fa-arrow-right"></i></span></div>
    </a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px; margin-bottom: 40px;">
    <div style="display: flex; flex-direction: column; gap: 40px;">
        
        <div class="card" style="margin:0;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 25px;">
                <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                    <div class="icon-box"><i class="fas fa-chart-line" style="color:var(--brand-secondary);"></i></div> Scholar Dist.
                </h3>
                <button class="btn-action" style="padding: 6px 12px; font-size: 0.75rem;" onclick="systemToast('Exporting Chart Data...')"><i class="fas fa-download"></i> Export</button>
            </div>
            <div style="height: 240px; width: 100%; position: relative;">
                <canvas id="deptChart"></canvas>
            </div>
        </div>

        <div class="card" style="margin:0; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-light); padding-bottom:20px; margin-bottom:25px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                        <div class="icon-box"><i class="fas fa-history" style="color:var(--text-dark);"></i></div> Live Neural Log
                    </h3>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <span class="status-pill" style="color: #f59e0b; border-color: #f59e0b;"><i class="fas fa-circle" style="font-size:8px; vertical-align:middle; margin-right:6px; animation: pulse-dot 2s infinite;"></i> Live Sync</span>
                    <a href="?clear_logs=true" class="btn-action btn-del" style="padding: 6px 12px; font-size: 0.75rem;"><i class="fas fa-eraser"></i> Purge</a>
                </div>
            </div>
            <div class="scroll-list">
                <?php
                $logs_check = @mysqli_query($conn, "SHOW TABLES LIKE 'system_logs'");
                if($logs_check && mysqli_num_rows($logs_check) > 0) {
                    $logs = mysqli_query($conn, "SELECT * FROM system_logs ORDER BY log_time DESC LIMIT 25");
                    if($logs && mysqli_num_rows($logs) > 0) {
                        while($l = mysqli_fetch_assoc($logs)) {
                            $action = htmlspecialchars($l['action']);
                            $target_url = '#'; 
                            $mock_ip = "192.168.1." . rand(10, 99);

                            echo "<a href='{$target_url}' class='log-item' onclick='systemToast(\"Fetching Audit Record...\")'>
                                    <div class='log-time'>
                                        <span><i class='far fa-clock'></i> " . date('M d, Y • h:i A', strtotime($l['log_time'])) . " <span style='opacity:0.5; margin-left:12px; font-family:monospace;'>[IP: {$mock_ip}]</span></span>
                                    </div>
                                    <div style='font-size:1.1rem; color:var(--text-dark); font-weight:800; line-height:1.5;'>{$action}</div>
                                    <span class='log-hover-action'>View Record <i class='fas fa-arrow-right'></i></span>
                                  </a>";
                        }
                    } else { echo "<div style='text-align:center; padding: 50px; opacity: 0.5;'><i class='fas fa-satellite-dish' style='font-size: 3.5rem; margin-bottom: 20px; color:var(--brand-secondary);'></i><p style='font-weight:700; font-size:1.1rem;'>Awaiting system telemetry.</p></div>"; }
                }
                ?>
            </div>
        </div>
    </div>
    
    <div style="display: flex; flex-direction: column; gap: 40px;">
        
        <div class="cal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 15px;">
                <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                    <div class="icon-box"><i class="far fa-calendar-alt" style="color:var(--brand-crimson);"></i></div> Calendar
                </h3>
                <a href="events.php" class="btn-action" style="padding: 6px 12px; font-size: 0.75rem;"><i class="fas fa-plus"></i> EVENT</a>
            </div>
            
            <div id="calBody" style="display: flex; flex-direction: column;">
                <div class="cal-month-nav">
                    <button class="cal-btn" onclick="prevMonth()"><i class="fas fa-chevron-left"></i></button>
                    <span id="calMonthYear" style="color:var(--text-dark);"></span>
                    <button class="cal-btn" onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-day-name">S</div><div class="calendar-day-name">M</div><div class="calendar-day-name">T</div>
                    <div class="calendar-day-name">W</div><div class="calendar-day-name">T</div><div class="calendar-day-name">F</div><div class="calendar-day-name">S</div>
                </div>
                <div class="calendar-grid" id="calGrid"></div>
                
                <div class="cal-footer">
                    <a href="events.php">Full Calendar</a>
                </div>
            </div>
        </div>

        <div class="card" style="margin:0;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 20px;">
                <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                    <div class="icon-box"><i class="fas fa-bolt" style="color:var(--brand-secondary);"></i></div> Campus Operations
                </h3>
            </div>
            <div class="ops-grid">
                <a href="#" class="ops-btn" onclick="systemToast('Loading Appointments...')"><i class="fas fa-clock"></i><span>Appts</span></a>
                <a href="#" class="ops-btn" onclick="systemToast('Loading Communications...')"><i class="fas fa-envelope"></i><span>Msgs</span></a>
                <a href="transport.php" class="ops-btn"><i class="fas fa-bus"></i><span>Transit</span></a>
                <a href="#" class="ops-btn" onclick="systemToast('Loading Facilities Map...')"><i class="fas fa-tools"></i><span>Maint</span></a>
                <a href="it_tickets.php" class="ops-btn"><i class="fas fa-ticket-alt"></i><span>IT Desk</span></a>
                <a href="#" class="ops-btn" onclick="systemToast('Loading Security Feeds...')"><i class="fas fa-shield-alt"></i><span>Security</span></a>
            </div>
        </div>

        <div class="card" style="margin:0;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 20px;">
                <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                    <div class="icon-box"><i class="fas fa-server" style="color:var(--text-dark);"></i></div> Metrics
                </h3>
                <button class="btn-action" style="padding: 6px 12px; font-size: 0.75rem;" onclick="systemToast('Running Server Diagnostics...')"><i class="fas fa-microchip"></i> Diag</button>
            </div>
            <div class="scroll-list" style="max-height: none;">
                <div class="list-row" style="cursor:default;">
                    <span style="font-weight:800; font-size:0.95rem; color:var(--text-dark);"><i class="fas fa-shield-alt" style="color:#10b981; margin-right:10px;"></i> Core Uptime</span>
                    <span style="font-weight:900; color:#10b981; font-size: 1.1rem;">99.9%</span>
                </div>
                <div class="list-row" style="cursor:default;">
                    <span style="font-weight:800; font-size:0.95rem; color:var(--text-dark);"><i class="fas fa-network-wired" style="color:#f59e0b; margin-right:10px;"></i> DB Ping</span>
                    <span style="font-weight:900; color:#f59e0b; font-size: 1.1rem; font-family:monospace;">14ms</span>
                </div>
                <div class="list-row" style="cursor:default;">
                    <span style="font-weight:800; font-size:0.95rem; color:var(--text-dark);"><i class="fas fa-users" style="color:var(--text-light); margin-right:10px;"></i> Sessions</span>
                    <span style="font-weight:900; color:var(--text-dark); font-size: 1.1rem;"><span class="rpg-live-dot" style="color:var(--text-light);"></span> 1 Online</span>
                </div>
            </div>
        </div>

        <div class="card" style="margin:0;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 20px;">
                <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                    <div class="icon-box"><i class="fas fa-ticket-alt" style="color:var(--text-dark);"></i></div> Live Support Queue
                </h3>
                <a href="it_tickets.php" class="btn-action" style="padding: 6px 12px; font-size: 0.75rem;"><i class="fas fa-external-link-alt"></i> View All</a>
            </div>
            <div class="scroll-list">
                <?php
                $t_check = @mysqli_query($conn, "SHOW TABLES LIKE 'it_tickets'");
                if($t_check && mysqli_num_rows($t_check) > 0) {
                    $tickets = mysqli_query($conn, "SELECT * FROM it_tickets WHERE status='Open' ORDER BY priority ASC, created_at DESC LIMIT 5");
                    if($tickets && mysqli_num_rows($tickets) > 0) {
                        while($t = mysqli_fetch_assoc($tickets)) {
                            $pri_color = $t['priority'] == 'Critical' ? 'color:var(--brand-crimson);' : 'color:var(--brand-secondary);';
                            echo "
                            <a href='it_tickets.php' class='list-row'>
                                <div>
                                    <div style='font-weight:800; color:var(--text-dark); font-size:0.95rem; margin-bottom:4px;'>{$t['issue']}</div>
                                    <div style='font-size:0.75rem; color:var(--text-light); font-family:monospace;'>{$t['ticket_id']} • {$t['location']}</div>
                                </div>
                                <div style='font-size:0.7rem; font-weight:900; text-transform:uppercase; {$pri_color}'><i class='fas fa-circle' style='font-size:0.5rem; vertical-align:middle; margin-right:4px;'></i> {$t['priority']}</div>
                            </a>";
                        }
                    } else { echo "<div style='text-align:center; padding:20px; font-weight:700; color:var(--text-light);'>No active tickets.</div>"; }
                }
                ?>
            </div>
        </div>

        <div class="card" style="margin:0;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 20px;">
                <h3 class="widget-title" style="margin:0; border:none; padding:0;">
                    <div class="icon-box"><i class="fas fa-id-badge" style="color:var(--text-dark);"></i></div> Active Visitors
                </h3>
                <a href="visitors.php" class="btn-action" style="padding: 6px 12px; font-size: 0.75rem;"><i class="fas fa-external-link-alt"></i> Registry</a>
            </div>
            <div class="scroll-list">
                <?php
                $v_check = @mysqli_query($conn, "SHOW TABLES LIKE 'visitors'");
                if($v_check && mysqli_num_rows($v_check) > 0) {
                    $vis = mysqli_query($conn, "SELECT * FROM visitors WHERE status='On Campus' ORDER BY check_in DESC LIMIT 5");
                    if($vis && mysqli_num_rows($vis) > 0) {
                        while($v = mysqli_fetch_assoc($vis)) {
                            $time = date('h:i A', strtotime($v['check_in']));
                            echo "
                            <a href='visitors.php' class='list-row'>
                                <div style='display:flex; gap:12px; align-items:center;'>
                                    <div style='width:35px; height:35px; border-radius:8px; background:var(--main-bg); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; color:var(--text-light);'><i class='fas fa-user'></i></div>
                                    <div>
                                        <div style='font-weight:800; color:var(--text-dark); font-size:0.95rem; margin-bottom:4px;'>{$v['visitor_name']}</div>
                                        <div style='font-size:0.75rem; color:var(--text-light); text-transform:uppercase;'>Host: {$v['host_person']}</div>
                                    </div>
                                </div>
                                <div style='font-size:0.75rem; font-weight:800; color:#10b981; border:1px solid #10b981; padding:4px 8px; border-radius:6px;'>IN: {$time}</div>
                            </a>";
                        }
                    } else { echo "<div style='text-align:center; padding:20px; font-weight:700; color:var(--text-light);'>No active visitors logged.</div>"; }
                }
                ?>
            </div>
        </div>

    </div>
</div>

<script>
let lineChart;

function initCharts() {
    const getChartColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#0E2C46';
    const getGridColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? 'rgba(255, 255, 255, 0.05)' : 'rgba(14, 44, 70, 0.05)';
    const getLineColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#FC9D01' : '#0E2C46';
    const getLineBg = () => document.documentElement.getAttribute('data-theme') === 'dark' ? 'rgba(252, 157, 1, 0.2)' : 'rgba(14, 44, 70, 0.1)';

    Chart.defaults.color = getChartColor();
    Chart.defaults.font.family = "'Inter', sans-serif";

    const commonTooltip = {
        backgroundColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#0d2136' : '#ffffff',
        titleColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#FC9D01' : '#0E2C46',
        bodyColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#556b82',
        borderColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#1e3a5f' : '#cbd5e1',
        borderWidth: 1,
        padding: 15,
        cornerRadius: 8,
        titleFont: { family: "'Playfair Display', serif", size: 14, weight: 'bold' },
        bodyFont: { family: "'Inter', sans-serif", size: 13, weight: '600' },
        displayColors: false
    };
    
    const ctxDept = document.getElementById('deptChart');
    if (ctxDept) {
        lineChart = new Chart(ctxDept.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= $dept_labels ?>,
                datasets: [{ 
                    label: 'Students', 
                    data: <?= $dept_data ?>, 
                    borderColor: getLineColor(), 
                    backgroundColor: getLineBg(),
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#D94F00',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: commonTooltip }, 
                scales: { 
                    y: { beginAtZero: true, grid: { color: getGridColor(), lineWidth: 1 }, border: { display: false } }, 
                    x: { grid: { display: false }, border: { display: false } } 
                } 
            }
        });
    }

    const ctxFee = document.getElementById('feeChart');
    if (ctxFee) {
        new Chart(ctxFee.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= $fee_labels ?>,
                datasets: [{ 
                    data: <?= $fee_data ?>, 
                    backgroundColor: ['#FC9D01', '#D94F00', '#0E2C46', '#AB3620', '#556b82'], 
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { position: 'right', labels: { padding: 15, font: { size: 12, weight: '700', family: "'Inter', sans-serif" }, usePointStyle: true, pointStyle: 'circle' } },
                    tooltip: commonTooltip
                }, 
                cutout: '70%' 
            }
        });
    }
}

let currDate = new Date();

function renderCalendar() {
    const year = currDate.getFullYear();
    const month = currDate.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();
    document.getElementById('calMonthYear').innerText = new Intl.DateTimeFormat('en-US', { month: 'short', year: 'numeric' }).format(currDate);
    
    let daysHtml = '';
    for(let i=0; i<firstDay; i++) daysHtml += '<div class="calendar-day empty"></div>';
    
    let today = new Date();
    for(let i=1; i<=lastDate; i++) {
        let isToday = (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) ? 'today' : '';
        let hasEvent = '';
        if(i === 12 || i === 18) hasEvent = 'has-event';
        if(i === 24) hasEvent = 'has-event-blue';
        
        daysHtml += `<div class="calendar-day ${isToday} ${hasEvent}" onclick="systemToast('Viewing Event Data for ${month+1}/${i}/${year}')">${i}</div>`;
    }
    document.getElementById('calGrid').innerHTML = daysHtml;
}

function prevMonth() { currDate.setMonth(currDate.getMonth() - 1); renderCalendar(); }
function nextMonth() { currDate.setMonth(currDate.getMonth() + 1); renderCalendar(); }

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    renderCalendar();
});
</script>
<?php include 'footer.php'; ?>