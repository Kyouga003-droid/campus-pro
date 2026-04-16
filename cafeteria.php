<?php
include 'config.php';

$patch_queries = [
    "CREATE TABLE IF NOT EXISTS cafeteria_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        category VARCHAR(50),
        description TEXT,
        price DECIMAL(10,2),
        prep_time INT,
        tags VARCHAR(100),
        icon VARCHAR(50)
    )"
];
foreach($patch_queries as $q) { try { mysqli_query($conn, $q); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM cafeteria_items WHERE id = $id");
    header("Location: cafeteria.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_cafeteria_item'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $c = mysqli_real_escape_string($conn, $_POST['category']);
    $d = mysqli_real_escape_string($conn, $_POST['description']);
    $p = floatval($_POST['price']);
    $pt = intval($_POST['prep_time']);
    $t = mysqli_real_escape_string($conn, $_POST['tags']);
    $i = mysqli_real_escape_string($conn, $_POST['icon']);
    mysqli_query($conn, "INSERT INTO cafeteria_items (name, category, description, price, prep_time, tags, icon) VALUES ('$n', '$c', '$d', $p, $pt, '$t', '$i')");
    header("Location: cafeteria.php");
    exit();
}

$check_empty = mysqli_query($conn, "SELECT COUNT(*) as c FROM cafeteria_items");
if(mysqli_fetch_assoc($check_empty)['c'] == 0) {
    $seed_data = [
        ['Classic Cheeseburger', 'Mains', 'Angus beef, cheddar, lettuce, tomato, house sauce', 350.00, 10, 'Bestseller', 'fa-hamburger'],
        ['Spicy Chicken Sandwich', 'Mains', 'Crispy chicken, pepper jack, spicy mayo', 380.00, 12, 'Spicy', 'fa-drumstick-bite'],
        ['Margherita Pizza', 'Mains', 'Fresh mozzarella, basil, marinara, thin crust', 450.00, 15, 'Vegetarian', 'fa-pizza-slice'],
        ['Caesar Salad', 'Mains', 'Romaine, parmesan, croutons, creamy dressing', 250.00, 5, 'Healthy', 'fa-leaf'],
        ['Grilled Salmon Bowl', 'Mains', 'Wild salmon, quinoa, roasted vegetables', 520.00, 15, 'Gluten-Free', 'fa-fish'],
        ['Veggie Wrap', 'Mains', 'Hummus, spinach, peppers, cucumber, feta', 320.00, 5, 'Vegan', 'fa-carrot'],
        ['French Fries', 'Sides', 'Crispy golden fries with sea salt', 120.00, 5, 'Bestseller', 'fa-fry'],
        ['Sweet Potato Fries', 'Sides', 'Served with honey mustard dipping sauce', 150.00, 5, 'Vegan', 'fa-seedling']
    ];
    foreach($seed_data as $item) {
        $n = mysqli_real_escape_string($conn, $item[0]); $c = mysqli_real_escape_string($conn, $item[1]);
        $d = mysqli_real_escape_string($conn, $item[2]); $p = $item[3]; $pt = $item[4];
        $t = mysqli_real_escape_string($conn, $item[5]); $i = mysqli_real_escape_string($conn, $item[6]);
        mysqli_query($conn, "INSERT INTO cafeteria_items (name, category, description, price, prep_time, tags, icon) VALUES ('$n', '$c', '$d', $p, $pt, '$t', '$i')");
    }
}

include 'header.php';
?>

<style>
    .order-layout { display: grid; grid-template-columns: 1fr 380px; gap: 30px; align-items: start; }
    
    .cat-nav { display: flex; gap: 15px; margin-bottom: 30px; overflow-x: auto; padding-bottom: 10px; align-items: center; }
    .cat-pill { padding: 10px 20px; border-radius: 8px; background: var(--card-bg); border: 2px solid var(--border-color); color: var(--text-dark); font-weight: 800; cursor: pointer; transition: 0.2s; white-space: nowrap; font-family: var(--heading-font); letter-spacing: 1px; text-transform: uppercase; box-shadow: var(--glow-shadow);}
    .cat-pill:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); }
    .cat-pill.active { background: var(--brand-secondary); border-color: var(--brand-secondary); color: var(--brand-primary); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--brand-secondary); }

    .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
    .menu-item { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px; padding: 20px; transition: 0.2s; position: relative; display: flex; flex-direction: column; overflow:hidden; box-shadow: var(--glow-shadow);}
    .menu-item:hover { border-color: var(--brand-secondary); transform: translate(-4px, -4px); box-shadow: 6px 6px 0px rgba(0,0,0,0.1); }
    [data-theme="dark"] .menu-item:hover { box-shadow: 6px 6px 0px rgba(252, 157, 1, 0.2); }
    
    .item-del-btn { position: absolute; top: 12px; right: 12px; color: var(--brand-crimson); transition: 0.2s; cursor: pointer; z-index: 10; opacity: 0.5; background: var(--main-bg); padding: 6px; border-radius: 4px; border: 1px solid var(--brand-crimson);}
    .item-del-btn:hover { opacity: 1; transform: scale(1.1); }

    .item-icon { height: 110px; background: var(--main-bg); border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; color: var(--brand-primary); border: 2px solid var(--border-color); }
    [data-theme="dark"] .item-icon { color: var(--brand-secondary); }
    .item-title { font-size: 1.1rem; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; }
    .item-desc { font-size: 0.8rem; color: var(--text-light); line-height: 1.4; flex-grow: 1; margin-bottom: 15px; }
    .item-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .item-price { font-size: 1.3rem; font-weight: 900; color: var(--brand-secondary); font-family: var(--heading-font); }
    [data-theme="light"] .item-price { color: var(--brand-primary); }
    .item-tag { font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; background: rgba(252, 157, 1, 0.1); color: var(--brand-secondary); text-transform: uppercase; border: 1px solid var(--brand-secondary); }
    [data-theme="light"] .item-tag { background: rgba(14, 44, 70, 0.1); color: var(--brand-primary); border-color: var(--brand-primary); }
    
    .add-btn { width: 100%; padding: 10px; background: var(--main-bg); border: 2px solid var(--border-color); color: var(--text-dark); font-weight: 800; border-radius: 6px; cursor: pointer; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px; box-shadow: 2px 2px 0px var(--border-color);}
    .add-btn:active { transform: translate(2px, 2px); box-shadow: none; }
    .add-btn:hover { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary); box-shadow: 2px 2px 0px var(--brand-secondary);}
    
    .cart-panel { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px; padding: 25px; position: sticky; top: 105px; box-shadow: var(--glow-shadow); display: flex; flex-direction: column; max-height: calc(100vh - 130px); }
    .cart-header { font-size: 1.3rem; font-weight: 800; font-family: var(--heading-font); color: var(--text-dark); border-bottom: 2px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; text-transform: uppercase;}
    .cart-items { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; padding-right: 5px; }
    .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 6px; }
    .cart-item-info { flex-grow: 1; margin-left: 10px; }
    .cart-item-title { font-size: 0.85rem; font-weight: 700; color: var(--text-dark); }
    .cart-item-price { font-size: 0.9rem; font-weight: 900; color: var(--brand-secondary); }
    [data-theme="light"] .cart-item-price { color: var(--brand-primary); }
    .qty-ctrl { display: flex; align-items: center; gap: 8px; background: var(--card-bg); padding: 4px 8px; border: 2px solid var(--border-color); border-radius: 4px; }
    .qty-btn { background: none; border: none; color: var(--text-dark); cursor: pointer; font-weight: 900; font-size: 1rem; }
    .cart-totals { border-top: 2px solid var(--border-color); padding-top: 15px; margin-bottom: 20px; }
    .cart-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-light); font-weight: 600; }
    .cart-row.total { font-size: 1.4rem; color: var(--text-dark); font-weight: 900; margin-top: 10px; font-family: var(--heading-font); }
    
    .checkout-btn { width: 100%; padding: 16px; background: var(--brand-secondary); border: 2px solid var(--border-color); color: var(--brand-primary); font-weight: 900; font-size: 1.1rem; border-radius: 8px; cursor: pointer; transition: 0.2s; text-transform: uppercase; letter-spacing: 2px; box-shadow: 4px 4px 0px rgba(0,0,0,0.2); }
    .checkout-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px rgba(0,0,0,0.2); background: var(--brand-accent); color: #fff; border-color: var(--brand-accent); }
    .checkout-btn:active { transform: translate(4px, 4px); box-shadow: none; }
    
    .empty-cart { text-align: center; color: var(--text-light); font-weight: 600; padding: 40px 20px; }
    .empty-cart i { font-size: 3rem; opacity: 0.3; margin-bottom: 15px; }
</style>

<div class="card" style="margin-bottom: 30px; padding: 35px;">
    <h1 style="color: var(--text-dark); font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Campus Dining</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Place your order ahead of time to skip the line.</p>
</div>

<div class="order-layout">
    <div>
        <div class="cat-nav">
            <div class="cat-pill active" onclick="filterMenu('All')">All Items</div>
            <div class="cat-pill" onclick="filterMenu('Mains')">Mains</div>
            <div class="cat-pill" onclick="filterMenu('Sides')">Sides</div>
            <div class="cat-pill" onclick="filterMenu('Beverages')">Beverages</div>
            <div class="cat-pill" onclick="filterMenu('Desserts')">Desserts</div>
            <button onclick="document.getElementById('addModal').style.display='flex';" class="btn-primary" style="margin-left: auto; padding: 10px 20px;"><i class="fas fa-plus"></i> New Item</button>
        </div>

        <div class="menu-grid" id="menuGrid">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM cafeteria_items ORDER BY category ASC, id ASC");
            while($row = mysqli_fetch_assoc($res)) {
                $tag_html = $row['tags'] ? "<span class='item-tag'>{$row['tags']}</span>" : "";
                $price_fmt = number_format($row['price'], 2);
                echo '
                <div class="menu-item" data-category="'.$row['category'].'">
                    <a href="?del='.$row['id'].'" class="item-del-btn" title="Delete Menu Item"><i class="fas fa-trash"></i></a>
                    <div class="item-icon"><i class="fas '.$row['icon'].'"></i></div>
                    <div class="item-title">'.$row['name'].'</div>
                    <div class="item-desc">'.$row['description'].'</div>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <span style="font-size:0.75rem; color:var(--text-light); font-weight:700;"><i class="far fa-clock"></i> '.$row['prep_time'].'m</span>
                        '.$tag_html.'
                    </div>
                    <div class="item-meta">
                        <div class="item-price">₱'.$price_fmt.'</div>
                        <button class="add-btn" onclick="addToCart('.$row['id'].', \''.addslashes($row['name']).'\', '.$row['price'].')" style="width:auto; padding:8px 20px;"><i class="fas fa-plus"></i></button>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <div class="cart-panel">
        <div class="cart-header">
            <span><i class="fas fa-shopping-bag" style="color:var(--brand-secondary); margin-right:10px;"></i> Your Order</span>
            <span id="cartCount" style="background:var(--text-dark); color:var(--main-bg); padding:4px 10px; border-radius:4px; font-size:0.9rem;">0</span>
        </div>
        
        <div class="cart-items" id="cartItems">
            <div class="empty-cart">
                <i class="fas fa-tray"></i>
                <p>Your tray is currently empty.<br>Select items from the menu to begin.</p>
            </div>
        </div>

        <div class="cart-totals">
            <div class="cart-row"><span>Subtotal</span><span id="cartSub">₱0.00</span></div>
            <div class="cart-row"><span>Tax (8%)</span><span id="cartTax">₱0.00</span></div>
            <div class="cart-row total"><span>Total</span><span id="cartTotal">₱0.00</span></div>
        </div>
        
        <button class="checkout-btn" onclick="processCheckout()"><i class="fas fa-credit-card" style="margin-right:10px;"></i> Checkout</button>
    </div>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('addModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 style="font-size: 1.5rem; margin-bottom: 20px; font-family: var(--heading-font); text-transform:uppercase;"><i class="fas fa-utensils" style="color:var(--brand-secondary);"></i> Add Menu Item</h2>
        <form method="POST" action="cafeteria.php">
            <input type="hidden" name="add_cafeteria_item" value="1">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="name" placeholder="Item Name" required>
                <select name="category" required>
                    <option value="Mains">Mains</option>
                    <option value="Sides">Sides & Snacks</option>
                    <option value="Beverages">Beverages</option>
                    <option value="Desserts">Desserts</option>
                </select>
                <input type="number" step="0.01" name="price" placeholder="Price (₱)" required>
                <input type="number" name="prep_time" placeholder="Prep Time (mins)" required>
                <input type="text" name="tags" placeholder="Tags (e.g. Vegan, Spicy)">
                <input type="text" name="icon" placeholder="FA Icon (e.g. fa-burger)" value="fa-utensils">
            </div>
            <textarea name="description" placeholder="Item Description" rows="2" style="margin-top: 15px;" required></textarea>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;"><i class="fas fa-save"></i> Save to Database</button>
        </form>
    </div>
</div>

<script>
let cart = {};

function filterMenu(category) {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    event.target.classList.add('active');
    
    document.querySelectorAll('.menu-item').forEach(item => {
        if(category === 'All' || item.getAttribute('data-category') === category || (category === 'Sides' && item.getAttribute('data-category') === 'Sides')) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function addToCart(id, name, price) {
    if(cart[id]) { cart[id].qty++; } 
    else { cart[id] = { name: name, price: price, qty: 1 }; }
    renderCart();
}

function updateQty(id, delta) {
    if(cart[id]) {
        cart[id].qty += delta;
        if(cart[id].qty <= 0) delete cart[id];
        renderCart();
    }
}

function renderCart() {
    const container = document.getElementById('cartItems');
    let html = '';
    let subtotal = 0;
    let count = 0;

    for(let id in cart) {
        const item = cart[id];
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;
        count += item.qty;
        
        html += `
        <div class="cart-item">
            <div class="qty-ctrl">
                <button class="qty-btn" onclick="updateQty(${id}, -1)">-</button>
                <span style="font-weight:900; width:20px; text-align:center;">${item.qty}</span>
                <button class="qty-btn" onclick="updateQty(${id}, 1)">+</button>
            </div>
            <div class="cart-item-info">
                <div class="cart-item-title">${item.name}</div>
                <div class="cart-item-price">₱${itemTotal.toFixed(2)}</div>
            </div>
        </div>`;
    }

    if(count === 0) {
        html = `<div class="empty-cart"><i class="fas fa-tray"></i><p>Your tray is currently empty.<br>Select items from the menu to begin.</p></div>`;
    }

    container.innerHTML = html;
    document.getElementById('cartCount').innerText = count;
    
    const tax = subtotal * 0.08;
    const total = subtotal + tax;
    
    document.getElementById('cartSub').innerText = `₱${subtotal.toFixed(2)}`;
    document.getElementById('cartTax').innerText = `₱${tax.toFixed(2)}`;
    document.getElementById('cartTotal').innerText = `₱${total.toFixed(2)}`;
}

function processCheckout() {
    if(Object.keys(cart).length === 0) { alert("Please add items to your order first."); return; }
    alert("Order processed successfully! Firing off to the kitchen.");
    cart = {};
    renderCart();
}
</script>

<?php include 'footer.php'; ?>