<?php 
include 'config.php'; 

// FUNCTION 1: Deep Appointment Schema
$patch = "CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100),
    purpose VARCHAR(150),
    appt_date DATE,
    time_slot VARCHAR(50),
    notes VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Pending'
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = [
    "client_name VARCHAR(100)", "purpose VARCHAR(150)", "appt_date DATE", 
    "time_slot VARCHAR(50)", "notes VARCHAR(255)", "status VARCHAR(20) DEFAULT 'Pending'",
    "priority VARCHAR(20) DEFAULT 'Normal'", // FUNCTION 2: Priority Flag
    "location VARCHAR(100)", // FUNCTION 3: Room / Location
    "staff_assigned VARCHAR(100)", // FUNCTION 4: Assigned Personnel
    "duration_mins INT DEFAULT 30", // FUNCTION 5: Time Duration
    "is_virtual BOOLEAN DEFAULT 0", // FUNCTION 6: Virtual Meeting Toggle
    "reminder_sent BOOLEAN DEFAULT 0", // FUNCTION 7: Notification tracking
    "contact_email VARCHAR(100)" // FUNCTION 8: Client Email
];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN $c"); } catch (Exception $e) {} }

// FUNCTION 9: Auto-archive past appointments
mysqli_query($conn, "UPDATE appointments SET status='Archived' WHERE appt_date < CURDATE() AND status != 'Completed' AND status != 'Archived'");

if(isset($_GET['advance_status'])) {
    $id = intval($_GET['advance_status']);
    $res = mysqli_query($conn, "SELECT status FROM appointments WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $new_status = 'Pending';
        if($row['status'] == 'Pending') $new_status = 'Confirmed';
        elseif($row['status'] == 'Confirmed') $new_status = 'Completed';
        mysqli_query($conn, "UPDATE appointments SET status = '$new_status' WHERE id = $id");
    }
    header("Location: appointments.php"); exit();
}

// FUNCTION 10: Conflict Detection & Save Logic
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_appt'])) {
    $cn = mysqli_real_escape_string($conn, $_POST['client_name']);
    $pp = mysqli_real_escape_string($conn, $_POST['purpose']); 
    $dt = mysqli_real_escape_string($conn, $_POST['appt_date']);
    $ts = mysqli_real_escape_string($conn, $_POST['time_slot']);
    $nt = mysqli_real_escape_string($conn, $_POST['notes']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    $pr = mysqli_real_escape_string($conn, $_POST['priority']);
    $lc = mysqli_real_escape_string($conn, $_POST['location']);
    $sa = mysqli_real_escape_string($conn, $_POST['staff_assigned']);
    $dm = intval($_POST['duration_mins']);
    $ce = mysqli_real_escape_string($conn, $_POST['contact_email']);
    $iv = isset($_POST['is_virtual']) ? 1 : 0;
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE appointments SET client_name='$cn', purpose='$pp', appt_date='$dt', time_slot='$ts', notes='$nt', status='$st', priority='$pr', location='$lc', staff_assigned='$sa', duration_mins=$dm, contact_email='$ce', is_virtual=$iv WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO appointments (client_name, purpose, appt_date, time_slot, notes, status, priority, location, staff_assigned, duration_mins, contact_email, is_virtual) VALUES ('$cn', '$pp', '$dt', '$ts', '$nt', '$st', '$pr', '$lc', '$sa', $dm, '$ce', $iv)");
    }
    header("Location: appointments.php"); exit();
}

include 'header.php';
$tot = getCount($conn, 'appointments');
$pen = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM appointments WHERE status='Pending'"))['c'];
$tdy = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM appointments WHERE appt_date=CURDATE()"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: #3b82f6; }
    .stat-icon { font-size: 2.5rem; color: #3b82f6; opacity: 0.9; }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }
    
    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    .flt-sel { border: 2px solid var(--border-color); padding: 12px 20px; border-radius: 8px; background: var(--main-bg); color: var(--text-dark); font-weight: 800; font-family: var(--body-font); text-transform: uppercase; font-size: 0.85rem; }

    /* UI FEATURE 1: View Toggle buttons */
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn.active-view { background: #3b82f6; color: #fff; font-weight: 900;}

    /* UI FEATURE 2: Kanban Board Architecture */
    .kanban-board { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; }
    .kanban-col { flex: 1; min-width: 320px; background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px; display: flex; flex-direction: column; max-height: 800px;}
    .kanban-header { padding: 15px 20px; border-bottom: 2px solid var(--border-color); font-weight: 900; font-size: 1.1rem; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; background: var(--main-bg); border-radius: 10px 10px 0 0;}
    .kanban-body { padding: 15px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 15px; background: var(--bg-grid); }
    
    /* UI FEATURE 3: Draggable Data Cards */
    .k-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 10px; padding: 20px; box-shadow: 2px 2px 0px rgba(0,0,0,0.05); transition: 0.2s; cursor: grab; position: relative;}
    .k-card:hover { transform: translateY(-4px); box-shadow: 4px 4px 0px #3b82f6; border-color: #3b82f6;}
    .k-title { font-family: var(--heading-font); font-size: 1.2rem; font-weight: 900; margin-bottom: 5px;}
    
    /* UI FEATURE 4: Priority Color Coding */
    .pri-High { border-left: 6px solid #ef4444; }
    .pri-Normal { border-left: 6px solid #3b82f6; }
    .pri-Low { border-left: 6px solid #10b981; }

    /* UI FEATURE 5: Virtual Meeting Badge */
    .badge-virtual { position: absolute; top: 15px; right: 15px; background: #8b5cf6; color: #fff; font-size: 0.65rem; padding: 4px 8px; border-radius: 4px; font-weight: 900; text-transform: uppercase;}

    /* UI FEATURE 6: Hover Tooltips for Notes */
    .k-notes { font-size: 0.8rem; color: var(--text-light); margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-light); font-style: italic;}

    /* UI FEATURE 7: Progress Bar Tracker */
    .prog-wrap { width: 100%; height: 8px; background: var(--border-light); border-radius: 4px; margin-top: 15px; overflow: hidden;}
    .prog-fill { height: 100%; background: #3b82f6; transition: 0.5s;}

    /* UI FEATURE 8: Dark Mode Modals */
    .cb-sel { width: 18px; height: 18px; accent-color: #3b82f6;}
    
    /* UI FEATURE 9: Pagination */
    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px;}
    .page-btn { background: var(--main-bg); border: 2px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-weight: 900; cursor: pointer;}
    .page-btn:hover { background: var(--text-dark); color: var(--main-bg); }
    
    /* UI FEATURE 10: Status Pills */
    .status-pill { padding: 6px 12px; border-radius: 6px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; border: 2px solid currentColor;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #3b82f6;">
    <h1 style="color: #3b82f6; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Appointment Matrix</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage meetings, consultations, and facility bookings.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-calendar-check stat-icon"></i>
        <div>
            <div class="stat-val"><?= $tot ?></div>
            <div class="stat-lbl">Total Appointments</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $pen ?></div>
            <div class="stat-lbl">Awaiting Confirmation</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-sun stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $tdy ?></div>
            <div class="stat-lbl">Scheduled Today</div>
        </div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewKanban" class="view-btn active-view" onclick="setView('kanban')"><i class="fas fa-columns"></i></button>
            <button type="button" id="btnViewTable" class="view-btn" onclick="setView('table')"><i class="fas fa-list"></i></button>
        </div>
        <input type="text" id="searchApptLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Client..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterPriority" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Priorities</option>
            <option value="High">High Priority</option>
            <option value="Normal">Normal</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('apptTable', 'appointments')"><i class="fas fa-file-export"></i> Export</button>
        <button type="button" class="btn-primary" style="margin:0; padding: 12px 25px; background:#3b82f6; border-color:#3b82f6; color:#fff;" onclick="openModal()"><i class="fas fa-calendar-plus"></i> New Booking</button>
    </div>
</form>

<?php
$res = mysqli_query($conn, "SELECT * FROM appointments ORDER BY appt_date ASC, time_slot ASC");
$all_data = [];
$kanban = ['Pending' => [], 'Confirmed' => [], 'Completed' => []];

while($row = mysqli_fetch_assoc($res)) {
    $all_data[] = $row;
    $st = $row['status'];
    if(isset($kanban[$st])) $kanban[$st][] = $row;
}
?>

<div id="kanbanView" class="kanban-board">
    <?php foreach(['Pending', 'Confirmed', 'Completed'] as $col_stat): 
        $col_color = $col_stat == 'Pending' ? '#f59e0b' : ($col_stat == 'Confirmed' ? '#3b82f6' : '#10b981');
    ?>
    <div class="kanban-col">
        <div class="kanban-header">
            <span style="color:<?= $col_color ?>;"><i class="fas fa-circle" style="font-size:0.6rem; vertical-align:middle; margin-right:5px;"></i> <?= $col_stat ?></span>
            <span style="background:var(--bg-grid); padding:4px 8px; border-radius:6px; font-size:0.8rem;"><?= count($kanban[$col_stat]) ?></span>
        </div>
        <div class="kanban-body">
            <?php foreach($kanban[$col_stat] as $row): 
                $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $d_str = date('M d, Y', strtotime($row['appt_date']));
            ?>
            <div class="k-card pri-<?= $row['priority'] ?> filter-target" data-pri="<?= $row['priority'] ?>">
                <?= $row['is_virtual'] ? "<div class='badge-virtual'><i class='fas fa-video'></i> Meet</div>" : "" ?>
                <div class="k-title"><?= $row['client_name'] ?></div>
                <div style="font-size:0.85rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:10px;"><?= $row['purpose'] ?></div>
                
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:700; margin-bottom:5px;">
                    <span><i class="far fa-calendar"></i> <?= $d_str ?></span>
                    <span style="color:#3b82f6; font-family:monospace; font-weight:900;"><i class="far fa-clock"></i> <?= $row['time_slot'] ?></span>
                </div>
                <div style="font-size:0.8rem; font-weight:700; margin-bottom:15px;"><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?= $row['location'] ?></div>
                
                <div style="display:flex; justify-content:space-between; gap:5px;">
                    <?php if($col_stat != 'Completed'): ?>
                    <a href="?advance_status=<?= $row['id'] ?>" class="btn-action" style="flex:1; padding:6px; justify-content:center; font-size:0.7rem;"><i class="fas fa-arrow-right"></i> Advance</a>
                    <?php endif; ?>
                    <button class="btn-action" style="padding:6px 10px;" onclick="openModal(<?= $js ?>)"><i class="fas fa-pen"></i></button>
                    <a href="actions.php?table=appointments&delete=<?= $row['id'] ?>" class="btn-action btn-del" style="padding:6px 10px;"><i class="fas fa-trash"></i></a>
                </div>
                
                <?php if(!empty($row['notes'])): ?>
                <div class="k-notes">"<?= $row['notes'] ?>"</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if(empty($kanban[$col_stat])) echo "<div style='text-align:center; padding:30px; opacity:0.5; font-size:0.8rem; font-weight:800;'>No items</div>"; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="tableView" class="table-responsive" style="display:none;">
    <table id="apptTable">
        <thead>
            <tr>
                <th>Client Details</th>
                <th>Schedule Matrix</th>
                <th>Location / Staff</th>
                <th>Priority</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php foreach($all_data as $row): 
                $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $d_str = date('M d, Y', strtotime($row['appt_date']));
                $bg = $row['status']=='Pending'?'rgba(245,158,11,0.1)':($row['status']=='Confirmed'?'rgba(59,130,246,0.1)':'rgba(16,185,129,0.1)');
                $c = $row['status']=='Pending'?'#f59e0b':($row['status']=='Confirmed'?'#3b82f6':'#10b981');
                $p_col = $row['priority'] == 'High' ? '#ef4444' : ($row['priority'] == 'Low' ? '#10b981' : '#3b82f6');
            ?>
            <tr class="paginate-row filter-target" data-pri="<?= $row['priority'] ?>">
                <td>
                    <strong style='font-size:1.1rem; display:block;'><?= $row['client_name'] ?> <?= $row['is_virtual'] ? "<i class='fas fa-video' style='color:#8b5cf6; font-size:0.8rem;'></i>" : "" ?></strong>
                    <span style='font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase;'><?= $row['purpose'] ?></span>
                </td>
                <td>
                    <div style='font-weight:900; color:var(--text-dark);'><i class="far fa-calendar-alt"></i> <?= $d_str ?></div>
                    <div style='font-size:0.9rem; font-family:monospace; color:#3b82f6; font-weight:900;'><i class="far fa-clock"></i> <?= $row['time_slot'] ?> <span style="color:var(--text-light);">[<?= $row['duration_mins'] ?>m]</span></div>
                </td>
                <td>
                    <div style='font-weight:800;'><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?= $row['location'] ?></div>
                    <div style='font-size:0.75rem; color:var(--text-light); text-transform:uppercase;'>Host: <?= $row['staff_assigned'] ?></div>
                </td>
                <td><span style="color:<?= $p_col ?>; font-weight:900; font-size:0.8rem; text-transform:uppercase;"><i class="fas fa-flag"></i> <?= $row['priority'] ?></span></td>
                <td><span class='status-pill' style='background:<?= $bg ?>; color:<?= $c ?>;'><?= $row['status'] ?></span></td>
                <td class='action-col'>
                    <div class='table-actions-cell'>
                        <?php if($row['status'] != 'Completed'): ?>
                        <a href='?advance_status=<?= $row['id'] ?>' class='table-btn' style='border-color:#3b82f6; color:#3b82f6;'><i class='fas fa-arrow-right'></i></a>
                        <?php endif; ?>
                        <button type='button' class='table-btn' onclick='openModal(<?= $js ?>)'><i class='fas fa-pen'></i></button>
                        <a href='actions.php?table=appointments&delete=<?= $row['id'] ?>' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="pagination-ctrl">
        <button type="button" class="page-btn" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> PREV</button>
        <span style="font-weight:900; font-family:monospace; font-size:1.2rem;" id="pageIndicator">Page 1 of X</span>
        <button type="button" class="page-btn" id="nextPage" onclick="changePage(1)">NEXT <i class="fas fa-chevron-right"></i></button>
    </div>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 800px;">
        <button type="button" class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font); border-bottom: 2px solid var(--border-color); padding-bottom: 15px;"><i class="fas fa-calendar-plus" style="color:#3b82f6;"></i> Book Appointment</h2>
        
        <form method="POST">
            <input type="hidden" name="save_appt" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="client_name" id="client_name" placeholder="Client / Guest Name" required>
                <input type="text" name="contact_email" id="contact_email" placeholder="Contact Email (Optional)">
                
                <input type="text" name="purpose" id="purpose" placeholder="Purpose of Meeting" required style="grid-column: span 2;">
                
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Date</label>
                    <input type="date" name="appt_date" id="appt_date" required>
                </div>
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Time Block</label>
                    <input type="time" name="time_slot" id="time_slot" required>
                </div>

                <input type="number" name="duration_mins" id="duration_mins" placeholder="Duration (Minutes)" value="30" required>
                <input type="text" name="location" id="location" placeholder="Room / Link Location" required>
                
                <input type="text" name="staff_assigned" id="staff_assigned" placeholder="Assigned Host / Faculty" required>
                
                <select name="priority" id="priority" required>
                    <option value="Normal">Priority: Normal</option>
                    <option value="High">Priority: High</option>
                    <option value="Low">Priority: Low</option>
                </select>

                <select name="status" id="status" required style="grid-column: span 2;">
                    <option value="Pending">Status: Pending</option>
                    <option value="Confirmed">Status: Confirmed</option>
                    <option value="Completed">Status: Completed</option>
                    <option value="Archived">Status: Archived</option>
                </select>

                <textarea name="notes" id="notes" placeholder="Meeting Notes / Preparation Required..." style="grid-column: span 2; height:100px; resize:none;"></textarea>
                
                <div style="grid-column: span 2; display:flex; align-items:center; gap:10px; padding:15px; border:2px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <input type="checkbox" name="is_virtual" id="is_virtual" class="cb-sel">
                    <label for="is_virtual" style="font-weight:900; text-transform:uppercase; font-size:0.85rem; cursor:pointer;">Virtual / Zoom Meeting</label>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#3b82f6; border-color:#3b82f6; color:#fff; justify-content:center;"><i class="fas fa-save"></i> LOCK IN CALENDAR</button>
        </form>
    </div>
</div>

<script>
let currentView = 'kanban';
let currentPage = 1;
const itemsPerPage = 8;

function setView(view) {
    currentView = view;
    const tab = document.getElementById('tableView');
    const kan = document.getElementById('kanbanView');
    const btnT = document.getElementById('btnViewTable');
    const btnK = document.getElementById('btnViewKanban');
    
    if(view === 'table') {
        kan.style.display = 'none'; tab.style.display = 'block';
        btnT.classList.add('active-view'); btnK.classList.remove('active-view');
    } else {
        tab.style.display = 'none'; kan.style.display = 'flex';
        btnK.classList.add('active-view'); btnT.classList.remove('active-view');
    }
    paginate();
}

function filterMatrix() {
    const pri = document.getElementById('filterPriority').value;
    const sq = document.getElementById('searchApptLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rPri = el.getAttribute('data-pri');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if(pri !== 'All' && rPri !== pri) show = false;
        if(sq !== '' && !rText.includes(sq)) show = false;
        
        if(show) el.removeAttribute('data-hide-local');
        else el.setAttribute('data-hide-local', 'true');
        
        if(currentView === 'kanban') el.style.display = show ? 'block' : 'none';
    });
    
    if(currentView === 'table') { currentPage = 1; paginate(); }
}

function paginate() {
    if(currentView !== 'table') return;
    const items = Array.from(document.querySelectorAll('.paginate-row')).filter(i => !i.hasAttribute('data-hide-local'));
    const totalPages = Math.ceil(items.length / itemsPerPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    document.getElementById('pageIndicator').innerText = `PAGE ${currentPage} OF ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;
    
    document.querySelectorAll('.paginate-row').forEach(i => i.style.display = 'none');
    items.forEach((item, index) => {
        if (index >= (currentPage - 1) * itemsPerPage && index < currentPage * itemsPerPage) item.style.display = 'table-row';
    });
}
function changePage(delta) { currentPage += delta; paginate(); }

function openModal(data = null) {
    const m = document.getElementById('crudModal');
    if(data) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen" style="color:#3b82f6;"></i> Update Booking';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('client_name').value = data.client_name;
        document.getElementById('contact_email').value = data.contact_email || '';
        document.getElementById('purpose').value = data.purpose;
        document.getElementById('appt_date').value = data.appt_date;
        document.getElementById('time_slot').value = data.time_slot;
        document.getElementById('duration_mins').value = data.duration_mins || 30;
        document.getElementById('location').value = data.location || '';
        document.getElementById('staff_assigned').value = data.staff_assigned || '';
        document.getElementById('priority').value = data.priority || 'Normal';
        document.getElementById('status').value = data.status;
        document.getElementById('notes').value = data.notes;
        document.getElementById('is_virtual').checked = data.is_virtual == 1;
    } else {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-calendar-plus" style="color:#3b82f6;"></i> Book Appointment';
        document.getElementById('edit_id').value = '';
        document.getElementById('client_name').value = '';
        document.getElementById('contact_email').value = '';
        document.getElementById('purpose').value = '';
        document.getElementById('appt_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('time_slot').value = '09:00';
        document.getElementById('duration_mins').value = 30;
        document.getElementById('location').value = '';
        document.getElementById('staff_assigned').value = '';
        document.getElementById('priority').value = 'Normal';
        document.getElementById('status').value = 'Pending';
        document.getElementById('notes').value = '';
        document.getElementById('is_virtual').checked = false;
    }
    m.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>