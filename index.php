<?php 
include 'config.php'; 

if(isset($_GET['clear_logs']) && $_GET['clear_logs'] == 'true') {
    mysqli_query($conn, "TRUNCATE TABLE system_logs");
    header("Location: index.php");
    exit();
}

// Database schema patches to ensure required columns exist
$patch_queries = [
    "ALTER TABLE students ADD COLUMN department VARCHAR(50)",
    "ALTER TABLE billing ADD COLUMN fee_type VARCHAR(100)"
];
foreach($patch_queries as $q) { try { mysqli_query($conn, $q); } catch (Exception $e) {} }

include 'header.php';

// Check for emergency alerts
$active_alert_res = @mysqli_query($conn, "SELECT alert_type, severity, location, timestamp FROM emergency_alerts WHERE status='Active' ORDER BY timestamp DESC LIMIT 1");
$has_alert = $active_alert_res && mysqli_num_rows($active_alert_res) > 0;
$alert_data = $has_alert ? mysqli_fetch_assoc($active_alert_res) : null;

// Time-based greeting
$hour = date('H');
if ($hour < 12) { $greeting = "Good morning"; }
elseif ($hour < 17) { $greeting = "Good afternoon"; }
else { $greeting = "Good evening"; }

// Core Metrics
$student_count = getCount($conn, 'students');
$employee_count = getCount($conn, 'employees');
$class_count = getCount($conn, 'classes');
$book_count = getCount($conn, 'library_catalog');

// Advanced Metrics
$lib_loans = @mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Borrowed'"))['c'] ?? 0;
$maint_rooms = @mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms WHERE status='In Maintenance'"))['c'] ?? 0;
$total_revenue = @mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_paid) as v FROM billing"))['v'] ?? 0;
$trend_food = @mysqli_fetch_assoc(mysqli_query($conn, "SELECT item_name FROM cafeteria_menu WHERE is_trending=1 LIMIT 1"))['item_name'] ?? 'Matcha Latte';

// Demographics Chart Data
$dept_counts = [];
$res_dept = mysqli_query($conn, "SELECT department, COUNT(*) as c FROM students WHERE status='Enrolled' GROUP BY department");
if($res_dept) {
    while($row = mysqli_fetch_assoc($res_dept)){ 
        if(!empty($row['department'])) {
            $dept_counts[$row['department']] = $row['c']; 
        }
    }
}
$dept_labels = json_encode(array_keys($dept_counts));
$dept_data = json_encode(array_values($dept_counts));
?>

<style>
    /* Isometric Grid Background */
    body {
        background-color: var(--main-bg);
        background-image: linear-gradient(rgba(15, 23, 42, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
    }
    [data-theme="dark"] body {
        background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
    }

    /* Backdrop Blur Hero Panel */
    .hero-panel { 
        padding: 40px 35px; 
        margin-bottom: 30px; 
        display:flex; 
        justify-content:space-between; 
        align-items:flex-end; 
        background: rgba(var(--card-bg-rgb, 255,255,255), 0.6); 
        backdrop-filter: blur(20px); 
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--border-color); 
        border-radius: 24px; 
        box-shadow: var(--soft-shadow);
    }
    [data-theme="dark"] .hero-panel { background: rgba(30, 41, 59, 0.6); }
    
    /* Clean Solid Greeting */
    .hero-greeting { 
        font-size: 2.5rem; 
        font-weight: 800; 
        color: var(--text-dark);
        letter-spacing: -1px; 
        margin-bottom: 8px;
    }
    .hero-sub { 
        font-size: 1rem; 
        color: var(--text-light); 
        font-weight: 500;
    }
    
    /* Quick Actions Strip */
    .qa-strip { 
        display: flex; 
        gap: 15px; 
        margin-bottom: 30px; 
        overflow-x: auto; 
        padding-bottom: 10px;
        scrollbar-width: none;
    }
    .qa-strip::-webkit-scrollbar { display: none; }
    
    .qa-btn { 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        background: var(--card-bg); 
        border: 1px solid var(--border-color); 
        padding: 12px 20px; 
        border-radius: 12px; 
        color: var(--text-dark); 
        font-weight: 600; 
        font-size: 0.9rem; 
        text-decoration: none; 
        transition: 0.2s; 
        box-shadow: var(--soft-shadow); 
        white-space: nowrap;
    }
    .qa-btn:hover { 
        transform: translateY(-2px); 
        border-color: var(--brand-secondary); 
        color: var(--brand-secondary);
    }
    .qa-btn i { font-size: 1.1rem; }

    /* Metric Cards Grid */
    .metric-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
        gap: 20px; 
        margin-bottom: 40px; 
    }
    .metric-card { 
        display: flex; 
        flex-direction: column; 
        justify-content: space-between; 
        padding: 25px; 
        background: var(--card-bg); 
        border: 1px solid var(--border-color); 
        border-radius: 20px; 
        text-decoration: none; 
        transition: all 0.3s ease; 
        box-shadow: var(--soft-shadow); 
        position: relative; 
        overflow: hidden;
    }
    
    /* 3D Hover Lift */
    .metric-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); 
        border-color: var(--border-light); 
    }
    [data-theme="dark"] .metric-card:hover { 
        border-color: var(--brand-secondary); 
        box-shadow: 0 10px 30px -5px rgba(245, 158, 11, 0.15); 
    }
    
    .mc-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;}
    .mc-icon { font-size: 1.5rem; color: var(--text-dark); display: flex; align-items: center; justify-content: center; background: var(--main-bg); width: 45px; height: 45px; border-radius: 12px; border: 1px solid var(--border-color);}
    .mc-val { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); line-height: 1; letter-spacing: -1px;}
    .mc-lbl { font-size: 0.85rem; font-weight: 600; color: var(--text-light); margin-top: 5px; }
    .mc-trend { font-size: 0.75rem; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 4px; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 6px;}

    /* Avatar Stacks */
    .avatar-stack { display: flex; align-items: center; margin-top: 15px; }
    .avatar-circ { width: 30px; height: 30px; border-radius: 50%; background: var(--brand-secondary); border: 2px solid var(--card-bg); margin-left: -10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.7rem; font-weight: 700;}
    .avatar-circ:first-child { margin-left: 0; }

    /* Main Dashboard Layout */
    .dashboard-grid { 
        display: grid; 
        grid-template-columns: 2fr 1fr; 
        gap: 30px; 
        margin-bottom: 40px; 
    }
    @media (max-width: 1024px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }
    .dash-col { display: flex; flex-direction: column; gap: 30px; }
    
    /* Widget Architecture */
    .widget-card { 
        background: var(--card-bg); 
        border: 1px solid var(--border-color); 
        border-radius: 24px; 
        padding: 30px; 
        box-shadow: var(--soft-shadow); 
        transition: 0.3s; 
        position: relative;
    }
    
    .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;}
    .widget-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); display:flex; align-items:center; gap:10px;}
    .widget-title i { color: var(--brand-secondary); }
    .widget-controls { display: flex; gap: 10px; color: var(--text-light); }
    .w-ctrl-btn { background: transparent; border: none; color: inherit; cursor: pointer; transition: 0.2s; font-size: 1rem;}
    .w-ctrl-btn:hover { color: var(--text-dark); }

    /* Custom Scrollbars - FIXED FOR OVERFLOW */
    .scroll-list { 
        display: flex; 
        flex-direction: column; 
        gap: 10px; 
        overflow-y: auto; 
        padding-right: 10px; 
    }
    .scroll-list::-webkit-scrollbar { width: 6px; }
    .scroll-list::-webkit-scrollbar-track { background: transparent; }
    .scroll-list::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
    .scroll-list:hover::-webkit-scrollbar-thumb { background: var(--text-light); }

    /* Skeleton Loaders */
    .skeleton { background: linear-gradient(90deg, var(--bg-grid) 25%, var(--border-color) 50%, var(--bg-grid) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; color: transparent !important; }
    .skeleton * { visibility: hidden; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* Task Manager Inputs & Styles */
    .todo-input-wrap { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; background: var(--main-bg); padding: 8px; border-radius: 12px; border: 1px solid var(--border-color);}
    .todo-input-wrap input { flex: 1; background: transparent; border: none; padding: 8px 12px; font-size: 0.95rem; color: var(--text-dark); outline: none; }
    .todo-pri-sel { background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); outline: none; cursor: pointer;}
    .todo-add-btn { background: var(--text-dark); color: var(--main-bg); border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; display:flex; align-items:center; gap:6px;}
    .todo-add-btn:hover { opacity: 0.8; transform: translateY(-1px);}

    /* Task Toggles */
    .task-filters { display: flex; gap: 5px; margin-bottom: 15px; background: var(--main-bg); padding: 4px; border-radius: 10px; display: inline-flex;}
    .t-filt-btn { background: transparent; border: none; padding: 6px 12px; font-size: 0.8rem; font-weight: 600; border-radius: 6px; cursor: pointer; color: var(--text-light); transition: 0.2s;}
    .t-filt-btn.active { background: var(--card-bg); color: var(--text-dark); box-shadow: var(--soft-shadow);}

    /* Task Progress */
    .task-prog-wrap { width: 100%; height: 6px; background: var(--main-bg); border-radius: 3px; overflow: hidden; margin-bottom: 20px;}
    .task-prog-fill { height: 100%; background: #10b981; transition: width 0.4s ease;}

    /* Color Coded Tasks */
    .todo-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 10px;}
    .todo-item:hover { border-color: var(--border-light); transform: translateX(2px);}
    .pri-high { border-left: 4px solid #ef4444; }
    .pri-med { border-left: 4px solid #f59e0b; }
    .pri-low { border-left: 4px solid #10b981; }
    .todo-check { width: 18px; height: 18px; cursor: pointer; accent-color: var(--text-dark);}
    .t-text { font-size: 0.9rem; font-weight: 500; margin-left: 10px; flex:1; color: var(--text-dark);}
    .todo-del { color: var(--text-light); background: transparent; border: none; cursor: pointer; font-size: 1rem; transition:0.2s; padding: 4px;}
    .todo-del:hover { color: #ef4444; }

    /* System Logs & Alerts */
    .log-item { display: flex; align-items: flex-start; gap: 15px; padding: 15px; border-radius: 12px; transition: 0.2s; text-decoration: none;}
    .log-item:hover { background: var(--main-bg); }
    .log-icon { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-grid); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: var(--text-dark); flex-shrink: 0;}
    .log-time { font-size: 0.75rem; color: var(--text-light); margin-bottom: 4px; font-weight: 500;}
    .log-action { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); line-height: 1.3;}

    /* Live Counters */
    .live-counter-wrap { display: flex; justify-content: space-between; padding: 15px; background: var(--main-bg); border-radius: 12px; margin-bottom: 15px; align-items: center; border: 1px solid var(--border-color);}
    .lc-label { font-size: 0.85rem; font-weight: 600; color: var(--text-light); text-transform: uppercase;}
    .lc-value { font-family: monospace; font-size: 1.5rem; font-weight: 800; color: var(--text-dark);}
    
    /* Pulse Rings */
    .pulse-ring { width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block; margin-right: 8px; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); animation: pulsing 2s infinite;}
    @keyframes pulsing { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

    /* Empty States */
    .empty-state { text-align: center; padding: 40px 20px; color: var(--text-light); }
    .empty-state i { font-size: 2.5rem; opacity: 0.3; margin-bottom: 15px; }
    
    /* Emergency Strip */
    .alert-strip { background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-left: 4px solid #ef4444; border-radius: 12px; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; animation: slideDown 0.4s ease;}
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Global Lockdown Overlay */
    #lockdownOverlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(239,68,68,0.1); z-index:9998; pointer-events:none; display:none; animation: flashRed 2s infinite;}
    @keyframes flashRed { 0%, 100% { background:rgba(239,68,68,0.05); } 50% { background:rgba(239,68,68,0.2); } }
</style>

<div id="lockdownOverlay"></div>

<?php if($has_alert): ?>
<div class="alert-strip" id="alertStrip">
    <div style="display: flex; align-items: center; gap: 15px;">
        <i class="fas fa-exclamation-triangle" style="font-size:1.5rem; color:#ef4444;"></i>
        <div>
            <div style="font-size:0.95rem; font-weight:600; color:var(--text-dark);"><?= htmlspecialchars($alert_data['alert_type']) ?> Alert</div>
            <div style="font-size:0.85rem; color:var(--text-light); margin-top:2px;"><?= htmlspecialchars($alert_data['severity']) ?> detected in <?= htmlspecialchars($alert_data['location']) ?>. Please review protocols.</div>
        </div>
    </div>
    <a href="disasters.php" style="color:#ef4444; font-weight:600; font-size:0.9rem; text-decoration:none;">View Matrix</a>
</div>
<?php endif; ?>

<div class="hero-panel">
    <div>
        <h1 class="hero-greeting skeleton"><?= $greeting ?>, Admin.</h1>
        <div class="hero-sub skeleton">System performance is optimal. Here is your campus overview.</div>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn-action" onclick="exportDashboard()"><i class="fas fa-print"></i> Report</button>
        <button class="btn-action" style="color:#ef4444; border-color:rgba(239,68,68,0.3);" onclick="triggerLockdown()"><i class="fas fa-shield-alt"></i> Lockdown</button>
    </div>
</div>

<div class="qa-strip">
    <a href="students.php" class="qa-btn"><i class="fas fa-user-plus" style="color:#3b82f6;"></i> Enroll Scholar</a>
    <a href="it_tickets.php" class="qa-btn"><i class="fas fa-ticket-alt" style="color:#f59e0b;"></i> Open IT Ticket</a>
    <a href="billing.php" class="qa-btn"><i class="fas fa-file-invoice-dollar" style="color:#10b981;"></i> Draft Invoice</a>
    <a href="events.php" class="qa-btn"><i class="fas fa-calendar-plus" style="color:#ec4899;"></i> Schedule Event</a>
    <a href="rooms.php" class="qa-btn"><i class="fas fa-door-open" style="color:#8b5cf6;"></i> Book Space</a>
</div>

<div class="metric-grid">
    <a href="students.php" class="metric-card">
        <div class="mc-top">
            <div class="mc-icon"><i class="fas fa-users"></i></div>
            <div class="mc-trend"><i class="fas fa-arrow-up"></i> 4%</div>
        </div>
        <div>
            <div class="mc-val skeleton"><?= number_format($student_count) ?></div>
            <div class="mc-lbl skeleton">Total Scholars</div>
            <div class="avatar-stack skeleton">
                <div class="avatar-circ">JD</div>
                <div class="avatar-circ" style="background:#10b981;">AM</div>
                <div class="avatar-circ" style="background:#3b82f6;">+8</div>
            </div>
        </div>
    </a>
    
    <a href="employees.php" class="metric-card">
        <div class="mc-top">
            <div class="mc-icon" style="color:#10b981;"><i class="fas fa-user-tie"></i></div>
        </div>
        <div>
            <div class="mc-val skeleton"><?= number_format($employee_count) ?></div>
            <div class="mc-lbl skeleton">Active Faculty</div>
            <div style="font-size:0.8rem; font-weight:600; color:var(--text-light); margin-top:10px;" class="skeleton">
                <span class="pulse-ring"></span> 24 On Campus
            </div>
        </div>
    </a>
    
    <a href="billing.php" class="metric-card">
        <div class="mc-top">
            <div class="mc-icon" style="color:#f59e0b;"><i class="fas fa-chart-line"></i></div>
            <div class="mc-trend" style="color:#f59e0b; background:rgba(245,158,11,0.1);"><i class="fas fa-arrow-up"></i> 12%</div>
        </div>
        <div>
            <div class="mc-val skeleton" style="font-size:1.8rem;">₱<?= number_format($total_revenue / 1000, 1) ?>K</div>
            <div class="mc-lbl skeleton">Cleared Revenue</div>
        </div>
    </a>

    <a href="library.php" class="metric-card">
        <div class="mc-top">
            <div class="mc-icon" style="color:#8b5cf6;"><i class="fas fa-book-open"></i></div>
        </div>
        <div>
            <div class="mc-val skeleton"><?= number_format($lib_loans) ?></div>
            <div class="mc-lbl skeleton">Active Book Loans</div>
            <div style="font-size:0.8rem; font-weight:600; color:#ef4444; margin-top:10px;" class="skeleton">
                <i class="fas fa-exclamation-circle"></i> 14 Overdue
            </div>
        </div>
    </a>
    
    <div class="metric-card">
        <div class="mc-top">
            <div class="mc-icon" style="color:#ec4899;"><i class="fas fa-door-open"></i></div>
        </div>
        <div>
            <div class="mc-val skeleton">84%</div>
            <div class="mc-lbl skeleton">Campus Occupancy</div>
            <div class="task-prog-wrap skeleton" style="margin:10px 0 0; height:4px;">
                <div class="task-prog-fill" style="width:84%; background:#ec4899;"></div>
            </div>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="mc-top">
            <div class="mc-icon" style="color:#3b82f6;"><i class="fas fa-hamburger"></i></div>
        </div>
        <div>
            <div class="mc-val skeleton" style="font-size:1.4rem; letter-spacing:0; line-height:1.2; margin-bottom:10px;"><?= htmlspecialchars($trend_food) ?></div>
            <div class="mc-lbl skeleton">Cafeteria Trending</div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dash-col" id="col-left">
        
        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-chart-area"></i> Revenue vs Expenses</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body skeleton" style="height: 250px; width: 100%;">
                <canvas id="mainFinanceChart"></canvas>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-chart-pie"></i> Scholar Demographics</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body skeleton" style="height: 220px; width: 100%;">
                <canvas id="deptChart"></canvas>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-history"></i> Live Neural Logs</div>
                <div class="widget-controls">
                    <a href="?clear_logs=true" style="color:#ef4444; font-size:0.8rem; font-weight:600; text-decoration:none; margin-right:10px;">PURGE</a>
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body">
                <div class="scroll-list" id="sysLogContainer" style="height: 320px;">
                    <?php
                    $logs_check = @mysqli_query($conn, "SHOW TABLES LIKE 'system_logs'");
                    if($logs_check && mysqli_num_rows($logs_check) > 0) {
                        $logs = mysqli_query($conn, "SELECT * FROM system_logs ORDER BY log_time DESC LIMIT 25");
                        if($logs && mysqli_num_rows($logs) > 0) {
                            while($l = mysqli_fetch_assoc($logs)) {
                                $action = htmlspecialchars($l['action']);
                                $ip = htmlspecialchars($l['ip_address']);
                                $icon = strpos(strtolower($action), 'delete') !== false ? 'fa-trash' : (strpos(strtolower($action), 'status') !== false ? 'fa-sync' : 'fa-bolt');
                                
                                echo "
                                <div class='log-item skeleton'>
                                    <div class='log-icon'><i class='fas {$icon}'></i></div>
                                    <div>
                                        <div class='log-time'>" . date('M d, Y • h:i:s A', strtotime($l['log_time'])) . " <span style='font-family:monospace; margin-left:10px; opacity:0.5;'>[{$ip}]</span></div>
                                        <div class='log-action'>{$action}</div>
                                    </div>
                                </div>";
                            }
                        } else { 
                            echo "<div class='empty-state skeleton'><i class='fas fa-wind'></i><div style='font-weight:600;'>No logs generated yet.</div></div>"; 
                        }
                    } else { 
                        echo "<div class='empty-state skeleton'><i class='fas fa-database'></i><div style='font-weight:600;'>Log table missing.</div></div>"; 
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-tools"></i> Equipment Maintenance</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body skeleton">
                <?php if($maint_rooms > 0): ?>
                    <div class="log-item">
                        <div class="log-icon" style="color:#ef4444; border-color:#ef4444;"><i class="fas fa-wrench"></i></div>
                        <div>
                            <div class="log-time" style="color:#ef4444; font-weight:700;">CRITICAL PRIORITY</div>
                            <div class="log-action">There are <?= $maint_rooms ?> facilities currently marked for immediate maintenance.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color:#10b981; opacity:0.7;"></i>
                        <div style="font-weight:600; margin-top:10px; color:var(--text-dark);">All equipment functional.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
    
    <div class="dash-col" id="col-right">

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-tasks"></i> Executive Tasks</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body skeleton">
                
                <div class="todo-input-wrap">
                    <input type="text" id="todoInput" placeholder="Write a new task..." onkeypress="if(event.key==='Enter') addTask()">
                    <select id="todoPri" class="todo-pri-sel">
                        <option value="pri-low">Low</option>
                        <option value="pri-med">Med</option>
                        <option value="pri-high">High</option>
                    </select>
                    <button class="todo-add-btn" onclick="addTask()"><i class="fas fa-plus"></i> Add</button>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div class="task-filters">
                        <button class="t-filt-btn active" onclick="filterTasks('all', this)">All</button>
                        <button class="t-filt-btn" onclick="filterTasks('active', this)">Active</button>
                        <button class="t-filt-btn" onclick="filterTasks('done', this)">Done</button>
                    </div>
                    <div style="font-size:0.8rem; font-weight:700; color:var(--text-light);" id="taskProgText">0% Complete</div>
                </div>
                
                <div class="task-prog-wrap">
                    <div class="task-prog-fill" id="taskProgFill" style="width:0%;"></div>
                </div>
                
                <div class="scroll-list" id="todoList" style="height: 350px;">
                    </div>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-server"></i> Server Telemetry</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body skeleton">
                <div class="live-counter-wrap">
                    <div class="lc-label">System Uptime</div>
                    <div class="lc-value" id="uptimeCounter">00:00:00</div>
                </div>
                <div class="live-counter-wrap">
                    <div class="lc-label"><span class="pulse-ring"></span> Live Bandwidth</div>
                    <div class="lc-value" id="bwCounter">45 MB/s</div>
                </div>
                <div style="font-size:0.8rem; color:var(--text-light); text-align:center; font-weight:600; margin-top:20px;">
                    Admin on Duty: <strong style="color:var(--text-dark);">SYS-ADMIN-01</strong>
                </div>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-bullseye"></i> Goal Tracker</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body skeleton">
                <div style="display:flex; justify-content:space-between; font-size:0.9rem; font-weight:600; margin-bottom:8px;">
                    <span>Q4 Enrollment Target (5,000)</span>
                    <span><?= number_format($student_count) ?></span>
                </div>
                <?php $enroll_pct = min(100, ($student_count / 5000) * 100); ?>
                <div class="task-prog-wrap" style="height:8px;">
                    <div class="task-prog-fill" style="width:<?= $enroll_pct ?>%; background:#3b82f6;"></div>
                </div>
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-folder-open"></i> Recent Uploads</div>
                <div class="widget-controls">
                    <button class="w-ctrl-btn" onclick="toggleWidgetBody(this)"><i class="fas fa-chevron-up"></i></button>
                </div>
            </div>
            <div class="widget-body scroll-list skeleton" style="max-height: 200px;">
                <div class="log-item">
                    <div class="log-icon"><i class="fas fa-file-pdf" style="color:#ef4444;"></i></div>
                    <div><div class="log-time">10 mins ago</div><div class="log-action">Q3_Financial_Audit.pdf</div></div>
                </div>
                <div class="log-item">
                    <div class="log-icon"><i class="fas fa-file-excel" style="color:#10b981;"></i></div>
                    <div><div class="log-time">1 hour ago</div><div class="log-action">CS_Batch_Grades.xlsx</div></div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Skeleton Load Removal Simulation
document.addEventListener("DOMContentLoaded", () => {
    // Wrap initializations to prevent blocking errors
    try { initCharts(); } catch(e) { console.error("Chart Error:", e); }
    try { renderTasks(); } catch(e) { console.error("Task Error:", e); }
    try { startTelemetry(); } catch(e) { console.error("Telemetry Error:", e); }
    
    setTimeout(() => {
        document.querySelectorAll('.skeleton').forEach(el => el.classList.remove('skeleton'));
    }, 800);
});

// WIDGET COLLAPSE
function toggleWidgetBody(btn) {
    const body = btn.closest('.widget-card').querySelector('.widget-body');
    const icon = btn.querySelector('i');
    if(body.style.display === 'none') {
        body.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
    } else {
        body.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
    }
}

// CHARTS INITIALIZATION
let mainFinChart, deptChart;
function initCharts() {
    const getChartColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#0f172a';
    const getGridColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    const ctxFin = document.getElementById('mainFinanceChart');
    if (ctxFin) {
        mainFinChart = new Chart(ctxFin.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [
                    { label: 'Revenue', data: [12, 19, 15, 22, 28, 25, 30], borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 3, fill: true, tension: 0.4 },
                    { label: 'Expenses', data: [8, 12, 10, 15, 18, 20, 22], borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', borderWidth: 3, fill: true, tension: 0.4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: {color: getChartColor(), font:{family:"'Inter', sans-serif"}} } },
                scales: { 
                    y: { grid: { color: getGridColor(), drawBorder: false }, ticks:{color:getChartColor()} }, 
                    x: { grid: { display: false, drawBorder: false }, ticks:{color:getChartColor()} } 
                }
            }
        });
    }

    const ctxDept = document.getElementById('deptChart');
    if (ctxDept) {
        let labels = <?= $dept_labels ? $dept_labels : "[]" ?>;
        let data = <?= $dept_data ? $dept_data : "[]" ?>;
        
        if (labels.length === 0) {
            labels = ['No Data'];
            data = [1];
        }

        deptChart = new Chart(ctxDept.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#cbd5e1'], borderWidth: 0 }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels:{color: getChartColor(), font:{family:"'Inter', sans-serif"}}} },
                cutout: '75%'
            }
        });
    }
}

// ADVANCED TASK MANAGER LOGIC (FIXED)
let tasks = JSON.parse(localStorage.getItem('campus_admin_tasks') || '[]');
let currentTaskFilter = 'all';

function renderTasks() {
    const list = document.getElementById('todoList');
    if(!list) return;
    
    let filtered = tasks;
    if(currentTaskFilter === 'active') filtered = tasks.filter(t => !t.done);
    if(currentTaskFilter === 'done') filtered = tasks.filter(t => t.done);
    
    if(filtered.length === 0) { 
        list.innerHTML = `
            <div class="empty-state" style="padding: 30px; text-align: center; color: var(--text-light);">
                <i class="fas fa-clipboard-check" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 15px;"></i>
                <div style="font-weight:600;">No tasks found.</div>
            </div>`;
    } else {
        list.innerHTML = filtered.map((t, idx) => {
            const originalIndex = tasks.indexOf(t);
            return `
            <div class="todo-item ${t.done ? 'done' : ''} ${t.pri}">
                <div style="display:flex; align-items:center; gap:12px; flex:1;">
                    <input type="checkbox" class="todo-check" ${t.done ? 'checked' : ''} onchange="toggleTask(${originalIndex})">
                    <span class="t-text" style="${t.done ? 'text-decoration:line-through; opacity:0.7;' : ''}">${t.text}</span>
                </div>
                <button class="todo-del" onclick="deleteTask(${originalIndex})"><i class="fas fa-times"></i></button>
            </div>
            `;
        }).join('');
    }
    
    // Calculate Progress
    const total = tasks.length;
    const done = tasks.filter(t => t.done).length;
    const pct = total === 0 ? 0 : Math.round((done / total) * 100);
    
    const progFill = document.getElementById('taskProgFill');
    const progText = document.getElementById('taskProgText');
    if(progFill) progFill.style.width = pct + '%';
    if(progText) progText.innerText = pct + '% Complete';
}

function addTask() {
    const input = document.getElementById('todoInput');
    const pri = document.getElementById('todoPri').value;
    if(!input || !input.value.trim()) return;
    
    tasks.unshift({ text: input.value.trim(), pri: pri, done: false });
    localStorage.setItem('campus_admin_tasks', JSON.stringify(tasks));
    input.value = '';
    renderTasks();
}

function toggleTask(idx) {
    if(tasks[idx]) {
        tasks[idx].done = !tasks[idx].done;
        localStorage.setItem('campus_admin_tasks', JSON.stringify(tasks));
        renderTasks();
    }
}

function deleteTask(idx) {
    if(tasks[idx]) {
        tasks.splice(idx, 1);
        localStorage.setItem('campus_admin_tasks', JSON.stringify(tasks));
        renderTasks();
    }
}

function filterTasks(type, btn) {
    currentTaskFilter = type;
    document.querySelectorAll('.t-filt-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    renderTasks();
}

// LIVE TELEMETRY LOGIC
function startTelemetry() {
    // Uptime
    let sec = parseInt(localStorage.getItem('campus_uptime') || '36000');
    setInterval(() => {
        sec++;
        localStorage.setItem('campus_uptime', sec);
        let h = Math.floor(sec / 3600).toString().padStart(2, '0');
        let m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
        let s = (sec % 60).toString().padStart(2, '0');
        const el = document.getElementById('uptimeCounter');
        if(el) el.innerText = `${h}:${m}:${s}`;
    }, 1000);
    
    // Bandwidth
    setInterval(() => {
        const el = document.getElementById('bwCounter');
        if(el) el.innerText = Math.floor(20 + Math.random() * 80) + " MB/s";
    }, 2000);
}

// Export Dashboard
function exportDashboard() {
    if(typeof systemToast === 'function') systemToast("Preparing document formatting...");
    setTimeout(() => { window.print(); }, 1000);
}

// Lockdown Protocol
function triggerLockdown() {
    if(confirm("CRITICAL WARNING: Initiate campus lockdown? This will lock all digital access doors.")) {
        const overlay = document.getElementById('lockdownOverlay');
        if(overlay) {
            overlay.style.display = 'block';
            if(typeof systemToast === 'function') systemToast("LOCKDOWN PROTOCOL INITIATED.");
            // Simulated auto-resolve
            setTimeout(() => {
                overlay.style.display = 'none';
                if(typeof systemToast === 'function') systemToast("Lockdown lifted. All clear.");
            }, 8000);
        }
    }
}
</script>

<?php include 'footer.php'; ?>