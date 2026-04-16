<?php 
include 'config.php'; 

// FEATURE 1: Dynamic Schema Patcher
$patch = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20) UNIQUE,
    item_name VARCHAR(150),
    department VARCHAR(50),
    vendor VARCHAR(100),
    category VARCHAR(50),
    amount DECIMAL(10,2),
    budget_limit DECIMAL(10,2) DEFAULT 50000,
    order_date DATE,
    status VARCHAR(20) DEFAULT 'Pending'
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

// CRITICAL FIX: Force-inject missing columns into older legacy tables
$cols = ["po_number VARCHAR(20)", "item_name VARCHAR(150)", "department VARCHAR(50)", "vendor VARCHAR(100)", "category VARCHAR(50)", "amount DECIMAL(10,2)", "budget_limit DECIMAL(10,2) DEFAULT 50000", "order_date DATE", "status VARCHAR(20) DEFAULT 'Pending'"];
foreach($cols as $c) { 
    try { mysqli_query($conn, "ALTER TABLE orders ADD COLUMN $c"); } catch (Exception $e) {} 
}

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM orders WHERE id = $id");
    header("Location: orders.php"); exit();
}

if(isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $res = mysqli_query($conn, "SELECT status FROM orders WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $cur = $row['status'];
        if($cur == 'Pending') $nxt = 'Approved';
        elseif($cur == 'Approved') $nxt = 'Delivered';
        elseif($cur == 'Delivered') $nxt = 'Canceled';
        else $nxt = 'Pending';
        
        mysqli_query($conn, "UPDATE orders SET status = '$nxt' WHERE id = $id");
        header("Location: orders.php"); exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_order'])) {
    $po = mysqli_real_escape_string($conn, $_POST['po_number']);
    $it = mysqli_real_escape_string($conn, $_POST['item_name']);
    $dp = mysqli_real_escape_string($conn, $_POST['department']);
    $vn = mysqli_real_escape_string($conn, $_POST['vendor']);
    $ct = mysqli_real_escape_string($conn, $_POST['category']);
    $am = floatval($_POST['amount']);
    $bl = floatval($_POST['budget_limit']);
    $od = mysqli_real_escape_string($conn, $_POST['order_date']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE orders SET po_number='$po', item_name='$it', department='$dp', vendor='$vn', category='$ct', amount=$am, budget_limit=$bl, order_date='$od', status='$st' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO orders (po_number, item_name, department, vendor, category, amount, budget_limit, order_date, status) VALUES ('$po', '$it', '$dp', '$vn', '$ct', $am, $bl, '$od', '$st')");
    }
    header("Location: orders.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed = [
        ['Dell Optiplex Workstations (x30)', 'Computer Studies', 'Dell EMC', 'Hardware', 45000, 50000, 'Delivered'],
        ['Adobe Creative Cloud Licenses', 'Arts & Sciences', 'Adobe Systems', 'Software', 12000, 15000, 'Approved'],
        ['Microscopes (x10)', 'Engineering', 'Olympus Scientific', 'Lab Equipment', 28000, 30000, 'Pending'],
        ['Office Desk Chairs (x50)', 'Administration', 'Steelcase', 'Furniture', 15000, 20000, 'Delivered'],
        ['Printer Ink & Paper Bulk', 'General', 'Staples Business', 'Supplies', 2500, 5000, 'Approved'],
        ['Cisco Network Switches', 'Computer Studies', 'Cisco Systems', 'Hardware', 35000, 40000, 'Pending'],
        ['Industrial Fume Hood', 'Engineering', 'LabTech Solutions', 'Lab Equipment', 85000, 90000, 'Canceled'],
        ['Campus WiFi Access Points', 'Operations', 'Ubiquiti', 'Hardware', 22000, 25000, 'Delivered'],
        ['Whiteboards & Markers', 'General', 'Office Depot', 'Supplies', 1200, 3000, 'Approved'],
        ['Library Management SaaS', 'Academics', 'ExLibris', 'Software', 8000, 10000, 'Delivered'],
        ['Cafeteria Tables (x20)', 'Operations', 'Uline', 'Furniture', 9000, 12000, 'Pending'],
        ['Chemistry Glassware Set', 'Engineering', 'Fisher Scientific', 'Lab Equipment', 4500, 5000, 'Delivered'],
        ['Zoom Enterprise Licenses', 'Administration', 'Zoom Video', 'Software', 6000, 8000, 'Approved'],
        ['MacBook Pro (x5)', 'Arts & Sciences', 'Apple Inc.', 'Hardware', 14000, 15000, 'Pending'],
        ['Janitorial Supplies Bulk', 'Operations', 'Grainger', 'Supplies', 3500, 5000, 'Delivered'],
        ['Smart Interactive Displays', 'Academics', 'Promethean', 'Hardware', 48000, 50000, 'Canceled'],
        ['Business Simulation SaaS', 'Business', 'Capsim', 'Software', 5500, 7000, 'Approved'],
        ['Filing Cabinets (x10)', 'Administration', 'IKEA', 'Furniture', 4000, 6000, 'Delivered'],
        ['Oscilloscopes (x5)', 'Engineering', 'Tektronix', 'Lab Equipment', 18000, 20000, 'Pending'],
        ['ERP System Maintenance', 'Administration', 'Oracle', 'Software', 65000, 70000, 'Approved']
    ];
    for($i=0; $i<20; $i++) {
        $po = "PO-2026-" . str_pad($i+1, 4, "0", STR_PAD_LEFT);
        $od = date('Y-m-d', strtotime('-'.rand(1, 60).' days'));
        mysqli_query($conn, "INSERT INTO orders (po_number, item_name, department, vendor, category, amount, budget_limit, order_date, status) VALUES ('$po', '{$seed[$i][0]}', '{$seed[$i][1]}', '{$seed[$i][2]}', '{$seed[$i][3]}', {$seed[$i][4]}, {$seed[$i][5]}, '$od', '{$seed[$i][6]}')");
    }
}

include 'header.php';

$total = getCount($conn, 'orders');
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='Pending' OR status='Approved'"))['c'];
$spend = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM orders WHERE status!='Canceled'"))['s'] ?: 0;
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

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap:wrap; gap:15px;}
    
    .status-del { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-app { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: #3b82f6; }
    .status-pen { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .status-can { background: var(--main-bg); color: var(--text-light); border-color: var(--text-light); }
    
    .po-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .po-box { color: var(--brand-primary); }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #06b6d4;">
    <h1 style="color: #06b6d4; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Procurement Operations</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Manage purchase orders, vendor deliveries, and departmental budgets.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-shopping-cart stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Purchase Orders</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-box-open stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $pending ?></div>
            <div class="stat-lbl">Awaiting Delivery</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-chart-pie stat-icon" style="color:#06b6d4;"></i>
        <div>
            <div class="stat-val" style="color:#06b6d4;">₱<?= number_format($spend/1000, 1) ?>k</div>
            <div class="stat-lbl">Total Active Spend</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; flex-wrap:wrap; align-items:center;">
        <input type="text" id="searchOrdersLocal" onkeyup="filterOrders()" placeholder="&#xf002; Search PO or Item..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 250px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterOrders()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Stages</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Delivered">Delivered</option>
            <option value="Canceled">Canceled</option>
        </select>
        <select id="filterDept" onchange="filterOrders()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Departments">All Departments</option>
            <?php
            $d_res = mysqli_query($conn, "SELECT DISTINCT department FROM orders ORDER BY department ASC");
            while($d = mysqli_fetch_assoc($d_res)) { 
                $dept_val = htmlspecialchars($d['department']);
                if(!empty($dept_val)) echo "<option value='{$dept_val}'>{$dept_val}</option>"; 
            }
            ?>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting PO Logs...')"><i class="fas fa-file-csv"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#06b6d4; border-color:#06b6d4; color:#fff;" onclick="openOrderModal()"><i class="fas fa-plus"></i> Draft PO</button>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:20%;">PO Details</th>
                <th>Requesting Department</th>
                <th style="width:25%;">Budget Allocation</th>
                <th>Fulfillment</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="ordersTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM orders ORDER BY order_date DESC");
            while($row = mysqli_fetch_assoc($res)) {
                
                // Fallbacks for legacy schema rows missing new columns
                $po_num = isset($row['po_number']) ? $row['po_number'] : 'UNKNOWN';
                $amt = isset($row['amount']) ? floatval($row['amount']) : 0;
                $lim = isset($row['budget_limit']) ? floatval($row['budget_limit']) : 50000;
                $cat = isset($row['category']) ? $row['category'] : 'Supplies';
                $ven = isset($row['vendor']) ? $row['vendor'] : 'General Vendor';
                $itm = isset($row['item_name']) ? $row['item_name'] : 'Miscellaneous Item';
                $dpt = isset($row['department']) ? $row['department'] : 'General';
                $od  = isset($row['order_date']) ? $row['order_date'] : date('Y-m-d');
                $st = isset($row['status']) ? $row['status'] : 'Pending';

                if($st == 'Delivered') $st_class = 'status-del';
                elseif($st == 'Approved') $st_class = 'status-app';
                elseif($st == 'Pending') $st_class = 'status-pen';
                else $st_class = 'status-can';
                
                $row_style = $st == 'Canceled' ? "opacity: 0.6; filter: grayscale(50%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $icon = 'fa-box';
                if($cat == 'Software') $icon = 'fa-laptop-code';
                if($cat == 'Hardware') $icon = 'fa-server';
                if($cat == 'Furniture') $icon = 'fa-chair';
                if($cat == 'Lab Equipment') $icon = 'fa-flask';

                $f_date = date('M d, Y', strtotime($od));
                
                $pct = $lim > 0 ? ($amt / $lim) * 100 : 0;
                $bar_color = $pct > 90 ? '#ef4444' : ($pct > 60 ? '#f59e0b' : '#06b6d4');

                echo "
                <tr style='$row_style' data-stat='{$st}' data-dept='{$dpt}'>
                    <td>
                        <div class='po-box'>{$po_num}</div>
                        <div style='font-size:0.8rem; color:var(--text-light); margin-top:8px; font-weight:800; text-transform:uppercase;'><i class='fas {$icon}' style='margin-right:6px;'></i> {$cat}</div>
                    </td>
                    <td>
                        <strong style='color:var(--text-dark); font-size:1.1rem;'>{$itm}</strong>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:700; margin-top:4px;'><i class='fas fa-store'></i> Vendor: {$ven}</div>
                        <div style='font-size:0.8rem; color:var(--brand-secondary); font-weight:800; margin-top:6px; text-transform:uppercase;'>{$dpt}</div>
                    </td>
                    <td>
                        <div style='display:flex; justify-content:space-between; font-weight:900; color:var(--text-dark); margin-bottom:6px;'><span>Cost: ₱" . number_format($amt, 2) . "</span> <span>Max: ₱" . number_format($lim/1000, 1) . "k</span></div>
                        <div style='width: 100%; height: 6px; background: var(--border-light); border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                    </td>
                    <td>
                        <span class='status-pill {$st_class}' style='display:block; width:fit-content; margin-bottom:8px;'>{$st}</span>
                        <div style='font-size:0.8rem; color:var(--text-light); font-weight:800;'><i class='far fa-calendar-alt'></i> {$f_date}</div>
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openOrderModal($js_data)'><i class='fas fa-pen'></i></button>
                            <a href='?toggle_status={$row['id']}' class='table-btn' style='border-color:#06b6d4; color:#06b6d4;' onclick='systemToast(\"Routing PO Stage...\")'><i class='fas fa-sync-alt'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Voiding Order...\")'><i class='fas fa-trash'></i></a>
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
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-shopping-cart" style="color:#06b6d4;"></i> Draft PO</h2>
        <form method="POST">
            <input type="hidden" name="save_order" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="po_number" id="po_number" placeholder="PO Number (e.g. PO-2026-101)" required>
                <input type="text" name="item_name" id="item_name" placeholder="Item / Service Name" required>
                
                <input type="text" name="vendor" id="vendor" placeholder="Vendor / Supplier Name" required>
                <select name="department" id="department" required>
                    <option value="Administration">Administration</option>
                    <option value="Computer Studies">Computer Studies</option>
                    <option value="Business">Business</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Arts & Sciences">Arts & Sciences</option>
                    <option value="Operations">Operations</option>
                    <option value="Academics">Academics</option>
                    <option value="General">General / All</option>
                </select>

                <select name="category" id="category" required>
                    <option value="Hardware">Hardware / IT</option>
                    <option value="Software">Software / SaaS</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Lab Equipment">Lab Equipment</option>
                    <option value="Supplies">General Supplies</option>
                </select>
                <input type="date" name="order_date" id="order_date" required>
                
                <input type="number" step="0.01" name="amount" id="amount" placeholder="PO Amount (₱)" required>
                <input type="number" step="0.01" name="budget_limit" id="budget_limit" placeholder="Dept Budget Limit (₱)" required>
                
                <select name="status" id="status" style="grid-column: span 2;" required>
                    <option value="Pending">Pending Approval</option>
                    <option value="Approved">Approved / Processing</option>
                    <option value="Delivered">Delivered / Completed</option>
                    <option value="Canceled">Canceled</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#06b6d4; border-color:#06b6d4; color:#fff;"><i class="fas fa-save"></i> Save Procurement Data</button>
        </form>
    </div>
</div>

<script>
function filterOrders() {
    const sFilter = document.getElementById('filterStatus').value;
    const dFilter = document.getElementById('filterDept').value;
    const searchQ = document.getElementById('searchOrdersLocal').value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTableBody tr');
    
    rows.forEach(row => {
        const rStat = row.getAttribute('data-stat');
        const rDept = row.getAttribute('data-dept');
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

function openOrderModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#06b6d4;"></i> Edit Order';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('po_number').value = data.po_number || '';
        document.getElementById('item_name').value = data.item_name || '';
        document.getElementById('vendor').value = data.vendor || '';
        document.getElementById('department').value = data.department || 'General';
        document.getElementById('category').value = data.category || 'Supplies';
        document.getElementById('order_date').value = data.order_date || '';
        document.getElementById('amount').value = data.amount || '';
        document.getElementById('budget_limit').value = data.budget_limit || '';
        document.getElementById('status').value = data.status || 'Pending';
    } else {
        title.innerHTML = '<i class="fas fa-shopping-cart" style="color:#06b6d4;"></i> Draft PO';
        document.getElementById('edit_id').value = '';
        const yr = new Date().getFullYear();
        const rand = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('po_number').value = `PO-${yr}-${rand}`;
        document.getElementById('item_name').value = '';
        document.getElementById('vendor').value = '';
        document.getElementById('department').value = 'General';
        document.getElementById('category').value = 'Supplies';
        document.getElementById('order_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('amount').value = '';
        document.getElementById('budget_limit').value = '50000';
        document.getElementById('status').value = 'Pending';
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>