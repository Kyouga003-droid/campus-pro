<?php
include 'config.php';

$patch = "CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id VARCHAR(20) UNIQUE,
    visitor_name VARCHAR(100),
    purpose VARCHAR(150),
    host_name VARCHAR(100),
    check_in DATETIME,
    check_out DATETIME NULL,
    status VARCHAR(20) DEFAULT 'Active'
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = ["visitor_id VARCHAR(20)", "visitor_name VARCHAR(100)", "purpose VARCHAR(150)", "host_name VARCHAR(100)", "check_in DATETIME", "check_out DATETIME NULL", "status VARCHAR(20) DEFAULT 'Active'"];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE visitors ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM visitors WHERE id = $id");
    header("Location: visitors.php"); exit();
}

if(isset($_GET['checkout'])) {
    $id = intval($_GET['checkout']);
    mysqli_query($conn, "UPDATE visitors SET status = 'Departed', check_out = NOW() WHERE id = $id");
    header("Location: visitors.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_visitor'])) {
    $vid = mysqli_real_escape_string($conn, $_POST['visitor_id']);
    $vn = mysqli_real_escape_string($conn, $_POST['visitor_name']);
    $pur = mysqli_real_escape_string($conn, $_POST['purpose']);
    $hn = mysqli_real_escape_string($conn, $_POST['host_name']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE visitors SET visitor_id='$vid', visitor_name='$vn', purpose='$pur', host_name='$hn', status='$st' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO visitors (visitor_id, visitor_name, purpose, host_name, check_in, status) VALUES ('$vid', '$vn', '$pur', '$hn', NOW(), '$st')");
    }
    header("Location: visitors.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $vn = ['Elon Musk', 'Bill Gates', 'Tim Cook', 'Satya Nadella', 'Sundar Pichai', 'Jeff Bezos', 'Mark Zuckerberg', 'Larry Page', 'Sergey Brin', 'Reed Hastings', 'Jack Dorsey', 'Evan Spiegel', 'Peter Thiel', 'Marc Andreessen', 'Reid Hoffman'];
    $purp = ['Guest Lecture', 'Campus Tour', 'Facility Inspection', 'Vendor Meeting', 'Academic Partnership', 'Job Interview', 'Equipment Delivery', 'IT Deployment'];
    $host = ['Dr. Alan Turing', 'Admin Board', 'Facilities Dept', 'IT Support', 'Engineering Council', 'Business Society'];
    
    for($i=0; $i<15; $i++) {
        $vid = "VIS-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $v = mysqli_real_escape_string($conn, $vn[$i]);
        $p = mysqli_real_escape_string($conn, $purp[array_rand($purp)]);
        $h = mysqli_real_escape_string($conn, $host[array_rand($host)]);
        $stat = (rand(1,10) > 4) ? 'Active' : 'Departed';
        $ci = date('Y-m-d H:i:s', strtotime('-'.rand(1, 10).' hours'));
        $co = $stat == 'Departed' ? "'".date('Y-m-d H:i:s', strtotime('-'.rand(1, 30).' minutes'))."'" : "NULL";
        mysqli_query($conn, "INSERT INTO visitors (visitor_id, visitor_name, purpose, host_name, check_in, check_out, status) VALUES ('$vid', '$v', '$p', '$h', '$ci', $co, '$stat')");
    }
}

include 'header.php';

$total = getCount($conn, 'visitors');
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE status='Active'"))['c'];
$departed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE status='Departed'"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: #ec4899; }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: #ec4899; opacity: 0.9; }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }
    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    .status-active { background: rgba(236, 72, 153, 0.1); color: #ec4899; border-color: #ec4899; }
    .status-leave { background: var(--bg-grid); color: var(--text-light); border-color: var(--text-light); }
    .id-box { font-weight:900; font-family:monospace; font-size:1.1rem; color:#ec4899; background:var(--main-bg); border: 2px solid var(--border-color); padding:4px 10px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: #ec4899; color: #fff; font-weight: 900;}
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .data-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: #ec4899; }
    .data-card.dimmed { opacity: 0.55; filter: grayscale(80%); }
    .dc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .dc-title { font-family: var(--heading-font); font-size: 1.3rem; font-weight: 900; color: var(--text-dark); margin-bottom: 8px; line-height: 1.3;}
    .dc-detail { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 8px; font-weight: 600;}
    .dc-detail i { color: #ec4899; width: 16px; text-align: center;}
    .dc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #ec4899;">
    <h1 style="color: #ec4899; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Security & Visitors</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Monitor campus entry logs, guest clearance, and facility access.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-id-card-clip stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Life Logs</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-shield-alt stat-icon" style="color:#ec4899;"></i>
        <div>
            <div class="stat-val" style="color:#ec4899;"><?= $active ?></div>
            <div class="stat-lbl">Currently On Campus</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-sign-out-alt stat-icon" style="color:var(--text-light);"></i>
        <div>
            <div class="stat-val" style="color:var(--text-light);"><?= $departed ?></div>
            <div class="stat-lbl">Cleared / Departed</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchVisLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Visitor or ID..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterMatrix()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Statuses</option>
            <option value="Active">On Campus</option>
            <option value="Departed">Departed</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action"><i class="fas fa-file-export"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#ec4899; border-color:#ec4899; color:#fff;" onclick="openModal()"><i class="fas fa-shield-alt"></i> Log Visitor</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:1%;">Clearance ID</th>
                <th>Visitor Name</th>
                <th>Purpose & Host</th>
                <th>Time Log</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM visitors ORDER BY check_in DESC");
            $all_data = [];
            while($row = mysqli_fetch_assoc($res)) {
                $all_data[] = $row;
                $stat_class = $row['status'] == 'Active' ? 'status-active' : 'status-leave';
                $dim_class = $row['status'] != 'Active' ? "opacity:0.65; filter:grayscale(50%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $ci = date('M d, h:i A', strtotime($row['check_in']));
                $co = $row['check_out'] ? date('M d, h:i A', strtotime($row['check_out'])) : '--:--';
                
                echo "
                <tr class='filter-target' style='$dim_class' data-stat='{$row['status']}'>
                    <td><div class='id-box'>{$row['visitor_id']}</div></td>
                    <td><strong style='color:var(--text-dark); font-size:1.1rem;'>{$row['visitor_name']}</strong></td>
                    <td><span style='font-weight:900;'>{$row['purpose']}</span><br><span style='font-size:0.8rem; color:var(--text-light);'>Host: {$row['host_name']}</span></td>
                    <td>
                        <div style='font-size:0.85rem;'><i class='fas fa-sign-in-alt' style='color:#ec4899;'></i> IN: {$ci}</div>
                        <div style='font-size:0.85rem;'><i class='fas fa-sign-out-alt' style='color:var(--text-light);'></i> OUT: {$co}</div>
                    </td>
                    <td><span class='status-pill {$stat_class}'>{$row['status']}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            ".($row['status'] == 'Active' ? "<a href='?checkout={$row['id']}' class='table-btn' style='border-color:#ec4899; color:#ec4899;' onclick='systemToast(\"Logging Departure...\")'><i class='fas fa-sign-out-alt'></i> Out</a>" : "")."
                            <button class='table-btn btn-resolve' onclick='openModal($js_data)'><i class='fas fa-pen'></i></button>
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
        $st = $row['status'];
        $st_class = $st == 'Active' ? 'status-active' : 'status-leave';
        $dim_class = $st != 'Active' ? 'dimmed' : '';
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        $bdr_color = $st == 'Active' ? '#ec4899' : 'var(--border-light)';
        
        $ci = date('M d, h:i A', strtotime($row['check_in']));
        $co = $row['check_out'] ? date('M d, h:i A', strtotime($row['check_out'])) : '--:--';
        
        echo "
        <div class='data-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}'>
            <div class='dc-header'>
                <div class='id-box' style='font-size:0.85rem; padding:4px 8px;'>{$row['visitor_id']}</div>
                <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
            </div>
            <div class='dc-title'>{$row['visitor_name']}</div>
            <div style='font-size:0.85rem; font-weight:800; color:#ec4899; margin-bottom:15px; text-transform:uppercase;'>{$row['purpose']}</div>
            <div class='dc-detail'><i class='fas fa-user-shield'></i> Host: {$row['host_name']}</div>
            <div class='dc-detail'><i class='fas fa-sign-in-alt'></i> In: {$ci}</div>
            <div class='dc-detail'><i class='fas fa-sign-out-alt'></i> Out: {$co}</div>
            <div class='dc-footer'>
                <div style='display:flex; gap:8px; margin-left:auto;'>
                    ".($st == 'Active' ? "<a href='?checkout={$row['id']}' class='table-btn' style='padding:6px 10px; border-color:#ec4899; color:#ec4899;'><i class='fas fa-sign-out-alt' style='margin:0;'></i></a>" : "")."
                    <button class='table-btn btn-resolve' style='padding:6px 10px;' onclick='openModal($js_data)'><i class='fas fa-pen' style='margin:0;'></i></button>
                    <a href='?del={$row['id']}' class='table-btn btn-trash' style='padding:6px 10px;'><i class='fas fa-trash' style='margin:0;'></i></a>
                </div>
            </div>
        </div>";
    }
    ?>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-id-card-clip" style="color:#ec4899;"></i> Log Visitor</h2>
        <form method="POST">
            <input type="hidden" name="save_visitor" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            <div style="display:grid; grid-template-columns: 1fr; gap: 15px;">
                <input type="text" name="visitor_id" id="visitor_id" readonly style="background:var(--bg-grid); cursor:not-allowed; border-color:#ec4899;" required>
                <input type="text" name="visitor_name" id="visitor_name" placeholder="Full Name" required>
                <input type="text" name="purpose" id="purpose" placeholder="Purpose of Visit" required>
                <input type="text" name="host_name" id="host_name" placeholder="Campus Host / Approver" required>
                <select name="status" id="status" required>
                    <option value="Active">Active (On Campus)</option>
                    <option value="Departed">Departed</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#ec4899; border-color:#ec4899; color:#fff;"><i class="fas fa-save"></i> Save Log</button>
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
        localStorage.setItem('campus_vis_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_vis_view', 'table');
    }
}
document.addEventListener('DOMContentLoaded', () => { setView(localStorage.getItem('campus_vis_view') || 'table'); });

function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchVisLocal').value.toLowerCase();
    const targets = document.querySelectorAll('.filter-target');
    targets.forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        if(show) { el.removeAttribute('data-hide-local'); el.style.display = ''; } 
        else { el.setAttribute('data-hide-local', 'true'); el.style.display = 'none'; }
    });
    if(typeof globalTableSearch === 'function') globalTableSearch();
}

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#ec4899;"></i> Edit Log';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('visitor_id').value = data.visitor_id;
        document.getElementById('visitor_name').value = data.visitor_name;
        document.getElementById('purpose').value = data.purpose;
        document.getElementById('host_name').value = data.host_name;
        document.getElementById('status').value = data.status;
    } else {
        title.innerHTML = '<i class="fas fa-shield-alt" style="color:#ec4899;"></i> Log Visitor';
        document.getElementById('edit_id').value = '';
        document.getElementById('visitor_id').value = 'VIS-' + Math.random().toString(36).substr(2, 6).toUpperCase();
        document.getElementById('visitor_name').value = '';
        document.getElementById('purpose').value = '';
        document.getElementById('host_name').value = '';
        document.getElementById('status').value = 'Active';
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>