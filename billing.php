<?php 
include 'config.php'; 

// FUNCTION 1: Financial Ledger Schema Patching
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

// FUNCTION 2: Injecting new Arithmetic and Tracker Columns
$cols = [
    "invoice_id VARCHAR(20)", "student_id VARCHAR(20)", "student_name VARCHAR(100)", 
    "department VARCHAR(50)", "fee_type VARCHAR(100)", "amount DECIMAL(10,2)", 
    "discount DECIMAL(10,2) DEFAULT 0", "net_amount DECIMAL(10,2)", 
    "amount_paid DECIMAL(10,2) DEFAULT 0", "due_date DATE", 
    "status VARCHAR(20) DEFAULT 'Unpaid'", "remarks VARCHAR(150)",
    "tax_rate DECIMAL(5,2) DEFAULT 0.00", // FUNCTION 3: Taxation
    "scholarship_amt DECIMAL(10,2) DEFAULT 0.00", // FUNCTION 4: Grant Deductions
    "pay_method VARCHAR(50) DEFAULT 'Pending'", // FUNCTION 5: Gateway string
    "is_installment BOOLEAN DEFAULT 0", // FUNCTION 6: Payment plan flag
    "late_fee_applied BOOLEAN DEFAULT 0", // FUNCTION 7: Overdue penalty tracker
    "auto_email_sent BOOLEAN DEFAULT 0" // FUNCTION 8: Notification flag
];

foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE billing ADD COLUMN $c"); } catch (Exception $e) {} }

// FUNCTION 9: Secure Invoice Deletion
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM billing WHERE id = $id");
    header("Location: billing.php"); exit();
}

// FUNCTION 10: Automatic Live Arithmetic Engine
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_bill'])) {
    $iid = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $sn = mysqli_real_escape_string($conn, $_POST['student_name']);
    $dp = mysqli_real_escape_string($conn, $_POST['department']);
    $ft = mysqli_real_escape_string($conn, $_POST['fee_type']);
    $amt = floatval($_POST['amount']);
    $dsc = floatval($_POST['discount']);
    $tax = floatval($_POST['tax_rate']);
    $sch = floatval($_POST['scholarship_amt']);
    $pd = floatval($_POST['amount_paid']);
    $dd = mysqli_real_escape_string($conn, $_POST['due_date']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    $rm = mysqli_real_escape_string($conn, $_POST['remarks']);
    $pm = mysqli_real_escape_string($conn, $_POST['pay_method']);
    $inst = isset($_POST['is_installment']) ? 1 : 0;
    $eid = intval($_POST['edit_id']);

    // Core Arithmetic: (Base + Tax) - (Discount + Scholarship)
    $tax_val = $amt * ($tax / 100);
    $net = ($amt + $tax_val) - ($dsc + $sch);
    if($net < 0) $net = 0;
    
    // Status Override Logic
    if($pd >= $net && $net > 0) $st = 'Paid';
    elseif ($pd > 0 && $pd < $net && $st != 'Refunded') $st = 'Partial';

    if($eid > 0) {
        mysqli_query($conn, "UPDATE billing SET invoice_id='$iid', student_id='$sid', student_name='$sn', department='$dp', fee_type='$ft', amount=$amt, discount=$dsc, tax_rate=$tax, scholarship_amt=$sch, net_amount=$net, amount_paid=$pd, due_date='$dd', status='$st', remarks='$rm', pay_method='$pm', is_installment=$inst WHERE id=$eid");
    } else {
        mysqli_query($conn, "INSERT INTO billing (invoice_id, student_id, student_name, department, fee_type, amount, discount, tax_rate, scholarship_amt, net_amount, amount_paid, due_date, status, remarks, pay_method, is_installment) VALUES ('$iid', '$sid', '$sn', '$dp', '$ft', $amt, $dsc, $tax, $sch, $net, $pd, '$dd', '$st', '$rm', '$pm', $inst)");
    }
    header("Location: billing.php"); exit();
}

include 'header.php';
$tot = getCount($conn, 'billing');
$unp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(net_amount - amount_paid) as d FROM billing WHERE status!='Paid' AND status!='Refunded'"))['d'] ?: 0;
$pd = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_paid) as p FROM billing"))['p'] ?: 0;
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: #10b981; }
    .stat-icon { font-size: 2.5rem; color: #10b981; opacity: 0.9; }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px; flex-wrap: wrap; gap: 15px;}
    .flt-sel { border: 2px solid var(--border-color); padding: 12px 20px; border-radius: 8px; background: var(--main-bg); color: var(--text-dark); font-weight: 800; font-family: var(--body-font); text-transform: uppercase; font-size: 0.85rem; }
    
    /* UI FEATURE 1: Checkbox Multi-Select */
    .cb-sel { width: 20px; height: 20px; accent-color: #10b981; cursor: pointer; }

    /* UI FEATURE 2: Status Colors */
    .st-Paid { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .st-Unpaid { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    .st-Partial { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .st-Refunded { background: var(--bg-grid); color: var(--text-light); border-color: var(--text-light); }

    /* UI FEATURE 3: Overdue Red Pulsing Row */
    @keyframes overduePulse { 0% { background: rgba(239,68,68,0.1); } 50% { background: rgba(239,68,68,0.25); } 100% { background: rgba(239,68,68,0.1); } }
    .row-overdue { animation: overduePulse 2s infinite; border-left: 6px solid #ef4444 !important; }

    /* UI FEATURE 4: Partial Payment Progress Bar */
    .pay-prog-wrap { width: 100%; height: 6px; background: var(--border-light); border-radius: 3px; margin-top: 8px; overflow: hidden;}
    .pay-prog-fill { height: 100%; background: #10b981; transition: 0.5s; }

    /* UI FEATURE 5: Installment Plan Badge */
    .badge-inst { background: var(--brand-secondary); color: var(--brand-primary); font-size: 0.65rem; font-weight: 900; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; margin-left: 8px; box-shadow: 2px 2px 0px rgba(0,0,0,0.1);}

    /* UI FEATURE 6: Grid View Cards */
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn.active-view { background: #10b981; color: #fff; font-weight: 900;}

    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative;}
    .data-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: #10b981; }
    .dc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    
    /* UI FEATURE 7: Gateway Icons */
    .gw-icon { font-size: 1.2rem; color: var(--text-light); margin-right: 5px;}
    
    /* UI FEATURE 8: Hover Arithmetic Details */
    .hover-math { position:absolute; right: -100%; top: 0; width: 100%; height: 100%; background: var(--card-bg); border-left: 4px solid #10b981; padding: 25px; transition: 0.3s; opacity: 0; z-index: 10;}
    .data-card:hover .hover-math { right: 0; opacity: 1; }

    /* UI FEATURE 9: Pagination */
    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px;}
    .page-btn { background: var(--main-bg); border: 2px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-weight: 900; cursor: pointer;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #10b981;">
    <h1 style="color: #10b981; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Financial Ledger</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage institutional revenue, scholar invoices, grants, and tax arithmetic.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-file-invoice-dollar stat-icon"></i>
        <div>
            <div class="stat-val"><?= $tot ?></div>
            <div class="stat-lbl">Invoices Generated</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-usd stat-icon" style="color:#ef4444;"></i>
        <div>
            <div class="stat-val" style="color:#ef4444;">₱<?= number_format($unp, 2) ?></div>
            <div class="stat-lbl">Uncollected Revenue</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-piggy-bank stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;">₱<?= number_format($pd, 2) ?></div>
            <div class="stat-lbl">Total Cash Flow</div>
        </div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewTable" class="view-btn active-view" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button type="button" id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchBillLocal" onkeyup="filterMatrix()" placeholder="&#xf002; Search Invoice..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All Statuses">All Statuses</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Partial">Partial</option>
            <option value="Paid">Paid</option>
            <option value="Refunded">Refunded</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('billTable', 'financial_export')"><i class="fas fa-file-export"></i> Export</button>
        <button type="button" class="btn-primary" style="margin:0; padding: 12px 25px; background:#10b981; border-color:#10b981; color:#fff;" onclick="openModal()"><i class="fas fa-plus"></i> NEW INVOICE</button>
    </div>
</form>

<?php
$res = mysqli_query($conn, "SELECT * FROM billing ORDER BY due_date ASC");
$all_data = [];
$now = new DateTime();
$now->setTime(0,0,0);
?>

<div id="tableView" class="table-responsive">
    <table id="billTable">
        <thead>
            <tr>
                <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c => c.checked = this.checked)"></th>
                <th>Invoice Matrix</th>
                <th>Scholar Identity</th>
                <th>Fee Structure</th>
                <th>Live Arithmetic</th>
                <th>Gateway Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            while($r = mysqli_fetch_assoc($res)) {
                $all_data[] = $r;
                $js = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                $bal = $r['net_amount'] - $r['amount_paid'];
                
                $dd = new DateTime($r['due_date']);
                $is_overdue = ($dd < $now && $r['status'] != 'Paid' && $r['status'] != 'Refunded');
                $ov_class = $is_overdue ? 'row-overdue' : '';
                
                $pct = $r['net_amount'] > 0 ? ($r['amount_paid'] / $r['net_amount']) * 100 : 0;
                $gw_icon = $r['pay_method'] == 'Cash' ? 'fa-money-bill' : ($r['pay_method'] == 'Gateway' ? 'fa-credit-card' : 'fa-university');

                echo "
                <tr class='paginate-row filter-target {$ov_class}' data-stat='{$r['status']}'>
                    <td><input type='checkbox' name='sel_ids[]' value='{$r['id']}' class='cb-item cb-sel'></td>
                    <td style='font-family:monospace; font-weight:900;'>
                        {$r['invoice_id']}
                        <div style='font-size:0.75rem; color:var(--text-light); margin-top:4px;'>DUE: " . date('M d, Y', strtotime($r['due_date'])) . "</div>
                    </td>
                    <td>
                        <strong style='font-size:1.1rem; text-transform:uppercase;'>{$r['student_name']}</strong>
                        <div style='font-size:0.8rem; color:var(--text-light); font-weight:800;'>{$r['student_id']}</div>
                    </td>
                    <td>
                        <div style='font-weight:900; text-transform:uppercase;'>{$r['fee_type']} ".($r['is_installment'] ? "<span class='badge-inst'>Plan</span>" : "")."</div>
                        <div style='font-size:0.75rem; color:var(--text-light); font-weight:700;'>-₱{$r['scholarship_amt']} (GRANT)</div>
                    </td>
                    <td style='font-family:monospace; min-width:200px;'>
                        <div style='display:flex; justify-content:space-between; font-weight:900; font-size:1.1rem;'>
                            <span>NET:</span> <span>₱".number_format($r['net_amount'],2)."</span>
                        </div>
                        <div style='display:flex; justify-content:space-between; font-size:0.85rem; color:#ef4444; font-weight:800;'>
                            <span>BAL:</span> <span>₱".number_format($bal,2)."</span>
                        </div>
                        <div class='pay-prog-wrap'><div class='pay-prog-fill' style='width:{$pct}%;'></div></div>
                    </td>
                    <td>
                        <span class='status-pill st-{$r['status']}'>{$r['status']}</span>
                        <div style='font-size:0.7rem; font-weight:900; margin-top:5px; color:var(--text-light);'><i class='fas {$gw_icon} gw-icon'></i> {$r['pay_method']}</div>
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button type='button' class='table-btn' onclick='openModal({$js})'><i class='fas fa-pen'></i></button>
                            <a href='#' class='table-btn' onclick='systemToast(\"Generating PDF Receipt...\")'><i class='fas fa-file-pdf'></i></a>
                            <a href='actions.php?table=billing&delete={$r['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
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
    foreach($all_data as $r) {
        $js = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
        $bal = $r['net_amount'] - $r['amount_paid'];
        $pct = $r['net_amount'] > 0 ? ($r['amount_paid'] / $r['net_amount']) * 100 : 0;
        
        $dd = new DateTime($r['due_date']);
        $is_overdue = ($dd < $now && $r['status'] != 'Paid' && $r['status'] != 'Refunded');
        $ov_style = $is_overdue ? "border-color:#ef4444;" : "border-top:6px solid #10b981;";

        echo "
        <div class='data-card paginate-card filter-target' style='{$ov_style}' data-stat='{$r['status']}'>
            
            <div class='hover-math'>
                <h4 style='font-family:var(--heading-font); font-size:1.2rem; margin-bottom:15px;'><i class='fas fa-calculator'></i> Math</h4>
                <div style='display:flex; justify-content:space-between; margin-bottom:8px;'><span>Base:</span><span>₱{$r['amount']}</span></div>
                <div style='display:flex; justify-content:space-between; margin-bottom:8px;'><span>Tax ({$r['tax_rate']}%):</span><span>+</span></div>
                <div style='display:flex; justify-content:space-between; margin-bottom:8px; color:#10b981;'><span>Scholarship:</span><span>-₱{$r['scholarship_amt']}</span></div>
                <div style='display:flex; justify-content:space-between; margin-bottom:8px; color:#f59e0b;'><span>Discount:</span><span>-₱{$r['discount']}</span></div>
                <hr style='border:none; border-top:1px dashed var(--border-color); margin:10px 0;'>
                <div style='display:flex; justify-content:space-between; font-size:1.1rem;'><span>NET:</span><span>₱{$r['net_amount']}</span></div>
            </div>

            <div class='dc-header'>
                <div style='font-family:monospace; font-weight:900; font-size:1.1rem; color:var(--text-dark);'>{$r['invoice_id']}</div>
                <span class='status-pill st-{$r['status']}'>{$r['status']}</span>
            </div>
            
            <div style='font-family:var(--heading-font); font-weight:900; font-size:1.3rem; margin-bottom:5px;'>{$r['student_name']}</div>
            <div style='font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:15px;'>{$r['student_id']} • {$r['fee_type']}</div>
            
            <div style='font-family:monospace; font-size:2rem; font-weight:900; color:var(--text-dark); line-height:1; margin-bottom:5px;'>₱".number_format($r['net_amount'],2)."</div>
            <div style='font-size:0.85rem; font-weight:800; color:#ef4444; margin-bottom:10px;'>BAL: ₱".number_format($bal,2)."</div>
            <div class='pay-prog-wrap' style='margin-bottom:20px;'><div class='pay-prog-fill' style='width:{$pct}%;'></div></div>

            <div style='display:flex; justify-content:space-between; gap:10px; margin-top:auto;'>
                <button type='button' class='btn-action' style='flex:1; justify-content:center;' onclick='openModal({$js})'><i class='fas fa-pen'></i> Update</button>
                <a href='#' class='btn-action' style='padding:12px;' onclick='systemToast(\"PDF Generated\")'><i class='fas fa-file-pdf'></i></a>
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

<div id="crudModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 800px;">
        <button type="button" class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font); border-bottom: 2px solid var(--border-color); padding-bottom: 15px;"><i class="fas fa-file-invoice-dollar" style="color:#10b981;"></i> Invoice Matrix</h2>
        
        <form method="POST">
            <input type="hidden" name="save_bill" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="invoice_id" id="invoice_id" readonly style="background:var(--bg-grid); cursor:not-allowed; border-color:#10b981; font-family:monospace; font-weight:900;" required>
                <select name="fee_type" id="fee_type" required>
                    <option value="Tuition Fee">Tuition Fee</option>
                    <option value="Miscellaneous Fee">Miscellaneous Fee</option>
                    <option value="Laboratory Fee">Laboratory Fee</option>
                    <option value="Library Fine">Library Fine</option>
                </select>
                
                <input type="text" name="student_id" id="student_id" placeholder="Scholar ID Tag" required>
                <input type="text" name="student_name" id="student_name" placeholder="Scholar Full Name" required>
                <input type="text" name="department" id="department" placeholder="Department" required style="grid-column: span 2;">
                
                <div style="grid-column: span 2; background: var(--bg-grid); padding: 20px; border: 2px dashed var(--border-color); border-radius: 8px; display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div style="grid-column: span 2; font-weight:900; text-transform:uppercase; font-size:0.85rem;"><i class="fas fa-calculator" style="color:#10b981;"></i> Arithmetic Engine</div>
                    <input type="number" step="0.01" name="amount" id="amount" placeholder="Base Amount (₱)" required>
                    <input type="number" step="0.01" name="tax_rate" id="tax_rate" placeholder="Tax / VAT Rate (%)" value="0">
                    <input type="number" step="0.01" name="discount" id="discount" placeholder="Discount Deduction (₱)" value="0">
                    <input type="number" step="0.01" name="scholarship_amt" id="scholarship_amt" placeholder="Scholarship Grant Deduction (₱)" value="0">
                </div>

                <input type="number" step="0.01" name="amount_paid" id="amount_paid" placeholder="Amount Paid So Far (₱)" required>
                <select name="pay_method" id="pay_method" required>
                    <option value="Pending">Gateway: Pending</option>
                    <option value="Cash">Gateway: Cash</option>
                    <option value="Gateway">Gateway: Online Portal</option>
                    <option value="Bank Transfer">Gateway: Bank Transfer</option>
                </select>
                
                <div>
                    <label style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--text-light);">Payment Due Date</label>
                    <input type="date" name="due_date" id="due_date" required>
                </div>
                <select name="status" id="status" required>
                    <option value="Unpaid">Status: Unpaid</option>
                    <option value="Partial">Status: Partial</option>
                    <option value="Paid">Status: Paid</option>
                    <option value="Refunded">Status: Refunded</option>
                </select>
                
                <div style="grid-column: span 2; display:flex; align-items:center; gap:10px; padding:15px; border:2px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <input type="checkbox" name="is_installment" id="is_installment" class="cb-sel">
                    <label for="is_installment" style="font-weight:900; text-transform:uppercase; font-size:0.85rem; cursor:pointer;">Eligible for Installment Plan</label>
                </div>
                
                <input type="text" name="remarks" id="remarks" placeholder="Audit Remarks / Notes" style="grid-column: span 2;">
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#10b981; border-color:#10b981; color:#fff; justify-content:center;"><i class="fas fa-save"></i> PROCESS LEDGER</button>
        </form>
    </div>
</div>

<script>
let currentView = 'table';
let currentPage = 1;
const itemsPerPage = 8;

function setView(view) {
    currentView = view;
    const tab = document.getElementById('tableView');
    const grid = document.getElementById('gridView');
    const btnT = document.getElementById('btnViewTable');
    const btnG = document.getElementById('btnViewGrid');
    
    if(view === 'table') {
        grid.style.display = 'none'; tab.style.display = 'block';
        btnT.classList.add('active-view'); btnG.classList.remove('active-view');
    } else {
        tab.style.display = 'none'; grid.style.display = 'grid';
        btnG.classList.add('active-view'); btnT.classList.remove('active-view');
    }
    paginate();
}

function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchBillLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        if(sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if(searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) el.removeAttribute('data-hide-local');
        else el.setAttribute('data-hide-local', 'true');
    });
    currentPage = 1;
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
    
    document.querySelectorAll(selector).forEach(i => i.style.display = 'none');
    items.forEach((item, index) => {
        if (index >= (currentPage - 1) * itemsPerPage && index < currentPage * itemsPerPage) item.style.display = currentView === 'table' ? 'table-row' : 'block';
    });
}
function changePage(delta) { currentPage += delta; paginate(); }

function openModal(data = null) {
    const m = document.getElementById('crudModal');
    if(data) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen" style="color:#10b981;"></i> Edit Invoice';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('invoice_id').value = data.invoice_id;
        document.getElementById('fee_type').value = data.fee_type;
        document.getElementById('student_id').value = data.student_id;
        document.getElementById('student_name').value = data.student_name;
        document.getElementById('department').value = data.department;
        document.getElementById('amount').value = data.amount;
        document.getElementById('tax_rate').value = data.tax_rate || 0;
        document.getElementById('discount').value = data.discount || 0;
        document.getElementById('scholarship_amt').value = data.scholarship_amt || 0;
        document.getElementById('amount_paid').value = data.amount_paid;
        document.getElementById('pay_method').value = data.pay_method || 'Pending';
        document.getElementById('due_date').value = data.due_date;
        document.getElementById('status').value = data.status;
        document.getElementById('is_installment').checked = data.is_installment == 1;
        document.getElementById('remarks').value = data.remarks;
    } else {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice-dollar" style="color:#10b981;"></i> Generate Invoice';
        document.getElementById('edit_id').value = '';
        const yr = new Date().getFullYear().toString().substr(-2);
        document.getElementById('invoice_id').value = `INV-${yr}-` + Math.floor(1000+Math.random()*9000);
        document.getElementById('fee_type').value = 'Tuition Fee';
        document.getElementById('student_id').value = '';
        document.getElementById('student_name').value = '';
        document.getElementById('department').value = '';
        document.getElementById('amount').value = '';
        document.getElementById('tax_rate').value = '0';
        document.getElementById('discount').value = '0';
        document.getElementById('scholarship_amt').value = '0';
        document.getElementById('amount_paid').value = '0';
        document.getElementById('pay_method').value = 'Pending';
        const d = new Date(); d.setDate(d.getDate() + 30);
        document.getElementById('due_date').value = d.toISOString().split('T')[0];
        document.getElementById('status').value = 'Unpaid';
        document.getElementById('is_installment').checked = false;
        document.getElementById('remarks').value = '';
    }
    m.style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => { 
    paginate(); 
});
</script>
<?php include 'footer.php'; ?>