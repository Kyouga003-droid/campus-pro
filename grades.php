<?php
include 'config.php';

$patch = ["student_id VARCHAR(20)", "class_code VARCHAR(20)", "grade_value VARCHAR(10)", "remarks VARCHAR(50)", "recorded_by VARCHAR(50)", "last_updated DATETIME"];
foreach($patch as $p) { try { mysqli_query($conn, "ALTER TABLE grades ADD COLUMN $p"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM grades WHERE id = $id");
    header("Location: grades.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grade'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $cc = mysqli_real_escape_string($conn, $_POST['class_code']);
    $gv = mysqli_real_escape_string($conn, $_POST['grade_value']);
    $rem = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE grades SET student_id='$sid', class_code='$cc', grade_value='$gv', remarks='$rem', last_updated=NOW() WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO grades (student_id, class_code, grade_value, remarks, recorded_by, last_updated) VALUES ('$sid', '$cc', '$gv', '$rem', 'Admin', NOW())");
    }
    header("Location: grades.php");
    exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM grades");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    for($i=1; $i<=15; $i++) {
        $sid = "2026-" . str_pad(rand(1, 50), 4, "0", STR_PAD_LEFT);
        $cc = "CS" . rand(101, 401);
        $g = ['A', 'B+', 'B', 'C+', 'C', 'F'];
        $gv = $g[array_rand($g)];
        $rem = ($gv == 'F') ? 'Failed' : 'Passed';
        mysqli_query($conn, "INSERT INTO grades (student_id, class_code, grade_value, remarks, recorded_by, last_updated) VALUES ('$sid', '$cc', '$gv', '$rem', 'Auto-System', NOW())");
    }
}

include 'header.php';

$total = getCount($conn, 'grades');
$passed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM grades WHERE remarks='Passed' OR remarks='Excellent'"))['c'];
$fail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM grades WHERE remarks='Failed'"))['c'];
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

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid var(--brand-secondary);">
    <h1 style="color: var(--brand-secondary); font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Academic Records</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage student grading, transcripts, and term evaluations.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-file-alt stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Entries</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $passed ?></div>
            <div class="stat-lbl">Passing Grades</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-times-circle stat-icon" style="color:var(--brand-crimson);"></i>
        <div>
            <div class="stat-val" style="color:var(--brand-crimson);"><?= $fail ?></div>
            <div class="stat-lbl">Failed / INC</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px;">
        <select id="filterClass" onchange="filterMatrix()" style="width: auto; padding: 10px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option>All Classes</option>
            <option>CS101</option>
            <option>BA101</option>
            <option>PSY101</option>
        </select>
        <select id="filterStatus" onchange="filterMatrix()" style="width: auto; padding: 10px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option>All Statuses</option>
            <option>Passed</option>
            <option>Excellent</option>
            <option>Failed</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Transcripts...')"><i class="fas fa-file-export"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 10px 20px;" onclick="openModal()"><i class="fas fa-plus"></i> Input Grade</button>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:1%;">Student ID</th>
                <th>Class Code</th>
                <th>Final Grade</th>
                <th>Remarks</th>
                <th>Last Updated</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM grades ORDER BY last_updated DESC");
            while($row = mysqli_fetch_assoc($res)) {
                $rem_class = ($row['remarks'] == 'Passed' || $row['remarks'] == 'Excellent') ? 'style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981;"' : 'style="background: rgba(171, 54, 32, 0.1); color: var(--brand-crimson); border-color: var(--brand-crimson);"';
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $d = date('M d, Y', strtotime($row['last_updated']));
                echo "
                <tr data-class='{$row['class_code']}' data-stat='{$row['remarks']}'>
                    <td style='font-family:monospace; font-weight:800; color:var(--brand-primary); font-size:1.1rem;'>{$row['student_id']}</td>
                    <td><strong style='color:var(--text-dark); font-size:1.1rem;'>{$row['class_code']}</strong></td>
                    <td><span style='font-weight:900; font-size:1.5rem; color:var(--brand-secondary); font-family:var(--heading-font);'>{$row['grade_value']}</span></td>
                    <td><span class='status-pill' {$rem_class}>{$row['remarks']}</span></td>
                    <td style='font-size:0.85rem; color:var(--text-light); font-weight:700;'><i class='far fa-clock'></i> {$d}</td>
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

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-award" style="color:var(--brand-secondary);"></i> Input Grade</h2>
        <form method="POST">
            <input type="hidden" name="save_grade" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="student_id" id="student_id" placeholder="Student ID" required>
                <input type="text" name="class_code" id="class_code" placeholder="Class Code" required>
                
                <input type="text" name="grade_value" id="grade_value" placeholder="Grade (A, B+, 1.0)" required>
                <select name="remarks" id="remarks" required>
                    <option value="" disabled selected>Status Remarks</option>
                    <option value="Excellent">Excellent</option>
                    <option value="Passed">Passed</option>
                    <option value="Failed">Failed</option>
                    <option value="Incomplete">Incomplete</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px;"><i class="fas fa-save"></i> Save Record</button>
        </form>
    </div>
</div>

<script>
function filterMatrix() {
    const cFilter = document.getElementById('filterClass').value;
    const sFilter = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#filterTableBody tr');
    
    rows.forEach(row => {
        const rClass = row.getAttribute('data-class');
        const rStat = row.getAttribute('data-stat');
        let show = true;
        if (cFilter !== 'All Classes' && !rClass.includes(cFilter)) show = false;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        row.style.display = show ? '' : 'none';
    });
}

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:var(--brand-secondary);"></i> Edit Grade Record';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('student_id').value = data.student_id;
        document.getElementById('class_code').value = data.class_code;
        document.getElementById('grade_value').value = data.grade_value;
        document.getElementById('remarks').value = data.remarks;
    } else {
        title.innerHTML = '<i class="fas fa-award" style="color:var(--brand-secondary);"></i> Input New Grade';
        document.getElementById('edit_id').value = '';
        document.getElementById('student_id').value = '';
        document.getElementById('class_code').value = '';
        document.getElementById('grade_value').value = '';
        document.getElementById('remarks').value = '';
    }
    
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>