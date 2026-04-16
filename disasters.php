<?php 
include 'config.php'; 

$patch1 = "CREATE TABLE IF NOT EXISTS emergency_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(50),
    severity VARCHAR(20),
    location VARCHAR(150),
    message TEXT,
    source VARCHAR(50),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Active',
    ext_id VARCHAR(100) UNIQUE NULL
)";
try { mysqli_query($conn, $patch1); } catch (Exception $e) {}

$patch2 = "CREATE TABLE IF NOT EXISTS safety_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) UNIQUE,
    user_name VARCHAR(100),
    role VARCHAR(50),
    condition_status VARCHAR(50) DEFAULT 'Unreachable',
    location_ping VARCHAR(150),
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
)";
try { mysqli_query($conn, $patch2); } catch (Exception $e) {}

try { mysqli_query($conn, "ALTER TABLE emergency_alerts ADD COLUMN ext_id VARCHAR(100) UNIQUE NULL"); } catch (Exception $e) {}

function fetchLiveAlerts($conn) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CampusPro-Matrix/1.0');

    curl_setopt($ch, CURLOPT_URL, "https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&minlatitude=4.5&maxlatitude=21.5&minlongitude=116.0&maxlongitude=127.0&limit=3");
    $eq_data = curl_exec($ch);
    if($eq_data) {
        $eq_json = json_decode($eq_data, true);
        if(isset($eq_json['features'])) {
            foreach($eq_json['features'] as $eq) {
                $ext_id = "USGS-" . $eq['id'];
                $mag = floatval($eq['properties']['mag']);
                $place = mysqli_real_escape_string($conn, $eq['properties']['place']);
                $time = date('Y-m-d H:i:s', $eq['properties']['time'] / 1000);
                
                $sev = 'Level 2';
                if($mag >= 4.5) $sev = 'Level 3';
                if($mag >= 6.0) $sev = 'Level 4';
                
                $msg = "Magnitude {$mag} seismic event detected. Structural assessment recommended if felt on campus.";
                
                mysqli_query($conn, "INSERT IGNORE INTO emergency_alerts (alert_type, severity, location, message, source, timestamp, status, ext_id) VALUES ('Earthquake', '$sev', '$place', '$msg', 'PHIVOLCS / USGS Live', '$time', 'Active', '$ext_id')");
            }
        }
    }

    curl_setopt($ch, CURLOPT_URL, "https://api.reliefweb.int/v1/reports?appname=campus_pro&query[value]=Philippines&sort[]=date:desc&limit=3&profile=list");
    $rw_data = curl_exec($ch);
    if($rw_data) {
        $rw_json = json_decode($rw_data, true);
        if(isset($rw_json['data'])) {
            foreach($rw_json['data'] as $rw) {
                $ext_id = "RW-" . $rw['id'];
                $title = mysqli_real_escape_string($conn, $rw['fields']['title']);
                $time = date('Y-m-d H:i:s', strtotime($rw['fields']['date']['created']));
                
                $type = 'General Alert';
                if(stripos($title, 'Typhoon') !== false || stripos($title, 'Storm') !== false) $type = 'Typhoon';
                if(stripos($title, 'Flood') !== false) $type = 'Flood';
                if(stripos($title, 'Volcano') !== false) $type = 'Volcanic Ash';
                
                mysqli_query($conn, "INSERT IGNORE INTO emergency_alerts (alert_type, severity, location, message, source, timestamp, status, ext_id) VALUES ('$type', 'Level 2', 'Philippine Regional Alert', '$title', 'NDRRMC / ReliefWeb Live', '$time', 'Active', '$ext_id')");
            }
        }
    }
    curl_close($ch);
}

fetchLiveAlerts($conn);

if(isset($_GET['del_alert'])) {
    $id = intval($_GET['del_alert']);
    mysqli_query($conn, "UPDATE emergency_alerts SET status='Resolved' WHERE id=$id");
    header("Location: disasters.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_safety'])) {
    $uid = mysqli_real_escape_string($conn, $_POST['user_id']);
    $unm = mysqli_real_escape_string($conn, $_POST['user_name']);
    $rol = mysqli_real_escape_string($conn, $_POST['role']);
    $cnd = mysqli_real_escape_string($conn, $_POST['condition_status']);
    $loc = mysqli_real_escape_string($conn, $_POST['location_ping']);
    
    $check = mysqli_query($conn, "SELECT id FROM safety_status WHERE user_id='$uid'");
    if(mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE safety_status SET condition_status='$cnd', location_ping='$loc', last_updated=NOW() WHERE user_id='$uid'");
    } else {
        mysqli_query($conn, "INSERT INTO safety_status (user_id, user_name, role, condition_status, location_ping, last_updated) VALUES ('$uid', '$unm', '$rol', '$cnd', '$loc', NOW())");
    }
    header("Location: disasters.php"); exit();
}

$check_alerts = mysqli_query($conn, "SELECT COUNT(*) as c FROM emergency_alerts");
if(mysqli_fetch_assoc($check_alerts)['c'] == 0) {
    $alerts = [
        ['Earthquake', 'Level 4', 'Occidental Mindoro (Felt in Campus)', 'Magnitude 5.4 tectonic earthquake detected. Assess structural integrity.', 'PHIVOLCS'],
        ['Typhoon', 'Level 2', 'Western Visayas Sector', 'Tropical Storm approaching. Expected heavy rainfall and potential localized flooding.', 'PAGASA'],
        ['Fire', 'Level 1', 'North Wing Perimeter', 'Brush fire reported near the external campus perimeter. Security dispatched.', 'BFP'],
        ['Flood', 'Level 3', 'Lower Campus Road', 'Rapid water level rise detected. Evacuate ground floor facilities immediately.', 'NDRRMC']
    ];
    foreach($alerts as $a) {
        mysqli_query($conn, "INSERT INTO emergency_alerts (alert_type, severity, location, message, source, status) VALUES ('{$a[0]}', '{$a[1]}', '{$a[2]}', '{$a[3]}', '{$a[4]}', 'Active')");
    }
}

$check_safety = mysqli_query($conn, "SELECT COUNT(*) as c FROM safety_status");
if(mysqli_fetch_assoc($check_safety)['c'] == 0) {
    $names = ['James Smith', 'Mary Johnson', 'John Williams', 'Patricia Brown', 'Robert Jones', 'Jennifer Garcia', 'Alan Turing', 'Grace Hopper', 'Marcus Vance', 'Sarah Jenkins'];
    $roles = ['Student', 'Student', 'Student', 'Faculty', 'Staff', 'Student', 'Faculty', 'Faculty', 'Staff', 'Student'];
    $conds = ['Safe', 'Safe', 'Needs Assistance', 'Safe', 'Unreachable', 'Safe', 'Safe', 'Unreachable', 'Safe', 'Needs Assistance'];
    
    for($i=0; $i<10; $i++) {
        $uid = ($roles[$i] == 'Student' ? 'S26-' : 'FAC-') . strtoupper(substr(md5(uniqid()), 0, 6));
        $unm = mysqli_real_escape_string($conn, $names[$i]);
        $rol = $roles[$i];
        $cnd = $conds[$i];
        $loc = $cnd == 'Safe' ? 'Evacuation Area A' : ($cnd == 'Needs Assistance' ? 'Main Building, 3rd Floor' : 'Unknown');
        mysqli_query($conn, "INSERT INTO safety_status (user_id, user_name, role, condition_status, location_ping, last_updated) VALUES ('$uid', '$unm', '$rol', '$cnd', '$loc', NOW())");
    }
}

include 'header.php';

$tot_alerts = getCount($conn, 'emergency_alerts', "WHERE status='Active'");
$tot_safe = getCount($conn, 'safety_status', "WHERE condition_status='Safe'");
$tot_danger = getCount($conn, 'safety_status', "WHERE condition_status='Needs Assistance'");
$tot_mia = getCount($conn, 'safety_status', "WHERE condition_status='Unreachable'");
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: #ef4444; }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: #ef4444; opacity: 0.9; }
    .stat-val { font-size: 2.4rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    
    .status-safe { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; animation: pulseRed 2s infinite; }
    .status-mia { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    
    @keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

    .alert-banner { display:flex; flex-direction:column; gap:15px; margin-bottom: 40px; }
    .live-alert-card { background: var(--card-bg); border: 2px solid #ef4444; border-left: 8px solid #ef4444; border-radius: 10px; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 20px rgba(239, 68, 68, 0.1); }
    .live-indicator { display: inline-block; width: 12px; height: 12px; background: #ef4444; border-radius: 50%; margin-right: 10px; animation: blink 1s infinite; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

    .id-box { font-weight:900; font-family:monospace; font-size:1.1rem; color:#ef4444; background:var(--main-bg); border: 2px solid var(--border-color); padding:4px 10px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: #ef4444; color: #fff; font-weight: 900;}

    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .data-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: #ef4444; }
    
    .dc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .dc-title { font-family: var(--heading-font); font-size: 1.3rem; font-weight: 900; color: var(--text-dark); margin-bottom: 8px; line-height: 1.3;}
    .dc-detail { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 8px; font-weight: 600;}
    .dc-detail i { color: #ef4444; width: 16px; text-align: center;}
    .dc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #ef4444;">
    <h1 style="color: #ef4444; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Disaster Risk Management</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Live environmental telemetry and definitive campus personnel safety tracking.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-broadcast-tower stat-icon"></i>
        <div>
            <div class="stat-val"><?= $tot_alerts ?></div>
            <div class="stat-lbl">Active Telemetry Alerts</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-exclamation-triangle stat-icon" style="color:#ef4444;"></i>
        <div>
            <div class="stat-val" style="color:#ef4444;"><?= $tot_danger ?></div>
            <div class="stat-lbl">In Danger / Needs Help</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-question-circle stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $tot_mia ?></div>
            <div class="stat-lbl">Currently Unreachable</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-shield-check stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $tot_safe ?></div>
            <div class="stat-lbl">Confirmed Safe</div>
        </div>
    </div>
</div>

<div class="alert-banner">
    <?php
    $alert_res = mysqli_query($conn, "SELECT * FROM emergency_alerts WHERE status='Active' ORDER BY timestamp DESC");
    $now = new DateTime();
    
    while($al = mysqli_fetch_assoc($alert_res)) {
        $icon = 'fa-exclamation-circle';
        if(stripos($al['alert_type'], 'Earthquake') !== false) $icon = 'fa-house-damage';
        if(stripos($al['alert_type'], 'Typhoon') !== false || stripos($al['alert_type'], 'Storm') !== false) $icon = 'fa-wind';
        if(stripos($al['alert_type'], 'Fire') !== false) $icon = 'fa-fire-alt';
        if(stripos($al['alert_type'], 'Flood') !== false) $icon = 'fa-water';
        
        $alert_time = new DateTime($al['timestamp']);
        $diff = $now->diff($alert_time);
        
        $time_str = "";
        if($diff->d > 0) $time_str .= $diff->d . " days ";
        if($diff->h > 0) $time_str .= $diff->h . " hrs ";
        if($diff->i > 0) $time_str .= $diff->i . " mins ";
        if(empty($time_str)) $time_str = "Just now";
        else $time_str .= "ago";
        
        echo "
        <div class='live-alert-card'>
            <div>
                <div style='font-size:0.85rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:8px; letter-spacing:1px;'><span class='live-indicator'></span>LIVE FEED: {$al['source']}</div>
                <div style='font-family:var(--heading-font); font-size:1.5rem; font-weight:900; color:var(--text-dark); margin-bottom:8px;'><i class='fas {$icon}' style='color:#ef4444; margin-right:10px;'></i> {$al['alert_type']} Warning ({$al['severity']})</div>
                <div style='font-size:1rem; font-weight:600; color:var(--text-dark); max-width:800px; line-height:1.5;'>{$al['message']}</div>
                <div style='font-size:0.85rem; font-weight:800; color:var(--brand-secondary); margin-top:8px;'><i class='fas fa-map-marker-alt'></i> {$al['location']}</div>
            </div>
            <div style='text-align:right;'>
                <div style='font-size:0.85rem; font-weight:900; color:#ef4444; margin-bottom:15px;'><i class='far fa-clock'></i> Updated {$time_str}</div>
                <a href='?del_alert={$al['id']}' class='btn-action' style='padding:8px 15px; border-color:var(--border-light);'><i class='fas fa-check'></i> Mark Resolved</a>
            </div>
        </div>";
    }
    if(mysqli_num_rows($alert_res) == 0) {
        echo "<div class='live-alert-card' style='border-color:#10b981; border-left-color:#10b981;'><div style='font-family:var(--heading-font); font-size:1.5rem; font-weight:900; color:var(--text-dark);'><i class='fas fa-shield-alt' style='color:#10b981; margin-right:10px;'></i> No Active Regional Alerts</div></div>";
    }
    ?>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchSafetyLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Name or ID..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterMatrix()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Conditions">All Conditions</option>
            <option value="Needs Assistance">Needs Assistance</option>
            <option value="Unreachable">Unreachable</option>
            <option value="Safe">Safe</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" style="border-color:#ef4444; color:#ef4444;" onclick="systemToast('Executing Emergency Mass SMS Ping...')"><i class="fas fa-satellite-dish"></i> Ping All Unreachable</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#ef4444; border-color:#ef4444; color:#fff;" onclick="openModal()"><i class="fas fa-heartbeat"></i> Update Status</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:1%;">Identity Tag</th>
                <th>Personnel Name & Role</th>
                <th style="width:25%;">Last Known Location</th>
                <th>Safety Condition</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM safety_status ORDER BY CASE condition_status WHEN 'Needs Assistance' THEN 1 WHEN 'Unreachable' THEN 2 ELSE 3 END, last_updated DESC");
            $all_data = [];
            while($row = mysqli_fetch_assoc($res)) {
                $all_data[] = $row;
                $cnd = $row['condition_status'];
                if($cnd == 'Safe') $stat_class = 'status-safe';
                elseif($cnd == 'Needs Assistance') $stat_class = 'status-danger';
                else $stat_class = 'status-mia';
                
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $upd_time = new DateTime($row['last_updated']);
                $diff = $now->diff($upd_time);
                $upd_str = "";
                if($diff->d > 0) $upd_str .= $diff->d . "d ";
                if($diff->h > 0) $upd_str .= $diff->h . "h ";
                if($diff->i > 0) $upd_str .= $diff->i . "m ";
                if(empty($upd_str)) $upd_str = "Just now";
                else $upd_str .= "ago";
                
                echo "
                <tr class='filter-target' data-stat='{$cnd}'>
                    <td><div class='id-box'>{$row['user_id']}</div></td>
                    <td><strong style='color:var(--text-dark); font-size:1.1rem;'>{$row['user_name']}</strong><br><span style='font-size:0.8rem; color:var(--text-light); font-weight:800; text-transform:uppercase;'>{$row['role']}</span></td>
                    <td>
                        <div style='font-weight:900; color:var(--text-dark); font-size:0.9rem;'><i class='fas fa-map-marker-alt' style='color:#ef4444; margin-right:6px;'></i> {$row['location_ping']}</div>
                        <div style='font-size:0.75rem; color:var(--text-light); font-weight:700; margin-top:6px;'><i class='far fa-clock'></i> Logged: {$upd_str}</div>
                    </td>
                    <td><span class='status-pill {$stat_class}'>{$cnd}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
                        </div>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="gridView" class="data-grid" style="display:none;">
    <?php
    foreach($all_data as $row) {
        $cnd = $row['condition_status'];
        if($cnd == 'Safe') { $stat_class = 'status-safe'; $bdr_color = '#10b981'; $icon = 'fa-shield-check';}
        elseif($cnd == 'Needs Assistance') { $stat_class = 'status-danger'; $bdr_color = '#ef4444'; $icon = 'fa-exclamation-triangle';}
        else { $stat_class = 'status-mia'; $bdr_color = '#f59e0b'; $icon = 'fa-question-circle';}
        
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        
        $upd_time = new DateTime($row['last_updated']);
        $diff = $now->diff($upd_time);
        $upd_str = "";
        if($diff->d > 0) $upd_str .= $diff->d . "d ";
        if($diff->h > 0) $upd_str .= $diff->h . "h ";
        if($diff->i > 0) $upd_str .= $diff->i . "m ";
        if(empty($upd_str)) $upd_str = "Just now";
        else $upd_str .= "ago";
        
        echo "
        <div class='data-card filter-target' style='border-top: 6px solid {$bdr_color};' data-stat='{$cnd}'>
            <div class='dc-header'>
                <div class='id-box' style='font-size:0.85rem; padding:4px 8px; color:{$bdr_color};'>{$row['user_id']}</div>
                <span class='status-pill {$stat_class}' style='font-size:0.65rem;'><i class='fas {$icon}'></i> {$cnd}</span>
            </div>
            <div class='dc-title'>{$row['user_name']}</div>
            <div style='font-size:0.85rem; font-weight:800; color:var(--text-light); margin-bottom:15px; text-transform:uppercase;'>{$row['role']}</div>
            <div class='dc-detail'><i class='fas fa-map-marker-alt' style='color:{$bdr_color};'></i> {$row['location_ping']}</div>
            <div class='dc-detail'><i class='far fa-clock' style='color:var(--text-light);'></i> Logged: {$upd_str}</div>
            <div class='dc-footer'>
                <button class='btn-action' style='width:100%; justify-content:center;' onclick='openModal($js_data)'><i class='fas fa-sync-alt'></i> Update Status</button>
            </div>
        </div>";
    }
    ?>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-heartbeat" style="color:#ef4444;"></i> Roster Status Update</h2>
        <form method="POST">
            <input type="hidden" name="save_safety" value="1">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="user_id" id="user_id" placeholder="ID (e.g. S26-XXXX)" required>
                <input type="text" name="user_name" id="user_name" placeholder="Full Name" required>
                
                <select name="role" id="role" required style="grid-column: span 2;">
                    <option value="Student">Student</option>
                    <option value="Faculty">Faculty</option>
                    <option value="Staff">Staff / Admin</option>
                </select>

                <input type="text" name="location_ping" id="location_ping" placeholder="Current Location / Room" style="grid-column: span 2;" required>
                
                <select name="condition_status" id="condition_status" required style="grid-column: span 2;">
                    <option value="Safe">Safe / Secured</option>
                    <option value="Needs Assistance">Needs Assistance / In Danger</option>
                    <option value="Unreachable">Unreachable / Unknown</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#ef4444; border-color:#ef4444; color:#fff;"><i class="fas fa-broadcast-tower"></i> Broadcast Update</button>
        </form>
    </div>
</div>

<script>
function setView(view) {
    const table = document.getElementById('tableView');
    const grid = document.getElementById('gridView');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');
    if(view === 'grid') {
        table.style.display = 'none';
        grid.style.display = 'grid';
        btnGrid.classList.add('active-view');
        btnTable.classList.remove('active-view');
        localStorage.setItem('campus_disaster_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_disaster_view', 'table');
    }
}
document.addEventListener('DOMContentLoaded', () => { setView(localStorage.getItem('campus_disaster_view') || 'table'); });

function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchSafetyLocal').value.toLowerCase();
    const targets = document.querySelectorAll('.filter-target');
    targets.forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if (sFilter !== 'All Conditions' && rStat !== sFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        if(show) { el.removeAttribute('data-hide-local'); el.style.display = ''; } 
        else { el.setAttribute('data-hide-local', 'true'); el.style.display = 'none'; }
    });
    if(typeof globalTableSearch === 'function') globalTableSearch();
}

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    if(data) {
        document.getElementById('user_id').value = data.user_id;
        document.getElementById('user_id').setAttribute('readonly', true);
        document.getElementById('user_id').style.background = 'var(--bg-grid)';
        document.getElementById('user_name').value = data.user_name;
        document.getElementById('role').value = data.role;
        document.getElementById('location_ping').value = data.location_ping;
        document.getElementById('condition_status').value = data.condition_status;
    } else {
        document.getElementById('user_id').value = '';
        document.getElementById('user_id').removeAttribute('readonly');
        document.getElementById('user_id').style.background = 'var(--main-bg)';
        document.getElementById('user_name').value = '';
        document.getElementById('role').value = 'Student';
        document.getElementById('location_ping').value = '';
        document.getElementById('condition_status').value = 'Safe';
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>