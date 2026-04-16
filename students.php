<?php
include 'config.php';

$patch = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    course VARCHAR(50),
    year_level VARCHAR(20),
    department VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Enrolled',
    enrollment_date DATE
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = ["student_id VARCHAR(20)", "first_name VARCHAR(50)", "last_name VARCHAR(50)", "email VARCHAR(100)", "course VARCHAR(50)", "year_level VARCHAR(20)", "department VARCHAR(50)", "status VARCHAR(20) DEFAULT 'Enrolled'", "enrollment_date DATE"];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE students ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM students WHERE id = $id");
    header("Location: students.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_student'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $f = mysqli_real_escape_string($conn, $_POST['first_name']);
    $l = mysqli_real_escape_string($conn, $_POST['last_name']);
    $e = mysqli_real_escape_string($conn, $_POST['email']);
    $c = mysqli_real_escape_string($conn, $_POST['course']);
    $y = mysqli_real_escape_string($conn, $_POST['year_level']);
    $d = mysqli_real_escape_string($conn, $_POST['department']);
    $s = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE students SET student_id='$sid', first_name='$f', last_name='$l', email='$e', course='$c', year_level='$y', department='$d', status='$s' WHERE id=$id");
    } else {
        $ed = date('Y-m-d');
        mysqli_query($conn, "INSERT INTO students (student_id, first_name, last_name, email, course, year_level, department, status, enrollment_date) VALUES ('$sid', '$f', '$l', '$e', '$c', '$y', '$d', '$s', '$ed')");
    }
    header("Location: students.php");
    exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM students");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $fn = ['James','Mary','John','Patricia','Robert','Jennifer','Michael','Linda','William','Elizabeth','David','Barbara','Richard','Susan','Joseph','Jessica','Thomas','Sarah','Charles','Karen'];
    $ln = ['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Hernandez','Lopez','Gonzalez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin'];
    $depts = ['Computer Studies' => ['BSCS', 'BSIT', 'BSIS'], 'Business' => ['BSBA', 'BSA'], 'Engineering' => ['BSCE', 'BSEE', 'BSME'], 'Arts & Sciences' => ['AB Comm', 'BS Psych']];
    $yrs = ['1A', '1B', '2A', '2B', '3A', '4A'];
    
    for($i=1; $i<=20; $i++) {
        $sid = "S26-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $f = mysqli_real_escape_string($conn, $fn[array_rand($fn)]);
        $l = mysqli_real_escape_string($conn, $ln[array_rand($ln)]);
        $e = strtolower($f.".".$l."@campus.edu");
        $dept_key = array_rand($depts);
        $crs = $depts[$dept_key][array_rand($depts[$dept_key])];
        $yr = $yrs[array_rand($yrs)];
        $stat = (rand(1,10) > 2) ? 'Enrolled' : 'On Leave';
        $ed = date('Y-m-d', strtotime('-'.rand(1, 800).' days'));
        
        mysqli_query($conn, "INSERT INTO students (student_id, first_name, last_name, email, course, year_level, department, status, enrollment_date) VALUES ('$sid', '$f', '$l', '$e', '$crs', '$yr', '$dept_key', '$stat', '$ed')");
    }
}

include 'header.php';

$total = getCount($conn, 'students');
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM students WHERE status='Enrolled'"))['c'];
$depts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as c FROM students"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: var(--brand-secondary); opacity: 0.9; }
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }
    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-leave { background: rgba(245, 158, 11, 0.1); color: var(--brand-secondary); border-color: var(--brand-secondary); }
    .id-box { font-weight:900; font-family:monospace; font-size:1.1rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:4px 10px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .id-box { color: var(--brand-primary); }
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--brand-secondary); color: var(--brand-primary); font-weight: 900;}
    [data-theme="light"] .view-btn.active-view { background: var(--brand-primary); color: #fff;}
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .data-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="light"] .data-card:hover { border-color: var(--brand-primary); }
    .data-card.dimmed { opacity: 0.55; filter: grayscale(80%); }
    .dc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .dc-title { font-family: var(--heading-font); font-size: 1.3rem; font-weight: 900; color: var(--text-dark); margin-bottom: 8px; line-height: 1.3;}
    .dc-detail { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 8px; font-weight: 600;}
    .dc-detail i { color: var(--brand-secondary); width: 16px; text-align: center;}
    [data-theme="light"] .dc-detail i { color: var(--brand-primary); }
    .dc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #3b82f6;">
    <h1 style="color: #3b82f6; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Student Registry</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage admissions, academic standing, and scholar demographics.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-users stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Registered</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-check stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $active ?></div>
            <div class="stat-lbl">Actively Enrolled</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-building stat-icon"></i>
        <div>
            <div class="stat-val"><?= $depts ?></div>
            <div class="stat-lbl">Academic Departments</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchStudentLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Name or ID..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterDept" onchange="filterMatrix()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Departments">All Departments</option>
            <?php
            $d_res = mysqli_query($conn, "SELECT DISTINCT department FROM students ORDER BY department ASC");
            while($d = mysqli_fetch_assoc($d_res)) { echo "<option value='{$d['department']}'>{$d['department']}</option>"; }
            ?>
        </select>
        <select id="filterStatus" onchange="filterMatrix()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Statuses</option>
            <option value="Enrolled">Enrolled</option>
            <option value="On Leave">On Leave</option>
            <option value="Graduated">Graduated</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action"><i class="fas fa-file-export"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px;" onclick="openModal()"><i class="fas fa-user-plus"></i> New Scholar</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:1%;">ID Number</th>
                <th>Scholar Name</th>
                <th>Course & Year</th>
                <th>Department</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM students ORDER BY last_name ASC");
            $all_data = [];
            while($row = mysqli_fetch_assoc($res)) {
                $all_data[] = $row;
                $stat_class = $row['status'] == 'Enrolled' ? 'status-active' : 'status-leave';
                $dim_class = $row['status'] != 'Enrolled' ? "opacity:0.65; filter:grayscale(50%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                echo "
                <tr class='filter-target' style='$dim_class' data-dept='{$row['department']}' data-stat='{$row['status']}'>
                    <td><div class='id-box'>{$row['student_id']}</div></td>
                    <td><strong style='color:var(--text-dark); font-size:1.1rem;'>{$row['last_name']}</strong>, {$row['first_name']}<br><span style='font-size:0.8rem; color:var(--text-light);'>{$row['email']}</span></td>
                    <td><span style='font-weight:900;'>{$row['course']}</span> - {$row['year_level']}</td>
                    <td><strong style='color:var(--brand-secondary); text-transform:uppercase; font-size:0.85rem;'>{$row['department']}</strong></td>
                    <td><span class='status-pill {$stat_class}'>{$row['status']}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
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
        $st_class = $st == 'Enrolled' ? 'status-active' : 'status-leave';
        $dim_class = $st != 'Enrolled' ? 'dimmed' : '';
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        $bdr_color = "var(--border-color)";
        if($row['department'] == 'Computer Studies') $bdr_color = '#3b82f6';
        if($row['department'] == 'Engineering') $bdr_color = '#f59e0b';
        if($row['department'] == 'Business') $bdr_color = '#10b981';
        if($row['department'] == 'Arts & Sciences') $bdr_color = '#ec4899';
        
        echo "
        <div class='data-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}' data-dept='{$row['department']}'>
            <div class='dc-header'>
                <div class='id-box' style='font-size:0.85rem; padding:4px 8px;'>{$row['student_id']}</div>
                <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
            </div>
            <div class='dc-title'>{$row['last_name']}, {$row['first_name']}</div>
            <div style='font-size:0.85rem; font-weight:800; color:var(--brand-secondary); margin-bottom:15px; text-transform:uppercase;'>{$row['department']}</div>
            <div class='dc-detail'><i class='fas fa-graduation-cap'></i> {$row['course']} - Year {$row['year_level']}</div>
            <div class='dc-detail'><i class='fas fa-envelope'></i> {$row['email']}</div>
            <div class='dc-footer'>
                <div style='display:flex; gap:8px; margin-left:auto;'>
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
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-user-graduate" style="color:var(--brand-secondary);"></i> Add Scholar</h2>
        <form method="POST">
            <input type="hidden" name="save_student" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="student_id" id="student_id" readonly style="background:var(--bg-grid); cursor:not-allowed; border-color:var(--brand-secondary);" required>
                <input type="email" name="email" id="email" placeholder="Campus Email" required>
                <input type="text" name="first_name" id="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>
                <select name="department" id="department" required>
                    <option value="" disabled selected>Select Department</option>
                    <option value="Computer Studies">Computer Studies</option>
                    <option value="Business">Business</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Arts & Sciences">Arts & Sciences</option>
                    <option value="General">General / All</option>
                </select>
                <div style="display:flex; gap: 10px;">
                    <input type="text" name="course" id="course" placeholder="Course (BSCS)" style="flex:2;" required>
                    <input type="text" name="year_level" id="year_level" placeholder="Yr (2A)" style="flex:1;" required>
                </div>
                <select name="status" id="status" required style="grid-column: span 2;">
                    <option value="Enrolled">Enrolled</option>
                    <option value="On Leave">On Leave</option>
                    <option value="Graduated">Graduated</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px;"><i class="fas fa-save"></i> Save Record</button>
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
        localStorage.setItem('campus_student_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_student_view', 'table');
    }
}
document.addEventListener('DOMContentLoaded', () => { setView(localStorage.getItem('campus_student_view') || 'table'); });

function filterMatrix() {
    const dFilter = document.getElementById('filterDept').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchStudentLocal').value.toLowerCase();
    const targets = document.querySelectorAll('.filter-target');
    targets.forEach(el => {
        const rDept = el.getAttribute('data-dept');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if (dFilter !== 'All Departments' && rDept !== dFilter) show = false;
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
        title.innerHTML = '<i class="fas fa-pen" style="color:var(--brand-secondary);"></i> Edit Scholar';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('student_id').value = data.student_id;
        document.getElementById('first_name').value = data.first_name;
        document.getElementById('last_name').value = data.last_name;
        document.getElementById('email').value = data.email;
        document.getElementById('department').value = data.department;
        document.getElementById('course').value = data.course;
        document.getElementById('year_level').value = data.year_level;
        document.getElementById('status').value = data.status;
    } else {
        title.innerHTML = '<i class="fas fa-user-graduate" style="color:var(--brand-secondary);"></i> Add Scholar';
        document.getElementById('edit_id').value = '';
        document.getElementById('student_id').value = 'S26-' + Math.random().toString(36).substr(2, 6).toUpperCase();
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('department').value = '';
        document.getElementById('course').value = '';
        document.getElementById('year_level').value = '';
        document.getElementById('status').value = 'Enrolled';
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>