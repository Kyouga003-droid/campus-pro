<?php 
include 'config.php'; 

// FEATURE 1: Dynamic Schema Patcher
$patch = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_code VARCHAR(50) UNIQUE,
    subject_name VARCHAR(150),
    department VARCHAR(50),
    instructor VARCHAR(100),
    schedule VARCHAR(100),
    room VARCHAR(50),
    enrolled INT DEFAULT 0,
    capacity INT DEFAULT 40,
    status VARCHAR(20) DEFAULT 'Active'
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = ["class_code VARCHAR(50)", "subject_name VARCHAR(150)", "department VARCHAR(50)", "instructor VARCHAR(100)", "schedule VARCHAR(100)", "room VARCHAR(50)", "enrolled INT DEFAULT 0", "capacity INT DEFAULT 40", "status VARCHAR(20) DEFAULT 'Active'"];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE classes ADD COLUMN $c"); } catch (Exception $e) {} }

// FEATURE 16: Secure Deletion Protocol
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM classes WHERE id = $id");
    header("Location: classes.php"); exit();
}

// 1-Click Status Rotator
if(isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $res = mysqli_query($conn, "SELECT status FROM classes WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $cur = $row['status'];
        if($cur == 'Active') $nxt = 'Completed';
        elseif($cur == 'Completed') $nxt = 'Canceled';
        else $nxt = 'Active';
        mysqli_query($conn, "UPDATE classes SET status = '$nxt' WHERE id = $id");
        header("Location: classes.php"); exit();
    }
}

// Universal CRUD Modal Logic
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_class'])) {
    $cc = mysqli_real_escape_string($conn, $_POST['class_code']);
    $sn = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $dp = mysqli_real_escape_string($conn, $_POST['department']);
    $in = mysqli_real_escape_string($conn, $_POST['instructor']);
    $sc = mysqli_real_escape_string($conn, $_POST['schedule']);
    $rm = mysqli_real_escape_string($conn, $_POST['room']);
    $en = intval($_POST['enrolled']);
    $cp = intval($_POST['capacity']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE classes SET class_code='$cc', subject_name='$sn', department='$dp', instructor='$in', schedule='$sc', room='$rm', enrolled=$en, capacity=$cp, status='$st' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO classes (class_code, subject_name, department, instructor, schedule, room, enrolled, capacity, status) VALUES ('$cc', '$sn', '$dp', '$in', '$sc', '$rm', $en, $cp, '$st')");
    }
    header("Location: classes.php"); exit();
}

// FEATURE 2: Mass 20-Class Seed Engine
$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM classes");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed = [
        ['CS101', 'Intro to Programming', 'Computer Studies', 'Dr. Alan Turing', 'Mon/Wed 08:00 AM - 10:00 AM', 'LB-101', 35, 40, 'Active'],
        ['CS202', 'Data Structures', 'Computer Studies', 'Prof. Grace Hopper', 'Tue/Thu 10:00 AM - 12:00 PM', 'LB-102', 38, 40, 'Active'],
        ['CS305', 'Database Management', 'Computer Studies', 'Dr. Edgar Codd', 'Mon/Wed 01:00 PM - 03:00 PM', 'LB-103', 40, 40, 'Active'],
        ['ENG101', 'College Calculus 1', 'Engineering', 'Prof. Isaac Newton', 'Tue/Thu 08:00 AM - 10:00 AM', 'LH-201', 95, 100, 'Active'],
        ['ENG201', 'Engineering Physics', 'Engineering', 'Dr. Albert Einstein', 'Mon/Wed 10:00 AM - 12:00 PM', 'LH-202', 85, 100, 'Active'],
        ['ENG305', 'Thermodynamics', 'Engineering', 'Prof. Lord Kelvin', 'Fri 08:00 AM - 12:00 PM', 'LB-901', 25, 30, 'Active'],
        ['BUS101', 'Principles of Mgmt', 'Business', 'Dr. Peter Drucker', 'Tue/Thu 01:00 PM - 03:00 PM', 'CR-301', 45, 50, 'Active'],
        ['BUS201', 'Financial Accounting', 'Business', 'Prof. Luca Pacioli', 'Mon/Wed 03:00 PM - 05:00 PM', 'CR-302', 48, 50, 'Active'],
        ['BUS301', 'Macroeconomics', 'Business', 'Dr. John Keynes', 'Fri 01:00 PM - 04:00 PM', 'CR-303', 30, 40, 'Active'],
        ['ART101', 'Art History Survey', 'Arts & Sciences', 'Prof. Ernst Gombrich', 'Tue/Thu 03:00 PM - 05:00 PM', 'LH-201', 110, 150, 'Active'],
        ['LIT101', 'World Literature', 'Arts & Sciences', 'Dr. William Shake', 'Mon/Wed 08:00 AM - 10:00 AM', 'CR-304', 35, 40, 'Active'],
        ['PSY101', 'General Psychology', 'Arts & Sciences', 'Prof. Sigmund Freud', 'Tue/Thu 10:00 AM - 12:00 PM', 'LH-202', 90, 100, 'Active'],
        ['SOC101', 'Intro to Sociology', 'Arts & Sciences', 'Dr. Max Weber', 'Mon/Wed 01:00 PM - 03:00 PM', 'CR-301', 40, 50, 'Active'],
        ['CS401', 'Artificial Intelligence', 'Computer Studies', 'Prof. John McCarthy', 'Fri 08:00 AM - 12:00 PM', 'IT-401', 28, 35, 'Active'],
        ['CS405', 'Software Engineering', 'Computer Studies', 'Dr. Margaret Hamilton', 'Tue/Thu 08:00 AM - 10:00 AM', 'IT-402', 30, 30, 'Active'],
        ['ENG401', 'Structural Analysis', 'Engineering', 'Prof. Gustave Eiffel', 'Mon/Wed 03:00 PM - 05:00 PM', 'CR-302', 35, 40, 'Active'],
        ['BUS401', 'Strategic Management', 'Business', 'Dr. Michael Porter', 'Tue/Thu 10:00 AM - 12:00 PM', 'CR-303', 40, 40, 'Active'],
        ['ART201', 'Graphic Design 1', 'Arts & Sciences', 'Prof. Paul Rand', 'Fri 01:00 PM - 05:00 PM', 'IT-401', 20, 25, 'Active'],
        ['SCI101', 'General Chemistry', 'Engineering', 'Dr. Marie Curie', 'Mon/Wed 10:00 AM - 12:00 PM', 'LB-101', 28, 30, 'Completed'],
        ['SCI102', 'General Biology', 'Arts & Sciences', 'Prof. Charles Darwin', 'Tue/Thu 01:00 PM - 03:00 PM', 'LB-102', 0, 25, 'Canceled']
    ];
    foreach($seed as $s) {
        mysqli_query($conn, "INSERT INTO classes (class_code, subject_name, department, instructor, schedule, room, enrolled, capacity, status) VALUES ('{$s[0]}', '{$s[1]}', '{$s[2]}', '{$s[3]}', '{$s[4]}', '{$s[5]}', {$s[6]}, {$s[7]}, '{$s[8]}')");
    }
}

include 'header.php';

$total = getCount($conn, 'classes');
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM classes WHERE status='Active'"))['c'];
$cap_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(enrolled) as e, SUM(capacity) as c FROM classes WHERE status='Active'"));
$tot_enrolled = $cap_data['e'] ?: 0;
$tot_capacity = $cap_data['c'] ?: 0;
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
    
    .status-act { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-comp { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: #3b82f6; }
    .status-can { background: var(--main-bg); color: var(--text-light); border-color: var(--text-light); }
    
    .code-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .code-box { color: var(--brand-primary); }

    /* VIEW TOGGLE CSS */
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--brand-secondary); color: var(--brand-primary); font-weight: 900;}
    [data-theme="light"] .view-btn.active-view { background: var(--brand-primary); color: #fff;}

    /* WINDOWED GRID VIEW CSS */
    .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .class-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .class-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="light"] .class-card:hover { border-color: var(--brand-primary); }
    .class-card.dimmed { opacity: 0.6; filter: grayscale(50%); }
    
    .cc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .cc-title { font-family: var(--heading-font); font-size: 1.25rem; font-weight: 900; color: var(--text-dark); margin-bottom: 15px; line-height: 1.3;}
    .cc-detail { display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 10px; font-weight: 600;}
    .cc-detail i { color: var(--brand-secondary); width: 16px; text-align: center; margin-top: 3px;}
    [data-theme="light"] .cc-detail i { color: var(--brand-primary); }
    
    .cc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #10b981;">
    <h1 style="color: #10b981; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Academic Classes</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Manage course offerings, faculty assignments, and scheduling.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-chalkboard stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Registered Classes</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $active ?></div>
            <div class="stat-lbl">Active Sessions</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-users stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= number_format($tot_enrolled) ?> <span style="font-size:1.2rem; color:var(--text-light);">/ <?= number_format($tot_capacity) ?></span></div>
            <div class="stat-lbl">Global Seat Occupancy</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; flex-wrap:wrap; align-items:center;">
        
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')" title="List View"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')" title="Windowed Grid View"><i class="fas fa-th-large"></i></button>
        </div>

        <input type="text" id="searchClassLocal" onkeyup="filterClasses()" placeholder="&#xf002; Search Subject or Code..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 250px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterClasses()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Completed">Completed</option>
            <option value="Canceled">Canceled</option>
        </select>
        <select id="filterDept" onchange="filterClasses()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Departments">All Departments</option>
            <?php
            $d_res = mysqli_query($conn, "SELECT DISTINCT department FROM classes ORDER BY department ASC");
            while($d = mysqli_fetch_assoc($d_res)) { echo "<option value='{$d['department']}'>{$d['department']}</option>"; }
            ?>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Class Roster...')"><i class="fas fa-file-csv"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#10b981; border-color:#10b981; color:#fff;" onclick="openClassModal()"><i class="fas fa-plus"></i> Add Class</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:20%;">Class Code</th>
                <th>Subject & Department</th>
                <th style="width:25%;">Schedule & Room</th>
                <th style="width:15%;">Capacity</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="classTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM classes ORDER BY status ASC, class_code ASC");
            // Store array to render the grid view right after without querying again
            $all_classes = [];
            
            while($row = mysqli_fetch_assoc($res)) {
                $all_classes[] = $row;
                
                $st = $row['status'];
                if($st == 'Active') $st_class = 'status-act';
                elseif($st == 'Completed') $st_class = 'status-comp';
                else $st_class = 'status-can';
                
                $row_style = $st != 'Active' ? "opacity: 0.6; filter: grayscale(50%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $enr = intval($row['enrolled']);
                $cap = intval($row['capacity']);
                $pct = $cap > 0 ? ($enr / $cap) * 100 : 0;
                $bar_color = $pct >= 100 ? '#ef4444' : ($pct > 75 ? '#f59e0b' : '#10b981');

                echo "
                <tr class='filter-target' style='$row_style' data-stat='{$st}' data-dept='{$row['department']}'>
                    <td>
                        <div class='code-box'>{$row['class_code']}</div>
                    </td>
                    <td>
                        <strong style='color:var(--text-dark); font-size:1.15rem; font-family:var(--heading-font);'>{$row['subject_name']}</strong>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:700; margin-top:4px;'><i class='fas fa-user-tie'></i> {$row['instructor']}</div>
                        <div style='font-size:0.8rem; color:var(--brand-secondary); font-weight:800; margin-top:6px; text-transform:uppercase;'>{$row['department']}</div>
                    </td>
                    <td>
                        <div style='font-size:0.9rem; color:var(--text-dark); font-weight:800; margin-bottom:6px;'><i class='far fa-clock' style='color:var(--brand-secondary); margin-right:6px;'></i> {$row['schedule']}</div>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:700;'><i class='fas fa-map-marker-alt' style='margin-right:6px;'></i> {$row['room']}</div>
                    </td>
                    <td>
                        <div style='display:flex; justify-content:space-between; font-weight:900; color:var(--text-dark); margin-bottom:6px; font-size:0.85rem;'><span>{$enr}</span> <span>{$cap}</span></div>
                        <div style='width: 100%; height: 6px; background: var(--border-light); border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                    </td>
                    <td>
                        <span class='status-pill {$st_class}'>{$st}</span>
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openClassModal($js_data)'><i class='fas fa-pen'></i></button>
                            <a href='?toggle_status={$row['id']}' class='table-btn' style='border-color:#3b82f6; color:#3b82f6;' onclick='systemToast(\"Updating Status...\")'><i class='fas fa-sync-alt'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Deleting Class...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            if(count($all_classes) == 0) echo "<tr><td colspan='6' style='text-align:center; padding:50px; font-weight:800; opacity:0.5;'><i class='fas fa-chalkboard' style='font-size:3rem; margin-bottom:15px; color:var(--brand-secondary);'></i><br>No classes found.</td></tr>";
            ?>
        </tbody>
    </table>
</div>

<div id="gridView" class="class-grid" style="display:none;">
    <?php
    foreach($all_classes as $row) {
        $st = $row['status'];
        if($st == 'Active') $st_class = 'status-act';
        elseif($st == 'Completed') $st_class = 'status-comp';
        else $st_class = 'status-can';
        
        $dim_class = $st != 'Active' ? 'dimmed' : '';
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        
        $enr = intval($row['enrolled']);
        $cap = intval($row['capacity']);
        $pct = $cap > 0 ? ($enr / $cap) * 100 : 0;
        $bar_color = $pct >= 100 ? '#ef4444' : ($pct > 75 ? '#f59e0b' : '#10b981');
        
        // Dept Color coding for top border
        $dept = $row['department'];
        $bdr_color = "var(--brand-secondary)";
        if(strpos($dept, 'Computer') !== false) $bdr_color = "#3b82f6";
        if(strpos($dept, 'Engineering') !== false) $bdr_color = "#f59e0b";
        if(strpos($dept, 'Arts') !== false) $bdr_color = "#ec4899";
        if(strpos($dept, 'Business') !== false) $bdr_color = "#10b981";

        echo "
        <div class='class-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}' data-dept='{$dept}'>
            <div class='cc-header'>
                <div class='code-box' style='font-size:0.9rem; padding:4px 8px;'>{$row['class_code']}</div>
                <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
            </div>
            
            <div class='cc-title'>{$row['subject_name']}</div>
            
            <div class='cc-detail'><i class='fas fa-user-tie'></i> {$row['instructor']}</div>
            <div class='cc-detail'><i class='far fa-clock'></i> {$row['schedule']}</div>
            <div class='cc-detail'><i class='fas fa-map-marker-alt'></i> Room: {$row['room']}</div>
            
            <div class='cc-footer'>
                <div style='flex-grow:1; margin-right:20px;'>
                    <div style='display:flex; justify-content:space-between; font-weight:800; color:var(--text-dark); margin-bottom:6px; font-size:0.75rem;'><span>{$enr} Enrolled</span> <span>{$cap} Max</span></div>
                    <div style='width: 100%; height: 6px; background: var(--border-light); border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                </div>
                <div style='display:flex; gap:8px;'>
                    <button class='table-btn btn-resolve' style='padding:6px 10px;' onclick='openClassModal($js_data)'><i class='fas fa-pen' style='margin:0;'></i></button>
                    <a href='?del={$row['id']}' class='table-btn btn-trash' style='padding:6px 10px;'><i class='fas fa-trash' style='margin:0;'></i></a>
                </div>
            </div>
        </div>";
    }
    ?>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-chalkboard" style="color:#10b981;"></i> Manage Class</h2>
        <form method="POST">
            <input type="hidden" name="save_class" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="class_code" id="class_code" placeholder="Class Code (e.g. CS101)" required>
                <input type="text" name="subject_name" id="subject_name" placeholder="Subject Name" required>
                
                <select name="department" id="department" style="grid-column: span 2;" required>
                    <option value="Administration">Administration</option>
                    <option value="Computer Studies">Computer Studies</option>
                    <option value="Business">Business</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Arts & Sciences">Arts & Sciences</option>
                    <option value="General">General / All</option>
                </select>

                <input type="text" name="instructor" id="instructor" placeholder="Instructor Name" required>
                <input type="text" name="room" id="room" placeholder="Room Assignment" required>
                
                <input type="text" name="schedule" id="schedule" placeholder="Schedule (e.g. Mon/Wed 08:00 AM)" style="grid-column: span 2;" required>
                
                <input type="number" name="enrolled" id="enrolled" placeholder="Current Enrolled" value="0" required>
                <input type="number" name="capacity" id="capacity" placeholder="Max Capacity" value="40" required>
                
                <select name="status" id="status" style="grid-column: span 2;" required>
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                    <option value="Canceled">Canceled</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#10b981; border-color:#10b981; color:#fff;"><i class="fas fa-save"></i> Save Class Data</button>
        </form>
    </div>
</div>

<script>
// VIEW CONTROLLER
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
        localStorage.setItem('campus_class_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_class_view', 'table');
    }
}

// Ensure saved view loads immediately
document.addEventListener('DOMContentLoaded', () => {
    const pref = localStorage.getItem('campus_class_view') || 'table';
    setView(pref);
});

// MULTI-VECTOR FILTER ENGINE (Operates on BOTH Table and Grid targets)
function filterClasses() {
    const sFilter = document.getElementById('filterStatus').value;
    const dFilter = document.getElementById('filterDept').value;
    const searchQ = document.getElementById('searchClassLocal').value.toLowerCase();
    
    // Select both TRs in the table and Divs in the grid
    const targets = document.querySelectorAll('.filter-target');
    
    targets.forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rDept = el.getAttribute('data-dept');
        const rText = el.innerText.toLowerCase(); // Extracts text from either TR or Div
        
        let show = true;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (dFilter !== 'All Departments' && rDept !== dFilter) show = false;
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

function openClassModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#10b981;"></i> Edit Class';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('class_code').value = data.class_code;
        document.getElementById('subject_name').value = data.subject_name;
        document.getElementById('department').value = data.department;
        document.getElementById('instructor').value = data.instructor;
        document.getElementById('schedule').value = data.schedule;
        document.getElementById('room').value = data.room;
        document.getElementById('enrolled').value = data.enrolled;
        document.getElementById('capacity').value = data.capacity;
        document.getElementById('status').value = data.status;
    } else {
        title.innerHTML = '<i class="fas fa-chalkboard" style="color:#10b981;"></i> Add Class';
        document.getElementById('edit_id').value = '';
        document.getElementById('class_code').value = '';
        document.getElementById('subject_name').value = '';
        document.getElementById('department').value = 'Computer Studies';
        document.getElementById('instructor').value = '';
        document.getElementById('schedule').value = '';
        document.getElementById('room').value = '';
        document.getElementById('enrolled').value = '0';
        document.getElementById('capacity').value = '40';
        document.getElementById('status').value = 'Active';
    }
    modal.style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>