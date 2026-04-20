<?php
include 'config.php';

// FUNCTION 1: Deep Schema Patching for Advanced Academic Tracking
$patch = "CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    class_code VARCHAR(20),
    grade_value VARCHAR(10),
    remarks VARCHAR(50),
    recorded_by VARCHAR(50),
    last_updated DATETIME
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = [
    "student_id VARCHAR(20)", "class_code VARCHAR(20)", "grade_value VARCHAR(10)", 
    "remarks VARCHAR(50)", "recorded_by VARCHAR(50)", "last_updated DATETIME",
    "term_semester VARCHAR(50) DEFAULT '1st Semester'", // FUNCTION 2: Term Tracking
    "professor_name VARCHAR(100)", // FUNCTION 3: Instructor Mapping
    "credit_hours INT DEFAULT 3", // FUNCTION 4: Academic Credits
    "is_published BOOLEAN DEFAULT 0", // FUNCTION 5: Grade Visibility Toggle
    "numeric_equivalent DECIMAL(3,2)" // FUNCTION 6: Auto-computed GPA equivalent
];
foreach($cols as $p) { try { mysqli_query($conn, "ALTER TABLE grades ADD COLUMN $p"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM grades WHERE id = $id");
    header("Location: grades.php"); exit();
}

// FUNCTION 7: Quick Status Toggle (Pass/Fail flip)
if(isset($_GET['quick_toggle'])) {
    $id = intval($_GET['quick_toggle']);
    $res = mysqli_query($conn, "SELECT remarks FROM grades WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $current = mysqli_fetch_assoc($res)['remarks'];
        $new = ($current == 'Passed' || $current == 'Excellent') ? 'Failed' : 'Passed';
        mysqli_query($conn, "UPDATE grades SET remarks = '$new', last_updated=NOW() WHERE id = $id");
    }
    header("Location: grades.php"); exit();
}

// FUNCTION 8: Mass Batch Execution Engine
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_action'])) {
    if(!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        if ($_POST['mass_action_type'] === 'publish') {
            mysqli_query($conn, "UPDATE grades SET is_published = 1 WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM grades WHERE id IN ($ids)");
        }
    }
    header("Location: grades.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grade'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $cc = mysqli_real_escape_string($conn, $_POST['class_code']);
    $gv = mysqli_real_escape_string($conn, $_POST['grade_value']);
    $rem = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    $ts = mysqli_real_escape_string($conn, $_POST['term_semester']);
    $pn = mysqli_real_escape_string($conn, $_POST['professor_name']);
    $ch = intval($_POST['credit_hours']);
    $ip = isset($_POST['is_published']) ? 1 : 0;
    $eid = intval($_POST['edit_id']);

    // FUNCTION 9: Auto-compute numeric GPA equivalent based on letter grade
    $ne = 0.00;
    $g = strtoupper($gv);
    if($g=='A' || $g=='1.0') $ne = 4.00;
    elseif($g=='B+' || $g=='1.5') $ne = 3.50;
    elseif($g=='B' || $g=='2.0') $ne = 3.00;
    elseif($g=='C+' || $g=='2.5') $ne = 2.50;
    elseif($g=='C' || $g=='3.0') $ne = 2.00;
    elseif($g=='F' || $g=='5.0') $ne = 0.00;
    
    if($eid > 0) {
        mysqli_query($conn, "UPDATE grades SET student_id='$sid', class_code='$cc', grade_value='$gv', remarks='$rem', term_semester='$ts', professor_name='$pn', credit_hours=$ch, is_published=$ip, numeric_equivalent=$ne, last_updated=NOW() WHERE id=$eid");
    } else {
        mysqli_query($conn, "INSERT INTO grades (student_id, class_code, grade_value, remarks, recorded_by, last_updated, term_semester, professor_name, credit_hours, is_published, numeric_equivalent) VALUES ('$sid', '$cc', '$gv', '$rem', 'Admin', NOW(), '$ts', '$pn', $ch, $ip, $ne)");
    }
    header("Location: grades.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM grades");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    for($i=1; $i<=15; $i++) {
        $sid = "2026-" . str_pad(rand(1, 50), 4, "0", STR_PAD_LEFT);
        $cc = "CS" . rand(101, 401);
        $g = ['A', 'B+', 'B', 'C+', 'C', 'F'];
        $gv = $g[array_rand($g)];
        $rem = ($gv == 'F') ? 'Failed' : 'Passed';
        $prof = ['Dr. Smith', 'Prof. Johnson', 'Dr. Turing', 'Prof. Lovelace'][rand(0,3)];
        mysqli_query($conn, "INSERT INTO grades (student_id, class_code, grade_value, remarks, recorded_by, last_updated, professor_name) VALUES ('$sid', '$cc', '$gv', '$rem', 'Auto-System', NOW(), '$prof')");
    }
}

include 'header.php';

$total = getCount($conn, 'grades');
$passed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM grades WHERE remarks='Passed' OR remarks='Excellent'"))['c'];
$fail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM grades WHERE remarks='Failed'"))['c'];
?>

<style>
    /* UI FEATURE 1: Soft Modern Typography & Layout spacing */
    .page-header { margin-bottom: 30px; }
    .page-title { font-size: 2.2rem; font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px; margin-bottom: 5px; }
    .page-sub { color: var(--text-light); font-size: 1rem; }

    /* UI FEATURE 2: Clean Stripe-style Stat Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .stat-card { background: var(--card-bg); padding: 25px; border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; border-radius: 16px; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .stat-icon { font-size: 1.8rem; color: var(--brand-secondary); display:flex; justify-content:center; align-items:center; width: 50px; height: 50px; background: var(--main-bg); border-radius: 12px; }
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2rem; font-weight: 700; color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; }

    /* UI FEATURE 3: Minimalist Control Bar */
    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 25px; background: var(--card-bg); border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap:wrap; gap:15px;}
    .flt-sel { border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 20px; background: transparent; color: var(--text-dark); font-weight: 500; font-size: 0.9rem; outline:none; transition: 0.2s;}
    .flt-sel:focus { border-color: var(--text-light); }

    /* UI FEATURE 4: Checkbox Selectors */
    .cb-sel { width: 18px; height: 18px; accent-color: var(--text-dark); cursor: pointer; }

    /* UI FEATURE 5: View Toggle Pills */
    .view-toggle { display: flex; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; padding:2px;}
    .view-btn { padding: 8px 16px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1rem; border:none; background:transparent; border-radius: 18px;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--card-bg); color: var(--text-dark); box-shadow: var(--soft-shadow);}

    /* UI FEATURE 6: Modern Grid Data Cards */
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative;}
    .data-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-color: var(--text-light); }
    .dc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px solid var(--border-light); padding-bottom: 15px;}
    
    /* UI FEATURE 7: Status Badges */
    .badge-status { padding: 4px 10px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border-radius: 6px; letter-spacing: 0.5px;}
    .badge-pass { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge-fail { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    
    .dc-grade { font-size: 2.5rem; font-weight: 800; font-family: var(--body-font); color: var(--text-dark); line-height: 1; margin-bottom: 15px; }
    .dc-equiv { font-size: 0.85rem; color: var(--text-light); font-weight: 600; margin-left: 10px; }

    /* UI FEATURE 8: Pagination Control Design */
    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px;}
    .page-btn { background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; color: var(--text-dark); transition: 0.2s; box-shadow: var(--soft-shadow);}
    .page-btn:hover { background: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow:none;}

    /* UI FEATURE 9: Published / Draft Labels */
    .pub-lbl { position: absolute; top: 15px; right: 15px; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; background: var(--bg-grid); color: var(--text-light); border: 1px solid var(--border-color); }
</style>

<div class="page-header">
    <h1 class="page-title">Academic Records</h1>
    <p class="page-sub">Manage student grading, term evaluations, and transcript publishing.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Entries</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.1);"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $passed ?></div>
            <div class="stat-lbl">Passing Grades</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#ef4444; background:rgba(239,68,68,0.1);"><i class="fas fa-times-circle"></i></div>
        <div>
            <div class="stat-val" style="color:#ef4444;"><?= $fail ?></div>
            <div class="stat-lbl">Failed / INC</div>
        </div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewTable" class="view-btn active-view" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button type="button" id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchGradeLocal" onkeyup="filterMatrix()" placeholder="Search records..." class="flt-sel" style="width: 220px;">
        
        <select id="filterClass" class="flt-sel" onchange="filterMatrix()">
            <option value="All Classes">All Classes</option>
            <?php
            $c_res = mysqli_query($conn, "SELECT DISTINCT class_code FROM grades ORDER BY class_code ASC");
            while($c = mysqli_fetch_assoc($c_res)) { echo "<option value='{$c['class_code']}'>{$c['class_code']}</option>"; }
            ?>
        </select>
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All Statuses">All Statuses</option>
            <option value="Passed">Passed</option>
            <option value="Excellent">Excellent</option>
            <option value="Failed">Failed</option>
            <option value="Incomplete">Incomplete</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('gradeTable', 'transcripts_export')"><i class="fas fa-download"></i> Export Data</button>
        <button type="button" class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Input Grade</button>
    </div>
</form>

<form method="POST" id="massForm">
    <input type="hidden" name="mass_action" value="1">
    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center; background: var(--card-bg); padding: 15px 25px; border: 1px solid var(--border-color); border-radius: 12px; box-shadow:var(--soft-shadow);">
        <span style="font-weight: 600; font-size:0.9rem;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding: 8px 15px;">
            <option value="publish">Publish Grades to Students</option>
            <option value="delete">Delete Records</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation?')">Apply</button>
    </div>

    <div id="tableView" class="table-responsive">
        <table id="gradeTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c => c.checked = this.checked)"></th>
                    <th style="width:1%;">Student ID</th>
                    <th>Class Details</th>
                    <th>Academic Grade</th>
                    <th>Status</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody id="filterTableBody">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM grades ORDER BY last_updated DESC");
                $all_data = [];
                while($row = mysqli_fetch_assoc($res)) {
                    $all_data[] = $row;
                    $rem = $row['remarks'];
                    $b_cls = ($rem == 'Passed' || $rem == 'Excellent') ? 'badge-pass' : 'badge-fail';
                    $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    $d = date('M d, Y', strtotime($row['last_updated']));
                    $pub_icon = $row['is_published'] ? "<i class='fas fa-eye' style='color:#10b981;' title='Published'></i>" : "<i class='fas fa-eye-slash' style='color:var(--text-light);' title='Draft'></i>";

                    echo "
                    <tr class='paginate-row filter-target' data-class='{$row['class_code']}' data-stat='{$row['remarks']}'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$row['id']}' class='cb-item cb-sel'></td>
                        <td style='font-family:monospace; font-weight:700; color:var(--text-dark); font-size:1.05rem;'>{$row['student_id']}</td>
                        <td>
                            <strong style='color:var(--text-dark); font-size:1rem;'>{$row['class_code']}</strong> {$pub_icon}<br>
                            <span style='font-size:0.8rem; color:var(--text-light);'>{$row['term_semester']} • {$row['professor_name']}</span>
                        </td>
                        <td>
                            <span style='font-weight:800; font-size:1.3rem; color:var(--text-dark);'>{$row['grade_value']}</span>
                            <span class='dc-equiv'>({$row['numeric_equivalent']})</span>
                        </td>
                        <td><span class='badge-status {$b_cls}'>{$row['remarks']}</span></td>
                        <td class='action-col'>
                            <div class='table-actions-cell'>
                                <a href='?quick_toggle={$row['id']}' class='table-btn' title='Toggle Pass/Fail'><i class='fas fa-sync-alt'></i></a>
                                <button type='button' class='table-btn' onclick='openModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
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
            $rem = $row['remarks'];
            $b_cls = ($rem == 'Passed' || $rem == 'Excellent') ? 'badge-pass' : 'badge-fail';
            $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            $bdr = ($rem == 'Passed' || $rem == 'Excellent') ? '#10b981' : '#ef4444';
            
            echo "
            <div class='data-card paginate-card filter-target' style='border-top: 4px solid {$bdr};' data-class='{$row['class_code']}' data-stat='{$row['remarks']}'>
                ".($row['is_published'] ? "<div class='pub-lbl'>Published</div>" : "<div class='pub-lbl'>Draft</div>")."
                
                <div class='dc-header'>
                    <div>
                        <div style='font-family:monospace; font-weight:700; font-size:0.9rem; color:var(--text-light); margin-bottom:4px;'>{$row['student_id']}</div>
                        <div style='font-size:1.1rem; font-weight:700; color:var(--text-dark);'>{$row['class_code']}</div>
                    </div>
                </div>
                
                <div style='display:flex; align-items:baseline;'>
                    <div class='dc-grade'>{$row['grade_value']}</div>
                    <div class='dc-equiv'>GPA: {$row['numeric_equivalent']}</div>
                </div>
                
                <div style='margin-bottom:15px;'><span class='badge-status {$b_cls}'>{$row['remarks']}</span></div>
                
                <div style='font-size:0.8rem; color:var(--text-light); margin-bottom:20px;'>
                    <i class='fas fa-user-tie'></i> {$row['professor_name']}<br>
                    <i class='far fa-clock' style='margin-top:5px;'></i> {$row['term_semester']}
                </div>

                <div style='margin-top:auto; padding-top:15px; border-top:1px solid var(--border-light); display:flex; justify-content:space-between;'>
                    <a href='?quick_toggle={$row['id']}' class='table-btn'><i class='fas fa-sync-alt'></i></a>
                    <button type='button' class='table-btn' onclick='openModal({$js_data})'><i class='fas fa-pen'></i> Edit</button>
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
    <div class="modal-box" style="max-width: 650px;">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 25px;"><i class="fas fa-award"></i> Grade Registry</h2>
        
        <form method="POST">
            <input type="hidden" name="save_grade" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="student_id" id="student_id" placeholder="Student ID (S26-XXXX)" required>
                <input type="text" name="class_code" id="class_code" placeholder="Class Code (e.g. CS101)" required>
                
                <select name="term_semester" id="term_semester" required style="grid-column: span 2;">
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Summer Term">Summer Term</option>
                </select>
                
                <input type="text" name="professor_name" id="professor_name" placeholder="Instructor Name" required>
                <input type="number" name="credit_hours" id="credit_hours" placeholder="Credit Hours" value="3" required>

                <input type="text" name="grade_value" id="grade_value" placeholder="Grade Output (A, B+, 1.0)" required>
                <select name="remarks" id="remarks" required>
                    <option value="" disabled selected>Select Evaluation</option>
                    <option value="Excellent">Excellent</option>
                    <option value="Passed">Passed</option>
                    <option value="Failed">Failed</option>
                    <option value="Incomplete">Incomplete</option>
                </select>
                
                <div style="grid-column: span 2; display:flex; align-items:center; gap:10px; padding:15px; border:1px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <input type="checkbox" name="is_published" id="is_published" class="cb-sel">
                    <label for="is_published" style="font-weight:500; font-size:0.85rem; cursor:pointer;">Publish Grade to Student Portal</label>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; justify-content:center;">Commit Grade</button>
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
        localStorage.setItem('campus_grade_view', 'grid');
    } else {
        table.style.display = 'block'; grid.style.display = 'none';
        btnTable.classList.add('active-view'); btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_grade_view', 'table');
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
    const cFilter = document.getElementById('filterClass').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchGradeLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rClass = el.getAttribute('data-class');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if (cFilter !== 'All Classes' && !rClass.includes(cFilter)) show = false;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
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
        title.innerHTML = '<i class="fas fa-pen"></i> Edit Grade Record';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('student_id').value = data.student_id;
        document.getElementById('class_code').value = data.class_code;
        document.getElementById('grade_value').value = data.grade_value;
        document.getElementById('remarks').value = data.remarks;
        document.getElementById('term_semester').value = data.term_semester || '1st Semester';
        document.getElementById('professor_name').value = data.professor_name || '';
        document.getElementById('credit_hours').value = data.credit_hours || 3;
        document.getElementById('is_published').checked = data.is_published == 1;
    } else {
        title.innerHTML = '<i class="fas fa-award"></i> Input New Grade';
        document.getElementById('edit_id').value = '';
        document.getElementById('student_id').value = '';
        document.getElementById('class_code').value = '';
        document.getElementById('grade_value').value = '';
        document.getElementById('remarks').value = '';
        document.getElementById('term_semester').value = '1st Semester';
        document.getElementById('professor_name').value = '';
        document.getElementById('credit_hours').value = 3;
        document.getElementById('is_published').checked = false;
    }
    
    modal.style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => { 
    setView(localStorage.getItem('campus_grade_view') || 'table'); 
});
</script>

<?php include 'footer.php'; ?>