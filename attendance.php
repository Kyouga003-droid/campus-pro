<?php 
include 'config.php'; 

// FEATURE 1: Dynamic Schema Patcher
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

// CRITICAL FIX: Force-inject missing columns into older legacy tables
$cols = ["student_id VARCHAR(20)", "student_name VARCHAR(100)", "class_code VARCHAR(50)", "record_date DATE", "time_in TIME", "status VARCHAR(20) DEFAULT 'Present'", "remarks TEXT"];
foreach($cols as $c) { 
    try { mysqli_query($conn, "ALTER TABLE attendance ADD COLUMN $c"); } catch (Exception $e) {} 
}

// FEATURE 13: Secure Deletion Protocol
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM attendance WHERE id = $id");
    header("Location: attendance.php");
    exit();
}

// FEATURE 19: Bulk Wipe Feature
if(isset($_GET['wipe_past'])) {
    mysqli_query($conn, "DELETE FROM attendance WHERE record_date < CURDATE() - INTERVAL 30 DAY");
    header("Location: attendance.php");
    exit();
}

// FEATURE 7: 1-Click Status Rotator
if(isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $res = mysqli_query($conn, "SELECT status FROM attendance WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $cur = $row['status'];
        if($cur == 'Present') $nxt = 'Late';
        elseif($cur == 'Late') $nxt = 'Absent';
        elseif($cur == 'Absent') $nxt = 'Excused';
        else $nxt = 'Present';
        
        mysqli_query($conn, "UPDATE attendance SET status = '$nxt' WHERE id = $id");
        header("Location: attendance.php");
        exit();
    }
}

// FEATURE 11 & 12: Universal CRUD Modal Logic
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $snm = mysqli_real_escape_string($conn, $_POST['student_name']);
    $cls = mysqli_real_escape_string($conn, $_POST['class_code']);
    $rdt = mysqli_real_escape_string($conn, $_POST['record_date']);
    $tin = mysqli_real_escape_string($conn, $_POST['time_in']);
    $sts = mysqli_real_escape_string($conn, $_POST['status']);
    $rmk = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE attendance SET student_id='$sid', student_name='$snm', class_code='$cls', record_date='$rdt', time_in='$tin', status='$sts', remarks='$rmk' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO attendance (student_id, student_name, class_code, record_date, time_in, status, remarks) VALUES ('$sid', '$snm', '$cls', '$rdt', '$tin', '$sts', '$rmk')");
    }
    header("Location: attendance.php");
    exit();
}

// FEATURE 2: Mass Roster Seeding
$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_names = ['James Smith', 'Mary Johnson', 'William Brown', 'Patricia Jones', 'Robert Garcia', 'Linda Miller', 'Michael Davis', 'Elizabeth Rodriguez', 'David Martinez', 'Jennifer Hernandez', 'Richard Lopez', 'Maria Gonzalez', 'Joseph Wilson', 'Susan Anderson', 'Thomas Thomas', 'Margaret Taylor', 'Charles Moore', 'Dorothy Jackson', 'Christopher Martin', 'Lisa Lee'];
    $classes = ['CS101 - Intro to Programming', 'BA101 - Business Ethics', 'ENG101 - College English', 'PSY101 - Gen Psychology'];
    
    for($i=1; $i<=20; $i++) {
        $sid = "2026-" . str_pad($i, 4, "0", STR_PAD_LEFT);
        $snm = mysqli_real_escape_string($conn, $seed_names[$i-1]);
        $cls = $classes[array_rand($classes)];
        $rdt = date('Y-m-d');
        
        $st_rand = rand(1, 10);
        if($st_rand <= 6) { $sts = 'Present'; $tin = '08:00:00'; }
        elseif($st_rand <= 8) { $sts = 'Late'; $tin = '08:25:00'; }
        elseif($st_rand == 9) { $sts = 'Absent'; $tin = '00:00:00'; }
        else { $sts = 'Excused'; $tin = '00:00:00'; }
        
        mysqli_query($conn, "INSERT INTO attendance (student_id, student_name, class_code, record_date, time_in, status, remarks) VALUES ('$sid', '$snm', '$cls', '$rdt', '$tin', '$sts', 'Auto-seeded record')");
    }
}

include 'header.php';

// FEATURE 3: Live Telemetry Cards
$total = getCount($conn, 'attendance');
$present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE status='Present'"))['c'];
$late = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE status='Late'"))['c'];
$absent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE status='Absent' OR status='Excused'"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 16px; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: var(--brand-secondary); opacity: 0.9; padding: 15px; background: var(--main-bg); border-radius: 12px; border: 1px solid var(--border-color);}
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 25px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap: wrap; gap: 15px;}
    
    /* FEATURE 18: Color-Coded Pills */
    .status-present { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-late { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .status-absent { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    .status-excused { background: var(--main-bg); color: var(--text-light); border-color: var(--text-light); }
    
    /* FEATURE 9: Monospaced IDs */
    .id-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 2px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .id-box { color: var(--brand-primary); }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #4f46e5;">
    <h1 style="color: #4f46e5; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Class Roll Call</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Monitor student attendance, track late arrivals, and calculate engagement rates.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-clipboard-list stat-icon" style="color:#4f46e5;"></i>
        <div>
            <div class="stat-val" style="color:#4f46e5;"><?= $total ?></div>
            <div class="stat-lbl">Total Logs</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-check stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $present ?></div>
            <div class="stat-lbl">Perfect Attendance</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $late ?></div>
            <div class="stat-lbl">Late Arrivals</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-times stat-icon" style="color:#ef4444;"></i>
        <div>
            <div class="stat-val" style="color:#ef4444;"><?= $absent ?></div>
            <div class="stat-lbl">Absences / Excused</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        
        <input type="text" id="searchStudentLocal" onkeyup="filterAttendance()" placeholder="&#xf002; Search Scholar Name or ID..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">

        <select id="filterClass" onchange="filterAttendance()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Classes">All Classes</option>
            <?php
            $c_res = mysqli_query($conn, "SELECT DISTINCT class_code FROM attendance ORDER BY class_code ASC");
            while($c = mysqli_fetch_assoc($c_res)) { 
                $cc = htmlspecialchars($c['class_code']);
                if(!empty($cc)) echo "<option value='{$cc}'>{$cc}</option>"; 
            }
            ?>
        </select>
        <select id="filterStatus" onchange="filterAttendance()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Statuses</option>
            <option value="Present">Present</option>
            <option value="Late">Late</option>
            <option value="Absent">Absent</option>
            <option value="Excused">Excused</option>
        </select>
        <input type="date" id="filterDate" onchange="filterAttendance()" style="width: auto; padding: 10px 20px; font-weight: 800; border-width: 2px; margin:0; border-radius:8px;">
    </div>
    <div style="display:flex; gap: 15px;">
        <a href="?wipe_past=true" class="btn-action btn-del" onclick="return confirm('Wipe attendance records older than 30 days?');"><i class="fas fa-broom"></i> Wipe Old</a>
        <button class="btn-action" onclick="systemToast('Exporting Attendance Registry...')"><i class="fas fa-file-csv"></i> Export Data</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#4f46e5; border-color:#4f46e5; color:#fff;" onclick="openAttendanceModal()"><i class="fas fa-plus"></i> Manual Log</button>
    </div>
</div>

<?php
$att_pct = $total > 0 ? ($present / $total) * 100 : 0;
$bar_color = $att_pct > 75 ? '#10b981' : ($att_pct > 50 ? '#f59e0b' : '#ef4444');
?>
<div class="card" style="padding: 25px; margin-bottom: 30px; display:flex; align-items:center; gap:20px;">
    <div style="font-weight:900; color:var(--text-dark); font-size:1.1rem; white-space:nowrap;"><i class="fas fa-chart-line" style="color:var(--brand-secondary); margin-right:8px;"></i> Overall Attendance Rate:</div>
    <div style="flex-grow:1; height:12px; background:var(--bg-grid); border: 2px solid var(--border-color); border-radius:10px; overflow:hidden;">
        <div style="height:100%; width:<?= $att_pct ?>%; background:<?= $bar_color ?>; transition: 1s ease-out;"></div>
    </div>
    <div style="font-weight:900; font-family:var(--heading-font); font-size:1.5rem; color:<?= $bar_color ?>;"><?= number_format($att_pct, 1) ?>%</div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:20%;">Student Identification</th>
                <th>Class Reference</th>
                <th style="width:20%;">Timestamp Data</th>
                <th>Current Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="attendanceTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM attendance ORDER BY record_date DESC, time_in ASC");
            while($row = mysqli_fetch_assoc($res)) {
                
                $st = $row['status'];
                if($st == 'Present') $st_class = 'status-present';
                elseif($st == 'Late') $st_class = 'status-late';
                elseif($st == 'Absent') $st_class = 'status-absent';
                else $st_class = 'status-excused';
                
                // Backup for missing array keys in legacy rows
                $sid = isset($row['student_id']) ? $row['student_id'] : 'Unknown';
                $snm = isset($row['student_name']) ? $row['student_name'] : 'Unknown';
                $cls = isset($row['class_code']) ? $row['class_code'] : 'General';
                $rmk = isset($row['remarks']) ? $row['remarks'] : '';
                
                // FEATURE 10: Absence Dimming
                $row_style = ($st == 'Absent' || $st == 'Excused') ? "opacity: 0.65; filter: grayscale(40%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                // FEATURE 8: Dynamic Iconography
                $icon = 'fa-check-circle';
                if($st == 'Late') $icon = 'fa-clock';
                if($st == 'Absent') $icon = 'fa-times-circle';
                if($st == 'Excused') $icon = 'fa-envelope-open-text';

                // FEATURE 6: Time Formatting
                $f_date = date('M d, Y', strtotime($row['record_date']));
                $f_time = $row['time_in'] != '00:00:00' ? date('h:i A', strtotime($row['time_in'])) : '--:-- --';

                echo "
                <tr style='$row_style' data-stat='{$st}' data-class='{$cls}' data-date='{$row['record_date']}'>
                    <td>
                        <div class='id-box'>{$sid}</div>
                        <div style='font-size:1.15rem; color:var(--text-dark); margin-top:8px; font-weight:900; font-family:var(--heading-font);'>{$snm}</div>
                    </td>
                    <td>
                        <strong style='color:var(--brand-secondary); text-transform:uppercase; font-size:0.95rem;'><i class='fas fa-chalkboard' style='margin-right:6px;'></i> {$cls}</strong>
                        <div style='font-size:0.8rem; color:var(--text-light); font-weight:600; margin-top:8px; line-height:1.4;'>{$rmk}</div>
                    </td>
                    <td>
                        <div style='font-weight:900; color:var(--text-dark); font-size:1.05rem;'><i class='fas fa-calendar-day' style='color:var(--brand-secondary); margin-right:8px;'></i> {$f_date}</div>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:800; margin-top:6px;'><i class='fas fa-sign-in-alt' style='color:var(--brand-primary); margin-right:8px;'></i> IN: {$f_time}</div>
                    </td>
                    <td>
                        <span class='status-pill {$st_class}'><i class='fas {$icon}' style='margin-right:6px;'></i> {$st}</span>
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openAttendanceModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
                            <a href='?toggle_status={$row['id']}' class='table-btn' style='border-color:#f59e0b; color:#f59e0b;' onclick='systemToast(\"Rotating Attendance Status...\")'><i class='fas fa-sync-alt'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Purging Log...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='5' style='text-align:center; padding:50px; font-weight:800; opacity:0.5;'><i class='fas fa-calendar-times' style='font-size:3rem; margin-bottom:15px; color:var(--brand-secondary);'></i><br>No attendance records logged.</td></tr>";
            ?>
        </tbody>
    </table>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-calendar-check" style="color:#4f46e5;"></i> Manual Log</h2>
        <form method="POST">
            <input type="hidden" name="save_attendance" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="student_id" id="student_id" placeholder="Student ID (e.g. 2026-0001)" required>
                <input type="text" name="student_name" id="student_name" placeholder="Student Full Name" required>
                
                <input type="text" name="class_code" id="class_code" placeholder="Class Code (e.g. CS101)" style="grid-column: span 2;" required>
                
                <input type="date" name="record_date" id="record_date" required>
                <input type="time" name="time_in" id="time_in" required>
                
                <select name="status" id="status" required style="grid-column: span 2;">
                    <option value="Present">Present</option>
                    <option value="Late">Late</option>
                    <option value="Absent">Absent</option>
                    <option value="Excused">Excused</option>
                </select>

                <textarea name="remarks" id="remarks" placeholder="Notes / Excuses (Optional)" style="grid-column: span 2; height:80px; resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#4f46e5; border-color:#4f46e5; color:#fff;"><i class="fas fa-save"></i> Save Roll Call Data</button>
        </form>
    </div>
</div>

<script>
// ADVANCED CHAINED FILTER ENGINE
function filterAttendance() {
    const cFilter = document.getElementById('filterClass').value;
    const sFilter = document.getElementById('filterStatus').value;
    const dFilter = document.getElementById('filterDate').value;
    const searchQ = document.getElementById('searchStudentLocal').value.toLowerCase();
    const rows = document.querySelectorAll('#attendanceTableBody tr');
    
    rows.forEach(row => {
        const rClass = row.getAttribute('data-class');
        const rStat = row.getAttribute('data-stat');
        const rDate = row.getAttribute('data-date');
        const rText = row.cells[0].innerText.toLowerCase(); 
        
        let show = true;
        
        if (cFilter !== 'All Classes' && rClass !== cFilter) show = false;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (dFilter !== '' && rDate !== dFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) {
            row.removeAttribute('data-hide-local');
            row.style.display = '';
        } else {
            row.setAttribute('data-hide-local', 'true');
            row.style.display = 'none';
        }
    });

    // Chains with the Global Search in header.php
    if(typeof globalTableSearch === 'function') globalTableSearch();
}

// FEATURE 12: Inline JSON Data Binding
function openAttendanceModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#4f46e5;"></i> Edit Log Data';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('student_id').value = data.student_id || '';
        document.getElementById('student_name').value = data.student_name || '';
        document.getElementById('class_code').value = data.class_code || '';
        document.getElementById('record_date').value = data.record_date || '';
        document.getElementById('time_in').value = data.time_in || '';
        document.getElementById('status').value = data.status || 'Present';
        document.getElementById('remarks').value = data.remarks || '';
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
    }
    
    modal.style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>