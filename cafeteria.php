<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

// FUNCTION 1: Core Menu Schema Setup
$patch1 = "CREATE TABLE IF NOT EXISTS cafeteria_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100),
    category VARCHAR(50),
    price DECIMAL(10,2),
    calories INT,
    allergens VARCHAR(100),
    stock INT,
    is_trending BOOLEAN DEFAULT FALSE,
    is_combo BOOLEAN DEFAULT FALSE
)";
try { 
    mysqli_query($conn, $patch1); 
} catch(Exception $e) {}

// FUNCTION 2: Core Order Schema Setup
$patch2 = "CREATE TABLE IF NOT EXISTS cafeteria_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    total_amount DECIMAL(10,2),
    order_data TEXT,
    pickup_time VARCHAR(20),
    status VARCHAR(20) DEFAULT 'Preparing',
    order_hash VARCHAR(100)
)";
try { 
    mysqli_query($conn, $patch2); 
} catch(Exception $e) {}

// FUNCTION 3 - 10: Advanced Nutritional & Marketing Metrics
$cols1 = [
    "dietary_badge VARCHAR(50) DEFAULT 'None'",
    "protein_g INT DEFAULT 0",
    "carbs_g INT DEFAULT 0",
    "fat_g INT DEFAULT 0",
    "prep_time_mins INT DEFAULT 5",
    "discount_pct INT DEFAULT 0",
    "is_active BOOLEAN DEFAULT 1",
    "spiciness_level INT DEFAULT 0"
];
foreach ($cols1 as $c) {
    try { 
        mysqli_query($conn, "ALTER TABLE cafeteria_menu ADD COLUMN $c"); 
    } catch(Exception $e) {}
}

// FUNCTION 11 - 18: Advanced Order Tracking & Finance Metrics
$cols2 = [
    "promo_code VARCHAR(20)",
    "donation_amt DECIMAL(10,2) DEFAULT 0",
    "is_favorite BOOLEAN DEFAULT 0",
    "kitchen_notes TEXT",
    "payment_method VARCHAR(20) DEFAULT 'Campus Cash'",
    "loyalty_earned INT DEFAULT 0",
    "refund_requested BOOLEAN DEFAULT 0",
    "completion_time DATETIME"
];
foreach ($cols2 as $c) {
    try { 
        mysqli_query($conn, "ALTER TABLE cafeteria_orders ADD COLUMN $c"); 
    } catch(Exception $e) {}
}

// FUNCTION 19: Automatic Data Seeding
$check_menu = mysqli_query($conn, "SELECT COUNT(*) as c FROM cafeteria_menu");
if (mysqli_fetch_assoc($check_menu)['c'] == 0) {
    $menu = [
        ['Artisan Smashburger', 'Mains', 180.00, 650, 'Gluten, Dairy', 15, 1, 0, 'None', 35, 40, 45, 10, 0, 1, 0],
        ['Spicy Basil Pasta', 'Mains', 150.00, 420, 'Gluten', 8, 0, 0, 'Vegetarian', 12, 65, 15, 8, 0, 1, 2],
        ['Vegan Buddha Bowl', 'Healthy', 195.00, 310, 'None', 5, 0, 0, 'Vegan', 15, 45, 10, 5, 0, 1, 0],
        ['Truffle Fries', 'Sides', 85.00, 380, 'None', 20, 1, 0, 'Vegetarian', 5, 45, 25, 5, 0, 1, 0],
        ['Matcha Latte', 'Beverages', 110.00, 120, 'Dairy', 30, 0, 0, 'None', 4, 15, 5, 3, 0, 1, 0],
        ['Campus Combo A', 'Combos', 250.00, 950, 'Gluten, Dairy', 10, 1, 1, 'None', 45, 100, 55, 12, 10, 1, 0],
        ['Keto Grilled Chicken', 'Healthy', 210.00, 280, 'None', 4, 0, 0, 'Keto', 45, 5, 15, 15, 0, 1, 0],
        ['Iced Americano', 'Beverages', 95.00, 15, 'None', 50, 0, 0, 'Vegan', 1, 2, 0, 2, 0, 1, 0]
    ];
    
    foreach ($menu as $m) {
        mysqli_query($conn, "INSERT INTO cafeteria_menu 
            (item_name, category, price, calories, allergens, stock, is_trending, is_combo, dietary_badge, protein_g, carbs_g, fat_g, prep_time_mins, discount_pct, is_active, spiciness_level) 
            VALUES 
            ('{$m[0]}', '{$m[1]}', {$m[2]}, {$m[3]}, '{$m[4]}', {$m[5]}, {$m[6]}, {$m[7]}, '{$m[8]}', {$m[9]}, {$m[10]}, {$m[11]}, {$m[12]}, {$m[13]}, {$m[14]}, {$m[15]})");
    }
}

// FUNCTION 20: Secure Order Checkout Processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $uid = $_SESSION['user_id'] ?? 'GUEST';
    $total = floatval($_POST['total_amount']);
    $data = mysqli_real_escape_string($conn, $_POST['order_data']);
    $time = mysqli_real_escape_string($conn, $_POST['pickup_time']);
    $hash = md5(uniqid($uid, true));
    $pc = mysqli_real_escape_string($conn, $_POST['promo_code']);
    $da = floatval($_POST['donation_amt']);
    $kn = mysqli_real_escape_string($conn, $_POST['kitchen_notes']);
    $pm = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $le = intval($_POST['loyalty_earned']);
    
    mysqli_query($conn, "INSERT INTO cafeteria_orders 
        (user_id, total_amount, order_data, pickup_time, order_hash, promo_code, donation_amt, kitchen_notes, payment_method, loyalty_earned) 
        VALUES 
        ('$uid', $total, '$data', '$time', '$hash', '$pc', $da, '$kn', '$pm', $le)");
        
    $order_id = mysqli_insert_id($conn);
    header("Location: cafeteria.php?success=1&receipt=$hash&oid=$order_id");
    exit();
}

$user_role = $_SESSION['role'] ?? 'admin';
if ($user_role === 'student') {
    include 'student_header.php';
} else {
    include 'header.php';
}

$campus_cash = 1450.50;
$loyalty_pts = 320;
$queue_number = rand(104, 118);
$kitchen_load = rand(45, 95);
$wait_time = ceil($kitchen_load / 5) + 5;
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
    /* UI FEATURE 1: Clean Root Variables */
    :root {
        --food-primary: #0f172a;
        --food-accent: #f59e0b;
        --food-leaf: #10b981;
        --food-bg: #f8fafc;
        --food-card: #ffffff;
    }

    [data-theme="dark"] {
        --food-primary: #f8fafc;
        --food-bg: #0f172a;
        --food-card: #1e293b;
    }

    /* UI FEATURE 2: Telemetry Dashboard Header */
    .cafe-header {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .telemetry-board {
        display: flex;
        gap: 20px;
        flex: 1;
    }
    
    .t-card {
        background: var(--food-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
        box-shadow: var(--soft-shadow);
        transition: 0.3s;
    }
    
    .t-card:hover {
        transform: translateY(-2px);
        border-color: var(--text-light);
    }
    
    .t-val {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }
    
    .t-lbl {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .t-icon {
        position: absolute;
        right: 20px;
        bottom: 20px;
        font-size: 3rem;
        opacity: 0.03;
        color: var(--text-dark);
    }

    /* UI FEATURE 3: Glassmorphism Wallet Card */
    .wallet-card {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border-radius: 16px;
        padding: 24px;
        min-width: 280px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }
    
    .wallet-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    /* UI FEATURE 4: Grid Layouts */
    .cafe-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 30px;
        align-items: start;
    }
    
    @media(max-width: 1100px) {
        .cafe-grid { grid-template-columns: 1fr; }
    }

    /* UI FEATURE 5: Scrollable Filter Menu */
    .menu-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
        overflow-x: auto;
        padding-bottom: 10px;
        scrollbar-width: none;
    }
    
    .menu-filters::-webkit-scrollbar {
        display: none;
    }
    
    .filter-btn {
        background: var(--food-card);
        border: 1px solid var(--border-color);
        padding: 10px 20px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 30px;
        cursor: pointer;
        color: var(--text-dark);
        white-space: nowrap;
        transition: 0.2s;
        box-shadow: var(--soft-shadow);
    }
    
    .filter-btn:hover, .filter-btn.active {
        background: var(--text-dark);
        color: var(--food-bg);
        border-color: var(--text-dark);
    }

    /* UI FEATURE 6: Product Item Cards */
    .menu-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
    }
    
    .menu-card {
        background: var(--food-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        transition: 0.3s;
        box-shadow: var(--soft-shadow);
        position: relative;
    }
    
    .menu-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.08);
        border-color: var(--border-light);
    }
    
    .menu-card.disabled {
        opacity: 0.5;
        pointer-events: none;
        filter: grayscale(1);
    }

    /* UI FEATURE 7: Product Headers */
    .mc-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .mc-img-ph {
        width: 60px;
        height: 60px;
        background: var(--bg-grid);
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.5rem;
        color: var(--text-light);
    }
    
    .mc-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    .mc-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 6px;
        line-height: 1.3;
    }

    /* UI FEATURE 8: Dietary Badge System */
    .mc-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 15px;
    }
    
    .tag {
        font-size: 0.65rem;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 20px;
        background: var(--bg-grid);
        color: var(--text-light);
    }
    
    .tag-cal { color: #f59e0b; background: rgba(245,158,11,0.1); }
    .tag-alg { color: #ef4444; background: rgba(239,68,68,0.1); }
    .tag-diet { color: #10b981; background: rgba(16,185,129,0.1); }

    /* UI FEATURE 9: Macro Progress Bars */
    .mc-macros {
        display: flex;
        justify-content: space-between;
        font-size: 0.7rem;
        color: var(--text-light);
        margin-bottom: 15px;
        padding-top: 15px;
        border-top: 1px solid var(--border-light);
    }
    
    .macro-bar {
        height: 4px;
        border-radius: 2px;
        background: var(--border-light);
        overflow: hidden;
        flex: 1;
        margin: 0 5px;
    }
    
    .macro-fill {
        height: 100%;
    }

    /* UI FEATURE 10: Add to Cart Controls */
    .mc-action { margin-top: auto; }
    
    .btn-add {
        width: 100%;
        background: transparent;
        border: 1px solid var(--border-color);
        font-weight: 600;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.2s;
        color: var(--text-dark);
    }
    
    .btn-add:hover {
        background: var(--text-dark);
        color: var(--food-bg);
        border-color: var(--text-dark);
    }

    /* UI FEATURE 11: Sticky Cart Panel */
    .cart-panel {
        background: var(--food-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        position: sticky;
        top: 100px;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 120px);
        box-shadow: var(--soft-shadow);
        overflow: hidden;
    }
    
    /* UI FEATURE 12: Glass Cart Header */
    .cart-head {
        padding: 20px 25px;
        border-bottom: 1px solid var(--border-color);
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(var(--card-bg-rgb), 0.9);
        backdrop-filter: blur(10px);
        z-index: 10;
    }
    
    .cart-body {
        padding: 20px 25px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-light);
        padding-bottom: 15px;
    }
    
    .ci-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 4px;
    }
    
    .ci-price {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-light);
    }
    
    /* UI FEATURE 13: Increment/Decrement Controls */
    .ci-ctrl {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--bg-grid);
        border-radius: 20px;
        padding: 4px;
    }
    
    .ci-btn {
        background: transparent;
        border: none;
        width: 24px;
        height: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        cursor: pointer;
        font-weight: 600;
        color: var(--text-dark);
        transition: 0.2s;
    }
    
    .ci-btn:hover {
        background: var(--food-card);
        box-shadow: var(--soft-shadow);
    }

    /* UI FEATURE 14: Cart Options Section */
    .cart-opts {
        padding: 20px 25px;
        border-top: 1px solid var(--border-color);
        background: var(--bg-grid);
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .opt-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-dark);
    }
    
    .opt-select {
        border: 1px solid var(--border-color);
        background: var(--food-card);
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.85rem;
        outline: none;
    }

    /* UI FEATURE 15: iOS Style Toggle Switch */
    .toggle-switch {
        position: relative;
        width: 40px;
        height: 22px;
        appearance: none;
        background: var(--border-color);
        border-radius: 20px;
        cursor: pointer;
        outline: none;
        transition: 0.3s;
    }
    
    .toggle-switch:checked {
        background: #10b981;
    }
    
    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .toggle-switch:checked::after {
        transform: translateX(18px);
    }

    /* UI FEATURE 16: Checkout Footer */
    .cart-foot {
        padding: 20px 25px;
        background: var(--food-card);
    }
    
    .cf-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        color: var(--text-light);
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .cf-total {
        display: flex;
        justify-content: space-between;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed var(--border-color);
    }
    
    .btn-checkout {
        width: 100%;
        background: var(--text-dark);
        color: var(--food-bg);
        border: none;
        padding: 16px;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 12px;
        cursor: pointer;
        margin-top: 20px;
        transition: 0.2s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .btn-checkout:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    /* UI FEATURE 17: QR Code Receipt Modal */
    .qr-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15,23,42,0.4);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }
    
    .qr-box {
        background: var(--food-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        animation: popIn 0.3s ease;
    }
    
    @keyframes popIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    /* UI FEATURE 18: Nutrition Overlay Modal */
    .nutri-modal {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(5px);
        z-index: 5;
        display: none;
        flex-direction: column;
        padding: 20px;
        border-radius: 16px;
    }
    
    [data-theme="dark"] .nutri-modal {
        background: rgba(30,41,59,0.95);
    }
</style>

<div class="cafe-header">
    <div class="telemetry-board">
        <div class="t-card">
            <i class="fas fa-users t-icon"></i>
            <div class="t-val"><?= $queue_number ?></div>
            <div class="t-lbl">Orders in Queue</div>
        </div>
        <div class="t-card">
            <i class="fas fa-fire t-icon"></i>
            <div class="t-val" style="color:#ef4444;"><?= $kitchen_load ?>%</div>
            <div class="t-lbl">Kitchen Capacity</div>
        </div>
        <div class="t-card">
            <i class="fas fa-clock t-icon"></i>
            <div class="t-val"><?= $wait_time ?>m</div>
            <div class="t-lbl">Est. Prep Time</div>
        </div>
    </div>
    
    <div class="wallet-card">
        <div style="font-size:0.75rem; font-weight:600; letter-spacing:1px; opacity:0.8; margin-bottom:8px; text-transform:uppercase;">
            Campus Wallet
        </div>
        <div style="font-size:2.2rem; font-weight:700; line-height:1; margin-bottom:15px;">
            ₱<?= number_format($campus_cash, 2) ?>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:500; border-top:1px solid rgba(255,255,255,0.1); padding-top:15px;">
            <span><i class="fas fa-star" style="color:var(--food-accent);"></i> <?= $loyalty_pts ?> Points</span>
            <span style="color:var(--food-leaf);"><i class="fas fa-leaf"></i> Eco-Tier Active</span>
        </div>
    </div>
</div>

<div class="cafe-grid">
    <div class="menu-section">
        <div class="menu-filters">
            <button class="filter-btn active" onclick="filterMenu('All')">All Items</button>
            <button class="filter-btn" onclick="filterMenu('Mains')">Mains</button>
            <button class="filter-btn" onclick="filterMenu('Healthy')">Healthy Choices</button>
            <button class="filter-btn" onclick="filterMenu('Combos')">Combos</button>
            <button class="filter-btn" onclick="filterMenu('Beverages')">Beverages</button>
            <button class="filter-btn" onclick="filterMenu('Trending')">
                <i class="fas fa-fire" style="color:#ef4444;"></i> Trending
            </button>
        </div>
        
        <div class="menu-items">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM cafeteria_menu WHERE is_active=1");
            while ($m = mysqli_fetch_assoc($res)) {
                $icon = 'fa-utensils';
                if (stripos($m['item_name'], 'burger') !== false) $icon = 'fa-hamburger';
                if (stripos($m['item_name'], 'latte') !== false || stripos($m['item_name'], 'americano') !== false) $icon = 'fa-coffee';
                if (stripos($m['item_name'], 'vegan') !== false || stripos($m['item_name'], 'keto') !== false) $icon = 'fa-seedling';
                if (stripos($m['item_name'], 'fries') !== false) $icon = 'fa-french-fries';
                
                $jsData = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');
                $dis_class = $m['stock'] == 0 ? 'disabled' : '';
                
                echo "
                <div class='menu-card menu-item-dom {$dis_class}' data-cat='{$m['category']}' data-trend='{$m['is_trending']}'>
                    <div class='nutri-modal' id='nutri-{$m['id']}'>
                        <div style='display:flex; justify-content:space-between; margin-bottom:15px;'>
                            <strong style='font-size:1.1rem;'>Nutrition Facts</strong>
                            <i class='fas fa-times' style='cursor:pointer;' onclick=\"document.getElementById('nutri-{$m['id']}').style.display='none'\"></i>
                        </div>
                        <div style='font-size:0.8rem; margin-bottom:5px; display:flex; justify-content:space-between;'><span>Calories</span><strong>{$m['calories']}</strong></div>
                        <div style='font-size:0.8rem; margin-bottom:5px; display:flex; justify-content:space-between;'><span>Protein</span><strong>{$m['protein_g']}g</strong></div>
                        <div style='font-size:0.8rem; margin-bottom:5px; display:flex; justify-content:space-between;'><span>Carbs</span><strong>{$m['carbs_g']}g</strong></div>
                        <div style='font-size:0.8rem; margin-bottom:5px; display:flex; justify-content:space-between;'><span>Fat</span><strong>{$m['fat_g']}g</strong></div>
                        <div style='font-size:0.8rem; margin-top:10px; color:#ef4444;'>Allergens: {$m['allergens']}</div>
                    </div>
                    
                    " . ($m['discount_pct'] > 0 ? "<div style='position:absolute; top:10px; right:10px; background:#ef4444; color:#fff; font-size:0.7rem; font-weight:700; padding:4px 8px; border-radius:12px; z-index:2;'>-{$m['discount_pct']}%</div>" : "") . "
                    
                    <div class='mc-head'>
                        <div class='mc-img-ph'><i class='fas {$icon}'></i></div>
                        <div class='mc-price'>₱" . number_format($m['price'], 2) . "</div>
                    </div>
                    
                    <div class='mc-title'>{$m['item_name']}</div>
                    
                    <div class='mc-tags'>
                        <span class='tag' style='cursor:pointer;' onclick=\"document.getElementById('nutri-{$m['id']}').style.display='flex'\">
                            <i class='fas fa-info-circle'></i> {$m['calories']} kcal
                        </span>
                        " . ($m['dietary_badge'] != 'None' ? "<span class='tag tag-diet'>{$m['dietary_badge']}</span>" : "") . "
                        " . ($m['spiciness_level'] > 0 ? "<span class='tag tag-alg'>" . str_repeat('🌶️', $m['spiciness_level']) . "</span>" : "") . "
                        " . ($m['stock'] < 5 && $m['stock'] > 0 ? "<span class='tag' style='color:#ef4444;'>Only {$m['stock']} left</span>" : "") . "
                    </div>
                    
                    <div class='mc-macros'>
                        <div style='display:flex; align-items:center; flex:1;'>
                            <span style='width:15px;'>P</span>
                            <div class='macro-bar'><div class='macro-fill' style='width:" . min(100, $m['protein_g'] * 2) . "%; background:#3b82f6;'></div></div>
                        </div>
                        <div style='display:flex; align-items:center; flex:1;'>
                            <span style='width:15px;'>C</span>
                            <div class='macro-bar'><div class='macro-fill' style='width:" . min(100, $m['carbs_g'] * 1.5) . "%; background:#f59e0b;'></div></div>
                        </div>
                        <div style='display:flex; align-items:center; flex:1;'>
                            <span style='width:15px;'>F</span>
                            <div class='macro-bar'><div class='macro-fill' style='width:" . min(100, $m['fat_g'] * 3) . "%; background:#ef4444;'></div></div>
                        </div>
                    </div>
                    
                    <div class='mc-action'>
                        <button class='btn-add' onclick='addToCart({$jsData})'>" . ($m['stock'] == 0 ? "Out of Stock" : "Add to Order") . "</button>
                    </div>
                </div>";
            }
            ?>
        </div>
    </div>

    <div class="cart-section">
        <div class="cart-panel">
            <div class="cart-head">
                <span>Your Order</span>
                <span id="cartCount" style="background:var(--brand-secondary); color:#fff; padding:2px 10px; border-radius:12px; font-size:0.85rem;">0</span>
            </div>
            
            <div class="cart-body" id="cartBody">
                <div style="text-align:center; padding:40px 20px; color:var(--text-light);">
                    <i class="fas fa-shopping-bag" style="font-size:3rem; margin-bottom:15px; opacity:0.2;"></i>
                    <div style="font-size:0.9rem; font-weight:500;">Your tray is empty</div>
                </div>
            </div>
            
            <div class="cart-opts">
                <div class="opt-row">
                    <span>Pickup Time</span>
                    <select class="opt-select" id="pickupTime">
                        <option value="ASAP">ASAP (<?= $wait_time ?>m)</option>
                        <option value="12:00 PM">12:00 PM</option>
                        <option value="01:00 PM">01:00 PM</option>
                    </select>
                </div>
                
                <div class="opt-row">
                    <span>Eco-Packaging (+₱15)</span>
                    <input type="checkbox" class="toggle-switch" id="ecoPack" onchange="updateCartTotals()">
                </div>
                
                <div class="opt-row">
                    <span>Donate to Pantry</span>
                    <select class="opt-select" id="donation" onchange="updateCartTotals()">
                        <option value="0">No</option>
                        <option value="10">₱10.00</option>
                        <option value="50">₱50.00</option>
                    </select>
                </div>
                
                <div class="opt-row">
                    <span>Promo Code</span>
                    <input type="text" id="promoCode" class="opt-select" style="width:100px;" placeholder="CODE" onblur="applyPromo()">
                </div>
                
                <input type="text" id="orderNotes" class="opt-select" style="width:100%; margin-top:5px;" placeholder="Add kitchen instructions...">
            </div>
            
            <div class="cart-foot">
                <div class="cf-row">
                    <span>Subtotal</span>
                    <span id="subTotal">₱0.00</span>
                </div>
                <div class="cf-row">
                    <span>Fees & Donations</span>
                    <span id="feeTotal">₱0.00</span>
                </div>
                <div class="cf-row" style="color:#ef4444; display:none;" id="discountRow">
                    <span>Discount</span>
                    <span id="discountTotal">-₱0.00</span>
                </div>
                <div class="cf-row" style="color:#10b981;">
                    <span>Est. Calories</span>
                    <span id="calTotal">0 kcal</span>
                </div>
                
                <div class="cf-total">
                    <span>Total</span>
                    <span id="grandTotal">₱0.00</span>
                </div>
                
                <form method="POST" id="checkoutForm">
                    <input type="hidden" name="place_order" value="1">
                    <input type="hidden" name="total_amount" id="formTotal">
                    <input type="hidden" name="order_data" id="formData">
                    <input type="hidden" name="pickup_time" id="formTime">
                    <input type="hidden" name="promo_code" id="formPromo">
                    <input type="hidden" name="donation_amt" id="formDonation">
                    <input type="hidden" name="kitchen_notes" id="formNotes">
                    <input type="hidden" name="payment_method" value="Campus Cash">
                    <input type="hidden" name="loyalty_earned" id="formLoyalty">
                    
                    <button type="button" onclick="processCheckout()" class="btn-checkout">
                        Pay with Campus Cash <i class="fas fa-fingerprint"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="qr-modal" id="qrModal">
    <div class="qr-box">
        <div style="width:60px; height:60px; background:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.8rem; margin:0 auto 20px;">
            <i class="fas fa-check"></i>
        </div>
        <h2 style="font-weight:700; font-size:1.5rem; margin-bottom:8px; color:var(--text-dark);">Order Confirmed</h2>
        <p style="font-size:0.9rem; color:var(--text-light); margin-bottom:25px;">Present this code at the pickup counter.</p>
        
        <div id="qrcode" style="background:#fff; padding:15px; border-radius:12px; display:inline-block; margin-bottom:20px; border:1px solid var(--border-color);"></div>
        
        <div style="background:var(--bg-grid); padding:12px; border-radius:8px; margin-bottom:25px;">
            <div style="font-size:0.75rem; font-weight:600; color:var(--text-light); text-transform:uppercase;">Order Hash</div>
            <div id="hashDisplay" style="font-family:monospace; font-size:1rem; font-weight:700; color:var(--text-dark); word-break:break-all;"></div>
        </div>
        
        <a href="cafeteria.php" style="display:block; background:var(--text-dark); color:var(--food-bg); text-decoration:none; padding:12px; border-radius:10px; font-weight:600;">Close Receipt</a>
    </div>
</div>

<script>
let cart = {};
let promoMultiplier = 1;
let campusCash = <?= $campus_cash ?>;

function filterMenu(cat) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    document.querySelectorAll('.menu-item-dom').forEach(el => {
        if (cat === 'All' || el.getAttribute('data-cat') === cat || (cat === 'Trending' && el.getAttribute('data-trend') === '1')) {
            el.style.display = 'flex';
        } else {
            el.style.display = 'none';
        }
    });
}

function addToCart(item) {
    let finalPrice = item.price - (item.price * (item.discount_pct / 100));
    if (cart[item.id]) {
        cart[item.id].qty++;
    } else {
        cart[item.id] = { ...item, cart_price: finalPrice, qty: 1 };
    }
    renderCart();
}

function updateQty(id, delta) {
    if (cart[id]) {
        cart[id].qty += delta;
        if (cart[id].qty <= 0) {
            delete cart[id];
        }
        renderCart();
    }
}

function applyPromo() {
    const code = document.getElementById('promoCode').value.toUpperCase();
    if (code === 'CAMPUS20') {
        promoMultiplier = 0.8;
        systemToast('20% Discount Applied');
    } else {
        promoMultiplier = 1;
    }
    updateCartTotals();
}

function renderCart() {
    const body = document.getElementById('cartBody');
    let html = '';
    let count = 0;
    
    for (let id in cart) {
        const item = cart[id];
        count += item.qty;
        
        html += `
        <div class="cart-item">
            <div>
                <div class="ci-name">${item.item_name}</div>
                <div class="ci-price">₱${(item.cart_price * item.qty).toFixed(2)}</div>
            </div>
            <div class="ci-ctrl">
                <button class="ci-btn" onclick="updateQty(${id}, -1)">-</button>
                <span style="font-size:0.85rem; font-weight:600; width:15px; text-align:center;">${item.qty}</span>
                <button class="ci-btn" onclick="updateQty(${id}, 1)">+</button>
            </div>
        </div>`;
    }
    
    if (count === 0) {
        html = `
        <div style="text-align:center; padding:40px 20px; color:var(--text-light);">
            <i class="fas fa-shopping-bag" style="font-size:3rem; margin-bottom:15px; opacity:0.2;"></i>
            <div style="font-size:0.9rem; font-weight:500;">Your tray is empty</div>
        </div>`;
    }
    
    body.innerHTML = html;
    document.getElementById('cartCount').innerText = count;
    updateCartTotals();
}

function updateCartTotals() {
    let sub = 0;
    let cals = 0;
    
    for (let id in cart) {
        sub += (cart[id].cart_price * cart[id].qty);
        cals += (cart[id].calories * cart[id].qty);
    }
    
    let fees = 0;
    if (document.getElementById('ecoPack').checked && sub > 0) {
        fees += 15;
    }
    
    let don = parseFloat(document.getElementById('donation').value);
    fees += don;
    
    let discountAmt = 0;
    if (promoMultiplier < 1 && sub > 0) {
        discountAmt = sub * (1 - promoMultiplier);
        document.getElementById('discountRow').style.display = 'flex';
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
    
    let grand = (sub - discountAmt) + fees;
    let pts = Math.floor(grand * 0.05);
    
    document.getElementById('subTotal').innerText = '₱' + sub.toFixed(2);
    document.getElementById('feeTotal').innerText = '₱' + fees.toFixed(2);
    document.getElementById('discountTotal').innerText = '-₱' + discountAmt.toFixed(2);
    document.getElementById('calTotal').innerText = cals + ' kcal';
    document.getElementById('grandTotal').innerText = '₱' + grand.toFixed(2);
    
    document.getElementById('formTotal').value = grand.toFixed(2);
    document.getElementById('formDonation').value = don;
    document.getElementById('formPromo').value = document.getElementById('promoCode').value;
    document.getElementById('formLoyalty').value = pts;
    
    let orderData = { 
        items: cart, 
        eco: document.getElementById('ecoPack').checked, 
        cals: cals 
    };
    document.getElementById('formData').value = JSON.stringify(orderData);
}

function processCheckout() {
    if (Object.keys(cart).length === 0) {
        if (typeof systemToast === 'function') systemToast("Tray is empty.");
        return;
    }
    
    let total = parseFloat(document.getElementById('formTotal').value);
    if (total > campusCash) {
        if (typeof systemToast === 'function') systemToast("Insufficient Campus Cash balance.");
        return;
    }
    
    document.getElementById('formTime').value = document.getElementById('pickupTime').value;
    document.getElementById('formNotes').value = document.getElementById('orderNotes').value;
    document.getElementById('checkoutForm').submit();
}

<?php if(isset($_GET['success']) && isset($_GET['receipt'])): ?>
document.addEventListener('DOMContentLoaded', () => {
    const hash = '<?= htmlspecialchars($_GET['receipt']) ?>';
    document.getElementById('hashDisplay').innerText = hash;
    document.getElementById('qrModal').style.display = 'flex';
    
    new QRCode(document.getElementById("qrcode"), {
        text: "CAMPUS_ORDER:" + hash,
        width: 160,
        height: 160,
        colorDark : "#0f172a",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>