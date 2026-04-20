<?php 
include 'config.php'; 

try { mysqli_query($conn, "CREATE TABLE IF NOT EXISTS emergency_alerts (id INT AUTO_INCREMENT PRIMARY KEY, alert_type VARCHAR(50), severity VARCHAR(20), location VARCHAR(150), message TEXT, source VARCHAR(50), timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, status VARCHAR(20) DEFAULT 'Active', ext_id VARCHAR(100) UNIQUE NULL)"); } catch (Exception $e) {}
try { mysqli_query($conn, "CREATE TABLE IF NOT EXISTS safety_status (id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(20) UNIQUE, user_name VARCHAR(100), role VARCHAR(50), condition_status VARCHAR(50) DEFAULT 'Unreachable', location_ping VARCHAR(150), assigned_zone VARCHAR(50) DEFAULT 'Pending', last_updated DATETIME DEFAULT CURRENT_TIMESTAMP)"); } catch (Exception $e) {}
try { mysqli_query($conn, "CREATE TABLE IF NOT EXISTS broadcast_logs (id INT AUTO_INCREMENT PRIMARY KEY, msg TEXT, target_audience VARCHAR(50) DEFAULT 'All', ts DATETIME DEFAULT CURRENT_TIMESTAMP)"); } catch(Exception $e){}
try { mysqli_query($conn, "CREATE TABLE IF NOT EXISTS campus_incidents (id INT AUTO_INCREMENT PRIMARY KEY, reporter VARCHAR(50), incident_desc TEXT, status VARCHAR(20) DEFAULT 'Open', ts DATETIME DEFAULT CURRENT_TIMESTAMP)"); } catch(Exception $e){}
try { mysqli_query($conn, "CREATE TABLE IF NOT EXISTS building_lockdowns (id INT AUTO_INCREMENT PRIMARY KEY, building_name VARCHAR(100) UNIQUE, status VARCHAR(20) DEFAULT 'Open', last_toggled DATETIME DEFAULT CURRENT_TIMESTAMP)"); } catch(Exception $e){}
try { mysqli_query($conn, "ALTER TABLE emergency_alerts ADD COLUMN ext_id VARCHAR(100) UNIQUE NULL"); } catch (Exception $e) {}
try { mysqli_query($conn, "ALTER TABLE safety_status ADD COLUMN assigned_zone VARCHAR(50) DEFAULT 'Pending'"); } catch (Exception $e) {}

$chk_bldgs = mysqli_query($conn, "SELECT COUNT(*) as c FROM building_lockdowns");
if($chk_bldgs && mysqli_fetch_assoc($chk_bldgs)['c'] == 0) {
    $bldgs = ['Main University Library', 'Advanced Science Wing', 'Engineering Block Alpha', 'Student Union Center', 'North Campus Dormitory', 'Athletic Complex'];
    foreach($bldgs as $b) {
        mysqli_query($conn, "INSERT IGNORE INTO building_lockdowns (building_name) VALUES ('$b')");
    }
}

if(isset($_POST['quick_safe'])) {
    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    mysqli_query($conn, "UPDATE safety_status SET condition_status='Safe', last_updated=NOW() WHERE user_id='$uid'");
    header("Location: disasters.php"); exit();
}

if(isset($_POST['mark_all_safe'])) {
    mysqli_query($conn, "UPDATE safety_status SET condition_status='Safe', last_updated=NOW() WHERE condition_status != 'Safe'");
    header("Location: disasters.php"); exit();
}

if(isset($_POST['trigger_sos'])) {
    $uid = $_SESSION['user_id'] ?? 'SYS-01'; 
    mysqli_query($conn, "UPDATE safety_status SET condition_status='Needs Assistance', location_ping='EMERGENCY BEACON ACTIVATED', last_updated=NOW() WHERE user_id='$uid'");
    header("Location: disasters.php"); exit();
}

if(isset($_POST['pa_broadcast'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['pa_msg']);
    $tgt = mysqli_real_escape_string($conn, $_POST['target_audience']);
    mysqli_query($conn, "INSERT INTO broadcast_logs (msg, target_audience) VALUES ('$msg', '$tgt')");
    header("Location: disasters.php"); exit();
}

if(isset($_POST['log_incident'])) {
    $rep = $_SESSION['full_name'] ?? 'System Admin';
    $desc = mysqli_real_escape_string($conn, $_POST['incident_desc']);
    mysqli_query($conn, "INSERT INTO campus_incidents (reporter, incident_desc) VALUES ('$rep', '$desc')");
    header("Location: disasters.php"); exit();
}

if(isset($_POST['toggle_lockdown'])) {
    $bid = intval($_POST['building_id']);
    $curr = mysqli_real_escape_string($conn, $_POST['current_status']);
    $new_stat = $curr === 'Open' ? 'Locked' : 'Open';
    mysqli_query($conn, "UPDATE building_lockdowns SET status='$new_stat', last_toggled=NOW() WHERE id=$bid");
    header("Location: disasters.php"); exit();
}

function fetchLiveAlerts($conn) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CampusPro-Matrix/1.0');
    curl_setopt($ch, CURLOPT_URL, "https://eonet.gsfc.nasa.gov/api/v3/events?bbox=121.0,9.0,126.0,13.0&status=open&limit=5");
    $data = curl_exec($ch);
    if($data) {
        $json = json_decode($data, true);
        if(isset($json['events'])) {
            foreach($json['events'] as $ev) {
                $ext_id = "NASA-" . mysqli_real_escape_string($conn, $ev['id']);
                $title = mysqli_real_escape_string($conn, $ev['title']);
                $cat = isset($ev['categories'][0]['title']) ? $ev['categories'][0]['title'] : 'Geophysical Alert';
                $geom = end($ev['geometry']);
                $time = date('Y-m-d H:i:s', strtotime($geom['date']));
                $type = 'Geophysical Alert';
                $sev = 'Level 2';
                if(stripos($cat, 'Storm') !== false || stripos($cat, 'Cyclone') !== false) { $type = 'Severe Storm'; $sev = 'Level 3'; }
                if(stripos($cat, 'Volcano') !== false) { $type = 'Volcanic Activity'; $sev = 'Level 4'; }
                if(stripos($cat, 'Wildfire') !== false) { $type = 'Wildfire'; $sev = 'Level 2'; }
                if(stripos($cat, 'Earthquake') !== false) { $type = 'Seismic Event'; $sev = 'Level 3'; }
                if(stripos($cat, 'Flood') !== false) { $type = 'Flash Flood'; $sev = 'Level 3'; }
                mysqli_query($conn, "INSERT IGNORE INTO emergency_alerts (alert_type, severity, location, message, source, timestamp, status, ext_id) VALUES ('$type', '$sev', 'Regional Proximity', '$title', 'NASA EONET Telemetry', '$time', 'Active', '$ext_id')");
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
    $zone = mysqli_real_escape_string($conn, $_POST['assigned_zone']);
    
    $check = mysqli_query($conn, "SELECT id FROM safety_status WHERE user_id='$uid'");
    if(mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE safety_status SET condition_status='$cnd', location_ping='$loc', assigned_zone='$zone', last_updated=NOW() WHERE user_id='$uid'");
    } else {
        mysqli_query($conn, "INSERT INTO safety_status (user_id, user_name, role, condition_status, location_ping, assigned_zone, last_updated) VALUES ('$uid', '$unm', '$rol', '$cnd', '$loc', '$zone', NOW())");
    }
    header("Location: disasters.php"); exit();
}

$check_alerts = mysqli_query($conn, "SELECT COUNT(*) as c FROM emergency_alerts");
if(mysqli_fetch_assoc($check_alerts)['c'] == 0) {
    $alerts = [
        ['Seismic Event', 'Level 4', 'Panay Fault Line Sector', 'Simulated 5.4 tectonic earthquake detected. Assess campus structural integrity immediately.', 'PhilVolcs Relay'],
        ['Severe Storm', 'Level 2', 'Western Visayas Basin', 'Simulated Tropical Depression approaching. Secure loose external fixtures.', 'PAGASA Relay']
    ];
    foreach($alerts as $a) {
        mysqli_query($conn, "INSERT INTO emergency_alerts (alert_type, severity, location, message, source, status) VALUES ('{$a[0]}', '{$a[1]}', '{$a[2]}', '{$a[3]}', '{$a[4]}', 'Active')");
    }
}

$check_safety = mysqli_query($conn, "SELECT COUNT(*) as c FROM safety_status");
if(mysqli_fetch_assoc($check_safety)['c'] == 0) {
    $names = ['James Smith', 'Mary Johnson', 'John Williams', 'Patricia Brown', 'Robert Jones', 'Jennifer Garcia', 'Alan Turing', 'Grace Hopper', 'Marcus Vance', 'Sarah Jenkins', 'David Chen', 'Elena Rodriguez'];
    $roles = ['Student', 'Student', 'Student', 'Faculty', 'Staff', 'Student', 'Faculty', 'Faculty', 'Staff', 'Student', 'Student', 'Faculty'];
    $conds = ['Safe', 'Safe', 'Needs Assistance', 'Safe', 'Unreachable', 'Safe', 'Safe', 'Unreachable', 'Safe', 'Needs Assistance', 'Safe', 'Needs Assistance'];
    $zones = ['Zone A', 'Zone B', 'Pending', 'Zone C', 'Pending', 'Zone A', 'Zone B', 'Pending', 'Zone C', 'Pending', 'Zone A', 'Pending'];
    
    for($i=0; $i<12; $i++) {
        $uid = ($roles[$i] == 'Student' ? 'S26-' : 'FAC-') . strtoupper(substr(md5(uniqid()), 0, 6));
        $unm = mysqli_real_escape_string($conn, $names[$i]);
        $rol = $roles[$i];
        $cnd = $conds[$i];
        $zon = $zones[$i];
        $loc = $cnd == 'Safe' ? 'Evacuation Area A' : ($cnd == 'Needs Assistance' ? 'Main Building, 3rd Floor' : 'Unknown Origin');
        mysqli_query($conn, "INSERT INTO safety_status (user_id, user_name, role, condition_status, location_ping, assigned_zone, last_updated) VALUES ('$uid', '$unm', '$rol', '$cnd', '$loc', '$zon', NOW())");
    }
}

include 'header.php';

$tot_alerts = getCount($conn, 'emergency_alerts', "WHERE status='Active'");
$tot_safe = getCount($conn, 'safety_status', "WHERE condition_status='Safe'");
$tot_danger = getCount($conn, 'safety_status', "WHERE condition_status='Needs Assistance'");
$tot_mia = getCount($conn, 'safety_status', "WHERE condition_status='Unreachable'");
$tot_locked = getCount($conn, 'building_lockdowns', "WHERE status='Locked'");
?>

<style>
    :root {
        --eoc-bg: #0b1120;
        --eoc-card: #141e33;
        --eoc-border: #233554;
        --eoc-text-main: #f8fafc;
        --eoc-text-muted: #94a3b8;
        --eoc-accent: #3b82f6;
        --eoc-danger: #ef4444;
        --eoc-warning: #f59e0b;
        --eoc-safe: #10b981;
    }

    body {
        background-color: var(--eoc-bg);
        background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 20px 20px;
        color: var(--eoc-text-main);
        font-family: 'Inter', sans-serif;
    }

    .eoc-ticker {
        background: var(--eoc-danger);
        color: #fff;
        padding: 8px 0;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        display: flex;
        overflow: hidden;
        white-space: nowrap;
        box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        margin-bottom: 25px;
        border-radius: 8px;
    }
    .ticker-content { display: inline-block; padding-left: 100%; animation: ticker 25s linear infinite; }
    @keyframes ticker { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }

    .eoc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .eoc-title { font-size: 2.2rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 5px; color: var(--eoc-text-main); }
    .eoc-sub { font-size: 1rem; color: var(--eoc-text-muted); font-weight: 500; }

    .clock-display {
        background: var(--eoc-card);
        border: 1px solid var(--eoc-border);
        padding: 12px 24px;
        border-radius: 12px;
        font-family: monospace;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--eoc-accent);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .bento-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 25px;
        align-items: start;
    }
    @media (max-width: 1200px) { .bento-grid { grid-template-columns: 1fr; } }

    .eoc-panel {
        background: var(--eoc-card);
        border: 1px solid var(--eoc-border);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--eoc-border);
        padding-bottom: 15px;
    }
    .panel-title { font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 10px; color: var(--eoc-text-main); text-transform: uppercase; letter-spacing: 1px; }

    .metric-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
    @media (max-width: 800px) { .metric-row { grid-template-columns: repeat(2, 1fr); } }
    
    .metric-box {
        background: rgba(0,0,0,0.2);
        border: 1px solid var(--eoc-border);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        transition: 0.3s;
    }
    .metric-box:hover { transform: translateY(-3px); border-color: var(--eoc-accent); }
    .m-val { font-size: 2.2rem; font-weight: 900; margin-bottom: 5px; line-height: 1; }
    .m-lbl { font-size: 0.75rem; font-weight: 700; color: var(--eoc-text-muted); text-transform: uppercase; letter-spacing: 1px; }

    .map-container {
        height: 300px;
        background: #000;
        border-radius: 16px;
        border: 1px solid var(--eoc-border);
        position: relative;
        overflow: hidden;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .radar-grid {
        position: absolute; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(59,130,246,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(59,130,246,0.1) 1px, transparent 1px);
        background-size: 40px 40px;
    }
    .radar-sweep {
        position: absolute; width: 300px; height: 300px; background: conic-gradient(from 0deg, transparent 70%, rgba(59,130,246,0.4) 100%);
        border-radius: 50%; animation: spin 4s linear infinite;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .map-node { position: absolute; width: 12px; height: 12px; border-radius: 50%; transform: translate(-50%, -50%); z-index: 10; }
    .map-node::after { content:''; position:absolute; inset:-4px; border-radius:50%; border:2px solid currentColor; animation: ping 2s infinite ease-out; }
    @keyframes ping { 0% { transform: scale(0.5); opacity: 1; } 100% { transform: scale(3); opacity: 0; } }

    .filter-strip { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .f-input, .f-select { background: rgba(0,0,0,0.2); border: 1px solid var(--eoc-border); color: var(--eoc-text-main); padding: 10px 16px; border-radius: 12px; font-size: 0.9rem; outline: none; transition: 0.2s; }
    .f-input:focus, .f-select:focus { border-color: var(--eoc-accent); }

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { padding: 12px 16px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--eoc-text-muted); border-bottom: 2px solid var(--eoc-border); }
    .data-table td { padding: 16px; border-bottom: 1px solid var(--eoc-border); font-size: 0.9rem; vertical-align: middle; }
    .data-table tr:hover td { background: rgba(255,255,255,0.02); }

    .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 6px; }
    .badge-safe { background: rgba(16,185,129,0.1); color: var(--eoc-safe); border: 1px solid rgba(16,185,129,0.3); }
    .badge-danger { background: rgba(239,68,68,0.1); color: var(--eoc-danger); border: 1px solid rgba(239,68,68,0.3); }
    .badge-mia { background: rgba(245,158,11,0.1); color: var(--eoc-warning); border: 1px solid rgba(245,158,11,0.3); }

    .alert-feed { display: flex; flex-direction: column; gap: 15px; }
    .feed-card { background: rgba(0,0,0,0.2); border: 1px solid var(--eoc-border); border-radius: 12px; padding: 16px; position: relative; border-left: 4px solid var(--eoc-danger); }
    .feed-meta { font-size: 0.75rem; color: var(--eoc-text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 6px; display: flex; justify-content: space-between;}
    .feed-title { font-size: 1rem; font-weight: 800; color: var(--eoc-text-main); margin-bottom: 6px; }
    .feed-desc { font-size: 0.85rem; color: var(--eoc-text-muted); line-height: 1.4; }

    .lockdown-list { display: flex; flex-direction: column; gap: 10px; }
    .lockdown-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(0,0,0,0.2); border: 1px solid var(--eoc-border); border-radius: 12px; }
    .switch { position: relative; display: inline-block; width: 48px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; inset: 0; background-color: var(--eoc-border); transition: .4s; border-radius: 26px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--eoc-danger); }
    input:checked + .slider:before { transform: translateX(22px); }

    .btn-action { background: var(--eoc-border); color: var(--eoc-text-main); border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
    .btn-action:hover { background: var(--eoc-accent); color: #fff; }
    .btn-danger { background: rgba(239,68,68,0.1); color: var(--eoc-danger); border: 1px solid rgba(239,68,68,0.3); }
    .btn-danger:hover { background: var(--eoc-danger); color: #fff; }

    .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .qa-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 20px; background: rgba(0,0,0,0.2); border: 1px solid var(--eoc-border); border-radius: 12px; color: var(--eoc-text-main); font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.2s; }
    .qa-btn i { font-size: 1.5rem; color: var(--eoc-accent); }
    .qa-btn:hover { background: var(--eoc-border); transform: translateY(-2px); }

    .eoc-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 10000; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
    .eoc-modal.show { opacity: 1; }
    .eoc-modal-content { background: var(--eoc-card); border: 1px solid var(--eoc-border); border-radius: 20px; padding: 30px; width: 100%; max-width: 500px; transform: scale(0.95); transition: 0.3s; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
    .eoc-modal.show .eoc-modal-content { transform: scale(1); }

    .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
    .form-group label { font-size: 0.8rem; font-weight: 700; color: var(--eoc-text-muted); text-transform: uppercase; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--eoc-border); background: rgba(0,0,0,0.2); color: var(--eoc-text-main); font-family: 'Inter', sans-serif; font-size: 0.95rem; outline: none; transition: 0.2s; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--eoc-accent); }

    .scroll-y { overflow-y: auto; }
    .scroll-y::-webkit-scrollbar { width: 6px; }
    .scroll-y::-webkit-scrollbar-track { background: transparent; }
    .scroll-y::-webkit-scrollbar-thumb { background: var(--eoc-border); border-radius: 10px; }
</style>

<div class="eoc-ticker">
    <div class="ticker-content">
        <?php
        $tic_res = mysqli_query($conn, "SELECT alert_type, location FROM emergency_alerts WHERE status='Active' LIMIT 5");
        $t_str = "";
        if(mysqli_num_rows($tic_res) > 0) {
            while($tr = mysqli_fetch_assoc($tic_res)) { $t_str .= "⚠️ ACTIVE ALERT: {$tr['alert_type']} IN {$tr['location']} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; }
            echo $t_str . $t_str;
        } else { echo "SYSTEM NOMINAL: NO ACTIVE EXTERNAL THREATS DETECTED. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; SYSTEM NOMINAL: NO ACTIVE EXTERNAL THREATS DETECTED."; }
        ?>
    </div>
</div>

<div style="max-width:1600px; margin:0 auto; padding:0 20px;">

    <div class="eoc-header">
        <div>
            <h1 class="eoc-title">Campus E.O.C.</h1>
            <p class="eoc-sub">Emergency Operations Center & Academic Risk Telemetry</p>
        </div>
        <div class="clock-display" id="liveClock">00:00:00</div>
    </div>

    <div class="metric-row">
        <div class="metric-box">
            <div class="m-val" style="color:var(--eoc-accent);"><?= $tot_alerts ?></div>
            <div class="m-lbl">Active Telemetry</div>
        </div>
        <div class="metric-box">
            <div class="m-val" style="color:var(--eoc-danger);"><?= $tot_danger ?></div>
            <div class="m-lbl">Needs Assistance</div>
        </div>
        <div class="metric-box">
            <div class="m-val" style="color:var(--eoc-warning);"><?= $tot_mia ?></div>
            <div class="m-lbl">Signal Lost</div>
        </div>
        <div class="metric-box">
            <div class="m-val" style="color:var(--eoc-safe);"><?= $tot_safe ?></div>
            <div class="m-lbl">Secured Personnel</div>
        </div>
    </div>

    <div class="bento-grid">
        
        <div style="display:flex; flex-direction:column; gap:25px; min-width:0;">
            <div class="eoc-panel" style="padding:0;">
                <div class="panel-header" style="padding:25px 25px 15px; margin:0;">
                    <div class="panel-title"><i class="fas fa-map-marked-alt"></i> Zone Radar</div>
                    <div style="font-size:0.75rem; font-weight:700; color:var(--eoc-safe); border:1px solid currentColor; padding:4px 8px; border-radius:6px;">GPS SYNCED</div>
                </div>
                <div class="map-container" style="margin:0; border-radius:0; border-left:none; border-right:none;">
                    <div class="radar-grid"></div>
                    <div class="radar-sweep"></div>
                    <div class="map-node" style="color:var(--eoc-safe); top:30%; left:40%; background:currentColor;"></div>
                    <div class="map-node" style="color:var(--eoc-danger); top:65%; left:60%; background:currentColor;"></div>
                    <div class="map-node" style="color:var(--eoc-warning); top:45%; left:75%; background:currentColor;"></div>
                </div>
                <div style="padding:15px 25px; background:rgba(0,0,0,0.2); display:flex; gap:15px; font-size:0.8rem; font-weight:600; color:var(--eoc-text-muted);">
                    <div style="display:flex; align-items:center; gap:6px;"><div style="width:10px;height:10px;border-radius:50%;background:var(--eoc-safe);"></div> Secured</div>
                    <div style="display:flex; align-items:center; gap:6px;"><div style="width:10px;height:10px;border-radius:50%;background:var(--eoc-danger);"></div> Danger</div>
                    <div style="display:flex; align-items:center; gap:6px;"><div style="width:10px;height:10px;border-radius:50%;background:var(--eoc-warning);"></div> Unknown</div>
                </div>
            </div>

            <div class="eoc-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-users"></i> Personnel Safety Roster</div>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="mark_all_safe" value="1">
                        <button type="submit" class="btn-action" style="background:var(--eoc-safe); color:#fff; border:none;" onclick="return confirm('Systematically mark all personnel as safe?')"><i class="fas fa-check-double"></i> Resolve All</button>
                    </form>
                </div>
                
                <div class="filter-strip">
                    <input type="text" id="sSearch" class="f-input" placeholder="Search ID or Name..." style="flex:1; min-width:150px;" onkeyup="filRos()">
                    <select id="sRole" class="f-select" onchange="filRos()">
                        <option value="All">All Classifications</option>
                        <option value="Student">Students</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Staff">Staff</option>
                    </select>
                    <select id="sStat" class="f-select" onchange="filRos()">
                        <option value="All">All Conditions</option>
                        <option value="Needs Assistance">Needs Assistance</option>
                        <option value="Unreachable">Unreachable</option>
                        <option value="Safe">Safe</option>
                    </select>
                </div>

                <div class="scroll-y" style="max-height:400px; padding-right:10px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Personnel</th>
                                <th>Telemetry</th>
                                <th>Zone</th>
                                <th>Status</th>
                                <th>Cmd</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $r_res = mysqli_query($conn, "SELECT * FROM safety_status ORDER BY CASE condition_status WHEN 'Needs Assistance' THEN 1 WHEN 'Unreachable' THEN 2 ELSE 3 END, last_updated DESC");
                            while($rw = mysqli_fetch_assoc($r_res)) {
                                $cnd = $rw['condition_status'];
                                $bdg = 'badge-safe'; $ic = 'fa-check';
                                if($cnd == 'Needs Assistance') { $bdg = 'badge-danger'; $ic = 'fa-exclamation-triangle'; }
                                elseif($cnd == 'Unreachable') { $bdg = 'badge-mia'; $ic = 'fa-question'; }
                                
                                $js = htmlspecialchars(json_encode($rw), ENT_QUOTES, 'UTF-8');
                                
                                echo "
                                <tr class='ros-row' data-stat='{$cnd}' data-role='{$rw['role']}'>
                                    <td>
                                        <div style='font-weight:700; color:var(--eoc-text-main); margin-bottom:2px;'>{$rw['user_name']}</div>
                                        <div style='font-family:monospace; font-size:0.75rem; color:var(--eoc-text-muted);'>{$rw['user_id']} • {$rw['role']}</div>
                                    </td>
                                    <td>
                                        <div style='font-size:0.85rem; color:var(--eoc-text-main); margin-bottom:2px;'><i class='fas fa-crosshairs' style='opacity:0.5; margin-right:4px;'></i>{$rw['location_ping']}</div>
                                        <div style='font-size:0.7rem; color:var(--eoc-text-muted);'>TS: " . date('H:i:s', strtotime($rw['last_updated'])) . "</div>
                                    </td>
                                    <td style='font-weight:700;'>{$rw['assigned_zone']}</td>
                                    <td><span class='status-badge {$bdg}'><i class='fas {$ic}'></i> {$cnd}</span></td>
                                    <td>
                                        <div style='display:flex; gap:6px;'>
                                            " . ($cnd != 'Safe' ? "<form method='POST' style='margin:0;'><input type='hidden' name='quick_safe' value='1'><input type='hidden' name='uid' value='{$rw['user_id']}'><button type='submit' class='btn-action' style='padding:6px 10px;' title='Mark Safe'><i class='fas fa-check' style='color:var(--eoc-safe);'></i></button></form>" : "") . "
                                            <button class='btn-action' style='padding:6px 10px;' onclick='openModal(\"override\", {$js})'><i class='fas fa-pen'></i></button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div style="display:flex; flex-direction:column; gap:25px;">
            
            <div class="eoc-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-satellite-dish"></i> External Telemetry</div>
                </div>
                <div class="alert-feed scroll-y" style="max-height: 300px; padding-right:5px;">
                    <?php
                    $al_res = mysqli_query($conn, "SELECT * FROM emergency_alerts WHERE status='Active' ORDER BY timestamp DESC LIMIT 4");
                    if(mysqli_num_rows($al_res) > 0) {
                        while($a = mysqli_fetch_assoc($al_res)) {
                            $time_diff = (new DateTime())->diff(new DateTime($a['timestamp']));
                            $ts_str = $time_diff->h > 0 ? $time_diff->h."h ago" : ($time_diff->i > 0 ? $time_diff->i."m ago" : "Just now");
                            echo "
                            <div class='feed-card'>
                                <div class='feed-meta'><span>{$a['source']}</span><span>T-MINUS: {$ts_str}</span></div>
                                <div class='feed-title'>{$a['alert_type']} ({$a['severity']})</div>
                                <div class='feed-desc'>{$a['message']}</div>
                                <div style='display:flex; justify-content:space-between; align-items:center; margin-top:12px;'>
                                    <div style='font-size:0.75rem; font-weight:700; color:var(--eoc-text-muted);'><i class='fas fa-map-marker-alt'></i> {$a['location']}</div>
                                    <a href='?del_alert={$a['id']}' class='btn-action btn-danger' style='padding:4px 8px; font-size:0.7rem;'>Dismiss</a>
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<div style='text-align:center; padding:20px; color:var(--eoc-text-muted); font-size:0.9rem; font-weight:600;'><i class='fas fa-shield-check' style='font-size:2rem; color:var(--eoc-safe); margin-bottom:10px; display:block;'></i>No active external threats.</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="eoc-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-building"></i> Structural Lockdowns</div>
                </div>
                <div class="lockdown-list">
                    <?php
                    $b_res = mysqli_query($conn, "SELECT * FROM building_lockdowns ORDER BY building_name ASC");
                    while($b = mysqli_fetch_assoc($b_res)) {
                        $is_l = $b['status'] === 'Locked';
                        $chk = $is_l ? 'checked' : '';
                        $col = $is_l ? 'var(--eoc-danger)' : 'var(--eoc-text-main)';
                        echo "
                        <div class='lockdown-item'>
                            <div>
                                <div style='font-size:0.95rem; font-weight:700; color:{$col}; transition:0.3s;'>{$b['building_name']}</div>
                                <div style='font-size:0.75rem; color:var(--eoc-text-muted); margin-top:4px;'>Status: {$b['status']}</div>
                            </div>
                            <form method='POST' style='margin:0;'>
                                <input type='hidden' name='toggle_lockdown' value='1'>
                                <input type='hidden' name='building_id' value='{$b['id']}'>
                                <input type='hidden' name='current_status' value='{$b['status']}'>
                                <label class='switch'>
                                    <input type='checkbox' {$chk} onchange='this.form.submit()'>
                                    <span class='slider'></span>
                                </label>
                            </form>
                        </div>";
                    }
                    ?>
                </div>
            </div>

            <div class="eoc-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-bolt"></i> Rapid Response</div>
                </div>
                <div class="quick-actions">
                    <div class="qa-btn" onclick="openModal('broadcast')"><i class="fas fa-bullhorn"></i> PA Broadcast</div>
                    <div class="qa-btn" onclick="openModal('incident')"><i class="fas fa-clipboard-list"></i> Log Incident</div>
                </div>
                <form method="POST" style="margin-top:12px;">
                    <input type="hidden" name="trigger_sos" value="1">
                    <button type="submit" class="btn-action" style="width:100%; justify-content:center; padding:15px; background:var(--eoc-danger); color:#fff; border:none; box-shadow:0 4px 15px rgba(239,68,68,0.3); font-size:1rem;"><i class="fas fa-radiation"></i> Trigger S.O.S Beacon</button>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="eoc-modal" id="mdl_override" onclick="closeModal('override', event)">
    <div class="eoc-modal-content" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:1.3rem; font-weight:800; margin:0;"><i class="fas fa-user-edit" style="color:var(--eoc-accent); margin-right:8px;"></i> Manual Roster Override</h2>
            <i class="fas fa-times" style="cursor:pointer; color:var(--eoc-text-muted); font-size:1.2rem;" onclick="closeModal('override')"></i>
        </div>
        <form method="POST">
            <input type="hidden" name="save_safety" value="1">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group"><label>ID Tag</label><input type="text" name="user_id" id="o_uid" required readonly style="opacity:0.7;"></div>
                <div class="form-group"><label>Classification</label><input type="text" name="role" id="o_rol" required readonly style="opacity:0.7;"></div>
                <div class="form-group" style="grid-column:span 2;"><label>Full Name</label><input type="text" name="user_name" id="o_unm" required readonly style="opacity:0.7;"></div>
                
                <div class="form-group" style="grid-column:span 2; position:relative;">
                    <label>Location Ping (Coords/Desc)</label>
                    <input type="text" name="location_ping" id="o_loc" required style="padding-right:50px;">
                    <button type="button" class="btn-action" style="position:absolute; right:6px; bottom:6px; padding:8px;" onclick="genGPS()" title="Auto-Ping GPS"><i class="fas fa-crosshairs"></i></button>
                </div>
                
                <div class="form-group"><label>Evac Zone</label>
                    <select name="assigned_zone" id="o_zon" required>
                        <option value="Pending">Pending</option>
                        <option value="Zone A">Zone A</option>
                        <option value="Zone B">Zone B</option>
                        <option value="Zone C">Zone C</option>
                    </select>
                </div>
                <div class="form-group"><label>Condition</label>
                    <select name="condition_status" id="o_cnd" required style="font-weight:700;">
                        <option value="Safe">Safe / Secured</option>
                        <option value="Needs Assistance">Needs Assistance</option>
                        <option value="Unreachable">Unreachable</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-action" style="width:100%; justify-content:center; margin-top:10px; background:var(--eoc-accent); color:#fff; border:none; padding:15px;"><i class="fas fa-save"></i> Transmit Update</button>
        </form>
    </div>
</div>

<div class="eoc-modal" id="mdl_broadcast" onclick="closeModal('broadcast', event)">
    <div class="eoc-modal-content" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:1.3rem; font-weight:800; margin:0;"><i class="fas fa-bullhorn" style="color:var(--eoc-warning); margin-right:8px;"></i> Emergency Broadcast</h2>
            <i class="fas fa-times" style="cursor:pointer; color:var(--eoc-text-muted); font-size:1.2rem;" onclick="closeModal('broadcast')"></i>
        </div>
        <div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); padding:15px; border-radius:10px; font-size:0.85rem; color:var(--eoc-text-main); margin-bottom:20px; line-height:1.4;">
            <i class="fas fa-info-circle" style="color:var(--eoc-warning);"></i> Transmissions will bypass standard device DND settings via SMS and Push protocols.
        </div>
        <form method="POST">
            <input type="hidden" name="pa_broadcast" value="1">
            <div class="form-group">
                <label>Target Audience Routing</label>
                <select name="target_audience" required>
                    <option value="All Personnel">All Campus Personnel</option>
                    <option value="Students Only">Students Only</option>
                    <option value="Faculty & Staff">Faculty & Staff Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Message Payload</label>
                <textarea name="pa_msg" rows="5" required placeholder="Enter directives..." style="resize:none;"></textarea>
            </div>
            <button type="submit" class="btn-action" style="width:100%; justify-content:center; margin-top:10px; background:var(--eoc-warning); color:#fff; border:none; padding:15px;"><i class="fas fa-paper-plane"></i> Dispatch Alert</button>
        </form>
    </div>
</div>

<div class="eoc-modal" id="mdl_incident" onclick="closeModal('incident', event)">
    <div class="eoc-modal-content" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:1.3rem; font-weight:800; margin:0;"><i class="fas fa-clipboard-list" style="color:var(--eoc-accent); margin-right:8px;"></i> Log Incident Report</h2>
            <i class="fas fa-times" style="cursor:pointer; color:var(--eoc-text-muted); font-size:1.2rem;" onclick="closeModal('incident')"></i>
        </div>
        <form method="POST">
            <input type="hidden" name="log_incident" value="1">
            <div class="form-group">
                <label>Incident Description & Logistics</label>
                <textarea name="incident_desc" rows="6" required placeholder="Detail the situation, precise location, and assets required..." style="resize:none;"></textarea>
            </div>
            <button type="submit" class="btn-action" style="width:100%; justify-content:center; margin-top:10px; background:var(--eoc-accent); color:#fff; border:none; padding:15px;"><i class="fas fa-file-signature"></i> Submit Report to DB</button>
        </form>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').innerText = now.toTimeString().split(' ')[0];
}
setInterval(updateClock, 1000);
updateClock();

function filRos() {
    const sSearch = document.getElementById('sSearch').value.toLowerCase();
    const sRole = document.getElementById('sRole').value;
    const sStat = document.getElementById('sStat').value;
    const rows = document.querySelectorAll('.ros-row');
    
    rows.forEach(r => {
        const dStat = r.getAttribute('data-stat');
        const dRole = r.getAttribute('data-role');
        const text = r.innerText.toLowerCase();
        let show = true;
        
        if(sStat !== 'All' && dStat !== sStat) show = false;
        if(sRole !== 'All' && dRole !== sRole) show = false;
        if(sSearch && !text.includes(sSearch)) show = false;
        
        r.style.display = show ? '' : 'none';
    });
}

function openModal(type, data = null) {
    const m = document.getElementById('mdl_' + type);
    if(type === 'override' && data) {
        document.getElementById('o_uid').value = data.user_id;
        document.getElementById('o_rol').value = data.role;
        document.getElementById('o_unm').value = data.user_name;
        document.getElementById('o_loc').value = data.location_ping;
        document.getElementById('o_zon').value = data.assigned_zone || 'Pending';
        
        const cnd = document.getElementById('o_cnd');
        cnd.value = data.condition_status;
        cnd.style.color = data.condition_status === 'Safe' ? 'var(--eoc-safe)' : (data.condition_status === 'Needs Assistance' ? 'var(--eoc-danger)' : 'var(--eoc-warning)');
        cnd.onchange = function() {
            this.style.color = this.value === 'Safe' ? 'var(--eoc-safe)' : (this.value === 'Needs Assistance' ? 'var(--eoc-danger)' : 'var(--eoc-warning)');
        }
    }
    m.style.display = 'flex';
    setTimeout(() => m.classList.add('show'), 10);
}

function closeModal(type, e = null) {
    const m = document.getElementById('mdl_' + type);
    if(e && e.target !== m && !e.target.classList.contains('fa-times')) return;
    m.classList.remove('show');
    setTimeout(() => m.style.display = 'none', 300);
}

function genGPS() {
    const lat = (10 + Math.random() * 2).toFixed(4);
    const lon = (122 + Math.random() * 2).toFixed(4);
    document.getElementById('o_loc').value = `${lat}N, ${lon}E`;
}
</script>

<?php include 'footer.php'; ?>