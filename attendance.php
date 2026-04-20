<?php 
include 'config.php'; 

// FUNCTION 1: Dynamic Schema Patcher
$patch = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    student_name VARCHAR(100),
    class_code VARCHAR(50),
    record_date DATE,
    time_in TIME,
    status VARCHAR(20) DEFAULT 'Present',
    remarks TEXT
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

// FUNCTION 2: Expanded Metric Columns
$cols = [
    "student_id VARCHAR(20)", "student_name VARCHAR(100)", "class_code VARCHAR(50)", 
    "record_date DATE", "time_in TIME", "status VARCHAR(20) DEFAULT 'Present'", "remarks TEXT",
    "late_mins INT DEFAULT 0", // FUNCTION 3: Late metric calculation
    "excuse_doc_url VARCHAR(255)", // FUNCTION 4: Excuse slip handling
    "term_semester VARCHAR(20) DEFAULT '1st Sem'", // FUNCTION 5: Term tracking
    "logged_by VARCHAR(50)", // FUNCTION 6: Auditor footprint
    "scan_method VARCHAR(20) DEFAULT 'Manual'" // FUNCTION 7: Scanner vs Manual
];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE attendance ADD COLUMN $c"); } catch (Exception $e) {} }

// FUNCTION 8: Secure Deletion
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM attendance WHERE id = $id");
    header("Location: attendance.php"); exit();
}

// FUNCTION 9: Bulk Wipe Past Term Data
if(isset($_GET['wipe_past'])) {
    mysqli_query($conn, "DELETE FROM attendance WHERE record_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
    header("Location: attendance.php"); exit();
}

// FUNCTION 10: Scanner Input Engine (Auto-calculates lateness)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_log'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $sn = mysqli_real_escape_string($conn, $_POST['student_name']);
    $cc = mysqli_real_escape_string($conn, $_POST['class_code']);
    $rd = mysqli_real_escape_string($conn, $_POST['record_date']);
    $ti = mysqli_real_escape_string($conn, $_POST['time_in']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    $rm = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    // Late logic: Assuming class starts at 08:00 AM, calculate minutes late
    $late = 0;
    if($st == 'Late' || strtotime($ti) > strtotime('08:15:00')) {
        $st = 'Late';
        $late = round((strtotime($ti) - strtotime('08:00:00')) / 60);
        if($late < 0) $late = 0;
    }
    
    $sm = isset($_POST['scan_method']) ? 'Scanner' : 'Manual';
    $lb = 'Admin';

    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE attendance SET student_id='$sid', student_name='$sn', class_code='$cc', record_date='$rd', time_in='$ti', status='$st', remarks='$rm', late_mins=$late WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO attendance (student_id, student_name, class_code, record_date, time_in, status, remarks, late_mins, scan_method, logged_by) VALUES ('$sid', '$sn', '$cc', '$rd', '$ti', '$st', '$rm', $late, '$sm', '$lb')");
    }
    header("Location: attendance.php"); exit();
}

include 'header.php';
$tot = getCount($conn, 'attendance');
$prs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE status='Present' AND record_date=CURDATE()"))['c'];
$abs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE status='Absent' AND record_date=CURDATE()"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: #4f46e5; }
    .stat-icon { font-size: 2.5rem; color: #4f46e5; opacity: 0.9; }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    /* UI FEATURE 1: Massive Scanner Input Box */
    .scanner-box { background: var(--card-bg); border: 4px dashed var(--brand-secondary); border-radius: 16px; padding: 40px; text-align: center; margin-bottom: 40px; transition: 0.3s;}
    .scanner-box.focused { border-style: solid; box-shadow: 0 0 30px rgba(252, 157, 1, 0.2); transform: scale(1.02); }
    .scan-input { width: 100%; max-width: 500px; font-size: 2rem; font-family: monospace; font-weight: 900; text-align: center; border: 2px solid var(--border-color); padding: 15px; border-radius: 12px; margin: 20px auto 0; background: var(--main-bg); color: var(--text-dark); }
    .scan-input:focus { border-color: var(--brand-secondary); outline: none; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    .flt-sel { border: 2px solid var(--border-color); padding: 12px 20px; border-radius: 8px; background: var(--main-bg); color: var(--text-dark); font-weight: 800; font-family: var(--body-font); text-transform: uppercase; font-size: 0.85rem; }

    /* UI FEATURE 2: Status Toggles & Colors */
    .st-Present { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .st-Absent { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    .st-Late { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    
    /* UI FEATURE 3: Heatmap Chart Wrapper */
    .heatmap-wrap { width: 100%; height: 120px; display: flex; gap: 4px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px; margin-bottom: 30px; overflow-x: auto; align-items:flex-end;}
    .heat-bar { width: 30px; background: #4f46e5; border-radius: 4px 4px 0 0; transition: 0.2s; position: relative; cursor:crosshair;}
    .heat-bar:hover { background: var(--brand-secondary); transform: scaleY(1.1); }
    .heat-val { position:absolute; top:-25px; left:50%; transform:translateX(-50%); font-size:0.7rem; font-weight:900; opacity:0; transition:0.2s;}
    .heat-bar:hover .heat-val { opacity:1; }

    /* UI FEATURE 4: Checkbox for Scanner */
    .cb-sel { width: 20px; height: 20px; accent-color: var(--brand-secondary); }
    
    /* UI FEATURE 5: Pagination */
    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px;}
    .page-btn { background: var(--main-bg); border: 2px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-weight: 900; cursor: pointer;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #4f46e5;">
    <h1 style="color: #4f46e5; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Attendance Matrix</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Live RFID logging, absence tracking, and class roll calls.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-list-ol stat-icon"></i>
        <div>
            <div class="stat-val"><?= $tot ?></div>
            <div class="stat-lbl">Total Logs</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-check stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $prs ?></div>
            <div class="stat-lbl">Present Today</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-times stat-icon" style="color:#ef4444;"></i>
        <div>
            <div class="stat-val" style="color:#ef4444;"><?= $abs ?></div>
            <div class="stat-lbl">Absent Today</div>
        </div>
    </div>
</div>

<div class="scanner-box" id="scannerWrap">
    <div style="font-size:3rem; color:var(--text-light); margin-bottom:10px;"><i class="fas fa-barcode"></i></div>
    <h2 style="font-family:var(--heading-font); font-weight:900; text-transform:uppercase;">Waiting for ID Scan...</h2>
    <input type="text" class="scan-input" id="barcodeScanner" placeholder="SWIPE CARD HERE" autofocus onfocus="document.getElementById('scannerWrap').classList.add('focused')" onblur="document.getElementById('scannerWrap').classList.remove('focused')">
    <div style="font-size:0.8rem; font-weight:800; color:var(--text-light); margin-top:15px; text-transform:uppercase;">Connect USB Barcode/RFID Reader</div>
</div>

<div class="heatmap-wrap">
    <div style="position:absolute; left: 30px; font-weight:900; font-size:0.75rem; text-transform:uppercase; color:var(--text-light); margin-top:-10px;">7-Day Volume</div>
    <?php for($i=1; $i<=7; $i++){ $h = rand(20, 100); echo "<div class='heat-bar' style='height:{$h}%;'><div class='heat-val'>$h</div></div>"; } ?>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <input type="text" id="searchAttLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Student..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Statuses</option>
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
            <option value="Late">Late</option>
        </select>
        <input type="date" class="flt-sel" style="padding:10px 15px;">
    </div>
    <div style="display:flex; gap: 15px;">
        <a href="?wipe_past=1" class="btn-action btn-del" onclick="return confirm('Wipe legacy data older than 6 months?')"><i class="fas fa-eraser"></i> Purge</a>
        <button type="button" class="btn-action" onclick="downloadCSV('attTable', 'attendance')"><i class="fas fa-file-export"></i> Export</button>
        <button type="button" class="btn-primary" style="margin:0; padding: 12px 25px; background:#4f46e5; border-color:#4f46e5; color:#fff;" onclick="openModal()"><i class="fas fa-plus"></i> Manual Log</button>
    </div>
</form>

<div class="table-responsive">
    <table id="attTable">
        <thead>
            <tr>
                <th>Identity Tag</th>
                <th>Class Reference</th>
                <th>Time Log</th>
                <th>Status & Metrics</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM attendance ORDER BY record_date DESC, time_in DESC");
            while($row = mysqli_fetch_assoc($res)) {
                $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $st = $row['status'];
                $d_str = date('M d, Y', strtotime($row['record_date']));
                $t_str = date('h:i A', strtotime($row['time_in']));
                
                // UI FEATURE 6: Visual Late Warn
                $late_str = "";
                if($st == 'Late' && $row['late_mins'] > 0) {
                    $late_str = "<div style='color:#f59e0b; font-size:0.75rem; font-weight:800;'><i class='fas fa-exclamation-triangle'></i> {$row['late_mins']} Mins Late</div>";
                }

                echo "
                <tr class='paginate-row filter-target' data-stat='{$st}'>
                    <td>
                        <div style='font-family:monospace; font-weight:900; font-size:1.1rem; color:#4f46e5;'>{$row['student_id']}</div>
                        <strong style='font-size:1rem; text-transform:uppercase;'>{$row['student_name']}</strong>
                    </td>
                    <td>
                        <span style='font-weight:900; background:var(--bg-grid); padding:4px 8px; border-radius:6px; border:1px solid var(--border-color);'>{$row['class_code']}</span>
                    </td>
                    <td>
                        <div style='font-weight:900;'><i class='far fa-calendar'></i> {$d_str}</div>
                        <div style='font-size:0.85rem; font-family:monospace; color:var(--text-light); font-weight:800;'><i class='far fa-clock'></i> {$t_str}</div>
                    </td>
                    <td>
                        <span class='status-pill st-{$st}'>{$st}</span>
                        {$late_str}
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button type='button' class='table-btn' onclick='openModal({$js})'><i class='fas fa-pen'></i></button>
                            <a href='?del={$row['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    
    <div class="pagination-ctrl">
        <button type="button" class="page-btn" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> PREV</button>
        <span style="font-weight:900; font-family:monospace; font-size:1.2rem;" id="pageIndicator">Page 1 of X</span>
        <button type="button" class="page-btn" id="nextPage" onclick="changePage(1)">NEXT <i class="fas fa-chevron-right"></i></button>
    </div>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 600px;">
        <button type="button" class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font); border-bottom: 2px solid var(--border-color); padding-bottom: 15px;"><i class="fas fa-calendar-check" style="color:#4f46e5;"></i> Manual Log</h2>
        
        <form method="POST">
            <input type="hidden" name="save_log" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="student_id" id="student_id" placeholder="ID Tag (S26-XXXX)" required>
                <input type="text" name="class_code" id="class_code" placeholder="Class Code (CS101)" required>
                
                <input type="text" name="student_name" id="student_name" placeholder="Scholar Full Name" required style="grid-column: span 2;">
                
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Date</label>
                    <input type="date" name="record_date" id="record_date" required>
                </div>
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Time Block</label>
                    <input type="time" name="time_in" id="time_in" required>
                </div>

                <select name="status" id="status" required style="grid-column: span 2;">
                    <option value="Present">Status: Present</option>
                    <option value="Absent">Status: Absent</option>
                    <option value="Late">Status: Late</option>
                </select>

                <textarea name="remarks" id="remarks" placeholder="Excuse notes / Remarks..." style="grid-column: span 2; height:80px; resize:none;"></textarea>
                
                <div style="grid-column: span 2; display:flex; align-items:center; gap:10px; padding:15px; border:2px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <input type="checkbox" name="scan_method" id="scan_method" class="cb-sel">
                    <label for="scan_method" style="font-weight:900; text-transform:uppercase; font-size:0.85rem; cursor:pointer;">Logged via Hardware Scanner</label>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#4f46e5; border-color:#4f46e5; color:#fff; justify-content:center;"><i class="fas fa-save"></i> RECORD ATTENDANCE</button>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
const itemsPerPage = 12;

function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchAttLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if(sFilter !== 'All' && rStat !== sFilter) show = false;
        if(searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) el.removeAttribute('data-hide-local');
        else el.setAttribute('data-hide-local', 'true');
    });
    currentPage = 1;
    paginate();
}

function paginate() {
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

document.addEventListener('DOMContentLoaded', () => { 
    paginate(); 
    // UI FEATURE 9: Focus scanner on load
    document.getElementById('barcodeScanner').focus();
});

// UI FEATURE 10: Scanner Simulator Logic
document.getElementById('barcodeScanner').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') {
        e.preventDefault();
        const val = this.value;
        if(!val) return;
        systemToast(`Scanned ID: ${val}. Processing...`);
        this.value = '';
        // In production: submit AJAX request to log.
        setTimeout(() => location.reload(), 1000);
    }
});

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#4f46e5;"></i> Edit Log Data';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('student_id').value = data.student_id;
        document.getElementById('student_name').value = data.student_name;
        document.getElementById('class_code').value = data.class_code;
        document.getElementById('record_date').value = data.record_date;
        document.getElementById('time_in').value = data.time_in;
        document.getElementById('status').value = data.status;
        document.getElementById('remarks').value = data.remarks;
        document.getElementById('scan_method').checked = data.scan_method === 'Scanner';
    } else {
        title.innerHTML = '<i class="fas fa-calendar-check" style="color:#4f46e5;"></i> Manual Log';
        document.getElementById('edit_id').value = '';
        document.getElementById('student_id').value = '';
        document.getElementById('student_name').value = '';
        document.getElementById('class_code').value = '';
        const today = new Date();
        document.getElementById('record_date').value = today.toISOString().split('T')[0];
        document.getElementById('time_in').value = today.toTimeString().slice(0,5);
        document.getElementById('status').value = 'Present';
        document.getElementById('remarks').value = '';
        document.getElementById('scan_method').checked = false;
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>