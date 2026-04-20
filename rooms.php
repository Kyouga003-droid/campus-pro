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

$cols = [
    "room_number VARCHAR(20)", "room_name VARCHAR(150)", "building VARCHAR(50)", 
    "room_type VARCHAR(50)", "capacity INT", "equipment_tags VARCHAR(255)", 
    "status VARCHAR(20) DEFAULT 'Available'", "last_cleaned DATETIME",
    "current_occupancy INT DEFAULT 0", 
    "hvac_status VARCHAR(20) DEFAULT 'Optimal'", 
    "is_locked BOOLEAN DEFAULT 0", 
    "lighting_status VARCHAR(10) DEFAULT 'Off'", 
    "next_booking DATETIME NULL", 
    "reported_issues TEXT", 
    "is_smart_room BOOLEAN DEFAULT 0" 
];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE campus_rooms ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM campus_rooms WHERE id = $id");
    header("Location: rooms.php"); exit();
}

if(isset($_GET['toggle_maint'])) {
    $id = intval($_GET['toggle_maint']);
    $res = mysqli_query($conn, "SELECT status FROM campus_rooms WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $new_status = $row['status'] == 'In Maintenance' ? 'Available' : 'In Maintenance';
        mysqli_query($conn, "UPDATE campus_rooms SET status = '$new_status' WHERE id = $id");
        header("Location: rooms.php"); exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_action'])) {
    if(!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        if ($_POST['mass_action_type'] === 'clean') {
            mysqli_query($conn, "UPDATE campus_rooms SET last_cleaned = NOW(), status = 'Available' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'lock') {
            mysqli_query($conn, "UPDATE campus_rooms SET is_locked = 1, status = 'Locked' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM campus_rooms WHERE id IN ($ids)");
        }
    }
    header("Location: rooms.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_room'])) {
    $rn = mysqli_real_escape_string($conn, $_POST['room_number']);
    $nm = mysqli_real_escape_string($conn, $_POST['room_name']);
    $bd = mysqli_real_escape_string($conn, $_POST['building']);
    $rt = mysqli_real_escape_string($conn, $_POST['room_type']);
    $cp = intval($_POST['capacity']);
    $eq = mysqli_real_escape_string($conn, $_POST['equipment_tags']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    $co = intval($_POST['current_occupancy']);
    $hs = mysqli_real_escape_string($conn, $_POST['hvac_status']);
    $ls = mysqli_real_escape_string($conn, $_POST['lighting_status']);
    $ri = mysqli_real_escape_string($conn, $_POST['reported_issues']);
    $il = isset($_POST['is_locked']) ? 1 : 0;
    $sm = isset($_POST['is_smart_room']) ? 1 : 0;

    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE campus_rooms SET room_number='$rn', room_name='$nm', building='$bd', room_type='$rt', capacity=$cp, equipment_tags='$eq', status='$st', current_occupancy=$co, hvac_status='$hs', lighting_status='$ls', reported_issues='$ri', is_locked=$il, is_smart_room=$sm WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO campus_rooms (room_number, room_name, building, room_type, capacity, equipment_tags, status, last_cleaned, current_occupancy, hvac_status, lighting_status, reported_issues, is_locked, is_smart_room) VALUES ('$rn', '$nm', '$bd', '$rt', $cp, '$eq', '$st', NOW(), $co, '$hs', '$ls', '$ri', $il, $sm)");
    }
    header("Location: rooms.php"); exit();
}

include 'header.php';

$total = getCount($conn, 'campus_rooms');
$avail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms WHERE status='Available'"))['c'] ?? 0;
$maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms WHERE status='In Maintenance'"))['c'] ?? 0;
$inuse = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM campus_rooms WHERE status='In Use'"))['c'] ?? 0;
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--card-bg); padding: 25px; border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; border-radius: 16px; transition: 0.2s;}
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .stat-icon { font-size: 2rem; color: var(--brand-secondary); display:flex; align-items:center; justify-content:center; width:50px; height:50px; background:var(--main-bg); border-radius:12px; }
    .stat-val { font-size: 1.8rem; font-weight: 700; color: var(--text-dark); line-height: 1; margin-bottom: 4px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 500; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px;}

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 25px; background: var(--card-bg); border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap: wrap; gap: 15px;}
    
    .status-pill { padding: 6px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 20px; display:inline-block; }
    .st-Available { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .st-In-Use { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
    .st-Maintenance { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
    .st-Locked { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    
    .view-toggle { display: flex; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; padding:2px;}
    .view-btn { padding: 8px 16px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1rem; border:none; background:transparent; border-radius: 18px;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--card-bg); color: var(--text-dark); box-shadow: var(--soft-shadow);}
    
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative;}
    .data-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-color: var(--text-light); }
    
    .dc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    .dc-title { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px;}
    .dc-sub { font-size: 0.85rem; color: var(--text-light); }
    
    .cap-bar-wrap { width:100%; height:6px; background:var(--main-bg); border-radius:3px; margin: 15px 0 5px; overflow:hidden;}
    .cap-bar-fill { height:100%; background:var(--brand-secondary); transition:0.4s;}
    
    .tag-chip { display:inline-block; padding: 4px 10px; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 20px; font-size: 0.7rem; font-weight: 500; color: var(--text-light); margin: 2px 2px 2px 0; }
    
    .cb-sel { width: 18px; height: 18px; accent-color: var(--text-dark); cursor: pointer; }
    .flt-sel { border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 20px; background: transparent; color: var(--text-dark); font-weight: 500; font-size: 0.9rem; outline:none; }
    .flt-sel:focus { border-color: var(--text-light); }

    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px;}
    .page-btn { background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; color: var(--text-dark); transition: 0.2s; box-shadow: var(--soft-shadow);}
    .page-btn:hover { background: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow:none;}
</style>

<div style="margin-bottom: 30px;">
    <h1 style="font-size: 2.2rem; font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px;">Space & Facilities</h1>
    <p style="color: var(--text-light); font-size: 1rem; margin-top: 5px;">Monitor campus rooms, occupancy, smart devices, and maintenance logs.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-door-open"></i></div>
        <div><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Total Spaces</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981;"><i class="fas fa-check-circle"></i></div>
        <div><div class="stat-val"><?= $avail ?></div><div class="stat-lbl">Available</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#3b82f6;"><i class="fas fa-users"></i></div>
        <div><div class="stat-val"><?= $inuse ?></div><div class="stat-lbl">In Use</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#f59e0b;"><i class="fas fa-tools"></i></div>
        <div><div class="stat-val"><?= $maint ?></div><div class="stat-lbl">Maintenance</div></div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewTable" class="view-btn active-view" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button type="button" id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchRoomLocal" onkeyup="filterMatrix()" placeholder="Search facility..." class="flt-sel" style="width: 250px;">
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Statuses</option>
            <option value="Available">Available</option>
            <option value="In Use">In Use</option>
            <option value="In Maintenance">In Maintenance</option>
            <option value="Locked">Locked</option>
        </select>
        <select id="filterType" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Types</option>
            <option value="Classroom">Classroom</option>
            <option value="Laboratory">Laboratory</option>
            <option value="Lecture Hall">Lecture Hall</option>
            <option value="Office">Office</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('roomTable', 'facilities_export')"><i class="fas fa-download"></i> Export</button>
        <button type="button" class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New Space</button>
    </div>
</form>

<form method="POST" id="massForm">
    <input type="hidden" name="mass_action" value="1">
    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center; background: var(--card-bg); padding: 15px 25px; border: 1px solid var(--border-color); border-radius: 12px; box-shadow:var(--soft-shadow);">
        <span style="font-weight: 600; font-size:0.9rem;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding: 8px 15px;">
            <option value="clean">Trigger Cleaning Protocol</option>
            <option value="lock">Lock Facilities</option>
            <option value="delete">Delete Records</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation?')">Apply</button>
    </div>

    <div id="tableView" class="table-responsive">
        <table id="roomTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c => c.checked = this.checked)"></th>
                    <th>Space ID</th>
                    <th>Details & Capacity</th>
                    <th>Smart & HVAC</th>
                    <th>Status & Cleaning</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody id="filterTableBody">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM campus_rooms ORDER BY building ASC, room_number ASC");
                $all_data = [];
                while($row = mysqli_fetch_assoc($res)) {
                    $all_data[] = $row;
                    $st = str_replace(' ', '-', $row['status']);
                    $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    $pct = $row['capacity'] > 0 ? ($row['current_occupancy'] / $row['capacity']) * 100 : 0;
                    $sm_icon = $row['is_smart_room'] ? "<i class='fas fa-wifi' style='color:#3b82f6;' title='Smart Room'></i>" : "";
                    $lc_icon = $row['is_locked'] ? "<i class='fas fa-lock' style='color:#ef4444;' title='Locked'></i>" : "";

                    echo "
                    <tr class='paginate-row filter-target' data-stat='{$row['status']}' data-type='{$row['room_type']}'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$row['id']}' class='cb-item cb-sel'></td>
                        <td>
                            <strong style='font-size:1.05rem; color:var(--text-dark);'>{$row['room_number']}</strong> {$sm_icon} {$lc_icon}<br>
                            <span style='font-size:0.8rem; color:var(--text-light);'>{$row['building']}</span>
                        </td>
                        <td>
                            <div style='font-weight:500;'>{$row['room_name']} <span style='color:var(--text-light); font-size:0.8rem;'>({$row['room_type']})</span></div>
                            <div style='font-size:0.8rem; margin-top:4px;'>Occ: {$row['current_occupancy']} / {$row['capacity']}</div>
                            <div class='cap-bar-wrap' style='margin:4px 0 0;'><div class='cap-bar-fill' style='width:{$pct}%;'></div></div>
                        </td>
                        <td>
                            <div style='font-size:0.85rem;'><i class='fas fa-wind'></i> HVAC: {$row['hvac_status']}</div>
                            <div style='font-size:0.85rem;'><i class='far fa-lightbulb'></i> Lights: {$row['lighting_status']}</div>
                        </td>
                        <td>
                            <span class='status-pill st-{$st}'>{$row['status']}</span><br>
                            <span style='font-size:0.75rem; color:var(--text-light);'>Cleaned: " . date('M d, H:i', strtotime($row['last_cleaned'])) . "</span>
                        </td>
                        <td class='action-col'>
                            <div class='table-actions-cell'>
                                <a href='?toggle_maint={$row['id']}' class='table-btn' title='Toggle Maint'><i class='fas fa-tools'></i></a>
                                <button type='button' class='table-btn' onclick='openModal({$js})'><i class='fas fa-pen'></i></button>
                                <a href='?del={$row['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
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
            $st = str_replace(' ', '-', $row['status']);
            $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            $pct = $row['capacity'] > 0 ? ($row['current_occupancy'] / $row['capacity']) * 100 : 0;
            $tags = explode(',', $row['equipment_tags']);
            
            echo "
            <div class='data-card paginate-card filter-target' data-stat='{$row['status']}' data-type='{$row['room_type']}'>
                <div class='dc-header'>
                    <div>
                        <div class='dc-title'>{$row['room_number']} " . ($row['is_smart_room'] ? "<i class='fas fa-wifi' style='color:#3b82f6; font-size:0.9rem;'></i>" : "") . "</div>
                        <div class='dc-sub'>{$row['building']} • {$row['room_type']}</div>
                    </div>
                    <span class='status-pill st-{$st}'>{$row['status']}</span>
                </div>
                
                <div style='font-size:0.95rem; font-weight:500; margin-bottom:15px;'>{$row['room_name']}</div>
                
                <div style='display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-light);'>
                    <span>Occupancy</span> <span>{$row['current_occupancy']} / {$row['capacity']}</span>
                </div>
                <div class='cap-bar-wrap'><div class='cap-bar-fill' style='width:{$pct}%;'></div></div>
                
                <div style='margin-top:15px; margin-bottom:20px;'>
                    " . implode('', array_map(function($t) { return trim($t) ? "<span class='tag-chip'>$t</span>" : ""; }, $tags)) . "
                </div>

                <div style='margin-top:auto; padding-top:15px; border-top:1px solid var(--border-light); display:flex; justify-content:space-between;'>
                    <a href='?toggle_maint={$row['id']}' class='table-btn'><i class='fas fa-tools'></i></a>
                    <button type='button' class='table-btn' onclick='openModal({$js})'><i class='fas fa-pen'></i> Edit</button>
                </div>
            </div>";
        }
        ?>
    </div>

    <div class="pagination-ctrl">
        <button type="button" class="page-btn" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> Prev</button>
        <span style="font-weight:600; font-size:0.9rem;" id="pageIndicator">Page 1</span>
        <button type="button" class="page-btn" id="nextPage" onclick="changePage(1)">Next <i class="fas fa-chevron-right"></i></button>
    </div>
</form>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 700px;">
        <button type="button" class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 25px;"><i class="fas fa-door-open"></i> Space Configuration</h2>
        
        <form method="POST">
            <input type="hidden" name="save_room" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="room_number" id="room_number" placeholder="Room Number (e.g. CR-101)" required>
                <select name="status" id="status" required>
                    <option value="Available">Status: Available</option>
                    <option value="In Use">Status: In Use</option>
                    <option value="In Maintenance">Status: Maintenance</option>
                    <option value="Locked">Status: Locked</option>
                </select>
                
                <input type="text" name="room_name" id="room_name" placeholder="Room Name / Alias" required style="grid-column: span 2;">
                
                <input type="text" name="building" id="building" placeholder="Building / Wing" required>
                <select name="room_type" id="room_type" required>
                    <option value="Classroom">Classroom</option>
                    <option value="Laboratory">Laboratory</option>
                    <option value="Lecture Hall">Lecture Hall</option>
                    <option value="Office">Office</option>
                    <option value="Storage">Storage</option>
                </select>
                
                <input type="number" name="capacity" id="capacity" placeholder="Max Capacity" required>
                <input type="number" name="current_occupancy" id="current_occupancy" placeholder="Current Occupancy" value="0">
                
                <select name="hvac_status" id="hvac_status">
                    <option value="Optimal">HVAC: Optimal</option>
                    <option value="Warning">HVAC: Warning</option>
                    <option value="Offline">HVAC: Offline</option>
                </select>
                <select name="lighting_status" id="lighting_status">
                    <option value="Off">Lights: Off</option>
                    <option value="On">Lights: On</option>
                    <option value="Auto">Lights: Auto/Smart</option>
                </select>

                <input type="text" name="equipment_tags" id="equipment_tags" placeholder="Equipment (e.g. Projector, Whiteboard, PCs)" style="grid-column: span 2;">
                <textarea name="reported_issues" id="reported_issues" placeholder="Maintenance logs or reported damages..." style="grid-column: span 2; height:80px; resize:none;"></textarea>

                <div style="grid-column: span 2; display:flex; gap: 20px; padding:15px; border:1px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="is_smart_room" id="is_smart_room" class="cb-sel">
                        <label for="is_smart_room" style="font-weight:500; font-size:0.85rem; cursor:pointer;">Smart Room Connected</label>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="is_locked" id="is_locked" class="cb-sel" style="accent-color:#ef4444;">
                        <label for="is_locked" style="font-weight:500; font-size:0.85rem; color:#ef4444; cursor:pointer;">Physically Locked</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; justify-content:center;">Save Configuration</button>
        </form>
    </div>
</div>

<script>
let currentView = 'table';
let currentPage = 1;
const itemsPerPage = 10;

function setView(view) {
    currentView = view;
    const table = document.getElementById('tableView');
    const grid = document.getElementById('gridView');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');
    
    if(view === 'grid') {
        table.style.display = 'none'; grid.style.display = 'grid';
        btnGrid.classList.add('active-view'); btnTable.classList.remove('active-view');
        localStorage.setItem('campus_room_view', 'grid');
    } else {
        table.style.display = 'block'; grid.style.display = 'none';
        btnTable.classList.add('active-view'); btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_room_view', 'table');
    }
    paginate();
}

function paginate() {
    const selector = currentView === 'table' ? '.paginate-row' : '.paginate-card';
    const items = Array.from(document.querySelectorAll(selector)).filter(i => !i.hasAttribute('data-hide-local'));
    const totalPages = Math.ceil(items.length / itemsPerPage) || 1;
    
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    document.getElementById('pageIndicator').innerText = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;
    
    document.querySelectorAll(selector).forEach(item => item.style.display = 'none');
    items.forEach((item, index) => {
        if (index >= (currentPage - 1) * itemsPerPage && index < currentPage * itemsPerPage) {
            item.style.display = currentView === 'table' ? 'table-row' : 'flex';
        }
    });
}

function changePage(delta) { currentPage += delta; paginate(); }

function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const tFilter = document.getElementById('filterType').value;
    const searchQ = document.getElementById('searchRoomLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rType = el.getAttribute('data-type');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if (sFilter !== 'All' && rStat !== sFilter) show = false;
        if (tFilter !== 'All' && rType !== tFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) el.removeAttribute('data-hide-local'); 
        else el.setAttribute('data-hide-local', 'true'); 
    });
    currentPage = 1;
    paginate();
}

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen"></i> Edit Space Data';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('room_number').value = data.room_number;
        document.getElementById('room_name').value = data.room_name;
        document.getElementById('building').value = data.building;
        document.getElementById('room_type').value = data.room_type;
        document.getElementById('capacity').value = data.capacity;
        document.getElementById('current_occupancy').value = data.current_occupancy || 0;
        document.getElementById('status').value = data.status;
        document.getElementById('equipment_tags').value = data.equipment_tags;
        document.getElementById('hvac_status').value = data.hvac_status || 'Optimal';
        document.getElementById('lighting_status').value = data.lighting_status || 'Off';
        document.getElementById('reported_issues').value = data.reported_issues || '';
        document.getElementById('is_smart_room').checked = data.is_smart_room == 1;
        document.getElementById('is_locked').checked = data.is_locked == 1;
    } else {
        title.innerHTML = '<i class="fas fa-door-open"></i> Register Space';
        document.getElementById('edit_id').value = '';
        document.getElementById('room_number').value = '';
        document.getElementById('room_name').value = '';
        document.getElementById('building').value = '';
        document.getElementById('room_type').value = 'Classroom';
        document.getElementById('capacity').value = '';
        document.getElementById('current_occupancy').value = '0';
        document.getElementById('status').value = 'Available';
        document.getElementById('equipment_tags').value = '';
        document.getElementById('hvac_status').value = 'Optimal';
        document.getElementById('lighting_status').value = 'Off';
        document.getElementById('reported_issues').value = '';
        document.getElementById('is_smart_room').checked = false;
        document.getElementById('is_locked').checked = false;
    }
    modal.style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => { 
    setView(localStorage.getItem('campus_room_view') || 'table'); 
});
</script>

<?php include 'footer.php'; ?>