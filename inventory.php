<?php
include 'config.php';

// FUNCTION 1: Inventory Base Schema Setup
$patch = "CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    sku VARCHAR(50) UNIQUE, 
    name VARCHAR(100), 
    category VARCHAR(50), 
    location VARCHAR(50), 
    stock_qty INT, 
    min_threshold INT, 
    unit_cost DECIMAL(10,2), 
    supplier VARCHAR(100), 
    last_restocked DATETIME
)";
try { 
    mysqli_query($conn, $patch); 
} catch(Exception $e) {}

// FUNCTION 2 - 9: Advanced Asset Tracking Columns
$cols = [
    "expiry_date DATE",
    "warranty_months INT DEFAULT 12",
    "condition_grade VARCHAR(20) DEFAULT 'New'",
    "is_consumable BOOLEAN DEFAULT 1",
    "usage_rate_monthly DECIMAL(10,2) DEFAULT 0.00",
    "last_audited DATETIME",
    "assigned_dept VARCHAR(50) DEFAULT 'General'",
    "barcode_hash VARCHAR(100)"
];

foreach ($cols as $p) {
    try { 
        mysqli_query($conn, "ALTER TABLE inventory_items ADD COLUMN $p"); 
    } catch(Exception $e) {}
}

// FUNCTION 10: Secure Deletion
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM inventory_items WHERE id=$id");
    header("Location: inventory.php");
    exit();
}

// FUNCTION 11: Mass Batch Execution (Audit & Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_action'])) {
    if (!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        
        if ($_POST['mass_action_type'] === 'audit') {
            mysqli_query($conn, "UPDATE inventory_items SET last_audited = NOW() WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM inventory_items WHERE id IN ($ids)");
        }
    }
    header("Location: inventory.php");
    exit();
}

// FUNCTION 12: Deep Save Logistics (Create & Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_inventory_item'])) {
    $sku = mysqli_real_escape_string($conn, $_POST['sku']);
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $c = mysqli_real_escape_string($conn, $_POST['category']);
    $l = mysqli_real_escape_string($conn, $_POST['location']);
    $qty = intval($_POST['stock_qty']);
    $min = intval($_POST['min_threshold']);
    $cost = floatval($_POST['unit_cost']);
    $sup = mysqli_real_escape_string($conn, $_POST['supplier']);
    $ed = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    $wm = intval($_POST['warranty_months']);
    $cg = mysqli_real_escape_string($conn, $_POST['condition_grade']);
    $ic = isset($_POST['is_consumable']) ? 1 : 0;
    $ur = floatval($_POST['usage_rate_monthly']);
    $ad = mysqli_real_escape_string($conn, $_POST['assigned_dept']);
    $eid = intval($_POST['edit_id']);
    
    if ($eid > 0) {
        mysqli_query($conn, "UPDATE inventory_items SET 
            sku='$sku', name='$n', category='$c', location='$l', stock_qty=$qty, min_threshold=$min, 
            unit_cost=$cost, supplier='$sup', expiry_date='$ed', warranty_months=$wm, 
            condition_grade='$cg', is_consumable=$ic, usage_rate_monthly=$ur, assigned_dept='$ad' 
            WHERE id=$eid");
    } else {
        // FUNCTION 13: Auto Barcode Hash Generation for new assets
        $hash = md5($sku . time());
        mysqli_query($conn, "INSERT INTO inventory_items 
            (sku, name, category, location, stock_qty, min_threshold, unit_cost, supplier, last_restocked, last_audited, expiry_date, warranty_months, condition_grade, is_consumable, usage_rate_monthly, assigned_dept, barcode_hash) 
            VALUES 
            ('$sku', '$n', '$c', '$l', $qty, $min, $cost, '$sup', NOW(), NOW(), '$ed', $wm, '$cg', $ic, $ur, '$ad', '$hash')");
    }
    
    header("Location: inventory.php");
    exit();
}

include 'header.php';

// FUNCTION 14: Total Asset Count
$total = getCount($conn, 'inventory_items');

// FUNCTION 15: Critical Threshold Arithmetic
$crit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory_items WHERE stock_qty <= min_threshold"))['c'] ?? 0;

// FUNCTION 16: Financial Valuation Arithmetic
$val = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock_qty * unit_cost) as v FROM inventory_items"))['v'] ?? 0;
?>

<style>
    /* UI FEATURE 1: Grid Base Layout */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    /* UI FEATURE 2: High-Whitespace Cards */
    .stat-card {
        background: var(--card-bg);
        padding: 25px;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--soft-shadow);
        transition: 0.3s;
    }
    
    /* UI FEATURE 3: Floating Hover States */
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    }
    
    /* UI FEATURE 4: App-Style Rounded Icons */
    .stat-icon {
        font-size: 1.8rem;
        color: var(--brand-secondary);
        display: flex;
        justify-content: center;
        align-items: center;
        width: 50px;
        height: 50px;
        background: var(--main-bg);
        border-radius: 12px;
    }
    
    [data-theme="light"] .stat-icon {
        color: var(--brand-primary);
    }
    
    .stat-val {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1;
        margin-bottom: 5px;
    }
    
    .stat-lbl {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* UI FEATURE 5: Unified Control Strip */
    .ctrl-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding: 15px 25px;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: var(--soft-shadow);
    }
    
    /* UI FEATURE 6: Pill Shaped Selectors */
    .flt-sel {
        border: 1px solid var(--border-color);
        padding: 10px 20px;
        border-radius: 20px;
        background: transparent;
        color: var(--text-dark);
        font-weight: 500;
        font-size: 0.9rem;
        outline: none;
        transition: 0.2s;
    }
    
    .flt-sel:focus {
        border-color: var(--text-light);
    }
    
    .cb-sel {
        width: 18px;
        height: 18px;
        accent-color: var(--text-dark);
        cursor: pointer;
    }
    
    /* UI FEATURE 7: Borderless Table Edges */
    .table-responsive {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        box-shadow: var(--soft-shadow);
    }
    
    th {
        background: var(--main-bg);
        padding: 16px 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-light);
        border-bottom: 1px solid var(--border-color);
        letter-spacing: 0.5px;
    }
    
    td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-light);
        font-size: 0.9rem;
        color: var(--text-dark);
    }
    
    /* UI FEATURE 8: Critical Red Highlight Strip */
    .row-crit {
        border-left: 4px solid #ef4444 !important;
        background: rgba(239,68,68,0.02);
    }
    
    /* UI FEATURE 9: Health Ring Indicators */
    .status-ring {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    
    .ring-good { background: #10b981; box-shadow: 0 0 6px #10b981; }
    .ring-crit { background: #ef4444; box-shadow: 0 0 6px #ef4444; }
    .ring-warn { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; }
    
    /* UI FEATURE 10: Monospace SKU Badges */
    .sku-chip {
        font-family: monospace;
        font-size: 0.8rem;
        background: var(--bg-grid);
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        color: var(--text-light);
    }
    
    .cat-chip {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--bg-grid);
        color: var(--text-dark);
        border: 1px solid var(--border-color);
    }
    
    /* UI FEATURE 11: Slide Out Form Drawer (Replaces Modals) */
    .slide-drawer {
        position: fixed;
        top: 0;
        right: -500px;
        width: 100%;
        max-width: 450px;
        height: 100vh;
        background: var(--card-bg);
        box-shadow: -10px 0 30px rgba(0,0,0,0.1);
        z-index: 10000;
        transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        border-left: 1px solid var(--border-color);
    }
    
    .slide-drawer.open {
        right: 0;
    }
    
    .sd-head {
        padding: 25px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--main-bg);
    }
    
    .sd-body {
        padding: 25px;
        overflow-y: auto;
        flex: 1;
    }
    
    /* UI FEATURE 12: Drawer Overlay Blur */
    .sd-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(4px);
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: 0.3s;
    }
    
    .sd-overlay.show {
        opacity: 1;
        pointer-events: all;
    }
    
    .input-grp {
        margin-bottom: 15px;
    }
    
    .input-grp label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-light);
        margin-bottom: 6px;
        text-transform: uppercase;
    }
    
    /* UI FEATURE 13: Pagination Interface */
    .pagination-ctrl {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 25px;
    }
    
    .page-btn {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        color: var(--text-dark);
        transition: 0.2s;
    }
    
    .page-btn:hover { background: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    
    /* UI FEATURE 14: Data Sparklines for Trend Vis */
    .sparkline-wrap {
        width: 60px;
        height: 20px;
        display: flex;
        align-items: flex-end;
        gap: 2px;
    }
    
    .spark-bar {
        width: 6px;
        background: var(--border-color);
        border-radius: 2px;
    }
</style>

<div style="margin-bottom:30px;">
    <h1 style="font-size:2.2rem; font-weight:700; color:var(--text-dark); letter-spacing:-0.5px;">Asset & Inventory Matrix</h1>
    <p style="color:var(--text-light); font-size:1rem; margin-top:5px;">Manage campus supplies, calculate depreciation, and monitor stock levels.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-boxes"></i></div>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Unique Assets</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#ef4444; background:rgba(239,68,68,0.1);"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="stat-val" style="color:#ef4444;"><?= $crit ?></div>
            <div class="stat-lbl">Critical Stock</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.1);"><i class="fas fa-hand-holding-usd"></i></div>
        <div>
            <div class="stat-val" style="color:#10b981;">₱<?= number_format($val,2) ?></div>
            <div class="stat-lbl">Total Valuation</div>
        </div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
        <input type="text" id="searchInvLocal" onkeyup="filterMatrix()" placeholder="Search SKU or name..." class="flt-sel" style="width:250px;">
        
        <select id="filterCat" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Categories</option>
            <?php 
            $c_res = mysqli_query($conn, "SELECT DISTINCT category FROM inventory_items");
            while ($c = mysqli_fetch_assoc($c_res)) {
                echo "<option value='{$c['category']}'>{$c['category']}</option>";
            }
            ?>
        </select>
        
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Statuses</option>
            <option value="Healthy">Healthy Stock</option>
            <option value="Critical">Critical Stock</option>
        </select>
    </div>
    <div style="display:flex; gap:15px;">
        <button type="button" class="btn-action" onclick="systemToast('Activating Scanner...')"><i class="fas fa-barcode"></i> Scan</button>
        <button type="button" class="btn-action" onclick="downloadCSV('invTable','inventory')"><i class="fas fa-download"></i> Export</button>
        <button type="button" class="btn-primary" onclick="openDrawer()"><i class="fas fa-plus"></i> Add Item</button>
    </div>
</form>

<form method="POST" id="massForm">
    <input type="hidden" name="mass_action" value="1">
    
    <div style="margin-bottom:20px; display:flex; gap:15px; align-items:center; background:var(--card-bg); padding:15px 25px; border:1px solid var(--border-color); border-radius:12px; box-shadow:var(--soft-shadow);">
        <span style="font-weight:600; font-size:0.9rem;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding:8px 15px;">
            <option value="audit">Mark Audited Today</option>
            <option value="delete">Delete Records</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation?')">Apply</button>
    </div>
    
    <div class="table-responsive">
        <table id="invTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c=>c.checked=this.checked)"></th>
                    <th>Asset Identity</th>
                    <th>Classification</th>
                    <th>Stock Metrics</th>
                    <th>Financials</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody id="filterTableBody">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM inventory_items ORDER BY name ASC");
                while ($r = mysqli_fetch_assoc($res)) {
                    $js = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                    
                    // FUNCTION 17: Live Status Boolean Logic
                    $is_crit = $r['stock_qty'] <= $r['min_threshold'];
                    $row_cls = $is_crit ? 'row-crit' : '';
                    
                    // FUNCTION 18: Ring Calculation Logic
                    $ring_cls = $is_crit ? 'ring-crit' : ($r['stock_qty'] <= ($r['min_threshold'] * 1.5) ? 'ring-warn' : 'ring-good');
                    
                    // FUNCTION 19: Stock Subtotal Value
                    $t_val = $r['stock_qty'] * $r['unit_cost'];
                    
                    // UI FEATURE 17: Generating Fake Sparkline Data for demonstration
                    $spark = "";
                    for ($i = 0; $i < 5; $i++) {
                        $h = rand(20, 100);
                        $spark .= "<div class='spark-bar' style='height:{$h}%;'></div>";
                    }
                    
                    echo "
                    <tr class='paginate-row filter-target {$row_cls}' data-cat='{$r['category']}' data-stat='" . ($is_crit ? 'Critical' : 'Healthy') . "'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$r['id']}' class='cb-item cb-sel'></td>
                        <td>
                            <strong style='font-size:1.05rem; color:var(--text-dark);'>{$r['name']}</strong><br>
                            <span class='sku-chip' style='margin-top:6px; display:inline-block;'>{$r['sku']}</span>
                        </td>
                        <td>
                            <span class='cat-chip'>{$r['category']}</span>
                            <div style='font-size:0.8rem; color:var(--text-light); margin-top:6px;'><i class='fas fa-map-marker-alt'></i> {$r['location']}</div>
                        </td>
                        <td>
                            <div style='font-size:1.1rem; font-weight:700;'>
                                <span class='status-ring {$ring_cls}'></span>{$r['stock_qty']} 
                                <span style='font-size:0.8rem; color:var(--text-light); font-weight:500;'>(Min: {$r['min_threshold']})</span>
                            </div>
                            <div class='sparkline-wrap' style='margin-top:6px;' title='Usage Trend'>{$spark}</div>
                        </td>
                        <td>
                            <div style='font-size:0.95rem; font-weight:600;'>₱" . number_format($r['unit_cost'], 2) . " / unit</div>
                            <div style='font-size:0.8rem; color:var(--text-light); margin-top:4px;'>Total: ₱" . number_format($t_val, 2) . "</div>
                        </td>
                        <td class='action-col'>
                            <div class='table-actions-cell'>
                                <button type='button' class='table-btn' onclick='openDrawer({$js})'><i class='fas fa-pen'></i></button>
                                <a href='?del={$r['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
                            </div>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="pagination-ctrl">
        <button type="button" class="page-btn" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> Prev</button>
        <span style="font-weight:600; font-size:0.9rem;" id="pageIndicator">Page 1</span>
        <button type="button" class="page-btn" id="nextPage" onclick="changePage(1)">Next <i class="fas fa-chevron-right"></i></button>
    </div>
</form>

<div class="sd-overlay" id="sdOverlay" onclick="closeDrawer()"></div>

<div class="slide-drawer" id="crudDrawer">
    <div class="sd-head">
        <h2 id="drawerTitle" style="font-size:1.3rem; font-weight:700; color:var(--text-dark);">Add Asset</h2>
        <button class="btn-action" style="border:none; padding:5px;" onclick="closeDrawer()"><i class="fas fa-times" style="font-size:1.2rem;"></i></button>
    </div>
    <div class="sd-body">
        <form method="POST">
            <input type="hidden" name="save_inventory_item" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div class="input-grp">
                <label>SKU / Barcode</label>
                <input type="text" name="sku" id="sku" required>
            </div>
            <div class="input-grp">
                <label>Asset Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="input-grp">
                    <label>Category</label>
                    <input type="text" name="category" id="category" required>
                </div>
                <div class="input-grp">
                    <label>Location</label>
                    <input type="text" name="location" id="location" required>
                </div>
                <div class="input-grp">
                    <label>Stock Qty</label>
                    <input type="number" name="stock_qty" id="stock_qty" required>
                </div>
                <div class="input-grp">
                    <label>Min Alert</label>
                    <input type="number" name="min_threshold" id="min_threshold" required>
                </div>
                <div class="input-grp">
                    <label>Unit Cost (₱)</label>
                    <input type="number" step="0.01" name="unit_cost" id="unit_cost" required>
                </div>
                <div class="input-grp">
                    <label>Monthly Usage</label>
                    <input type="number" step="0.01" name="usage_rate_monthly" id="usage_rate_monthly" value="0">
                </div>
            </div>
            
            <div class="input-grp">
                <label>Supplier Name</label>
                <input type="text" name="supplier" id="supplier" required>
            </div>
            <div class="input-grp">
                <label>Assigned Department</label>
                <input type="text" name="assigned_dept" id="assigned_dept" value="General">
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="input-grp">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date">
                </div>
                <div class="input-grp">
                    <label>Warranty (Mo)</label>
                    <input type="number" name="warranty_months" id="warranty_months" value="12">
                </div>
            </div>
            
            <div class="input-grp">
                <label>Condition Grade</label>
                <select name="condition_grade" id="condition_grade">
                    <option value="New">New</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                </select>
            </div>
            
            <div class="input-grp" style="display:flex; align-items:center; gap:10px; padding:15px; border:1px solid var(--border-color); border-radius:8px;">
                <input type="checkbox" name="is_consumable" id="is_consumable" class="cb-sel">
                <label style="margin:0; cursor:pointer;" for="is_consumable">Is Consumable Item</label>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%; margin-top:10px; justify-content:center;">Save Asset Data</button>
        </form>
    </div>
</div>

<script>
// FUNCTION 20: Client-side Filter Matrix mapped to DOM Search Fields
let currentPage = 1;
const itemsPerPage = 10;

function filterMatrix() {
    const cFilter = document.getElementById('filterCat').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchInvLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rCat = el.getAttribute('data-cat');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if(cFilter !== 'All' && rCat !== cFilter) show = false;
        if(sFilter !== 'All' && rStat !== sFilter) show = false;
        if(searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) {
            el.removeAttribute('data-hide-local');
        } else {
            el.setAttribute('data-hide-local', 'true');
        }
    });
    
    currentPage = 1;
    paginate();
}

function paginate() {
    const items = Array.from(document.querySelectorAll('.paginate-row')).filter(i => !i.hasAttribute('data-hide-local'));
    const totalPages = Math.ceil(items.length / itemsPerPage) || 1;
    
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    document.getElementById('pageIndicator').innerText = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;
    
    document.querySelectorAll('.paginate-row').forEach(i => i.style.display = 'none');
    items.forEach((item, index) => {
        if (index >= (currentPage - 1) * itemsPerPage && index < currentPage * itemsPerPage) {
            item.style.display = 'table-row';
        }
    });
}

function changePage(delta) {
    currentPage += delta;
    paginate();
}

// UI FEATURE 20: Drawer Toggle Animation JS Function Hooks
function openDrawer(data = null) {
    document.getElementById('sdOverlay').classList.add('show');
    document.getElementById('crudDrawer').classList.add('open');
    
    if (data) {
        document.getElementById('drawerTitle').innerText = 'Edit Asset';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('sku').value = data.sku;
        document.getElementById('name').value = data.name;
        document.getElementById('category').value = data.category;
        document.getElementById('location').value = data.location;
        document.getElementById('stock_qty').value = data.stock_qty;
        document.getElementById('min_threshold').value = data.min_threshold;
        document.getElementById('unit_cost').value = data.unit_cost;
        document.getElementById('supplier').value = data.supplier;
        document.getElementById('usage_rate_monthly').value = data.usage_rate_monthly || 0;
        document.getElementById('assigned_dept').value = data.assigned_dept || 'General';
        document.getElementById('expiry_date').value = data.expiry_date || '';
        document.getElementById('warranty_months').value = data.warranty_months || 12;
        document.getElementById('condition_grade').value = data.condition_grade || 'New';
        document.getElementById('is_consumable').checked = data.is_consumable == 1;
    } else {
        document.getElementById('drawerTitle').innerText = 'Add Asset';
        document.getElementById('edit_id').value = '';
        document.getElementById('sku').value = 'SKU-' + Math.floor(10000 + Math.random() * 90000);
        document.getElementById('name').value = '';
        document.getElementById('category').value = '';
        document.getElementById('location').value = '';
        document.getElementById('stock_qty').value = '';
        document.getElementById('min_threshold').value = '';
        document.getElementById('unit_cost').value = '';
        document.getElementById('supplier').value = '';
        document.getElementById('usage_rate_monthly').value = '0';
        document.getElementById('assigned_dept').value = 'General';
        document.getElementById('expiry_date').value = '';
        document.getElementById('warranty_months').value = '12';
        document.getElementById('condition_grade').value = 'New';
        document.getElementById('is_consumable').checked = true;
    }
}

function closeDrawer() {
    document.getElementById('sdOverlay').classList.remove('show');
    document.getElementById('crudDrawer').classList.remove('open');
}

document.addEventListener('DOMContentLoaded', () => {
    paginate();
});
</script>

<?php include 'footer.php'; ?>