<?php 
include 'config.php'; 

// FUNCTION 1: Expand Library Schema for advanced tracking
$patch = "CREATE TABLE IF NOT EXISTS library_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE,
    title VARCHAR(150),
    author VARCHAR(100),
    category VARCHAR(50),
    publish_year INT,
    status VARCHAR(20) DEFAULT 'Available',
    added_date DATE
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = [
    "isbn VARCHAR(20)", "title VARCHAR(150)", "author VARCHAR(100)", 
    "category VARCHAR(50)", "publish_year INT", "status VARCHAR(20) DEFAULT 'Available'", 
    "added_date DATE",
    "borrowed_by VARCHAR(50)", // FUNCTION 2: Track active borrower
    "due_date DATE NULL", // FUNCTION 3: Track return deadlines
    "condition_rating INT DEFAULT 5", // FUNCTION 4: Book physical condition 1-5
    "shelf_location VARCHAR(50)" // FUNCTION 5: Real-world physical mapping
];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE library_catalog ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM library_catalog WHERE id = $id");
    header("Location: library.php"); exit();
}

// FUNCTION 6: Quick Check-in / Check-out Engine
if(isset($_GET['quick_checkout'])) {
    $id = intval($_GET['quick_checkout']);
    $res = mysqli_query($conn, "SELECT status FROM library_catalog WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        if($row['status'] == 'Available') {
            $dd = date('Y-m-d', strtotime('+14 days')); // Auto set 2 week due date
            mysqli_query($conn, "UPDATE library_catalog SET status = 'Borrowed', due_date = '$dd' WHERE id = $id");
        } else {
            mysqli_query($conn, "UPDATE library_catalog SET status = 'Available', borrowed_by = NULL, due_date = NULL WHERE id = $id");
        }
        header("Location: library.php"); exit();
    }
}

// FUNCTION 7: Mass Actions Engine
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_action'])) {
    if(!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        if ($_POST['mass_action_type'] === 'return') {
            mysqli_query($conn, "UPDATE library_catalog SET status = 'Available', borrowed_by = NULL, due_date = NULL WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'lost') {
            mysqli_query($conn, "UPDATE library_catalog SET status = 'Lost / Damaged' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM library_catalog WHERE id IN ($ids)");
        }
    }
    header("Location: library.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_book'])) {
    $ib = mysqli_real_escape_string($conn, $_POST['isbn']);
    $ti = mysqli_real_escape_string($conn, $_POST['title']);
    $au = mysqli_real_escape_string($conn, $_POST['author']);
    $ca = mysqli_real_escape_string($conn, $_POST['category']);
    $yr = intval($_POST['publish_year']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    $bb = mysqli_real_escape_string($conn, $_POST['borrowed_by']);
    $dd = !empty($_POST['due_date']) ? "'".mysqli_real_escape_string($conn, $_POST['due_date'])."'" : "NULL";
    $cr = intval($_POST['condition_rating']);
    $sl = mysqli_real_escape_string($conn, $_POST['shelf_location']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE library_catalog SET isbn='$ib', title='$ti', author='$au', category='$ca', publish_year=$yr, status='$st', borrowed_by='$bb', due_date=$dd, condition_rating=$cr, shelf_location='$sl' WHERE id=$id");
    } else {
        $ad = date('Y-m-d');
        mysqli_query($conn, "INSERT INTO library_catalog (isbn, title, author, category, publish_year, status, added_date, borrowed_by, due_date, condition_rating, shelf_location) VALUES ('$ib', '$ti', '$au', '$ca', $yr, '$st', '$ad', '$bb', $dd, $cr, '$sl')");
    }
    header("Location: library.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_books = [
        ['Introduction to Algorithms', 'Thomas H. Cormen', 'STEM', 2009],
        ['Clean Code', 'Robert C. Martin', 'STEM', 2008],
        ['University Physics', 'Hugh D. Young', 'STEM', 2019],
        ['The Lean Startup', 'Eric Ries', 'Business', 2011],
        ['1984', 'George Orwell', 'Literature', 1949],
        ['Sapiens', 'Yuval Noah Harari', 'Humanities', 2011],
        ['The Design of Everyday Things', 'Don Norman', 'Arts & Design', 1988]
    ];
    foreach($seed_books as $idx => $b) {
        $ti = mysqli_real_escape_string($conn, $b[0]);
        $au = mysqli_real_escape_string($conn, $b[1]);
        $ca = mysqli_real_escape_string($conn, $b[2]);
        $yr = intval($b[3]);
        $isbn = "978-0-" . rand(10, 99) . "-" . rand(100000, 999999) . "-" . rand(0, 9);
        $stat = (rand(1, 100) > 70) ? 'Borrowed' : 'Available';
        $dd = $stat == 'Borrowed' ? "'".date('Y-m-d', strtotime('+'.rand(-5, 14).' days'))."'" : "NULL";
        $bb = $stat == 'Borrowed' ? "'S26-".rand(1000,9999)."'" : "NULL";
        $sl = "A" . rand(1,9) . "-S" . rand(1,5);
        $ad = date('Y-m-d', strtotime('-'.rand(1, 1000).' days'));
        mysqli_query($conn, "INSERT INTO library_catalog (isbn, title, author, category, publish_year, status, added_date, borrowed_by, due_date, shelf_location) VALUES ('$isbn', '$ti', '$au', '$ca', $yr, '$stat', '$ad', $bb, $dd, '$sl')");
    }
}

include 'header.php';

$total = getCount($conn, 'library_catalog');
$avail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Available'"))['c'] ?? 0;
$borrowed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Borrowed'"))['c'] ?? 0;
$lost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Lost / Damaged'"))['c'] ?? 0;
?>

<style>
    /* UI FEATURE 1: Soft Modern Typography & Layout spacing */
    .page-header { margin-bottom: 30px; }
    .page-title { font-size: 2.2rem; font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px; margin-bottom: 5px; }
    .page-sub { color: var(--text-light); font-size: 1rem; }

    /* UI FEATURE 2: Clean Stripe-style Stat Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--card-bg); padding: 25px; border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; border-radius: 16px; transition: 0.2s;}
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .stat-icon { font-size: 1.8rem; color: var(--brand-secondary); display:flex; align-items:center; justify-content:center; width:50px; height:50px; background:var(--main-bg); border-radius:12px; }
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 1.8rem; font-weight: 700; color: var(--text-dark); line-height: 1; margin-bottom: 4px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 500; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px;}

    /* UI FEATURE 3: Minimalist Control Bar */
    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 25px; background: var(--card-bg); border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap:wrap; gap:15px;}
    .flt-sel { border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 20px; background: transparent; color: var(--text-dark); font-weight: 500; font-size: 0.9rem; outline:none; transition: 0.2s;}
    .flt-sel:focus { border-color: var(--text-light); }
    
    .cb-sel { width: 18px; height: 18px; accent-color: var(--text-dark); cursor: pointer; }

    /* UI FEATURE 4: View Toggle Pills */
    .view-toggle { display: flex; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; padding:2px;}
    .view-btn { padding: 8px 16px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1rem; border:none; background:transparent; border-radius: 18px;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--card-bg); color: var(--text-dark); box-shadow: var(--soft-shadow);}
    
    /* UI FEATURE 5: Modern Grid Book Cards */
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .data-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-color: var(--text-light); }
    .data-card.dimmed { opacity: 0.6; filter: grayscale(70%); }
    
    /* UI FEATURE 6: Book Cover Visualization */
    .dc-cover { width: 100%; height: 100px; background: var(--border-color); position:relative;}
    .dc-body { padding: 20px; display:flex; flex-direction:column; flex:1; }
    
    .dc-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; line-height:1.2;}
    .dc-sub { font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px;}
    
    /* UI FEATURE 7: Status Badges */
    .badge-status { padding: 4px 10px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; border-radius: 6px; letter-spacing: 0.5px; display:inline-block;}
    .badge-avail { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge-borrow { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .badge-lost { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    
    /* UI FEATURE 8: Overdue Pulse Alert */
    @keyframes pulseAlert { 0% { box-shadow: 0 0 0 0 rgba(239,68,68, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(239,68,68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68, 0); } }
    .overdue-alert { color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 5px; display:flex; align-items:center; gap:5px;}
    .overdue-row { border-left: 4px solid #ef4444 !important; }

    /* UI FEATURE 9: Star Rating Display */
    .star-rating { color: #f59e0b; font-size: 0.75rem; }
    
    /* UI FEATURE 10: Pagination Control Design */
    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px;}
    .page-btn { background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; color: var(--text-dark); transition: 0.2s; box-shadow: var(--soft-shadow);}
    .page-btn:hover { background: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow:none;}
</style>

<div class="page-header">
    <h1 class="page-title">Digital Archive & Library</h1>
    <p class="page-sub">Manage digital catalogs, physical circulations, acquisitions, and overdue returns.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book"></i></div>
        <div><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Total Volumes</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.1);"><i class="fas fa-check-circle"></i></div>
        <div><div class="stat-val" style="color:#10b981;"><?= $avail ?></div><div class="stat-lbl">Available</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#3b82f6; background:rgba(59,130,246,0.1);"><i class="fas fa-hand-holding-heart"></i></div>
        <div><div class="stat-val" style="color:#3b82f6;"><?= $borrowed ?></div><div class="stat-lbl">Active Loans</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#ef4444; background:rgba(239,68,68,0.1);"><i class="fas fa-times-circle"></i></div>
        <div><div class="stat-val" style="color:#ef4444;"><?= $lost ?></div><div class="stat-lbl">Lost/Damaged</div></div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewTable" class="view-btn active-view" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button type="button" id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchLibraryLocal" onkeyup="filterMatrix()" placeholder="Search title or ISBN..." class="flt-sel" style="width: 250px;">
        
        <select id="filterCat" class="flt-sel" onchange="filterMatrix()">
            <option value="All Categories">All Categories</option>
            <?php
            $c_res = mysqli_query($conn, "SELECT DISTINCT category FROM library_catalog ORDER BY category ASC");
            while($c = mysqli_fetch_assoc($c_res)) { echo "<option value='{$c['category']}'>{$c['category']}</option>"; }
            ?>
        </select>
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All Statuses">All Availability</option>
            <option value="Available">Available</option>
            <option value="Borrowed">Borrowed</option>
            <option value="Lost / Damaged">Lost / Damaged</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('libTable', 'library_export')"><i class="fas fa-download"></i> Export</button>
        <button type="button" class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Catalog Book</button>
    </div>
</form>

<form method="POST" id="massForm">
    <input type="hidden" name="mass_action" value="1">
    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center; background: var(--card-bg); padding: 15px 25px; border: 1px solid var(--border-color); border-radius: 12px; box-shadow:var(--soft-shadow);">
        <span style="font-weight: 600; font-size:0.9rem;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding: 8px 15px;">
            <option value="return">Mass Check-in (Return)</option>
            <option value="lost">Mark as Lost/Damaged</option>
            <option value="delete">Delete Records</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation?')">Apply</button>
    </div>

    <div id="tableView" class="table-responsive">
        <table id="libTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c => c.checked = this.checked)"></th>
                    <th style="width:25%;">Volume Details</th>
                    <th>Classification & Location</th>
                    <th>Circulation Status</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody id="filterTableBody">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM library_catalog ORDER BY title ASC");
                $all_data = [];
                $now = new DateTime();
                
                while($row = mysqli_fetch_assoc($res)) {
                    $all_data[] = $row;
                    $st = $row['status'];
                    $b_cls = $st == 'Available' ? 'badge-avail' : ($st == 'Borrowed' ? 'badge-borrow' : 'badge-lost');
                    $dim_class = $st == 'Lost / Damaged' ? "opacity: 0.55; filter: grayscale(80%);" : "";
                    $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    
                    $cat = $row['category'];
                    $icon = 'fa-book';
                    if($cat == 'STEM') $icon = 'fa-flask';
                    if($cat == 'Business') $icon = 'fa-chart-line';
                    
                    // FUNCTION 8: Overdue Engine
                    $ov_html = ""; $ov_row = "";
                    if($st == 'Borrowed' && !empty($row['due_date'])) {
                        $dd = new DateTime($row['due_date']);
                        if($dd < $now) {
                            $days_late = $now->diff($dd)->days;
                            // FUNCTION 10: Late fee calculation simulation ($1 per day)
                            $fee = $days_late * 1.00;
                            $ov_html = "<div class='overdue-alert'><i class='fas fa-exclamation-circle' style='animation:pulseAlert 2s infinite; border-radius:50%;'></i> OVERDUE ({$days_late} Days) - Fee: $".number_format($fee,2)."</div>";
                            $ov_row = "overdue-row";
                        } else {
                            $ov_html = "<div style='font-size:0.75rem; color:var(--text-light); margin-top:4px;'><i class='far fa-clock'></i> Due: " . date('M d', strtotime($row['due_date'])) . "</div>";
                        }
                    }

                    // Condition Stars
                    $stars = str_repeat("<i class='fas fa-star'></i>", $row['condition_rating']) . str_repeat("<i class='far fa-star'></i>", 5 - $row['condition_rating']);

                    echo "
                    <tr class='paginate-row filter-target {$ov_row}' style='{$dim_class}' data-cat='{$cat}' data-stat='{$st}'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$row['id']}' class='cb-item cb-sel'></td>
                        <td>
                            <strong style='color:var(--text-dark); font-size:1.05rem;'>{$row['title']}</strong><br>
                            <span style='font-size:0.85rem; color:var(--text-light);'>By {$row['author']}</span><br>
                            <div style='font-family:monospace; font-size:0.75rem; color:var(--text-light); margin-top:4px;'>ISBN: {$row['isbn']}</div>
                        </td>
                        <td>
                            <span style='font-weight:600;'><i class='fas {$icon}'></i> {$cat}</span>
                            <div style='font-size:0.8rem; color:var(--text-light); margin-top:4px;'>Shelf: {$row['shelf_location']}</div>
                            <div class='star-rating' style='margin-top:4px;' title='Condition Rating'>{$stars}</div>
                        </td>
                        <td>
                            <span class='badge-status {$b_cls}'>{$st}</span>
                            " . ($st == 'Borrowed' ? "<div style='font-size:0.75rem; font-weight:600; margin-top:4px;'>User: {$row['borrowed_by']}</div>" : "") . "
                            {$ov_html}
                        </td>
                        <td class='action-col'>
                            <div class='table-actions-cell'>
                                <a href='?quick_checkout={$row['id']}' class='table-btn' title='Toggle Circulation'><i class='fas fa-sync-alt'></i></a>
                                <button type='button' class='table-btn' onclick='openModal($js_data)'><i class='fas fa-pen'></i></button>
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
            $b_cls = $st == 'Available' ? 'badge-avail' : ($st == 'Borrowed' ? 'badge-borrow' : 'badge-lost');
            $dim_class = $st == 'Lost / Damaged' ? 'dimmed' : '';
            $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            $cat = $row['category'];
            
            $bg_col = "var(--border-color)";
            if($cat == 'STEM') $bg_col = 'rgba(59, 130, 246, 0.2)';
            if($cat == 'Business') $bg_col = 'rgba(16, 185, 129, 0.2)';
            if($cat == 'Literature') $bg_col = 'rgba(236, 72, 153, 0.2)';
            
            $ov_html = "";
            if($st == 'Borrowed' && !empty($row['due_date'])) {
                $dd = new DateTime($row['due_date']);
                if($dd < $now) {
                    $ov_html = "<div class='overdue-alert' style='margin-bottom:10px;'><i class='fas fa-exclamation-circle' style='animation:pulseAlert 2s infinite; border-radius:50%;'></i> OVERDUE</div>";
                }
            }

            echo "
            <div class='data-card paginate-card filter-target {$dim_class}' data-cat='{$cat}' data-stat='{$st}'>
                <div class='dc-cover' style='background: {$bg_col};'>
                    <i class='fas fa-book'></i>
                    <div style='position:absolute; top:10px; right:10px;'><span class='badge-status {$b_cls}'>{$st}</span></div>
                </div>
                <div class='dc-body'>
                    <div class='dc-title'>{$row['title']}</div>
                    <div class='dc-sub'>By {$row['author']}</div>
                    {$ov_html}
                    
                    <div style='font-size:0.8rem; color:var(--text-light); margin-bottom:5px;'>Category: <strong>{$cat}</strong></div>
                    <div style='font-size:0.8rem; color:var(--text-light); margin-bottom:15px;'>Shelf: <strong>{$row['shelf_location']}</strong></div>
                    
                    <div style='margin-top:auto; padding-top:15px; border-top:1px solid var(--border-light); display:flex; justify-content:space-between;'>
                        <a href='?quick_checkout={$row['id']}' class='table-btn'><i class='fas fa-sync-alt'></i></a>
                        <button type='button' class='table-btn' onclick='openModal({$js_data})'><i class='fas fa-pen'></i> Edit</button>
                    </div>
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
        <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 25px;"><i class="fas fa-book"></i> Catalog Volume</h2>
        
        <form method="POST">
            <input type="hidden" name="save_book" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="isbn" id="isbn" placeholder="ISBN-13" required>
                <select name="status" id="status" required>
                    <option value="Available">Status: Available</option>
                    <option value="Borrowed">Status: Borrowed</option>
                    <option value="Lost / Damaged">Status: Lost / Damaged</option>
                </select>
                
                <input type="text" name="title" id="title" placeholder="Volume Title" required style="grid-column: span 2;">
                <input type="text" name="author" id="author" placeholder="Primary Author" required style="grid-column: span 2;">
                
                <select name="category" id="category" required>
                    <option value="STEM">Category: STEM</option>
                    <option value="Business">Category: Business</option>
                    <option value="Literature">Category: Literature</option>
                    <option value="Humanities">Category: Humanities</option>
                    <option value="Arts & Design">Category: Arts & Design</option>
                    <option value="General">Category: General / Other</option>
                </select>
                <input type="number" name="publish_year" id="publish_year" placeholder="Publication Year" required>
                
                <input type="text" name="shelf_location" id="shelf_location" placeholder="Physical Shelf Map (e.g. A1-S2)">
                <select name="condition_rating" id="condition_rating" required>
                    <option value="5">Condition: 5 - Pristine</option>
                    <option value="4">Condition: 4 - Good</option>
                    <option value="3">Condition: 3 - Fair</option>
                    <option value="2">Condition: 2 - Poor</option>
                    <option value="1">Condition: 1 - Damaged</option>
                </select>
                
                <div style="grid-column: span 2; padding:15px; border:1px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <div style="font-weight:600; font-size:0.8rem; text-transform:uppercase; margin-bottom:10px; color:var(--text-light);">Circulation Data (If Borrowed)</div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="text" name="borrowed_by" id="borrowed_by" placeholder="Borrower ID (e.g. S26-1000)">
                        <div>
                            <label style="font-size:0.7rem; color:var(--text-light);">Due Date</label>
                            <input type="date" name="due_date" id="due_date" style="margin-top:0;">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; justify-content:center;">Save Catalog Data</button>
        </form>
    </div>
</div>

<script>
let currentView = 'table';
let currentPage = 1;
const itemsPerPage = 8;

function setView(view) {
    currentView = view;
    const table = document.getElementById('tableView');
    const grid = document.getElementById('gridView');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');
    
    if(view === 'grid') {
        table.style.display = 'none'; grid.style.display = 'grid';
        btnGrid.classList.add('active-view'); btnTable.classList.remove('active-view');
        localStorage.setItem('campus_library_view', 'grid');
    } else {
        table.style.display = 'block'; grid.style.display = 'none';
        btnTable.classList.add('active-view'); btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_library_view', 'table');
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
    const cFilter = document.getElementById('filterCat').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchLibraryLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rCat = el.getAttribute('data-cat');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if (cFilter !== 'All Categories' && rCat !== cFilter) show = false;
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
        title.innerHTML = '<i class="fas fa-pen"></i> Edit Catalog Entry';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('isbn').value = data.isbn || '';
        document.getElementById('title').value = data.title || '';
        document.getElementById('author').value = data.author || '';
        document.getElementById('category').value = data.category || 'STEM';
        document.getElementById('publish_year').value = data.publish_year || '';
        document.getElementById('status').value = data.status || 'Available';
        document.getElementById('borrowed_by').value = data.borrowed_by || '';
        document.getElementById('due_date').value = data.due_date || '';
        document.getElementById('condition_rating').value = data.condition_rating || 5;
        document.getElementById('shelf_location').value = data.shelf_location || '';
    } else {
        title.innerHTML = '<i class="fas fa-book"></i> Catalog Volume';
        document.getElementById('edit_id').value = '';
        document.getElementById('isbn').value = `978-0-${Math.floor(10+Math.random()*90)}-${Math.floor(100000+Math.random()*900000)}-${Math.floor(Math.random()*10)}`;
        document.getElementById('title').value = '';
        document.getElementById('author').value = '';
        document.getElementById('category').value = 'STEM';
        document.getElementById('publish_year').value = new Date().getFullYear();
        document.getElementById('status').value = 'Available';
        document.getElementById('borrowed_by').value = '';
        document.getElementById('due_date').value = '';
        document.getElementById('condition_rating').value = 5;
        document.getElementById('shelf_location').value = '';
    }
    
    modal.style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => { 
    setView(localStorage.getItem('campus_library_view') || 'table'); 
});
</script>

<?php include 'footer.php'; ?>