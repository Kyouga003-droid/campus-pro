<?php
include 'config.php';

// FUNCTION 1: Schema Patching for Enterprise Faculty Metrics
$patch = "CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id VARCHAR(20) UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    department VARCHAR(50),
    role VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    hire_date DATE
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

// FUNCTION 2: Expanded Faculty HR Tracker Columns
$cols = [
    "emp_id VARCHAR(20)", "first_name VARCHAR(50)", "last_name VARCHAR(50)", "email VARCHAR(100)", 
    "department VARCHAR(50)", "role VARCHAR(50)", "status VARCHAR(20) DEFAULT 'Active'", "hire_date DATE",
    "salary_tier VARCHAR(20) DEFAULT 'Standard'", // FUNCTION 3: Salary Bracket
    "office_room VARCHAR(50)", // FUNCTION 4: Office Mapping
    "shift_schedule VARCHAR(50) DEFAULT 'Morning'", // FUNCTION 5: Shift Tracking
    "contract_expiry DATE", // FUNCTION 6: Contract Limits
    "emergency_contact VARCHAR(50)", // FUNCTION 7: Emergency DB
    "supervisor VARCHAR(100)", // FUNCTION 8: Hierarchy
    "barcode_hash VARCHAR(100)" // FUNCTION 9: Hash Generation for ID Cards
];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE employees ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM employees WHERE id = $id");
    header("Location: employees.php"); exit();
}

// FUNCTION 10: Mass Status Update (Archive/Active)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_action'])) {
    if(!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        if ($_POST['mass_action_type'] === 'leave') {
            mysqli_query($conn, "UPDATE employees SET status = 'On Leave' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'terminate') {
            mysqli_query($conn, "UPDATE employees SET status = 'Terminated', contract_expiry = CURDATE() WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM employees WHERE id IN ($ids)");
        }
    }
    header("Location: employees.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_emp'])) {
    $eid = mysqli_real_escape_string($conn, $_POST['emp_id']);
    $f = mysqli_real_escape_string($conn, $_POST['first_name']);
    $l = mysqli_real_escape_string($conn, $_POST['last_name']);
    $e = mysqli_real_escape_string($conn, $_POST['email']);
    $d = mysqli_real_escape_string($conn, $_POST['department']);
    $r = mysqli_real_escape_string($conn, $_POST['role']);
    $s = mysqli_real_escape_string($conn, $_POST['status']);
    
    $st = mysqli_real_escape_string($conn, $_POST['salary_tier']);
    $or = mysqli_real_escape_string($conn, $_POST['office_room']);
    $ss = mysqli_real_escape_string($conn, $_POST['shift_schedule']);
    $ce = mysqli_real_escape_string($conn, $_POST['contract_expiry']);
    $ec = mysqli_real_escape_string($conn, $_POST['emergency_contact']);
    $sup = mysqli_real_escape_string($conn, $_POST['supervisor']);
    $hd = mysqli_real_escape_string($conn, $_POST['hire_date']);

    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE employees SET emp_id='$eid', first_name='$f', last_name='$l', email='$e', department='$d', role='$r', status='$s', salary_tier='$st', office_room='$or', shift_schedule='$ss', contract_expiry='$ce', emergency_contact='$ec', supervisor='$sup', hire_date='$hd' WHERE id=$id");
    } else {
        $hash = md5($eid . time());
        mysqli_query($conn, "INSERT INTO employees (emp_id, first_name, last_name, email, department, role, status, hire_date, salary_tier, office_room, shift_schedule, contract_expiry, emergency_contact, supervisor, barcode_hash) VALUES ('$eid', '$f', '$l', '$e', '$d', '$r', '$s', '$hd', '$st', '$or', '$ss', '$ce', '$ec', '$sup', '$hash')");
    }
    header("Location: employees.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM employees");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $fn = ['Alan','Grace','Edgar','Isaac','Albert','Lord','Peter','Luca','John','Ernst','William','Sigmund','Max','Margaret','Gustave','Michael','Paul','Marie','Charles','Ada'];
    $ln = ['Turing','Hopper','Codd','Newton','Einstein','Kelvin','Drucker','Pacioli','Keynes','Gombrich','Shake','Freud','Weber','Hamilton','Eiffel','Porter','Rand','Curie','Darwin','Lovelace'];
    $depts = ['Computer Studies', 'Business', 'Engineering', 'Arts & Sciences', 'Administration', 'Operations'];
    $roles = ['Professor', 'Associate Professor', 'Adjunct Faculty', 'Department Head', 'Research Lead'];
    $shifts = ['Morning', 'Night', 'Flex'];
    
    for($i=0; $i<20; $i++) {
        $eid = "FAC-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $f = mysqli_real_escape_string($conn, $fn[$i]);
        $l = mysqli_real_escape_string($conn, $ln[$i]);
        $e = strtolower($f.".".$l."@campus.edu");
        $d = $depts[array_rand($depts)];
        $r = $roles[array_rand($roles)];
        $sh = $shifts[array_rand($shifts)];
        if($d == 'Administration' || $d == 'Operations') $r = 'Staff / Director';
        $stat = (rand(1,10) > 2) ? 'Active' : 'On Leave';
        $hd = date('Y-m-d', strtotime('-'.rand(100, 3000).' days'));
        $ce = date('Y-m-d', strtotime('+'.rand(100, 1000).' days'));
        $hash = md5($eid . time());
        mysqli_query($conn, "INSERT INTO employees (emp_id, first_name, last_name, email, department, role, status, hire_date, contract_expiry, shift_schedule, barcode_hash) VALUES ('$eid', '$f', '$l', '$e', '$d', '$r', '$stat', '$hd', '$ce', '$sh', '$hash')");
    }
}

include 'header.php';

$total = getCount($conn, 'employees');
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM employees WHERE status='Active'"))['c'];
$depts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as c FROM employees"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: #8b5cf6; }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: #8b5cf6; opacity: 0.9; }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }
    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    
    .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-leave { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .status-terminated { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    
    .id-box { font-weight:900; font-family:monospace; font-size:1.1rem; color:#8b5cf6; background:var(--main-bg); border: 2px solid var(--border-color); padding:4px 10px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: #8b5cf6; color: #fff; font-weight: 900;}
    
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .data-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: #8b5cf6; }
    .data-card.dimmed { opacity: 0.55; filter: grayscale(80%); }
    
    /* UI FEATURE 1: Checkbox Multi-Select */
    .cb-sel { width: 20px; height: 20px; accent-color: var(--text-dark); cursor: pointer; }
    /* UI FEATURE 2: Dropdowns */
    .flt-sel { border: 2px solid var(--border-color); padding: 12px 20px; border-radius: 8px; background: var(--main-bg); color: var(--text-dark); font-weight: 800; font-family: var(--body-font); text-transform: uppercase; font-size: 0.85rem; }

    /* UI FEATURE 3: Avatar Placeholder */
    .avatar-circle { width: 45px; height: 45px; border-radius: 50%; background: var(--main-bg); border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #8b5cf6; font-weight: 900; }
    
    /* UI FEATURE 4: Clickable Mail link wrapper */
    .mail-link { color: var(--text-light); text-decoration: none; transition: 0.2s;}
    .mail-link:hover { color: #8b5cf6; text-decoration: underline;}

    /* UI FEATURE 5: Service Anniv Badge */
    .badge-anniv { position: absolute; top: 15px; right: 15px; background: var(--brand-secondary); color: var(--brand-primary); padding: 4px 8px; font-size: 0.65rem; font-weight: 900; border-radius: 4px; text-transform: uppercase; letter-spacing: 1px; border: 1px solid var(--brand-primary); box-shadow: 2px 2px 0px rgba(0,0,0,0.1); z-index: 2;}
    
    /* UI FEATURE 6: Shift Dots */
    .shift-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .shift-Morning { background: #f59e0b; box-shadow: 0 0 5px #f59e0b;}
    .shift-Night { background: #3b82f6; box-shadow: 0 0 5px #3b82f6;}
    .shift-Flex { background: #10b981; box-shadow: 0 0 5px #10b981;}

    /* UI FEATURE 7: Contract Progress Bar */
    .progress-bar { width: 100%; height: 6px; background: var(--bg-grid); border-radius: 3px; overflow: hidden; margin-top: 5px; border: 1px solid var(--border-light);}
    .progress-fill { height: 100%; background: #8b5cf6; }

    /* UI FEATURE 8: Amber pulsing indicator for On Leave */
    @keyframes leavePulse { 0% { background: rgba(245,158,11,0.1); } 50% { background: rgba(245,158,11,0.3); } 100% { background: rgba(245,158,11,0.1); } }
    .leave-warn { animation: leavePulse 2s infinite; border-color: #f59e0b !important;}

    /* UI FEATURE 9: Pagination */
    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 30px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px;}
    .page-btn { background: var(--main-bg); border: 2px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-weight: 900; cursor: pointer; color: var(--text-dark); transition: 0.2s; }
    .page-btn:hover { background: var(--text-dark); color: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #8b5cf6;">
    <h1 style="color: #8b5cf6; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Faculty & Staff Registry</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage academic instructors, administration hierarchies, and contracts.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-user-tie stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Personnel</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $active ?></div>
            <div class="stat-lbl">Active Roster</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-building stat-icon"></i>
        <div>
            <div class="stat-val"><?= $depts ?></div>
            <div class="stat-lbl">Managed Sectors</div>
        </div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewTable" class="view-btn" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button type="button" id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchEmpLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Faculty..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterDept" class="flt-sel" onchange="filterMatrix()">
            <option value="All Departments">All Sectors</option>
            <?php
            $d_res = mysqli_query($conn, "SELECT DISTINCT department FROM employees ORDER BY department ASC");
            while($d = mysqli_fetch_assoc($d_res)) { echo "<option value='{$d['department']}'>{$d['department']}</option>"; }
            ?>
        </select>
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All Statuses">All Statuses</option>
            <option value="Active">Active</option>
            <option value="On Leave">On Leave</option>
            <option value="Terminated">Terminated</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('empTable', 'faculty_export')"><i class="fas fa-file-export"></i> Export</button>
        <button type="button" class="btn-primary" style="margin:0; padding: 12px 25px; background:#8b5cf6; border-color:#8b5cf6; color:#fff;" onclick="openModal()"><i class="fas fa-user-plus"></i> Add Faculty</button>
    </div>
</form>

<form method="POST" id="massForm">
    <input type="hidden" name="mass_action" value="1">
    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center; background: var(--card-bg); padding: 15px 25px; border: 2px solid var(--border-color); border-radius: 12px;">
        <span style="font-weight: 900; text-transform: uppercase;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding: 8px 15px;">
            <option value="leave">Set On Leave</option>
            <option value="terminate">Terminate Contract</option>
            <option value="delete">Purge Records</option>
        </select>
        <button type="submit" class="btn-action" onclick="return confirm('Execute batch operation on selected personnel?')"><i class="fas fa-bolt"></i> EXECUTE</button>
    </div>

    <div id="tableView" class="table-responsive">
        <table id="empTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c => c.checked = this.checked)"></th>
                    <th style="width:1%;">Faculty ID</th>
                    <th>Personnel Profile</th>
                    <th>Role & Contract</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody id="filterTableBody">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM employees ORDER BY last_name ASC");
                $all_data = [];
                $now = new DateTime();
                
                while($row = mysqli_fetch_assoc($res)) {
                    $all_data[] = $row;
                    
                    if ($row['status'] == 'Active') $stat_class = 'status-active';
                    elseif ($row['status'] == 'On Leave') $stat_class = 'status-leave';
                    else $stat_class = 'status-terminated';
                    
                    $dim_class = $row['status'] == 'Terminated' ? "opacity:0.65; filter:grayscale(80%);" : "";
                    $leave_pulse = $row['status'] == 'On Leave' ? "leave-warn" : "";
                    
                    $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    
                    // Duration calc
                    $hire_dt = new DateTime($row['hire_date']);
                    $y_serv = $now->diff($hire_dt)->y;
                    
                    $exp_dt = new DateTime($row['contract_expiry']);
                    $days_left = $now->diff($exp_dt)->days;
                    $is_expired = $exp_dt < $now;
                    if($is_expired) $days_left = 0;
                    $prog_pct = min(100, max(0, ($days_left / 365) * 100)); // Rough visual based on 1 yr scale
                    $prog_color = $prog_pct < 20 ? '#ef4444' : '#8b5cf6';

                    echo "
                    <tr class='paginate-row filter-target {$leave_pulse}' style='$dim_class' data-dept='{$row['department']}' data-stat='{$row['status']}'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$row['id']}' class='cb-item cb-sel'></td>
                        <td><div class='id-box'>{$row['emp_id']}</div></td>
                        <td>
                            <div style='display:flex; align-items:center; gap:12px;'>
                                <div class='avatar-circle'>" . substr($row['first_name'],0,1) . substr($row['last_name'],0,1) . "</div>
                                <div>
                                    <strong style='color:var(--text-dark); font-size:1.1rem;'>{$row['last_name']}, {$row['first_name']}</strong><br>
                                    <a href='mailto:{$row['email']}' class='mail-link' style='font-size:0.8rem;'><i class='fas fa-envelope'></i> {$row['email']}</a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style='font-weight:900;'>{$row['role']}</span>
                            <div style='font-size:0.75rem; color:var(--text-light); text-transform:uppercase;'>
                                <span class='shift-dot shift-{$row['shift_schedule']}'></span> {$row['shift_schedule']} Shift
                            </div>
                            <div class='progress-bar' title='{$days_left} Days until contract expiry'><div class='progress-fill' style='width:{$prog_pct}%; background:{$prog_color};'></div></div>
                        </td>
                        <td>
                            <strong style='color:#8b5cf6; text-transform:uppercase; font-size:0.85rem;'>{$row['department']}</strong>
                            <div style='font-size:0.75rem; font-weight:700; color:var(--text-light);'>{$y_serv} YRS SVC</div>
                        </td>
                        <td><span class='status-pill {$stat_class}'>{$row['status']}</span></td>
                        <td class='action-col'>
                            <div class='table-actions-cell'>
                                <button type='button' class='table-btn btn-resolve' onclick='openModal($js_data)'><i class='fas fa-pen'></i></button>
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
            if ($st == 'Active') $st_class = 'status-active';
            elseif ($st == 'On Leave') $st_class = 'status-leave';
            else $st_class = 'status-terminated';
            
            $dim_class = $st == 'Terminated' ? 'dimmed' : '';
            $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            $bdr_color = "var(--border-color)";
            if($row['department'] == 'Computer Studies') $bdr_color = '#3b82f6';
            if($row['department'] == 'Engineering') $bdr_color = '#f59e0b';
            if($row['department'] == 'Business') $bdr_color = '#10b981';
            if($row['department'] == 'Arts & Sciences') $bdr_color = '#ec4899';
            
            $hire_dt = new DateTime($row['hire_date']);
            $y_serv = $now->diff($hire_dt)->y;
            $anniv = ($y_serv > 0 && $y_serv % 5 == 0) ? "<div class='badge-anniv'><i class='fas fa-award'></i> $y_serv Yr Award</div>" : "";

            echo "
            <div class='data-card paginate-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}' data-dept='{$row['department']}'>
                {$anniv}
                <div class='dc-header' style='margin-top:10px;'>
                    <div class='id-box' style='font-size:0.85rem; padding:4px 8px;'>{$row['emp_id']}</div>
                    <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
                </div>
                
                <div style='display:flex; align-items:center; gap:15px; margin-bottom:15px;'>
                    <div class='avatar-circle' style='width:55px; height:55px; font-size:1.5rem;'>" . substr($row['first_name'],0,1) . substr($row['last_name'],0,1) . "</div>
                    <div>
                        <div style='font-family:var(--heading-font); font-weight:900; font-size:1.3rem; line-height:1.1; color:var(--text-dark);'>{$row['first_name']} {$row['last_name']}</div>
                        <div style='font-size:0.85rem; font-weight:800; color:#8b5cf6; text-transform:uppercase;'>{$row['department']}</div>
                    </div>
                </div>

                <div class='dc-detail'><i class='fas fa-id-badge'></i> {$row['role']}</div>
                <div class='dc-detail'><i class='fas fa-clock'></i> <span class='shift-dot shift-{$row['shift_schedule']}' style='margin-left:5px;'></span> {$row['shift_schedule']} Shift</div>
                <div class='dc-detail'><i class='fas fa-door-open'></i> Room: {$row['office_room']}</div>
                <div class='dc-detail'><a href='mailto:{$row['email']}' class='mail-link'><i class='fas fa-envelope'></i> {$row['email']}</a></div>
                
                <div class='dc-footer'>
                    <div style='display:flex; gap:8px; margin-left:auto;'>
                        <button type='button' class='table-btn btn-resolve' style='padding:6px 10px;' onclick='openModal($js_data)'><i class='fas fa-pen' style='margin:0;'></i></button>
                        <a href='?del={$row['id']}' class='table-btn btn-trash' style='padding:6px 10px;'><i class='fas fa-trash' style='margin:0;'></i></a>
                    </div>
                </div>
            </div>";
        }
        ?>
    </div>

    <div class="pagination-ctrl">
        <button type="button" class="page-btn" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> PREV</button>
        <span style="font-weight:900; font-family:monospace; font-size:1.2rem;" id="pageIndicator">Page 1 of X</span>
        <button type="button" class="page-btn" id="nextPage" onclick="changePage(1)">NEXT <i class="fas fa-chevron-right"></i></button>
    </div>
</form>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 800px;">
        <button type="button" class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font); border-bottom: 2px solid var(--border-color); padding-bottom: 15px;"><i class="fas fa-user-tie" style="color:#8b5cf6;"></i> Manage Personnel</h2>
        
        <form method="POST">
            <input type="hidden" name="save_emp" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="grid-column: span 2; display:flex; gap:15px;">
                    <input type="text" name="emp_id" id="emp_id" readonly style="background:var(--bg-grid); cursor:not-allowed; border-color:#8b5cf6; flex:1;" required>
                    <select name="status" id="status" required style="flex:1;">
                        <option value="Active">Status: Active</option>
                        <option value="On Leave">Status: On Leave</option>
                        <option value="Terminated">Status: Terminated</option>
                    </select>
                </div>

                <input type="text" name="first_name" id="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>
                
                <input type="email" name="email" id="email" placeholder="Campus Email Address" required style="grid-column: span 2;">
                
                <select name="department" id="department" required>
                    <option value="" disabled selected>Select Sector / Department</option>
                    <option value="Computer Studies">Computer Studies</option>
                    <option value="Business">Business</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Arts & Sciences">Arts & Sciences</option>
                    <option value="Administration">Administration</option>
                    <option value="Operations">Operations</option>
                </select>
                <input type="text" name="role" id="role" placeholder="Job Title / Role" required>
                
                <select name="shift_schedule" id="shift_schedule" required>
                    <option value="Morning">Shift: Morning</option>
                    <option value="Night">Shift: Night</option>
                    <option value="Flex">Shift: Flexible</option>
                </select>
                <input type="text" name="office_room" id="office_room" placeholder="Office / Room Allocation">
                
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Hire Date</label>
                    <input type="date" name="hire_date" id="hire_date" required>
                </div>
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Contract Expiry</label>
                    <input type="date" name="contract_expiry" id="contract_expiry" required>
                </div>
                
                <select name="salary_tier" id="salary_tier" required>
                    <option value="Standard">Pay Tier: Standard Faculty</option>
                    <option value="Tenured">Pay Tier: Tenured</option>
                    <option value="Executive">Pay Tier: Executive / Director</option>
                    <option value="Hourly">Pay Tier: Hourly Adjunct</option>
                </select>
                <input type="text" name="supervisor" id="supervisor" placeholder="Direct Supervisor / Dean">
                
                <input type="text" name="emergency_contact" id="emergency_contact" placeholder="Emergency Contact Number" required style="grid-column: span 2;">
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#8b5cf6; border-color:#8b5cf6; color:#fff; justify-content:center;"><i class="fas fa-save"></i> UPDATE HR RECORD</button>
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
        table.style.display = 'none';
        grid.style.display = 'grid';
        btnGrid.classList.add('active-view');
        btnTable.classList.remove('active-view');
        localStorage.setItem('campus_emp_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_emp_view', 'table');
    }
    paginate();
}

function paginate() {
    const selector = currentView === 'table' ? '.paginate-row' : '.paginate-card';
    const items = Array.from(document.querySelectorAll(selector)).filter(i => !i.hasAttribute('data-hide-local'));
    const totalPages = Math.ceil(items.length / itemsPerPage) || 1;
    
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    document.getElementById('pageIndicator').innerText = `PAGE ${currentPage} OF ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;
    
    document.querySelectorAll(selector).forEach(item => item.style.display = 'none');
    items.forEach((item, index) => {
        if (index >= (currentPage - 1) * itemsPerPage && index < currentPage * itemsPerPage) {
            item.style.display = currentView === 'table' ? 'table-row' : 'block';
        }
    });
}

function changePage(delta) { currentPage += delta; paginate(); }

document.addEventListener('DOMContentLoaded', () => { 
    setView(localStorage.getItem('campus_emp_view') || 'table'); 
});

function filterMatrix() {
    const dFilter = document.getElementById('filterDept').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchEmpLocal').value.toLowerCase();
    const targets = document.querySelectorAll('.filter-target');
    
    targets.forEach(el => {
        const rDept = el.getAttribute('data-dept');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if (dFilter !== 'All Departments' && rDept !== dFilter) show = false;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) { el.removeAttribute('data-hide-local'); } 
        else { el.setAttribute('data-hide-local', 'true'); }
    });
    currentPage = 1;
    paginate();
}

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#8b5cf6;"></i> Update HR File';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('emp_id').value = data.emp_id;
        document.getElementById('first_name').value = data.first_name;
        document.getElementById('last_name').value = data.last_name;
        document.getElementById('email').value = data.email;
        document.getElementById('department').value = data.department;
        document.getElementById('role').value = data.role;
        document.getElementById('status').value = data.status;
        
        document.getElementById('salary_tier').value = data.salary_tier || 'Standard';
        document.getElementById('office_room').value = data.office_room || '';
        document.getElementById('shift_schedule').value = data.shift_schedule || 'Morning';
        document.getElementById('contract_expiry').value = data.contract_expiry || '';
        document.getElementById('emergency_contact').value = data.emergency_contact || '';
        document.getElementById('supervisor').value = data.supervisor || '';
        document.getElementById('hire_date').value = data.hire_date || '';
    } else {
        title.innerHTML = '<i class="fas fa-user-tie" style="color:#8b5cf6;"></i> Provision Personnel';
        document.getElementById('edit_id').value = '';
        document.getElementById('emp_id').value = 'FAC-' + Math.random().toString(36).substr(2, 6).toUpperCase();
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('department').value = '';
        document.getElementById('role').value = '';
        document.getElementById('status').value = 'Active';
        
        document.getElementById('salary_tier').value = 'Standard';
        document.getElementById('office_room').value = '';
        document.getElementById('shift_schedule').value = 'Morning';
        document.getElementById('emergency_contact').value = '';
        document.getElementById('supervisor').value = '';
        
        const d = new Date();
        document.getElementById('hire_date').value = d.toISOString().split('T')[0];
        d.setFullYear(d.getFullYear() + 1);
        document.getElementById('contract_expiry').value = d.toISOString().split('T')[0];
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>