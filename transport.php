<?php 
include 'config.php'; 

$patch = "CREATE TABLE IF NOT EXISTS transport (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_type VARCHAR(50) DEFAULT 'Standard Bus',
    bus_route VARCHAR(150),
    vehicle_plate VARCHAR(20),
    capacity INT,
    driver_name VARCHAR(100),
    driver_contact VARCHAR(20),
    battery_level INT DEFAULT 100,
    status VARCHAR(20) DEFAULT 'Operational'
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = ["vehicle_type VARCHAR(50) DEFAULT 'Standard Bus'", "bus_route VARCHAR(150)", "vehicle_plate VARCHAR(20)", "capacity INT", "driver_name VARCHAR(100)", "driver_contact VARCHAR(20)", "battery_level INT DEFAULT 100", "status VARCHAR(20) DEFAULT 'Operational'"];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE transport ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM transport WHERE id = $id");
    header("Location: transport.php"); exit();
}

if(isset($_GET['toggle_fleet'])) {
    $id = intval($_GET['toggle_fleet']);
    $res = mysqli_query($conn, "SELECT status FROM transport WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $new_status = $row['status'] == 'Operational' ? 'In Maintenance' : 'Operational';
        mysqli_query($conn, "UPDATE transport SET status = '$new_status', battery_level = 100 WHERE id = $id");
        header("Location: transport.php"); exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_fleet'])) {
    $vt = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $br = mysqli_real_escape_string($conn, $_POST['bus_route']);
    $vp = mysqli_real_escape_string($conn, $_POST['vehicle_plate']);
    $cap = intval($_POST['capacity']);
    $dn = mysqli_real_escape_string($conn, $_POST['driver_name']);
    $dc = mysqli_real_escape_string($conn, $_POST['driver_contact']);
    $bl = intval($_POST['battery_level']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE transport SET vehicle_type='$vt', bus_route='$br', vehicle_plate='$vp', capacity='$cap', driver_name='$dn', driver_contact='$dc', battery_level=$bl, status='$st' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO transport (vehicle_type, bus_route, vehicle_plate, capacity, driver_name, driver_contact, battery_level, status) VALUES ('$vt', '$br', '$vp', $cap, '$dn', '$dc', $bl, '$st')");
    }
    header("Location: transport.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM transport");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_fleet = [
        ['Standard Bus', 'North Campus Loop', 'CP-2026A', 55, 'Arthur Pendelton', '(555) 019-2831', 85, 'Operational'],
        ['Standard Bus', 'South Campus Loop', 'CP-2026B', 55, 'Marcus Vance', '(555) 019-2832', 40, 'Operational'],
        ['Standard Bus', 'City Center Express', 'CP-2026C', 60, 'Elias Thorne', '(555) 019-2833', 90, 'Operational'],
        ['Express Shuttle', 'Dormitory Connector', 'SH-011', 24, 'Sarah Jenkins', '(555) 012-9934', 20, 'Operational'],
        ['Express Shuttle', 'Research Park Line', 'SH-012', 24, 'David Ross', '(555) 012-9935', 100, 'Operational'],
        ['Express Shuttle', 'Athletics Complex', 'SH-014', 24, 'Michael Chang', '(555) 012-9936', 15, 'In Maintenance'],
        ['ADA Transit Van', 'On-Demand Transit', 'AV-880', 8, 'Linda Ramirez', '(555) 088-1122', 75, 'Operational'],
        ['ADA Transit Van', 'Medical Center Run', 'AV-881', 8, 'Robert Cole', '(555) 088-1123', 80, 'Operational'],
        ['Minibus', 'Faculty Housing Route', 'MB-404', 35, 'James O\'Connor', '(555) 044-5566', 60, 'Operational'],
        ['Minibus', 'Off-Campus Apartments', 'MB-405', 35, 'William Dent', '(555) 044-5567', 10, 'In Maintenance'],
        ['EV Buggy', 'Main Quad Patrol', 'EV-001', 4, 'Officer Davis', '(555) 911-0001', 95, 'Operational'],
        ['EV Buggy', 'Library Perimeter', 'EV-002', 4, 'Officer Smith', '(555) 911-0002', 50, 'Operational'],
        ['Utility Cart', 'Grounds Maintenance', 'UC-101', 2, 'Tom Groundskeeper', '(555) 888-1111', 30, 'Operational'],
        ['Utility Cart', 'Facilities Repair', 'UC-102', 2, 'Jerry Fixit', '(555) 888-1112', 100, 'Operational'],
        ['Standard Bus', 'Weekend Excursion', 'CP-2026D', 55, 'Unassigned', 'N/A', 0, 'In Maintenance'],
        ['Express Shuttle', 'Downtown Link', 'SH-015', 24, 'Emma Frost', '(555) 012-9937', 88, 'Operational'],
        ['Minibus', 'Greek Row Loop', 'MB-406', 35, 'Liam Neeson', '(555) 044-5568', 70, 'Operational'],
        ['ADA Transit Van', 'Library Transport', 'AV-882', 8, 'Olivia Munn', '(555) 088-1124', 92, 'Operational'],
        ['EV Buggy', 'Stadium Security', 'EV-003', 4, 'Officer Jones', '(555) 911-0003', 45, 'Operational'],
        ['Utility Cart', 'IT Hardware Deploy', 'UC-103', 2, 'Tech Support', '(555) 888-1113', 15, 'In Maintenance']
    ];
    foreach($seed_fleet as $item) {
        $vt = mysqli_real_escape_string($conn, $item[0]);
        $br = mysqli_real_escape_string($conn, $item[1]);
        $vp = mysqli_real_escape_string($conn, $item[2]);
        $cap = intval($item[3]);
        $dn = mysqli_real_escape_string($conn, $item[4]);
        $dc = mysqli_real_escape_string($conn, $item[5]);
        $bl = intval($item[6]);
        $st = mysqli_real_escape_string($conn, $item[7]);
        mysqli_query($conn, "INSERT INTO transport (vehicle_type, bus_route, vehicle_plate, capacity, driver_name, driver_contact, battery_level, status) VALUES ('$vt', '$br', '$vp', $cap, '$dn', '$dc', $bl, '$st')");
    }
}

include 'header.php';

$total_fleet = getCount($conn, 'transport');
$active_fleet = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM transport WHERE status='Operational'"))['c'];
$fleet_capacity = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(capacity) as total FROM transport WHERE status='Operational'"));
$total_seats = $fleet_capacity['total'] ?: 0;
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

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap: wrap; gap:15px;}
    .status-op { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-maint { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .plate-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--text-dark); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 2px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="dark"] .plate-box { color: var(--brand-secondary); }

    /* VIEW TOGGLE CSS */
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--brand-secondary); color: var(--brand-primary); font-weight: 900;}
    [data-theme="light"] .view-btn.active-view { background: var(--brand-primary); color: #fff;}

    /* WINDOWED GRID VIEW CSS */
    .fleet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .fleet-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .fleet-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="light"] .fleet-card:hover { border-color: var(--brand-primary); }
    .fleet-card.dimmed { opacity: 0.6; filter: grayscale(50%); }
    
    .fc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .fc-title { font-family: var(--heading-font); font-size: 1.25rem; font-weight: 900; color: var(--text-dark); margin-bottom: 15px; line-height: 1.3;}
    .fc-detail { display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 10px; font-weight: 600;}
    .fc-detail i { color: var(--brand-secondary); width: 16px; text-align: center; margin-top: 3px;}
    [data-theme="light"] .fc-detail i { color: var(--brand-primary); }
    
    .fc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid var(--brand-secondary);">
    <h1 style="color: var(--brand-secondary); font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Transit Fleet Hub</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Manage dispatch routing, vehicle maintenance, and EV battery telemetry.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-bus stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total_fleet ?></div>
            <div class="stat-lbl">Registered Vehicles</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $active_fleet ?></div>
            <div class="stat-lbl">Active on Route</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-users stat-icon" style="color:var(--brand-accent);"></i>
        <div>
            <div class="stat-val" style="color:var(--brand-accent);"><?= $total_seats ?></div>
            <div class="stat-lbl">Active Seat Capacity</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; flex-wrap:wrap; align-items:center;">
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')" title="List View"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')" title="Windowed Grid View"><i class="fas fa-th-large"></i></button>
        </div>

        <input type="text" id="searchTransportLocal" onkeyup="filterFleet()" placeholder="&#xf002; Search Driver or Plate..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 250px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterFleet()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Conditions</option>
            <option value="Operational">Operational</option>
            <option value="In Maintenance">In Maintenance</option>
        </select>
        <select id="filterRoute" onchange="filterFleet()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Routes">All Transit Routes</option>
            <?php
            $r_res = mysqli_query($conn, "SELECT DISTINCT bus_route FROM transport ORDER BY bus_route ASC");
            while($r = mysqli_fetch_assoc($r_res)) { echo "<option value='{$r['bus_route']}'>{$r['bus_route']}</option>"; }
            ?>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Fleet Manifest...')"><i class="fas fa-file-csv"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px;" onclick="openFleetModal()"><i class="fas fa-plus"></i> Add Vehicle</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:20%;">Vehicle & Plate</th>
                <th>Route Assignment</th>
                <th style="width:20%;">Fuel / EV Battery</th>
                <th>Driver Info</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="fleetTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM transport ORDER BY status ASC, bus_route ASC");
            $all_fleet = [];
            
            while($row = mysqli_fetch_assoc($res)) {
                $all_fleet[] = $row;
                $st = $row['status'];
                $st_class = $st == 'Operational' ? 'status-op' : 'status-maint';
                $row_style = $st == 'In Maintenance' ? "opacity: 0.65; filter: grayscale(40%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $vt = $row['vehicle_type'];
                $icon = 'fa-bus';
                if(strpos($vt, 'Shuttle') !== false || strpos($vt, 'Minibus') !== false) $icon = 'fa-shuttle-van';
                if(strpos($vt, 'ADA') !== false) $icon = 'fa-wheelchair';
                if(strpos($vt, 'Buggy') !== false || strpos($vt, 'Cart') !== false) $icon = 'fa-car-side';
                
                $bl = intval($row['battery_level']);
                $bar_color = $bl > 50 ? '#10b981' : ($bl > 20 ? '#f59e0b' : '#ef4444');
                $bl_icon = $bl > 20 ? 'fa-battery-full' : 'fa-battery-quarter';

                $contact_str = $row['driver_contact'] != 'N/A' ? "<a href='tel:{$row['driver_contact']}' style='color:var(--text-light); text-decoration:none;' onmouseover='this.style.color=\"var(--brand-secondary)\"' onmouseout='this.style.color=\"var(--text-light)\"'><i class='fas fa-phone-alt'></i> {$row['driver_contact']}</a>" : "<span style='opacity:0.5;'><i class='fas fa-phone-slash'></i> No Contact</span>";

                echo "
                <tr class='filter-target' style='$row_style' data-stat='{$st}' data-route='{$row['bus_route']}'>
                    <td>
                        <div class='plate-box'>{$row['vehicle_plate']}</div>
                        <div style='font-size:0.8rem; color:var(--text-light); margin-top:8px; font-weight:800; text-transform:uppercase;'><i class='fas {$icon}' style='margin-right:6px;'></i> {$vt}</div>
                    </td>
                    <td>
                        <strong style='color:var(--text-dark); font-size:1.1rem;'><i class='fas fa-location-arrow' style='color:var(--brand-secondary); margin-right:8px; font-size:0.9rem;'></i> {$row['bus_route']}</strong>
                        <div style='font-size:0.8rem; color:var(--text-light); margin-top:5px;'><i class='fas fa-users'></i> {$row['capacity']} Seats Max</div>
                    </td>
                    <td>
                        <div style='font-weight:900; color:{$bar_color};'><i class='fas {$bl_icon}'></i> {$bl}% Charge</div>
                        <div style='width: 100%; height: 6px; background: var(--border-light); margin-top: 6px; border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$bl}%; background:{$bar_color};'></div></div>
                    </td>
                    <td>
                        <strong style='color:var(--text-dark);'>{$row['driver_name']}</strong><br>
                        <div style='font-size:0.85rem; font-weight:700; margin-top:4px;'>{$contact_str}</div>
                    </td>
                    <td><span class='status-pill {$st_class}'>{$st}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openFleetModal($js_data)'><i class='fas fa-pen'></i></button>
                            <a href='?toggle_fleet={$row['id']}' class='table-btn' style='border-color:#f59e0b; color:#f59e0b;' onclick='systemToast(\"Toggling Service Status...\")'><i class='fas fa-wrench'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Retiring Vehicle...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="gridView" class="fleet-grid" style="display:none;">
    <?php
    foreach($all_fleet as $row) {
        $st = $row['status'];
        $st_class = $st == 'Operational' ? 'status-op' : 'status-maint';
        $dim_class = $st == 'In Maintenance' ? 'dimmed' : '';
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        
        $vt = $row['vehicle_type'];
        $icon = 'fa-bus';
        if(strpos($vt, 'Shuttle') !== false || strpos($vt, 'Minibus') !== false) $icon = 'fa-shuttle-van';
        if(strpos($vt, 'ADA') !== false) $icon = 'fa-wheelchair';
        if(strpos($vt, 'Buggy') !== false || strpos($vt, 'Cart') !== false) $icon = 'fa-car-side';
        
        $bl = intval($row['battery_level']);
        $bar_color = $bl > 50 ? '#10b981' : ($bl > 20 ? '#f59e0b' : '#ef4444');
        $bl_icon = $bl > 20 ? 'fa-battery-full' : 'fa-battery-quarter';

        $bdr_color = $st == 'Operational' ? '#10b981' : '#f59e0b';
        if($st == 'Operational' && $bl <= 20) $bdr_color = '#ef4444'; // Red border if low battery even if operational

        echo "
        <div class='fleet-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}' data-route='{$row['bus_route']}'>
            <div class='fc-header'>
                <div class='plate-box' style='font-size:0.9rem; padding:4px 8px;'>{$row['vehicle_plate']}</div>
                <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
            </div>
            
            <div class='fc-title'><i class='fas {$icon}' style='color:var(--brand-secondary); margin-right:8px;'></i> {$vt}</div>
            
            <div class='fc-detail'><i class='fas fa-location-arrow'></i> {$row['bus_route']}</div>
            <div class='fc-detail'><i class='fas fa-user-tie'></i> {$row['driver_name']}</div>
            <div class='fc-detail'><i class='fas fa-phone-alt'></i> {$row['driver_contact']}</div>
            <div class='fc-detail'><i class='fas fa-users'></i> {$row['capacity']} Seats Max</div>
            
            <div class='fc-footer'>
                <div style='flex-grow:1; margin-right:20px;'>
                    <div style='display:flex; justify-content:space-between; font-weight:800; color:{$bar_color}; margin-bottom:6px; font-size:0.75rem;'><span><i class='fas {$bl_icon}'></i> {$bl}% Charge</span></div>
                    <div style='width: 100%; height: 6px; background: var(--border-light); border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$bl}%; background:{$bar_color};'></div></div>
                </div>
                <div style='display:flex; gap:8px;'>
                    <button class='table-btn btn-resolve' style='padding:6px 10px;' onclick='openFleetModal($js_data)'><i class='fas fa-pen' style='margin:0;'></i></button>
                    <a href='?toggle_fleet={$row['id']}' class='table-btn' style='padding:6px 10px; border-color:#f59e0b; color:#f59e0b;' onclick='systemToast(\"Toggling Service Status...\")'><i class='fas fa-wrench' style='margin:0;'></i></a>
                </div>
            </div>
        </div>";
    }
    ?>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-bus" style="color:var(--brand-secondary);"></i> Provision Vehicle</h2>
        <form method="POST">
            <input type="hidden" name="save_fleet" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="vehicle_plate" id="vehicle_plate" placeholder="License Plate (e.g. ABC-1234)" required>
                <select name="vehicle_type" id="vehicle_type" required>
                    <option value="Standard Bus">Standard Bus</option>
                    <option value="Minibus">Minibus</option>
                    <option value="Express Shuttle">Express Shuttle</option>
                    <option value="ADA Transit Van">ADA Transit Van</option>
                    <option value="EV Buggy">EV Buggy</option>
                    <option value="Utility Cart">Utility Cart</option>
                </select>

                <input type="text" name="bus_route" id="bus_route" placeholder="Assigned Transit Route" style="grid-column: span 2;" required>
                
                <input type="number" name="capacity" id="capacity" placeholder="Seat Capacity" required>
                <input type="number" name="battery_level" id="battery_level" placeholder="Battery % (0-100)" max="100" required>

                <input type="text" name="driver_name" id="driver_name" placeholder="Driver Name" required>
                <input type="text" name="driver_contact" id="driver_contact" placeholder="Driver Contact No." required>
                
                <select name="status" id="status" style="grid-column: span 2;" required>
                    <option value="Operational">Operational</option>
                    <option value="In Maintenance">In Maintenance</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px;"><i class="fas fa-save"></i> Save Fleet Data</button>
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
        localStorage.setItem('campus_transport_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_transport_view', 'table');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const pref = localStorage.getItem('campus_transport_view') || 'table';
    setView(pref);
});

function filterFleet() {
    const sFilter = document.getElementById('filterStatus').value;
    const rFilter = document.getElementById('filterRoute').value;
    const searchQ = document.getElementById('searchTransportLocal').value.toLowerCase();
    
    // Select both TRs and Grid Cards
    const targets = document.querySelectorAll('.filter-target');
    
    targets.forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rRoute = el.getAttribute('data-route');
        const rText = el.innerText.toLowerCase();
        
        let show = true;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (rFilter !== 'All Routes' && rRoute !== rFilter) show = false;
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

function openFleetModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:var(--brand-secondary);"></i> Edit Vehicle';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('vehicle_plate').value = data.vehicle_plate;
        document.getElementById('vehicle_type').value = data.vehicle_type;
        document.getElementById('bus_route').value = data.bus_route;
        document.getElementById('capacity').value = data.capacity;
        document.getElementById('battery_level').value = data.battery_level;
        document.getElementById('driver_name').value = data.driver_name;
        document.getElementById('driver_contact').value = data.driver_contact;
        document.getElementById('status').value = data.status;
    } else {
        title.innerHTML = '<i class="fas fa-bus" style="color:var(--brand-secondary);"></i> Provision Vehicle';
        document.getElementById('edit_id').value = '';
        document.getElementById('vehicle_plate').value = '';
        document.getElementById('vehicle_type').value = 'Standard Bus';
        document.getElementById('bus_route').value = '';
        document.getElementById('capacity').value = '';
        document.getElementById('battery_level').value = '100';
        document.getElementById('driver_name').value = '';
        document.getElementById('driver_contact').value = '';
        document.getElementById('status').value = 'Operational';
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>