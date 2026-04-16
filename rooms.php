<?php 
include 'config.php'; 

$patch = "CREATE TABLE IF NOT EXISTS campus_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) UNIQUE,
    room_name VARCHAR(150),
    building VARCHAR(50),
    room_type VARCHAR(50),
    capacity INT,
    equipment_tags VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Available',
    last_cleaned DATETIME
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM campus_rooms WHERE id = $id");
    header("Location: rooms.php");
    exit();
}

if(isset($_GET['toggle_maint'])) {
    $id = intval($_GET['toggle_maint']);
    $res = mysqli_query($conn, "SELECT status FROM campus_rooms WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $new_status = $row['status'] == 'In Maintenance' ? 'Available' : 'In Maintenance';
        mysqli_query($conn, "UPDATE campus_rooms SET status = '$new_status' WHERE id = $id");
        header("Location: rooms.php");
        exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_room'])) {
    $rn = mysqli_real_escape_string($conn, $_POST['room_number']);
    $nm = mysqli_real_escape_string($conn, $_POST['room_name']);
    $bd = mysqli_real_escape_string($conn, $_POST['building']);
    $rt = mysqli_real_escape_string($conn, $_POST['room_type']);
    $cp = intval($_POST['capacity']);
    $eq = mysqli_real_escape_string($conn, $_POST['equipment_tags']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE campus_rooms SET room_number='$rn', room_name='$nm', building='$bd', room_type='$rt', capacity=$cp, equipment_tags='$eq', status='$st' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO campus_rooms (room_number, room_name, building, room_type, capacity, equipment_tags, status, last_cleaned) VALUES ('$rn', '$nm', '$bd', '$rt', $cp, '$eq', '$st', NOW())");
    }
    header("Location: rooms.php");
    exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_rooms = [
        ['LB-101', 'Advanced Chemistry Lab', 'Science Wing', 'Laboratory', 30, 'Fume Hoods, Gas Lines, Eyewash Station', 'Available'],
        ['LB-102', 'Biology Research Lab', 'Science Wing', 'Laboratory', 25, 'Microscopes, Incubators, Sink', 'In Use'],
        ['LH-201', 'Main Auditorium', 'Main Building', 'Auditorium', 250, '4K Projector, PA System, Tiered Seating', 'Available'],
        ['LH-202', 'Lecture Hall A', 'Main Building', 'Lecture Hall', 100, 'Dual Projectors, Podium, Mic', 'In Maintenance'],
        ['CR-301', 'Standard Classroom', 'West Wing', 'Classroom', 40, 'Smartboard, Whiteboard, WiFi', 'Available'],
        ['CR-302', 'Standard Classroom', 'West Wing', 'Classroom', 40, 'Smartboard, Whiteboard, WiFi', 'In Use'],
        ['CR-303', 'Standard Classroom', 'West Wing', 'Classroom', 40, 'Projector, Whiteboard', 'Available'],
        ['IT-401', 'Programming Lab 1', 'Tech Center', 'Computer Lab', 35, '35 Workstations, Dual Monitors, Server Access', 'Available'],
        ['IT-402', 'Networking Lab', 'Tech Center', 'Computer Lab', 30, 'Cisco Routers, 30 Workstations', 'In Maintenance'],
        ['FC-501', 'Faculty Lounge', 'Admin Wing', 'Lounge', 20, 'Coffee Machine, Sofas, Printer', 'Available'],
        ['MR-601', 'Executive Boardroom', 'Admin Wing', 'Meeting Room', 15, 'Teleconference Hub, Executive Table', 'In Use'],
        ['ST-701', 'Main Equipment Storage', 'Basement', 'Storage', 0, 'Shelving Units, Climate Control', 'Available'],
        ['GY-801', 'Indoor Gymnasium', 'Athletics Complex', 'Recreation', 500, 'Basketball Court, Bleachers, Scoreboard', 'Available'],
        ['LB-901', 'Physics Lab', 'Science Wing', 'Laboratory', 30, 'Oscilloscopes, Vacuum Lines', 'Available'],
        ['CR-304', 'Seminar Room', 'West Wing', 'Classroom', 25, 'Moveable Desks, Interactive Display', 'In Use']
    ];
    foreach($seed_rooms as $item) {
        $rn = $item[0]; $nm = mysqli_real_escape_string($conn, $item[1]); $bd = $item[2];
        $rt = $item[3]; $cp = $item[4]; $eq = mysqli_real_escape_string($conn, $item[5]); $st = $item[6];
        $lc = date('Y-m-d H:i:s', strtotime('-'.rand(1, 48).' hours'));
        mysqli_query($conn, "INSERT INTO campus_rooms (room_number, room_name, building, room_type, capacity, equipment_tags, status, last_cleaned) VALUES ('$rn', '$nm', '$bd', '$rt', $cp, '$eq', '$st', '$lc')");
    }
}

include 'header.php';

$total = getCount($conn, 'campus_rooms');
$avail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms WHERE status='Available'"))['c'];
$maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms WHERE status='In Maintenance'"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 16px; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.8rem; color: var(--brand-secondary); opacity: 0.9; padding: 15px; background: var(--main-bg); border-radius: 12px; border: 1px solid var(--border-color);}
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.4rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 25px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap:wrap; gap:15px;}
    
    .status-avail { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-use { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: #3b82f6; }
    .status-maint { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    
    .id-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 2px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .id-box { color: var(--brand-primary); }

    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--brand-secondary); color: var(--brand-primary); font-weight: 900;}
    [data-theme="light"] .view-btn.active-view { background: var(--brand-primary); color: #fff;}

    .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .room-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .room-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="light"] .room-card:hover { border-color: var(--brand-primary); }
    .room-card.dimmed { opacity: 0.65; filter: grayscale(40%); }
    
    .rc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .rc-title { font-family: var(--heading-font); font-size: 1.25rem; font-weight: 900; color: var(--text-dark); margin-bottom: 15px; line-height: 1.3;}
    .rc-detail { display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 10px; font-weight: 600;}
    .rc-detail i { color: var(--brand-secondary); width: 16px; text-align: center; margin-top: 3px;}
    [data-theme="light"] .rc-detail i { color: var(--brand-primary); }
    
    .rc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #8b5cf6;">
    <h1 style="color: #8b5cf6; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Campus Facilities</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Manage room allocations, capacities, and structural maintenance.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-door-open stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Tracked Spaces</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $avail ?></div>
            <div class="stat-lbl">Spaces Available</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-tools stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $maint ?></div>
            <div class="stat-lbl">Under Maintenance</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')" title="List View"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')" title="Windowed Grid View"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchRoomLocal" onkeyup="filterRooms()" placeholder="&#xf002; Search Room or Building..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 250px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterRooms()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Conditions</option>
            <option value="Available">Available</option>
            <option value="In Use">In Use</option>
            <option value="In Maintenance">In Maintenance</option>
        </select>
        <select id="filterBldg" onchange="filterRooms()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Buildings">All Buildings</option>
            <?php
            $b_res = mysqli_query($conn, "SELECT DISTINCT building FROM campus_rooms ORDER BY building ASC");
            while($b = mysqli_fetch_assoc($b_res)) { echo "<option value='{$b['building']}'>{$b['building']}</option>"; }
            ?>
        </select>
        <select id="filterType" onchange="filterRooms()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Types">All Space Types</option>
            <?php
            $t_res = mysqli_query($conn, "SELECT DISTINCT room_type FROM campus_rooms ORDER BY room_type ASC");
            while($t = mysqli_fetch_assoc($t_res)) { echo "<option value='{$t['room_type']}'>{$t['room_type']}</option>"; }
            ?>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Facilities Manifest...')"><i class="fas fa-file-csv"></i> Export Data</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#8b5cf6; border-color:#8b5cf6; color:#fff;" onclick="openRoomModal()"><i class="fas fa-plus"></i> Register Space</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:20%;">Room Identification</th>
                <th>Location Details</th>
                <th style="width:25%;">Capacity & Tech</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="roomTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM campus_rooms ORDER BY building ASC, room_number ASC");
            $all_rooms = [];
            
            while($row = mysqli_fetch_assoc($res)) {
                $all_rooms[] = $row;
                $st = $row['status'];
                if($st == 'Available') $st_class = 'status-avail';
                elseif($st == 'In Use') $st_class = 'status-use';
                else $st_class = 'status-maint';
                
                $row_style = $st == 'In Maintenance' ? "opacity: 0.65; filter: grayscale(40%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $type = $row['room_type'];
                $icon = 'fa-door-open';
                if(strpos($type, 'Lab') !== false) $icon = 'fa-flask';
                if(strpos($type, 'Computer') !== false) $icon = 'fa-laptop-code';
                if(strpos($type, 'Lecture') !== false || strpos($type, 'Classroom') !== false) $icon = 'fa-chalkboard';
                if(strpos($type, 'Auditorium') !== false) $icon = 'fa-bullhorn';
                if(strpos($type, 'Meeting') !== false) $icon = 'fa-handshake';
                if(strpos($type, 'Storage') !== false) $icon = 'fa-box-open';
                
                $pct = ($row['capacity'] / 100) * 100;
                if($pct > 100) $pct = 100;
                $bar_color = $pct > 80 ? 'var(--brand-secondary)' : '#8b5cf6';

                $tags = explode(',', $row['equipment_tags']);
                $tag_html = "";
                foreach($tags as $tag) {
                    $t = trim($tag);
                    if(!empty($t)) $tag_html .= "<span style='display:inline-block; font-size:0.65rem; font-weight:800; background:var(--main-bg); border:1px solid var(--border-color); padding:2px 6px; border-radius:4px; margin-right:4px; margin-top:4px;'>$t</span>";
                }

                $lc = date('M d, Y • h:i A', strtotime($row['last_cleaned']));

                echo "
                <tr class='filter-target' style='$row_style' data-stat='{$st}' data-bldg='{$row['building']}' data-type='{$type}'>
                    <td>
                        <div class='id-box'>{$row['room_number']}</div>
                        <div style='font-size:1.15rem; color:var(--text-dark); margin-top:8px; font-weight:900; font-family:var(--heading-font);'>{$row['room_name']}</div>
                    </td>
                    <td>
                        <strong style='color:var(--brand-secondary); text-transform:uppercase; font-size:0.85rem;'><i class='fas fa-building' style='margin-right:6px;'></i> {$row['building']}</strong>
                        <div style='font-size:0.8rem; color:var(--text-light); font-weight:800; margin-top:6px;'><i class='fas {$icon}' style='margin-right:6px;'></i> {$type}</div>
                        <div style='font-size:0.75rem; color:var(--text-light); font-weight:600; margin-top:8px;'><i class='fas fa-broom' style='margin-right:6px;'></i> Cleaned: {$lc}</div>
                    </td>
                    <td>
                        <div style='font-weight:900; color:var(--text-dark);'>{$row['capacity']} Max Seats</div>
                        <div style='width: 100%; height: 6px; background: var(--border-light); margin-top: 6px; border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                        <div style='margin-top:8px;'>{$tag_html}</div>
                    </td>
                    <td><span class='status-pill {$st_class}'>{$st}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openRoomModal($js_data)'><i class='fas fa-pen'></i></button>
                            <a href='?toggle_maint={$row['id']}' class='table-btn' style='border-color:#f59e0b; color:#f59e0b;' onclick='systemToast(\"Toggling Room Maintenance Lock...\")'><i class='fas fa-wrench'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Demolishing Record...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            if(count($all_rooms) == 0) echo "<tr><td colspan='5' style='text-align:center; padding:50px; font-weight:800; opacity:0.5;'><i class='fas fa-door-closed' style='font-size:3rem; margin-bottom:15px; color:var(--brand-secondary);'></i><br>No facilities logged.</td></tr>";
            ?>
        </tbody>
    </table>
</div>

<div id="gridView" class="room-grid" style="display:none;">
    <?php
    foreach($all_rooms as $row) {
        $st = $row['status'];
        if($st == 'Available') $st_class = 'status-avail';
        elseif($st == 'In Use') $st_class = 'status-use';
        else $st_class = 'status-maint';
        
        $dim_class = $st == 'In Maintenance' ? 'dimmed' : '';
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        
        $type = $row['room_type'];
        $icon = 'fa-door-open';
        if(strpos($type, 'Lab') !== false) $icon = 'fa-flask';
        if(strpos($type, 'Computer') !== false) $icon = 'fa-laptop-code';
        if(strpos($type, 'Lecture') !== false || strpos($type, 'Classroom') !== false) $icon = 'fa-chalkboard';
        if(strpos($type, 'Auditorium') !== false) $icon = 'fa-bullhorn';
        if(strpos($type, 'Meeting') !== false) $icon = 'fa-handshake';
        if(strpos($type, 'Storage') !== false) $icon = 'fa-box-open';
        
        $pct = ($row['capacity'] / 100) * 100;
        if($pct > 100) $pct = 100;
        $bar_color = $pct > 80 ? 'var(--brand-secondary)' : '#8b5cf6';
        
        $bdr_color = '#10b981';
        if($st == 'In Use') $bdr_color = '#3b82f6';
        if($st == 'In Maintenance') $bdr_color = '#f59e0b';

        $tags = explode(',', $row['equipment_tags']);
        $tag_html = "";
        $count = 0;
        foreach($tags as $tag) {
            $t = trim($tag);
            if(!empty($t) && $count < 3) {
                $tag_html .= "<span style='display:inline-block; font-size:0.65rem; font-weight:800; background:var(--bg-grid); border:1px solid var(--border-color); padding:2px 6px; border-radius:4px; margin-right:4px; margin-bottom:4px;'>$t</span>";
                $count++;
            }
        }
        if(count($tags) > 3) $tag_html .= "<span style='font-size:0.65rem; color:var(--text-light);'>+ more</span>";

        $lc = date('M d, Y', strtotime($row['last_cleaned']));

        echo "
        <div class='room-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}' data-bldg='{$row['building']}' data-type='{$type}'>
            <div class='rc-header'>
                <div class='id-box' style='font-size:0.9rem; padding:4px 8px;'>{$row['room_number']}</div>
                <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
            </div>
            
            <div class='rc-title'>{$row['room_name']}</div>
            
            <div class='rc-detail'><i class='fas fa-building'></i> {$row['building']}</div>
            <div class='rc-detail'><i class='fas {$icon}'></i> {$type}</div>
            <div class='rc-detail'><i class='fas fa-broom'></i> Cleaned: {$lc}</div>
            
            <div style='margin-top:10px; margin-bottom:15px;'>{$tag_html}</div>
            
            <div class='rc-footer'>
                <div style='flex-grow:1; margin-right:20px;'>
                    <div style='display:flex; justify-content:space-between; font-weight:800; color:var(--text-dark); margin-bottom:6px; font-size:0.75rem;'><span>{$row['capacity']} Seats Max</span></div>
                    <div style='width: 100%; height: 6px; background: var(--border-light); border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                </div>
                <div style='display:flex; gap:8px;'>
                    <button class='table-btn btn-resolve' style='padding:6px 10px;' onclick='openRoomModal($js_data)'><i class='fas fa-pen' style='margin:0;'></i></button>
                    <a href='?toggle_maint={$row['id']}' class='table-btn' style='padding:6px 10px; border-color:#f59e0b; color:#f59e0b;'><i class='fas fa-wrench' style='margin:0;'></i></a>
                </div>
            </div>
        </div>";
    }
    ?>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-door-open" style="color:#8b5cf6;"></i> Register Space</h2>
        <form method="POST">
            <input type="hidden" name="save_room" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="room_number" id="room_number" placeholder="Room ID (e.g. LB-101)" required>
                <input type="text" name="room_name" id="room_name" placeholder="Space Descriptor (e.g. Bio Lab)" required>
                
                <input type="text" name="building" id="building" placeholder="Building/Wing Name" required>
                <select name="room_type" id="room_type" required>
                    <option value="Classroom">Classroom</option>
                    <option value="Lecture Hall">Lecture Hall</option>
                    <option value="Laboratory">Laboratory</option>
                    <option value="Computer Lab">Computer Lab</option>
                    <option value="Auditorium">Auditorium</option>
                    <option value="Meeting Room">Meeting Room</option>
                    <option value="Lounge">Lounge / Common Area</option>
                    <option value="Storage">Storage</option>
                    <option value="Recreation">Recreation</option>
                </select>
                
                <input type="number" name="capacity" id="capacity" placeholder="Max Capacity Count" required>
                <select name="status" id="status" required>
                    <option value="Available">Available</option>
                    <option value="In Use">In Use</option>
                    <option value="In Maintenance">In Maintenance</option>
                </select>

                <textarea name="equipment_tags" id="equipment_tags" placeholder="Equipment Tags (comma separated)" style="grid-column: span 2; height:80px; resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#8b5cf6; border-color:#8b5cf6; color:#fff;"><i class="fas fa-save"></i> Save Facility Data</button>
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
        localStorage.setItem('campus_room_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_room_view', 'table');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const pref = localStorage.getItem('campus_room_view') || 'table';
    setView(pref);
});

function filterRooms() {
    const sFilter = document.getElementById('filterStatus').value;
    const bFilter = document.getElementById('filterBldg').value;
    const tFilter = document.getElementById('filterType').value;
    const searchQ = document.getElementById('searchRoomLocal').value.toLowerCase();
    
    const targets = document.querySelectorAll('.filter-target');
    
    targets.forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rBldg = el.getAttribute('data-bldg');
        const rType = el.getAttribute('data-type');
        const rText = el.innerText.toLowerCase();
        
        let show = true;
        
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (bFilter !== 'All Buildings' && rBldg !== bFilter) show = false;
        if (tFilter !== 'All Types' && rType !== tFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) {
            el.removeAttribute('data-hide-local');
            el.style.display = '';
        } else {
            el.setAttribute('data-hide-local', 'true');
            el.style.display = 'none';
        }
    });

    if(typeof globalTableSearch === 'function') globalTableSearch();
}

function openRoomModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#8b5cf6;"></i> Edit Space Data';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('room_number').value = data.room_number || '';
        document.getElementById('room_name').value = data.room_name || '';
        document.getElementById('building').value = data.building || '';
        document.getElementById('room_type').value = data.room_type || 'Classroom';
        document.getElementById('capacity').value = data.capacity || '';
        document.getElementById('status').value = data.status || 'Available';
        document.getElementById('equipment_tags').value = data.equipment_tags || '';
    } else {
        title.innerHTML = '<i class="fas fa-door-open" style="color:#8b5cf6;"></i> Register Space';
        document.getElementById('edit_id').value = '';
        document.getElementById('room_number').value = '';
        document.getElementById('room_name').value = '';
        document.getElementById('building').value = '';
        document.getElementById('room_type').value = 'Classroom';
        document.getElementById('capacity').value = '';
        document.getElementById('status').value = 'Available';
        document.getElementById('equipment_tags').value = '';
    }
    
    modal.style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>