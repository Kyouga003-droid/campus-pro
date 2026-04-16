<?php 
include 'config.php'; 

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

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM library_catalog WHERE id = $id");
    header("Location: library.php"); exit();
}

if(isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $res = mysqli_query($conn, "SELECT status FROM library_catalog WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $cur = $row['status'];
        if($cur == 'Available') $nxt = 'Borrowed';
        elseif($cur == 'Borrowed') $nxt = 'Lost / Damaged';
        else $nxt = 'Available';
        mysqli_query($conn, "UPDATE library_catalog SET status = '$nxt' WHERE id = $id");
        header("Location: library.php"); exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_book'])) {
    $ib = mysqli_real_escape_string($conn, $_POST['isbn']);
    $ti = mysqli_real_escape_string($conn, $_POST['title']);
    $au = mysqli_real_escape_string($conn, $_POST['author']);
    $ca = mysqli_real_escape_string($conn, $_POST['category']);
    $yr = intval($_POST['publish_year']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE library_catalog SET isbn='$ib', title='$ti', author='$au', category='$ca', publish_year=$yr, status='$st' WHERE id=$id");
    } else {
        $ad = date('Y-m-d');
        mysqli_query($conn, "INSERT INTO library_catalog (isbn, title, author, category, publish_year, status, added_date) VALUES ('$ib', '$ti', '$au', '$ca', $yr, '$st', '$ad')");
    }
    header("Location: library.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_books = [
        ['Introduction to Algorithms', 'Thomas H. Cormen', 'STEM', 2009],
        ['Clean Code', 'Robert C. Martin', 'STEM', 2008],
        ['University Physics', 'Hugh D. Young', 'STEM', 2019],
        ['Organic Chemistry', 'Paula Y. Bruice', 'STEM', 2015],
        ['Calculus Early Transcendentals', 'James Stewart', 'STEM', 2020],
        ['Design Patterns', 'Erich Gamma', 'STEM', 1994],
        ['Artificial Intelligence', 'Stuart Russell', 'STEM', 2021],
        ['The Pragmatic Programmer', 'Andrew Hunt', 'STEM', 1999],
        ['Campbell Biology', 'Lisa A. Urry', 'STEM', 2017],
        ['Discrete Mathematics', 'Kenneth Rosen', 'STEM', 2018],
        ['The Lean Startup', 'Eric Ries', 'Business', 2011],
        ['Thinking, Fast and Slow', 'Daniel Kahneman', 'Business', 2011],
        ['Principles of Economics', 'N. Gregory Mankiw', 'Business', 2020],
        ['Marketing Management', 'Philip Kotler', 'Business', 2015],
        ['Financial Accounting', 'Jerry J. Weygandt', 'Business', 2018],
        ['Macroeconomics', 'Paul Krugman', 'Business', 2019],
        ['The Innovators Dilemma', 'Clayton M. Christensen', 'Business', 1997],
        ['Good to Great', 'Jim Collins', 'Business', 2001],
        ['Zero to One', 'Peter Thiel', 'Business', 2014],
        ['Hooked', 'Nir Eyal', 'Business', 2014],
        ['1984', 'George Orwell', 'Literature', 1949],
        ['To Kill a Mockingbird', 'Harper Lee', 'Literature', 1960],
        ['The Great Gatsby', 'F. Scott Fitzgerald', 'Literature', 1925],
        ['Pride and Prejudice', 'Jane Austen', 'Literature', 1813],
        ['Moby Dick', 'Herman Melville', 'Literature', 1851],
        ['The Catcher in the Rye', 'J.D. Salinger', 'Literature', 1951],
        ['Fahrenheit 451', 'Ray Bradbury', 'Literature', 1953],
        ['Brave New World', 'Aldous Huxley', 'Literature', 1932],
        ['Jane Eyre', 'Charlotte Bronte', 'Literature', 1847],
        ['Animal Farm', 'George Orwell', 'Literature', 1945],
        ['Sapiens', 'Yuval Noah Harari', 'Humanities', 2011],
        ['Guns, Germs, and Steel', 'Jared Diamond', 'Humanities', 1997],
        ['A Peoples History of the United States', 'Howard Zinn', 'Humanities', 1980],
        ['The Silk Roads', 'Peter Frankopan', 'Humanities', 2015],
        ['Meditations', 'Marcus Aurelius', 'Humanities', 180],
        ['The Republic', 'Plato', 'Humanities', 380],
        ['Critique of Pure Reason', 'Immanuel Kant', 'Humanities', 1781],
        ['Leviathan', 'Thomas Hobbes', 'Humanities', 1651],
        ['The Prince', 'Niccolo Machiavelli', 'Humanities', 1532],
        ['Beyond Good and Evil', 'Friedrich Nietzsche', 'Humanities', 1886],
        ['The Story of Art', 'E.H. Gombrich', 'Arts & Design', 1950],
        ['Ways of Seeing', 'John Berger', 'Arts & Design', 1972],
        ['Interaction of Color', 'Josef Albers', 'Arts & Design', 1963],
        ['Design of Everyday Things', 'Don Norman', 'Arts & Design', 1988],
        ['Thinking with Type', 'Ellen Lupton', 'Arts & Design', 2004],
        ['Grid Systems', 'Josef Muller-Brockmann', 'Arts & Design', 1981],
        ['Art of Color', 'Johannes Itten', 'Arts & Design', 1961],
        ['Steal Like an Artist', 'Austin Kleon', 'Arts & Design', 2012],
        ['Graphic Design: The New Basics', 'Ellen Lupton', 'Arts & Design', 2008],
        ['Elements of Typographic Style', 'Robert Bringhurst', 'Arts & Design', 1992]
    ];
    foreach($seed_books as $idx => $b) {
        $ti = mysqli_real_escape_string($conn, $b[0]);
        $au = mysqli_real_escape_string($conn, $b[1]);
        $ca = mysqli_real_escape_string($conn, $b[2]);
        $yr = intval($b[3]);
        $isbn = "978-0-" . rand(10, 99) . "-" . rand(100000, 999999) . "-" . rand(0, 9);
        
        $st_rand = rand(1, 100);
        if($st_rand <= 70) $stat = 'Available';
        elseif($st_rand <= 95) $stat = 'Borrowed';
        else $stat = 'Lost / Damaged';
        
        $ad = date('Y-m-d', strtotime('-'.rand(1, 1000).' days'));
        mysqli_query($conn, "INSERT INTO library_catalog (isbn, title, author, category, publish_year, status, added_date) VALUES ('$isbn', '$ti', '$au', '$ca', $yr, '$stat', '$ad')");
    }
}

include 'header.php';

$total = getCount($conn, 'library_catalog');
$avail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Available'"))['c'];
$borrowed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Borrowed'"))['c'];
$lost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Lost / Damaged'"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 16px; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: var(--brand-secondary); opacity: 0.9; padding: 15px; background: var(--main-bg); border-radius: 12px; border: 1px solid var(--border-color);}
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 25px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap: wrap; gap: 15px;}
    
    .status-avail { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-borrow { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .status-lost { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    
    .isbn-box { font-weight:900; font-family:monospace; font-size:1.1rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:4px 10px; border-radius:6px; display:inline-block; letter-spacing: 1px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .isbn-box { color: var(--brand-primary); }

    /* VIEW TOGGLE CSS */
    .view-toggle { display: flex; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .view-btn { padding: 10px 18px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1.1rem; border:none; background:transparent;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--brand-secondary); color: var(--brand-primary); font-weight: 900;}
    [data-theme="light"] .view-btn.active-view { background: var(--brand-primary); color: #fff;}

    /* WINDOWED GRID VIEW CSS */
    .lib-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .lib-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .lib-card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="light"] .lib-card:hover { border-color: var(--brand-primary); }
    .lib-card.dimmed { opacity: 0.55; filter: grayscale(80%); }
    
    .lc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
    .lc-title { font-family: var(--heading-font); font-size: 1.3rem; font-weight: 900; color: var(--text-dark); margin-bottom: 8px; line-height: 1.3;}
    .lc-detail { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-bottom: 8px; font-weight: 600;}
    .lc-detail i { color: var(--brand-secondary); width: 16px; text-align: center;}
    [data-theme="light"] .lc-detail i { color: var(--brand-primary); }
    
    .lc-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #6366f1;">
    <h1 style="color: #6366f1; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Digital Archive & Library</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Manage digital catalogs, physical circulations, and book acquisitions.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-book stat-icon" style="color:#6366f1;"></i>
        <div>
            <div class="stat-val" style="color:#6366f1;"><?= $total ?></div>
            <div class="stat-lbl">Total Volumes</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $avail ?></div>
            <div class="stat-lbl">Available on Shelf</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-heart stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $borrowed ?></div>
            <div class="stat-lbl">Active Loans</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-times-circle stat-icon" style="color:#ef4444;"></i>
        <div>
            <div class="stat-val" style="color:#ef4444;"><?= $lost ?></div>
            <div class="stat-lbl">Lost / Damaged</div>
        </div>
    </div>
</div>

<?php
$inv_pct = $total > 0 ? ($avail / $total) * 100 : 0;
$bar_color = $inv_pct > 70 ? '#10b981' : ($inv_pct > 40 ? '#f59e0b' : '#ef4444');
?>
<div class="card" style="padding: 25px; margin-bottom: 30px; display:flex; align-items:center; gap:20px;">
    <div style="font-weight:900; color:var(--text-dark); font-size:1.1rem; white-space:nowrap;"><i class="fas fa-chart-bar" style="color:var(--brand-secondary); margin-right:8px;"></i> Shelf Inventory Health:</div>
    <div style="flex-grow:1; height:12px; background:var(--bg-grid); border: 2px solid var(--border-color); border-radius:10px; overflow:hidden;">
        <div style="height:100%; width:<?= $inv_pct ?>%; background:<?= $bar_color ?>; transition: 1s ease-out;"></div>
    </div>
    <div style="font-weight:900; font-family:var(--heading-font); font-size:1.5rem; color:<?= $bar_color ?>;"><?= number_format($inv_pct, 1) ?>%</div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button id="btnViewTable" class="view-btn" onclick="setView('table')" title="List View"><i class="fas fa-list"></i></button>
            <button id="btnViewGrid" class="view-btn" onclick="setView('grid')" title="Windowed Grid View"><i class="fas fa-th-large"></i></button>
        </div>

        <input type="text" id="searchLibraryLocal" onkeyup="filterLibrary()" placeholder="&#xf002; Search Title, Author, ISBN..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 280px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        
        <select id="filterCat" onchange="filterLibrary()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Categories">All Categories</option>
            <?php
            $c_res = mysqli_query($conn, "SELECT DISTINCT category FROM library_catalog ORDER BY category ASC");
            while($c = mysqli_fetch_assoc($c_res)) { echo "<option value='{$c['category']}'>{$c['category']}</option>"; }
            ?>
        </select>
        <select id="filterStatus" onchange="filterLibrary()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Availability</option>
            <option value="Available">Available</option>
            <option value="Borrowed">Borrowed</option>
            <option value="Lost / Damaged">Lost / Damaged</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Catalog to CSV...')"><i class="fas fa-file-csv"></i> Export Data</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#6366f1; border-color:#6366f1; color:#fff;" onclick="openLibraryModal()"><i class="fas fa-plus"></i> Catalog Book</button>
    </div>
</div>

<div id="tableView" class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:25%;">Volume Details</th>
                <th>Author & ISBN</th>
                <th style="width:20%;">Classification</th>
                <th>Availability</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="libraryTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM library_catalog ORDER BY status ASC, title ASC");
            $all_books = [];
            
            while($row = mysqli_fetch_assoc($res)) {
                $all_books[] = $row;
                $st = $row['status'];
                if($st == 'Available') $st_class = 'status-avail';
                elseif($st == 'Borrowed') $st_class = 'status-borrow';
                else $st_class = 'status-lost';
                
                $row_style = $st == 'Lost / Damaged' ? "opacity: 0.55; filter: grayscale(80%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $cat = $row['category'];
                $icon = 'fa-book';
                if($cat == 'STEM') $icon = 'fa-flask';
                if($cat == 'Business') $icon = 'fa-chart-line';
                if($cat == 'Literature') $icon = 'fa-book-open';
                if($cat == 'Humanities') $icon = 'fa-landmark';
                if($cat == 'Arts & Design') $icon = 'fa-palette';

                $pub_tag = "<span style='display:inline-block; font-size:0.7rem; font-weight:900; background:var(--bg-grid); border:1px solid var(--border-color); padding:2px 6px; border-radius:4px; margin-top:8px; color:var(--text-light);'>Published: {$row['publish_year']}</span>";

                $risk_html = "";
                if($st == 'Borrowed') {
                    $rand_risk = rand(1, 10);
                    if($rand_risk > 8) $risk_html = "<div style='color:var(--brand-crimson); font-weight:900; margin-top:6px; font-size:0.75rem;'><i class='fas fa-exclamation-circle'></i> OVERDUE</div>";
                    else $risk_html = "<div style='color:var(--brand-secondary); font-weight:800; margin-top:6px; font-size:0.75rem;'><i class='far fa-clock'></i> On Loan</div>";
                }

                echo "
                <tr class='filter-target' style='$row_style' data-stat='{$st}' data-cat='{$cat}'>
                    <td>
                        <strong style='color:var(--text-dark); font-size:1.15rem; font-family:var(--heading-font);'>{$row['title']}</strong><br>
                        {$pub_tag}
                    </td>
                    <td>
                        <div style='font-weight:800; color:var(--text-dark); font-size:1.05rem;'>{$row['author']}</div>
                        <div class='isbn-box' style='margin-top:6px;'>{$row['isbn']}</div>
                    </td>
                    <td>
                        <strong style='color:var(--brand-secondary); text-transform:uppercase; font-size:0.9rem;'><i class='fas {$icon}' style='margin-right:6px;'></i> {$cat}</strong>
                    </td>
                    <td>
                        <span class='status-pill {$st_class}'><i class='fas fa-circle' style='font-size:0.5rem; vertical-align:middle; margin-right:6px;'></i> {$st}</span>
                        {$risk_html}
                    </td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openLibraryModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
                            <a href='?toggle_status={$row['id']}' class='table-btn' style='border-color:#f59e0b; color:#f59e0b;' onclick='systemToast(\"Updating Circulation Status...\")'><i class='fas fa-sync-alt'></i></a>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Decommissioning Volume...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="gridView" class="lib-grid" style="display:none;">
    <?php
    foreach($all_books as $row) {
        $st = $row['status'];
        if($st == 'Available') $st_class = 'status-avail';
        elseif($st == 'Borrowed') $st_class = 'status-borrow';
        else $st_class = 'status-lost';
        
        $dim_class = $st == 'Lost / Damaged' ? 'dimmed' : '';
        $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        
        $cat = $row['category'];
        $icon = 'fa-book';
        $bdr_color = "var(--border-color)";
        if($cat == 'STEM') { $icon = 'fa-flask'; $bdr_color = '#3b82f6'; }
        if($cat == 'Business') { $icon = 'fa-chart-line'; $bdr_color = '#10b981'; }
        if($cat == 'Literature') { $icon = 'fa-book-open'; $bdr_color = '#ec4899'; }
        if($cat == 'Humanities') { $icon = 'fa-landmark'; $bdr_color = '#f59e0b'; }
        if($cat == 'Arts & Design') { $icon = 'fa-palette'; $bdr_color = '#8b5cf6'; }
        if($st == 'Lost / Damaged') $bdr_color = '#ef4444'; // Red override

        echo "
        <div class='lib-card filter-target {$dim_class}' style='border-top: 6px solid {$bdr_color};' data-stat='{$st}' data-cat='{$cat}'>
            <div class='lc-header'>
                <div class='isbn-box' style='font-size:0.85rem; padding:4px 8px;'>{$row['isbn']}</div>
                <span class='status-pill {$st_class}' style='font-size:0.65rem;'>{$st}</span>
            </div>
            
            <div class='lc-title'>{$row['title']}</div>
            <div style='font-size:0.95rem; font-weight:800; color:var(--text-dark); margin-bottom:15px;'>{$row['author']}</div>
            
            <div class='lc-detail'><i class='fas {$icon}'></i> {$cat}</div>
            <div class='lc-detail'><i class='fas fa-calendar-alt'></i> Published: {$row['publish_year']}</div>
            
            <div class='lc-footer'>
                <div style='display:flex; gap:8px; margin-left:auto;'>
                    <button class='table-btn btn-resolve' style='padding:6px 10px;' onclick='openLibraryModal($js_data)'><i class='fas fa-pen' style='margin:0;'></i></button>
                    <a href='?toggle_status={$row['id']}' class='table-btn' style='padding:6px 10px; border-color:#f59e0b; color:#f59e0b;'><i class='fas fa-sync-alt' style='margin:0;'></i></a>
                </div>
            </div>
        </div>";
    }
    ?>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-book" style="color:#6366f1;"></i> Catalog Volume</h2>
        <form method="POST">
            <input type="hidden" name="save_book" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="isbn" id="isbn" placeholder="ISBN-13 (e.g. 978-0-00-000000-0)" required>
                <input type="number" name="publish_year" id="publish_year" placeholder="Publication Year" required>
                
                <input type="text" name="title" id="title" placeholder="Volume Title" style="grid-column: span 2;" required>
                <input type="text" name="author" id="author" placeholder="Primary Author" style="grid-column: span 2;" required>
                
                <select name="category" id="category" required>
                    <option value="STEM">STEM</option>
                    <option value="Business">Business</option>
                    <option value="Literature">Literature</option>
                    <option value="Humanities">Humanities</option>
                    <option value="Arts & Design">Arts & Design</option>
                    <option value="General">General / Other</option>
                </select>

                <select name="status" id="status" required>
                    <option value="Available">Available</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Lost / Damaged">Lost / Damaged</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#6366f1; border-color:#6366f1; color:#fff;"><i class="fas fa-save"></i> Save Catalog Data</button>
        </form>
    </div>
</div>

<script>
// VIEW CONTROLLER
function setView(view) {
    const table = document.getElementById('tableView');
    const grid = document.getElementById('gridView');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');

    if(view === 'grid') {
        table.style.display = 'none';
        grid.style.display = 'grid';
        btnGrid.classList.add('active-view');
        btnTable.classList.remove('active-view');
        localStorage.setItem('campus_library_view', 'grid');
    } else {
        table.style.display = 'block';
        grid.style.display = 'none';
        btnTable.classList.add('active-view');
        btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_library_view', 'table');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const pref = localStorage.getItem('campus_library_view') || 'table';
    setView(pref);
});

// FEATURE 6 & 7: Chained Filter Engine
function filterLibrary() {
    const cFilter = document.getElementById('filterCat').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchLibraryLocal').value.toLowerCase();
    
    // Select targets in BOTH views
    const targets = document.querySelectorAll('.filter-target');
    
    targets.forEach(el => {
        const rCat = el.getAttribute('data-cat');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        
        let show = true;
        if (cFilter !== 'All Categories' && rCat !== cFilter) show = false;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) {
            el.removeAttribute('data-hide-local');
            el.style.display = '';
        } else {
            el.setAttribute('data-hide-local', 'true');
            el.style.display = 'none';
        }
    });

    if(typeof globalTableSearch === 'function') globalTableSearch();
}

// FEATURE 14: Inline JSON Data Binding
function openLibraryModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#6366f1;"></i> Edit Catalog Entry';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('isbn').value = data.isbn || '';
        document.getElementById('title').value = data.title || '';
        document.getElementById('author').value = data.author || '';
        document.getElementById('category').value = data.category || 'STEM';
        document.getElementById('publish_year').value = data.publish_year || '';
        document.getElementById('status').value = data.status || 'Available';
    } else {
        title.innerHTML = '<i class="fas fa-book" style="color:#6366f1;"></i> Catalog Volume';
        document.getElementById('edit_id').value = '';
        document.getElementById('isbn').value = `978-0-${Math.floor(10+Math.random()*90)}-${Math.floor(100000+Math.random()*900000)}-${Math.floor(Math.random()*10)}`;
        document.getElementById('title').value = '';
        document.getElementById('author').value = '';
        document.getElementById('category').value = 'STEM';
        document.getElementById('publish_year').value = new Date().getFullYear();
        document.getElementById('status').value = 'Available';
    }
    
    modal.style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>