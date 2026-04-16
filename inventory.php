<?php
include 'config.php';

$patch = ["sku VARCHAR(50)", "name VARCHAR(100)", "category VARCHAR(50)", "location VARCHAR(50)", "stock_qty INT", "min_threshold INT", "unit_cost DECIMAL(10,2)", "supplier VARCHAR(100)", "last_restocked DATETIME"];
foreach($patch as $p) { try { mysqli_query($conn, "ALTER TABLE inventory_items ADD COLUMN $p"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM inventory_items WHERE id = $id");
    header("Location: inventory.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_inventory_item'])) {
    $sku = mysqli_real_escape_string($conn, $_POST['sku']);
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $c = mysqli_real_escape_string($conn, $_POST['category']);
    $l = mysqli_real_escape_string($conn, $_POST['location']);
    $qty = intval($_POST['stock_qty']);
    $min = intval($_POST['min_threshold']);
    $cost = floatval($_POST['unit_cost']);
    $sup = mysqli_real_escape_string($conn, $_POST['supplier']);
    mysqli_query($conn, "INSERT INTO inventory_items (sku, name, category, location, stock_qty, min_threshold, unit_cost, supplier, last_restocked) VALUES ('$sku', '$n', '$c', '$l', $qty, $min, $cost, '$sup', NOW())");
    header("Location: inventory.php");
    exit();
}

$check_empty = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory_items");
if(mysqli_fetch_assoc($check_empty)['c'] == 0) {
    $seed_data = [
        ['EL-101', 'Faculty Laptops (Dell)', 'Electronics', 'Storage A', 45, 10, 45000.00, 'Dell Corp'],
        ['EL-102', '4K Projectors', 'Electronics', 'Storage A', 12, 5, 25000.00, 'Epson'],
        ['EL-103', 'HDMI Cables (6ft)', 'Electronics', 'Aisle 2', 150, 30, 450.00, 'TechSupply'],
        ['EL-104', 'Graphing Calculators', 'Electronics', 'Aisle 2', 8, 20, 6500.00, 'Texas Instruments'],
        ['FN-201', 'Ergonomic Desk Chairs', 'Furniture', 'Storage B', 200, 50, 3500.00, 'Uline'],
        ['FN-202', 'Student Desks', 'Furniture', 'Storage B', 180, 40, 6500.00, 'Uline'],
        ['FN-203', 'Whiteboards (Large)', 'Furniture', 'Storage B', 5, 10, 12000.00, 'Quartet'],
        ['FN-204', 'Bookshelves', 'Furniture', 'Storage C', 15, 10, 4500.00, 'IKEA'],
        ['ST-301', 'Printer Paper (Ream)', 'Stationery', 'Aisle 1', 500, 100, 250.00, 'Dunder Mifflin']
    ];
    foreach($seed_data as $item) {
        $sku = $item[0]; $n = mysqli_real_escape_string($conn, $item[1]); $c = $item[2]; $l = $item[3];
        $qty = $item[4]; $min = $item[5]; $cost = $item[6]; $sup = mysqli_real_escape_string($conn, $item[7]);
        mysqli_query($conn, "INSERT INTO inventory_items (sku, name, category, location, stock_qty, min_threshold, unit_cost, supplier, last_restocked) VALUES ('$sku', '$n', '$c', '$l', $qty, $min, $cost, '$sup', NOW() - INTERVAL " . rand(1,30) . " DAY)");
    }
}

if(isset($_GET['restock'])) {
    $id = intval($_GET['restock']);
    mysqli_query($conn, "UPDATE inventory_items SET stock_qty = stock_qty + 50, last_restocked = NOW() WHERE id = $id");
    header("Location: inventory.php"); exit();
}

include 'header.php';

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock_qty * unit_cost) as val, COUNT(*) as total_items, SUM(IF(stock_qty <= min_threshold, 1, 0)) as low_stock FROM inventory_items"));
?>

<style>
    .inv-header-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.2s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: var(--brand-secondary); opacity: 0.9; }
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .inv-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px;}
    
    .inv-container { transition: 0.3s; }
    .inv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; }
    .inv-list { display: flex; flex-direction: column; gap: 15px; }
    
    .inv-card { background: var(--card-bg); border: 2px solid var(--border-color); padding: 30px; transition: 0.2s; display: flex; flex-direction: column; position: relative; border-radius: 16px; box-shadow: var(--soft-shadow);}
    .inv-card:hover { border-color: var(--brand-secondary); transform: translateY(-4px); box-shadow: var(--hard-shadow); }
    [data-theme="dark"] .inv-card:hover { border-color: var(--brand-primary); }
    
    .inv-list .inv-card { flex-direction: row; align-items: center; padding: 20px 30px; gap: 20px; justify-content: space-between;}
    
    .inv-sku { font-family: monospace; font-size: 0.85rem; font-weight: 800; color: var(--brand-primary); letter-spacing: 1px; margin-bottom: 12px; display: inline-block; padding: 6px 12px; background: rgba(0,0,0,0.05); border: 2px solid var(--border-color); border-radius: 6px;}
    [data-theme="dark"] .inv-sku { background: rgba(255,255,255,0.05); color: var(--brand-secondary); }
    .inv-list .inv-sku { margin-bottom: 0; }
    
    .inv-name { font-size: 1.25rem; font-weight: 900; color: var(--text-dark); margin-bottom: 5px; }
    .inv-list .inv-name { margin-bottom: 0; width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
    
    .inv-cat { font-size: 0.8rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; margin-bottom: 25px; display: flex; justify-content: space-between; }
    .inv-list .inv-cat { margin-bottom: 0; flex-direction: column; gap: 4px; width: 130px; justify-content: center;}
    
    .inv-stock-wrap { margin-bottom: 25px; }
    .inv-list .inv-stock-wrap { margin-bottom: 0; width: 200px; }
    .inv-stock-lbl { display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 800; color: var(--text-dark); margin-bottom: 10px; }
    .progress-bg { width: 100%; height: 10px; background: rgba(0,0,0,0.05); border: 1px solid var(--border-color); overflow: hidden; border-radius: 6px;}
    [data-theme="dark"] .progress-bg { background: rgba(255,255,255,0.05); }
    .progress-fill { height: 100%; transition: width 0.5s ease; }
    
    .status-ok .progress-fill { background: #10b981; }
    .status-low .progress-fill { background: #f59e0b; }
    .status-crit .progress-fill { background: var(--brand-crimson); }

    .inv-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.85rem; color: var(--text-light); font-weight: 700; margin-bottom: 25px; flex-grow: 1; }
    .inv-list .inv-meta { display: flex; flex-direction: row; margin-bottom: 0; flex-grow: 1; justify-content: space-around; gap: 15px;}
    .inv-meta div { display: flex; flex-direction: column; gap: 6px; padding: 12px; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px;}
    .inv-list .inv-meta div { background: transparent; border: none; padding: 0; }
    .inv-meta strong { color: var(--text-dark); font-size: 0.95rem; }

    .inv-actions { display: flex; gap: 12px; }
    .inv-list .inv-actions { margin-top: 0; width: auto; }
    .inv-btn { flex: 1; padding: 12px; text-align: center; text-decoration: none; font-weight: 900; font-size: 0.85rem; text-transform: uppercase; border: 2px solid var(--border-color); color: var(--text-dark); transition: 0.2s; cursor: pointer; white-space: nowrap; border-radius: 8px; box-shadow: 2px 2px 0px var(--border-color); background: var(--main-bg);}
    .inv-btn:active { transform: translate(2px, 2px); box-shadow: none;}
    .inv-btn:hover { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary); box-shadow: 2px 2px 0px var(--brand-secondary);}
    [data-theme="light"] .inv-btn:hover { background: var(--brand-primary); color: #fff; border-color: var(--brand-primary); box-shadow: 2px 2px 0px var(--brand-primary);}
    .inv-btn-del { flex: 0.3; color: var(--brand-crimson); border-color: var(--brand-crimson); display: flex; align-items: center; justify-content: center; box-shadow: 2px 2px 0px var(--brand-crimson);}
    .inv-btn-del:hover { background: var(--brand-crimson); color: #fff; border-color: var(--brand-crimson); box-shadow: 2px 2px 0px var(--brand-crimson);}

    .view-icon { color: var(--text-light); font-size: 1.4rem; cursor: pointer; transition: 0.2s; padding: 4px;}
    .view-icon.active { color: var(--text-dark); }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #10b981;">
    <h1 style="color: #10b981; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Asset Logistics</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Centralized warehouse tracking and procurement matrix.</p>
</div>

<div class="inv-header-grid">
    <div class="stat-card">
        <i class="fas fa-boxes stat-icon"></i>
        <div>
            <div class="stat-val"><?= number_format($stats['total_items']) ?></div>
            <div class="stat-lbl">Unique Assets Tracked</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-file-invoice-dollar stat-icon"></i>
        <div>
            <div class="stat-val">₱<?= number_format($stats['val'], 2) ?></div>
            <div class="stat-lbl">Total Capital Valuation</div>
        </div>
    </div>
    <div class="stat-card" style="<?= $stats['low_stock'] > 0 ? 'border-color: var(--brand-crimson);' : '' ?>">
        <i class="fas fa-exclamation-triangle stat-icon" style="<?= $stats['low_stock'] > 0 ? 'color: var(--brand-crimson);' : '' ?>"></i>
        <div>
            <div class="stat-val" style="<?= $stats['low_stock'] > 0 ? 'color: var(--brand-crimson);' : '' ?>"><?= number_format($stats['low_stock']) ?></div>
            <div class="stat-lbl">Critical Stock Alerts</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px;">
        <select id="filterCat" onchange="filterInv()" style="width: auto; padding: 10px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All">All Categories</option>
            <option value="Electronics">Electronics</option>
            <option value="Furniture">Furniture</option>
            <option value="Stationery">Stationery</option>
            <option value="Lab Supplies">Lab Supplies</option>
            <option value="Cleaning">Cleaning</option>
        </select>
        <button class="btn-primary" style="margin:0; padding: 10px 20px;" onclick="document.getElementById('addModal').style.display='flex';"><i class="fas fa-plus"></i> Add Asset</button>
    </div>
    <div style="display:flex; gap: 15px; align-items:center;">
        <span style="font-weight:800; font-size:0.85rem; color:var(--text-light); text-transform:uppercase;">View:</span>
        <i class="fas fa-th-large view-icon active" id="btnGrid" onclick="setView('grid')"></i>
        <i class="fas fa-list view-icon" id="btnList" onclick="setView('list')"></i>
    </div>
</div>

<div class="inv-container inv-grid" id="invContainer">
    <?php
    $res = mysqli_query($conn, "SELECT * FROM inventory_items ORDER BY category ASC, name ASC");
    while($row = mysqli_fetch_assoc($res)) {
        $pct = ($row['stock_qty'] / max($row['stock_qty'], $row['min_threshold'] * 3)) * 100;
        if($pct > 100) $pct = 100;
        
        $status_class = 'status-ok';
        $status_text = 'Optimal';
        if($row['stock_qty'] <= $row['min_threshold']) { $status_class = 'status-crit'; $status_text = 'Critical'; }
        elseif($row['stock_qty'] <= $row['min_threshold'] * 1.5) { $status_class = 'status-low'; $status_text = 'Low'; }
        
        $date = date('M d, Y', strtotime($row['last_restocked']));
        
        echo "
        <div class='inv-card {$status_class}' data-category='{$row['category']}'>
            <div><span class='inv-sku'><i class='fas fa-barcode'></i> {$row['sku']}</span></div>
            <div class='inv-name'>{$row['name']}</div>
            <div class='inv-cat'><span>{$row['category']}</span> <span><i class='fas fa-map-marker-alt'></i> {$row['location']}</span></div>
            
            <div class='inv-stock-wrap'>
                <div class='inv-stock-lbl'>
                    <span>Stock: {$row['stock_qty']} Units</span>
                    <span style='text-transform:uppercase;'>{$status_text}</span>
                </div>
                <div class='progress-bg'><div class='progress-fill' style='width: {$pct}%;'></div></div>
            </div>
            
            <div class='inv-meta'>
                <div><span>Unit Cost</span><strong>₱{$row['unit_cost']}</strong></div>
                <div><span>Total Value</span><strong>₱" . number_format($row['unit_cost'] * $row['stock_qty'], 2) . "</strong></div>
                <div><span>Min Threshold</span><strong>{$row['min_threshold']} Units</strong></div>
                <div><span>Last Restock</span><strong>{$date}</strong></div>
            </div>
            
            <div class='inv-actions'>
                <a href='?restock={$row['id']}' class='inv-btn'><i class='fas fa-box-open'></i> Restock</a>
                <a href='?del={$row['id']}' class='inv-btn inv-btn-del' title='Delete Asset'><i class='fas fa-trash'></i></a>
            </div>
        </div>";
    }
    ?>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('addModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 style="font-size: 1.8rem; margin-bottom: 25px; font-family: var(--heading-font); text-transform:uppercase;"><i class="fas fa-boxes" style="color:var(--brand-secondary);"></i> Register Asset</h2>
        <form method="POST" action="inventory.php">
            <input type="hidden" name="add_inventory_item" value="1">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="sku" placeholder="SKU ID (e.g. XX-123)" required>
                <input type="text" name="name" placeholder="Asset Name" required>
                <select name="category" required>
                    <option value="Electronics">Electronics</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Stationery">Stationery</option>
                    <option value="Lab Supplies">Lab Supplies</option>
                    <option value="Cleaning">Cleaning</option>
                </select>
                <input type="text" name="location" placeholder="Storage Location" required>
                <input type="number" name="stock_qty" placeholder="Initial Stock Qty" required>
                <input type="number" name="min_threshold" placeholder="Minimum Alert Threshold" required>
                <input type="number" step="0.01" name="unit_cost" placeholder="Unit Cost (₱)" required>
                <input type="text" name="supplier" placeholder="Supplier Name" required>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px;"><i class="fas fa-save"></i> Save to Database</button>
        </form>
    </div>
</div>

<script>
function filterInv() {
    const cat = document.getElementById('filterCat').value;
    document.querySelectorAll('.inv-card').forEach(card => {
        if(cat === 'All Categories' || card.getAttribute('data-category') === cat) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function setView(type) {
    const container = document.getElementById('invContainer');
    const bGrid = document.getElementById('btnGrid');
    const bList = document.getElementById('btnList');
    
    if(type === 'list') {
        container.classList.remove('inv-grid');
        container.classList.add('inv-list');
        bGrid.classList.remove('active');
        bList.classList.add('active');
    } else {
        container.classList.remove('inv-list');
        container.classList.add('inv-grid');
        bList.classList.remove('active');
        bGrid.classList.add('active');
    }
}
</script>

<?php include 'footer.php'; ?>