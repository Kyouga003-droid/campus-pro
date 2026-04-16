<?php 
include 'config.php'; 

$patch = "CREATE TABLE IF NOT EXISTS billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id VARCHAR(20) UNIQUE,
    student_id VARCHAR(20),
    student_name VARCHAR(100),
    department VARCHAR(50),
    fee_type VARCHAR(100),
    amount DECIMAL(10,2),
    discount DECIMAL(10,2) DEFAULT 0,
    net_amount DECIMAL(10,2),
    amount_paid DECIMAL(10,2) DEFAULT 0,
    due_date DATE,
    status VARCHAR(20) DEFAULT 'Unpaid',
    remarks VARCHAR(150)
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = ["invoice_id VARCHAR(20)", "student_id VARCHAR(20)", "student_name VARCHAR(100)", "department VARCHAR(50)", "fee_type VARCHAR(100)", "amount DECIMAL(10,2)", "discount DECIMAL(10,2) DEFAULT 0", "net_amount DECIMAL(10,2)", "amount_paid DECIMAL(10,2) DEFAULT 0", "due_date DATE", "status VARCHAR(20) DEFAULT 'Unpaid'", "remarks VARCHAR(150)"];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE billing ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM billing WHERE id = $id");
    header("Location: billing.php"); exit();
}

if(isset($_GET['toggle_pay'])) {
    $id = intval($_GET['toggle_pay']);
    $res = mysqli_query($conn, "SELECT status, net_amount FROM billing WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $cur = $row['status'];
        $net = floatval($row['net_amount']);
        if($cur == 'Unpaid') { $nxt = 'Partial'; $paid = $net / 2; }
        elseif($cur == 'Partial') { $nxt = 'Paid'; $paid = $net; }
        else { $nxt = 'Unpaid'; $paid = 0; }
        
        mysqli_query($conn, "UPDATE billing SET status = '$nxt', amount_paid = $paid WHERE id = $id");
        header("Location: billing.php"); exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_invoice'])) {
    $inv = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $snm = mysqli_real_escape_string($conn, $_POST['student_name']);
    $dpt = mysqli_real_escape_string($conn, $_POST['department']);
    $ftp = mysqli_real_escape_string($conn, $_POST['fee_type']);
    $amt = floatval($_POST['amount']);
    $dsc = floatval($_POST['discount']);
    $net = $amt - $dsc;
    $due = mysqli_real_escape_string($conn, $_POST['due_date']);
    $sts = mysqli_real_escape_string($conn, $_POST['status']);
    $rmk = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    $paid = 0;
    if($sts == 'Paid') $paid = $net;
    elseif($sts == 'Partial') $paid = $net / 2;

    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE billing SET invoice_id='$inv', student_id='$sid', student_name='$snm', department='$dpt', fee_type='$ftp', amount=$amt, discount=$dsc, net_amount=$net, amount_paid=$paid, due_date='$due', status='$sts', remarks='$rmk' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO billing (invoice_id, student_id, student_name, department, fee_type, amount, discount, net_amount, amount_paid, due_date, status, remarks) VALUES ('$inv', '$sid', '$snm', '$dpt', '$ftp', $amt, $dsc, $net, $paid, '$due', '$sts', '$rmk')");
    }
    header("Location: billing.php"); exit();
}

$st_check = @mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if($st_check && mysqli_num_rows($st_check) > 0) {
    $st_res = mysqli_query($conn, "SELECT student_id, first_name, last_name, department FROM students WHERE status='Enrolled'");
    if($st_res && mysqli_num_rows($st_res) > 0) {
        while($s = mysqli_fetch_assoc($st_res)) {
            $sid = $s['student_id'];
            $b_check = mysqli_query($conn, "SELECT id FROM billing WHERE student_id='$sid' AND fee_type='Tuition Assessment'");
            
            if(mysqli_num_rows($b_check) == 0) {
                $base = 25000;
                $dept = $s['department'];
                
                if(strpos($dept, 'Computer') !== false) $base += 8500; 
                elseif(strpos($dept, 'Engineering') !== false) $base += 12000; 
                elseif(strpos($dept, 'Business') !== false) $base += 3000;
                elseif(strpos($dept, 'Arts') !== false) $base += 1500;
                else $base += 2000;

                $rand = rand(1, 100);
                $dsc = 0; $rmk = '';
                if($rand <= 5) { $dsc = $base; $rmk = "100% Full Scholar"; } 
                elseif($rand <= 15) { $dsc = $base * 0.5; $rmk = "50% Academic Grant"; } 
                elseif($rand <= 30) { $dsc = $base * 0.2; $rmk = "20% Athletics/Arts"; } 
                
                $net = $base - $dsc;
                $inv = "INV-" . date('y') . "-" . rand(10000, 99999);
                $sname = mysqli_real_escape_string($conn, $s['first_name'] . ' ' . $s['last_name']);
                $due = date('Y-m-d', strtotime('+30 days'));
                
                $stat = $net == 0 ? 'Paid' : 'Unpaid';
                $paid = $net == 0 ? $base : 0; 
                
                mysqli_query($conn, "INSERT INTO billing (invoice_id, student_id, student_name, department, fee_type, amount, discount, net_amount, amount_paid, due_date, status, remarks) VALUES ('$inv', '$sid', '$sname', '$dept', 'Tuition Assessment', $base, $dsc, $net, $paid, '$due', '$stat', '$rmk')");
            }
        }
    }
}

$check_b = mysqli_query($conn, "SELECT COUNT(*) as c FROM billing");
if(mysqli_fetch_assoc($check_b)['c'] == 0) {
    for($i=1; $i<=10; $i++) {
        $inv = "INV-" . date('y') . "-" . rand(10000, 99999);
        $sid = "2026-" . str_pad($i, 4, "0", STR_PAD_LEFT);
        mysqli_query($conn, "INSERT INTO billing (invoice_id, student_id, student_name, department, fee_type, amount, net_amount, due_date, status) VALUES ('$inv', '$sid', 'Fallback Student $i', 'General', 'Miscellaneous Fee', 1500, 1500, CURDATE(), 'Unpaid')");
    }
}

include 'header.php';

$fin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(net_amount) as t_net, SUM(amount_paid) as t_paid FROM billing"));
$receivables = $fin['t_net'] ?: 0;
$collected = $fin['t_paid'] ?: 0;
$outstanding = $receivables - $collected;

$col_rate = $receivables > 0 ? ($collected / $receivables) * 100 : 0;
$col_color = $col_rate > 75 ? '#10b981' : ($col_rate > 40 ? '#f59e0b' : '#ef4444');
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 30px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 16px; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: var(--brand-secondary); opacity: 0.9; padding: 15px; background: var(--main-bg); border-radius: 12px; border: 1px solid var(--border-color);}
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 25px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap: wrap; gap:15px;}
    
    .status-paid { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-partial { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .status-unpaid { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    
    .inv-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .inv-box { color: var(--brand-primary); }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #10b981;">
    <h1 style="color: #10b981; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Financial Ledger</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Automated tuition tracking, bursar operations, and scholarship allocations.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-file-invoice-dollar stat-icon"></i>
        <div>
            <div class="stat-val">₱<?= number_format($receivables, 2) ?></div>
            <div class="stat-lbl">Total Expected Revenue</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-usd stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;">₱<?= number_format($collected, 2) ?></div>
            <div class="stat-lbl">Successfully Collected</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-exclamation-triangle stat-icon" style="color:#ef4444;"></i>
        <div>
            <div class="stat-val" style="color:#ef4444;">₱<?= number_format($outstanding, 2) ?></div>
            <div class="stat-lbl">Outstanding Deficit</div>
        </div>
    </div>
</div>

<div class="card" style="padding: 25px; margin-bottom: 30px; display:flex; align-items:center; gap:20px;">
    <div style="font-weight:900; color:var(--text-dark); font-size:1.1rem; white-space:nowrap;"><i class="fas fa-chart-line" style="color:var(--brand-secondary); margin-right:8px;"></i> Fiscal Collection Rate:</div>
    <div style="flex-grow:1; height:14px; background:var(--bg-grid); border: 2px solid var(--border-color); border-radius:10px; overflow:hidden;">
        <div style="height:100%; width:<?= $col_rate ?>%; background:<?= $col_color ?>; transition: 1.5s cubic-bezier(0.16, 1, 0.3, 1);"></div>
    </div>
    <div style="font-weight:900; font-family:var(--heading-font); font-size:1.6rem; color:<?= $col_color ?>;"><?= number_format($col_rate, 1) ?>%</div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        
        <input type="text" id="searchBillingLocal" onkeyup="filterBilling()" placeholder="&#xf002; Search Scholar Name or ID..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">

        <select id="filterStatus" onchange="filterBilling()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Account Statuses</option>
            <option value="Paid">Fully Paid</option>
            <option value="Partial">Partially Paid</option>
            <option value="Unpaid">Unpaid / Overdue</option>
        </select>
        <select id="filterDept" onchange="filterBilling()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Departments">All Departments</option>
            <?php
            $d_res = mysqli_query($conn, "SELECT DISTINCT department FROM billing ORDER BY department ASC");
            while($d = mysqli_fetch_assoc($d_res)) { echo "<option value='{$d['department']}'>{$d['department']}</option>"; }
            ?>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Ledger to Accounting...')"><i class="fas fa-file-csv"></i> Export Data</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#10b981; border-color:#10b981; color:#fff;" onclick="openBillingModal()"><i class="fas fa-plus"></i> Manual Invoice</button>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:20%;">Invoice Details</th>
                <th>Scholar / Department</th>
                <th style="width:25%;">Financial Breakdown</th>
                <th>Status & Due</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="billingTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM billing ORDER BY status DESC, due_date ASC");
            while($row = mysqli_fetch_assoc($res)) {
                
                $st = $row['status'];
                if($st == 'Paid') $st_class = 'status-paid';
                elseif($st == 'Partial') $st_class = 'status-partial';
                else $st_class = 'status-unpaid';
                
                $row_style = $st == 'Paid' ? "opacity: 0.6; filter: grayscale(30%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $ft = $row['fee_type'];
                $icon = 'fa-file-invoice';
                if(strpos($ft, 'Tuition') !== false) $icon = 'fa-graduation-cap';
                if(strpos($ft, 'Library') !== false) $icon = 'fa-book';
                if(strpos($ft, 'Lab') !== false) $icon = 'fa-flask';

                $due_time = strtotime($row['due_date']);
                $today_time = strtotime(date('Y-m-d'));
                $f_due = date('M d, Y', $due_time);
                $due_ui = ($st != 'Paid' && $due_time < $today_time) ? "<div style='color:var(--brand-crimson); font-weight:900; margin-top:6px; font-size:0.85rem;'><i class='fas fa-exclamation-circle'></i> OVERDUE: {$f_due}</div>" : "<div style='color:var(--text-light); font-weight:800; margin-top:6px; font-size:0.8rem;'><i class='far fa-calendar-alt'></i> Due: {$f_due}</div>";

                $tag_html = "";
                if(!empty($row['remarks']) && floatval($row['discount']) > 0) {
                    $tag_html = "<div style='display:inline-block; font-size:0.65rem; font-weight:900; background:rgba(217, 79, 0, 0.1); color:var(--brand-accent); border:1px solid var(--brand-accent); padding:3px 8px; border-radius:4px; margin-top:6px; text-transform:uppercase;'>{$row['remarks']}</div>";
                }

                echo "
                <tr style='$row_style' data-stat='{$st}' data-dept='{$row['department']}'>
                    <td>
                        <div class='inv-box'>{$row['invoice_id']}</div>
                        <div style='font-size:0.85rem; color:var(--text-light); margin-top:8px; font-weight:800; text-transform:uppercase;'><i class='fas {$icon}' style='margin-right:6px; color:var(--brand-secondary);'></i> {$ft}</div>
                    </td>
                    <td>
                        <strong style='color:var(--text-dark); font-size:1.15rem; font-family:var(--heading-font);'>{$row['student_name']}</strong>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:800; margin-top:6px;'>ID: {$row['student_id']}</div>
                        <div style='font-size:0.8rem; color:var(--brand-primary); font-weight:700; margin-top:4px; text-transform:uppercase;'>{$row['department']}</div>
                    </td>
                    <td>
                        <div style='display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-light); font-weight:700; margin-bottom:4px;'><span>Gross:</span> <span>₱" . number_format($row['amount'], 2) . "</span></div>
                        " . ($row['discount'] > 0 ? "<div style='display:flex; justify-content:space-between; font-size:0.85rem; color:var(--brand-accent); font-weight:800; margin-bottom:4px;'><span>Discount:</span> <span>- ₱" . number_format($row['discount'], 2) . "</span></div>" : "") . "
                        <div style='display:flex; justify-content:space-between; font-size:1.1rem; color:var(--text-dark); font-weight:900; margin-bottom:6px; border-top:1px solid var(--border-light); padding-top:4px;'><span>Net:</span> <span>₱" . number_format($row['net_amount'], 2) . "</span></div>
                        <div style='display:flex; justify-content:space-between; font-size:0.9rem; color:#10b981; font-weight:800;'><span>Paid:</span> <span>₱" . number_format($row['amount_paid'], 2) . "</span></div>
                        {$tag_html}
                    </td>
                    <td>
                        <span class='status-pill {$st_class}'>{$st}</span>
                        {$due_ui}
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openBillingModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
                            <a href='?toggle_pay={$row['id']}' class='table-btn' style='border-color:#10b981; color:#10b981;' onclick='systemToast(\"Updating Payment Ledger...\")'><i class='fas fa-cash-register'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Voiding Invoice...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='5' style='text-align:center; padding:50px; font-weight:800; opacity:0.5;'><i class='fas fa-file-invoice' style='font-size:3rem; margin-bottom:15px; color:var(--brand-secondary);'></i><br>No financial records found.</td></tr>";
            ?>
        </tbody>
    </table>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-file-invoice-dollar" style="color:#10b981;"></i> Invoice Details</h2>
        <form method="POST">
            <input type="hidden" name="save_invoice" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="invoice_id" id="invoice_id" placeholder="Invoice ID (e.g. INV-2026-101)" required>
                <input type="text" name="fee_type" id="fee_type" placeholder="Fee Type (e.g. Lab Fee, Library Fine)" required>
                
                <input type="text" name="student_id" id="student_id" placeholder="Student ID" required>
                <input type="text" name="student_name" id="student_name" placeholder="Student Name" required>
                
                <select name="department" id="department" style="grid-column: span 2;" required>
                    <option value="Administration">Administration</option>
                    <option value="Computer Studies">Computer Studies</option>
                    <option value="Business">Business</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Arts & Sciences">Arts & Sciences</option>
                    <option value="General">General / All</option>
                </select>

                <input type="number" step="0.01" name="amount" id="amount" placeholder="Gross Amount (₱)" required>
                <input type="number" step="0.01" name="discount" id="discount" placeholder="Discount Amount (₱) - Optional" value="0">
                
                <input type="date" name="due_date" id="due_date" required>
                <select name="status" id="status" required>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Partial">Partially Paid</option>
                    <option value="Paid">Fully Paid</option>
                </select>

                <textarea name="remarks" id="remarks" placeholder="Tags / Internal Notes (Optional)" style="grid-column: span 2; height:80px; resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#10b981; border-color:#10b981; color:#fff;"><i class="fas fa-save"></i> Save Financial Record</button>
        </form>
    </div>
</div>

<script>
function filterBilling() {
    const sFilter = document.getElementById('filterStatus').value;
    const dFilter = document.getElementById('filterDept').value;
    const searchQ = document.getElementById('searchBillingLocal').value.toLowerCase();
    const rows = document.querySelectorAll('#billingTableBody tr');
    
    rows.forEach(row => {
        const rStat = row.getAttribute('data-stat');
        const rDept = row.getAttribute('data-dept');
        // Evaluate the text contents of the first and second cells (Invoice ID + Student Name/ID)
        const rText = row.cells[0].innerText.toLowerCase() + " " + row.cells[1].innerText.toLowerCase();
        
        let show = true;
        
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (dFilter !== 'All Departments' && rDept !== dFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) {
            row.removeAttribute('data-hide-local');
            row.style.display = '';
        } else {
            row.setAttribute('data-hide-local', 'true');
            row.style.display = 'none';
        }
    });

    if(typeof globalTableSearch === 'function') globalTableSearch();
}

function openBillingModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#10b981;"></i> Edit Ledger';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('invoice_id').value = data.invoice_id || '';
        document.getElementById('fee_type').value = data.fee_type || '';
        document.getElementById('student_id').value = data.student_id || '';
        document.getElementById('student_name').value = data.student_name || '';
        document.getElementById('department').value = data.department || 'General';
        document.getElementById('amount').value = data.amount || '';
        document.getElementById('discount').value = data.discount || '0';
        document.getElementById('due_date').value = data.due_date || '';
        document.getElementById('status').value = data.status || 'Unpaid';
        document.getElementById('remarks').value = data.remarks || '';
    } else {
        title.innerHTML = '<i class="fas fa-file-invoice-dollar" style="color:#10b981;"></i> Manual Invoice';
        document.getElementById('edit_id').value = '';
        const yr = new Date().getFullYear().toString().substr(-2);
        const rand = Math.floor(10000 + Math.random() * 90000);
        document.getElementById('invoice_id').value = `INV-${yr}-${rand}`;
        document.getElementById('fee_type').value = 'Miscellaneous Fee';
        document.getElementById('student_id').value = '';
        document.getElementById('student_name').value = '';
        document.getElementById('department').value = 'General';
        document.getElementById('amount').value = '';
        document.getElementById('discount').value = '0';
        
        const d = new Date(); d.setDate(d.getDate() + 30);
        document.getElementById('due_date').value = d.toISOString().split('T')[0];
        
        document.getElementById('status').value = 'Unpaid';
        document.getElementById('remarks').value = '';
    }
    
    modal.style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>