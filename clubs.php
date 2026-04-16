<?php 
include 'config.php'; 

// FEATURE 1: Auto-Patcher & Sync (Clubs & Roster Tables)
$patch_clubs = "CREATE TABLE IF NOT EXISTS clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id VARCHAR(20) UNIQUE,
    club_name VARCHAR(150),
    category VARCHAR(50),
    president VARCHAR(100),
    advisor VARCHAR(100),
    members_count INT,
    status VARCHAR(20) DEFAULT 'Active',
    established_date DATE
)";
try { mysqli_query($conn, $patch_clubs); } catch (Exception $e) {}

$patch_roster = "CREATE TABLE IF NOT EXISTS club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id VARCHAR(20),
    student_name VARCHAR(100),
    course_year VARCHAR(50),
    role VARCHAR(50) DEFAULT 'Member'
)";
try { mysqli_query($conn, $patch_roster); } catch (Exception $e) {}

// FEATURE 12: Secure Delete Protocol
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $c_res = mysqli_query($conn, "SELECT club_id FROM clubs WHERE id = $id");
    if($c_row = mysqli_fetch_assoc($c_res)) {
        $cid = $c_row['club_id'];
        mysqli_query($conn, "DELETE FROM club_members WHERE club_id = '$cid'"); // Cascade delete roster
    }
    mysqli_query($conn, "DELETE FROM clubs WHERE id = $id");
    header("Location: clubs.php");
    exit();
}

// FEATURE 9 & 10: Universal CRUD Modal Logic
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_club'])) {
    $cid = mysqli_real_escape_string($conn, $_POST['club_id']);
    $cn = mysqli_real_escape_string($conn, $_POST['club_name']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $pr = mysqli_real_escape_string($conn, $_POST['president']);
    $adv = mysqli_real_escape_string($conn, $_POST['advisor']);
    $mc = intval($_POST['members_count']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE clubs SET club_id='$cid', club_name='$cn', category='$cat', president='$pr', advisor='$adv', members_count=$mc, status='$st' WHERE id=$id");
    } else {
        $ed = date('Y-m-d');
        mysqli_query($conn, "INSERT INTO clubs (club_id, club_name, category, president, advisor, members_count, status, established_date) VALUES ('$cid', '$cn', '$cat', '$pr', '$adv', $mc, '$st', '$ed')");
    }
    header("Location: clubs.php");
    exit();
}

// FEATURE 2: Mass 20-Item Auto Seeding (Clubs)
$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM clubs");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_clubs = [
        ['ORG-101', 'Computer Science Society', 'Technology', 'James Smith', 'Dr. Alan Turing', 120, 'Active'],
        ['ORG-102', 'Debate Team', 'Academic', 'Mary Johnson', 'Prof. John Locke', 45, 'Active'],
        ['ORG-103', 'Robotics Club', 'Technology', 'William Brown', 'Dr. Sarah Connor', 85, 'Active'],
        ['ORG-104', 'Drama Guild', 'Arts & Culture', 'Elizabeth Taylor', 'Prof. William Shake', 60, 'Active'],
        ['ORG-105', 'Campus Ministry', 'Community Service', 'David Anderson', 'Rev. Thomas More', 150, 'Active'],
        ['ORG-106', 'Chess Club', 'Sports & Recreation', 'Michael White', 'Dr. Garry Kasp', 30, 'Active'],
        ['ORG-107', 'Film Society', 'Arts & Culture', 'Jessica Martin', 'Prof. Stanley Kub', 55, 'Inactive'],
        ['ORG-108', 'E-Sports Varsity', 'Sports & Recreation', 'Christopher Lee', 'Dr. Hideo Kojima', 200, 'Active'],
        ['ORG-109', 'Photography Club', 'Arts & Culture', 'Matthew Harris', 'Prof. Ansel Adams', 75, 'Active'],
        ['ORG-110', 'Marketing Association', 'Academic', 'Ashley Clark', 'Dr. Philip Kotler', 90, 'Active'],
        ['ORG-111', 'Engineering Council', 'Academic', 'Joshua Lewis', 'Prof. Elon Musk', 180, 'Active'],
        ['ORG-112', 'Dance Troupe', 'Arts & Culture', 'Amanda Robinson', 'Dr. Martha Graham', 65, 'Active'],
        ['ORG-113', 'Literature Club', 'Academic', 'Joseph Walker', 'Prof. Jane Austen', 40, 'Inactive'],
        ['ORG-114', 'Astronomy Society', 'Technology', 'Andrew Perez', 'Dr. Carl Sagan', 50, 'Active'],
        ['ORG-115', 'Culinary Arts Club', 'Arts & Culture', 'Samantha Hall', 'Chef Gordon Ram', 80, 'Active'],
        ['ORG-116', 'Finance Association', 'Academic', 'Daniel Young', 'Prof. Adam Smith', 110, 'Active'],
        ['ORG-117', 'Outdoor Adventure', 'Sports & Recreation', 'Ryan Allen', 'Dr. Bear Grylls', 130, 'Active'],
        ['ORG-118', 'Model United Nations', 'Academic', 'Emily King', 'Prof. Kofi Annan', 70, 'Active'],
        ['ORG-119', 'Music Ensemble', 'Arts & Culture', 'Nicholas Wright', 'Dr. Hans Zimmer', 85, 'Active'],
        ['ORG-120', 'Student Newspaper', 'Community Service', 'Lauren Scott', 'Prof. Clark Kent', 45, 'Inactive']
    ];
    foreach($seed_clubs as $item) {
        $cid = $item[0]; $cn = mysqli_real_escape_string($conn, $item[1]); $cat = $item[2];
        $pr = mysqli_real_escape_string($conn, $item[3]); $adv = mysqli_real_escape_string($conn, $item[4]);
        $mc = $item[5]; $st = $item[6]; $ed = date('Y-m-d', strtotime('-'.rand(10, 1000).' days'));
        mysqli_query($conn, "INSERT INTO clubs (club_id, club_name, category, president, advisor, members_count, status, established_date) VALUES ('$cid', '$cn', '$cat', '$pr', '$adv', $mc, '$st', '$ed')");
    }
}

// DYNAMIC ROSTER SEEDING
$check_m = mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members");
if(mysqli_fetch_assoc($check_m)['c'] == 0) {
    $clubs_res = mysqli_query($conn, "SELECT club_id FROM clubs");
    
    // Check if students exist to map real users
    $students_exist = false;
    $s_check = @mysqli_query($conn, "SELECT first_name, last_name, course, year_level FROM students LIMIT 50");
    if($s_check && mysqli_num_rows($s_check) > 0) {
        $students_exist = true;
        $student_pool = [];
        while($s = mysqli_fetch_assoc($s_check)) { $student_pool[] = $s; }
    } else {
        $fallback_names = ['Liam Carter', 'Olivia Bennett', 'Noah Mitchell', 'Emma Hayes', 'Oliver Brooks', 'Ava Ramirez', 'Elijah Foster', 'Sophia Jenkins', 'Lucas Patel', 'Isabella Ward'];
        $fallback_courses = ['BSCS 2A', 'BSBA 3B', 'BSME 4A', 'AB Comm 1A', 'BSIT 2B'];
    }

    while($c = mysqli_fetch_assoc($clubs_res)) {
        $cid = $c['club_id'];
        $num_members = rand(3, 8); // Mocking 3-8 key members per club for visual roster
        
        if($students_exist) {
            shuffle($student_pool);
            for($k=0; $k<$num_members; $k++) {
                $sname = mysqli_real_escape_string($conn, $student_pool[$k]['first_name'] . ' ' . $student_pool[$k]['last_name']);
                $cy = mysqli_real_escape_string($conn, $student_pool[$k]['course'] . ' ' . $student_pool[$k]['year_level']);
                $role = ($k == 0) ? 'Vice President' : ($k == 1 ? 'Secretary' : 'Active Member');
                mysqli_query($conn, "INSERT INTO club_members (club_id, student_name, course_year, role) VALUES ('$cid', '$sname', '$cy', '$role')");
            }
        } else {
            for($k=0; $k<$num_members; $k++) {
                $sname = $fallback_names[array_rand($fallback_names)];
                $cy = $fallback_courses[array_rand($fallback_courses)];
                $role = ($k == 0) ? 'Vice President' : ($k == 1 ? 'Secretary' : 'Active Member');
                mysqli_query($conn, "INSERT INTO club_members (club_id, student_name, course_year, role) VALUES ('$cid', '$sname', '$cy', '$role')");
            }
        }
    }
}

include 'header.php';

// FEATURE 3: Live Telemetry Cards
$total = getCount($conn, 'clubs');
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM clubs WHERE status='Active'"))['c'];
$members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(members_count) as total FROM clubs WHERE status='Active'"))['total'] ?: 0;
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

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 25px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; }
    
    .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-inactive { background: var(--main-bg); color: var(--text-light); border-color: var(--text-light); }
    
    .id-box { font-weight:900; font-family:monospace; font-size:1.15rem; color:var(--brand-secondary); background:var(--main-bg); border: 2px solid var(--border-color); padding:6px 12px; border-radius:6px; display:inline-block; letter-spacing: 2px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    [data-theme="light"] .id-box { color: var(--brand-primary); }

    /* ROSTER ACCORDION CSS */
    .roster-row { background: var(--bg-grid); transition: all 0.3s ease; }
    .roster-card { padding: 30px; background: var(--sub-menu-bg); border: 2px solid var(--border-light); border-radius: 12px; margin: 15px 30px; box-shadow: inset 0 2px 10px rgba(0,0,0,0.02); border-left: 8px solid var(--brand-secondary);}
    [data-theme="light"] .roster-card { border-left-color: var(--brand-primary); }
    .roster-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 20px;}
    .roster-member { display: flex; align-items: center; gap: 15px; background: var(--card-bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 2px 2px 0px rgba(0,0,0,0.05); transition:0.2s;}
    .roster-member:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); border-color: var(--brand-secondary);}
    .roster-avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--main-bg); border: 2px solid var(--border-color); display:flex; align-items:center; justify-content:center; color:var(--text-light); font-size:1.2rem;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #ec4899;">
    <h1 style="color: #ec4899; font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Clubs & Organizations</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Manage campus societies, student leadership, and active memberships.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-users-cog stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Registered Orgs</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon" style="color:#10b981;"></i>
        <div>
            <div class="stat-val" style="color:#10b981;"><?= $active ?></div>
            <div class="stat-lbl">Active Organizations</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-users stat-icon" style="color:#ec4899;"></i>
        <div>
            <div class="stat-val" style="color:#ec4899;"><?= number_format($members) ?></div>
            <div class="stat-lbl">Total Active Members</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px;">
        <select id="filterStatus" onchange="filterClubs()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Conditions</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
        <select id="filterCat" onchange="filterClubs()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Categories">All Categories</option>
            <?php
            $r_res = mysqli_query($conn, "SELECT DISTINCT category FROM clubs ORDER BY category ASC");
            while($r = mysqli_fetch_assoc($r_res)) {
                echo "<option value='{$r['category']}'>{$r['category']}</option>";
            }
            ?>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Club Registry to CSV...')"><i class="fas fa-file-csv"></i> Export Data</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:#ec4899; border-color:#ec4899; color:#fff;" onclick="openClubModal()"><i class="fas fa-plus"></i> Register Org</button>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:25%;">Organization Details</th>
                <th>Category</th>
                <th style="width:25%;">Leadership Core</th>
                <th>Membership</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="clubTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM clubs ORDER BY status ASC, club_name ASC");
            while($row = mysqli_fetch_assoc($res)) {
                
                $st = $row['status'];
                $st_class = $st == 'Active' ? 'status-active' : 'status-inactive';
                $row_style = $st == 'Inactive' ? "opacity: 0.65; filter: grayscale(40%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $cid = $row['club_id'];
                
                $cat = $row['category'];
                $icon = 'fa-users';
                if($cat == 'Technology') $icon = 'fa-laptop-code';
                if($cat == 'Academic') $icon = 'fa-book-open';
                if($cat == 'Arts & Culture') $icon = 'fa-palette';
                if($cat == 'Sports & Recreation') $icon = 'fa-running';
                if($cat == 'Community Service') $icon = 'fa-hands-helping';
                
                $pct = ($row['members_count'] / 200) * 100;
                if($pct > 100) $pct = 100;
                $bar_color = $pct > 80 ? 'var(--brand-secondary)' : '#ec4899';

                // MAIN CLUB ROW
                echo "
                <tr style='$row_style' data-stat='{$st}' data-cat='{$cat}'>
                    <td>
                        <div class='id-box'>{$cid}</div>
                        <div style='font-size:1.15rem; color:var(--text-dark); margin-top:8px; font-weight:900; font-family:var(--heading-font);'>{$row['club_name']}</div>
                    </td>
                    <td>
                        <strong style='color:var(--brand-secondary); text-transform:uppercase; font-size:0.8rem;'><i class='fas {$icon}' style='margin-right:6px;'></i> {$cat}</strong>
                    </td>
                    <td>
                        <div style='font-weight:800; color:var(--text-dark); font-size:1rem;'><i class='fas fa-user-graduate' style='color:var(--brand-secondary); margin-right:8px;'></i> {$row['president']}</div>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:700; margin-top:6px;'><i class='fas fa-chalkboard-teacher' style='color:var(--brand-primary); margin-right:8px;'></i> {$row['advisor']}</div>
                    </td>
                    <td>
                        <div style='font-weight:900; color:var(--text-dark);'>{$row['members_count']} Enrolled</div>
                        <div style='width: 100%; height: 6px; background: var(--border-light); margin-top: 6px; border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                    </td>
                    <td><span class='status-pill {$st_class}'>{$st}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick=\"toggleRoster('roster_{$row['id']}', this)\"><i class='fas fa-chevron-down'></i> Roster</button>
                            <button class='table-btn' onclick='openClubModal($js_data)'><i class='fas fa-pen'></i> Edit</button>
                            <a href='?del={$row['id']}' class='table-btn btn-trash' onclick='systemToast(\"Dissolving Organization...\")'><i class='fas fa-trash'></i></a>
                        </div>
                    </td>
                </tr>";

                // TOGGLE ROSTER SUB-ROW
                echo "
                <tr id='roster_{$row['id']}' class='roster-row' style='display:none;'>
                    <td colspan='6' style='padding:0;'>
                        <div class='roster-card'>
                            <div style='display:flex; justify-content:space-between; border-bottom: 2px solid var(--border-color); padding-bottom: 15px;'>
                                <h4 style='margin:0; font-size:1.2rem; text-transform:uppercase;'><i class='fas fa-users' style='color:var(--brand-secondary); margin-right:10px;'></i> Official Roster for {$row['club_name']}</h4>
                                <button class='btn-action' style='padding: 4px 10px; font-size:0.7rem;' onclick='systemToast(\"Exporting Roster...\")'><i class='fas fa-download'></i> Export</button>
                            </div>
                            <div class='roster-grid'>";
                            
                            $mem_res = mysqli_query($conn, "SELECT * FROM club_members WHERE club_id = '$cid' ORDER BY role DESC");
                            while($mem = mysqli_fetch_assoc($mem_res)) {
                                $r_color = strpos($mem['role'], 'President') !== false ? 'color:var(--brand-accent);' : (strpos($mem['role'], 'Secretary') !== false ? 'color:#10b981;' : 'color:var(--text-light);');
                                echo "
                                <div class='roster-member'>
                                    <div class='roster-avatar'><i class='fas fa-user'></i></div>
                                    <div>
                                        <div style='font-weight:800; color:var(--text-dark);'>{$mem['student_name']}</div>
                                        <div style='font-size:0.75rem; font-weight:800; margin-top:4px; text-transform:uppercase; {$r_color}'>{$mem['role']} • {$mem['course_year']}</div>
                                    </div>
                                </div>";
                            }
                            if(mysqli_num_rows($mem_res) == 0) {
                                echo "<div style='color:var(--text-light); font-weight:600; font-style:italic; padding:10px;'>No active members mapped in database.</div>";
                            }

                echo "      </div>
                        </div>
                    </td>
                </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='6' style='text-align:center; padding:50px; font-weight:800; opacity:0.5;'><i class='fas fa-users-slash' style='font-size:3rem; margin-bottom:15px; color:var(--brand-secondary);'></i><br>No organizations registered.</td></tr>";
            ?>
        </tbody>
    </table>
</div>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-users-cog" style="color:#ec4899;"></i> Register Org</h2>
        <form method="POST">
            <input type="hidden" name="save_club" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="club_id" id="club_id" placeholder="Org ID (e.g. ORG-101)" required>
                <input type="text" name="club_name" id="club_name" placeholder="Official Organization Name" required>
                
                <select name="category" id="category" style="grid-column: span 2;" required>
                    <option value="" disabled selected>Select Category</option>
                    <option value="Academic">Academic</option>
                    <option value="Arts & Culture">Arts & Culture</option>
                    <option value="Technology">Technology</option>
                    <option value="Sports & Recreation">Sports & Recreation</option>
                    <option value="Community Service">Community Service</option>
                </select>
                
                <input type="text" name="president" id="president" placeholder="Student President Name" required>
                <input type="text" name="advisor" id="advisor" placeholder="Faculty Advisor Name" required>
                
                <input type="number" name="members_count" id="members_count" placeholder="Total Enrolled Members" required>
                <select name="status" id="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:#ec4899; border-color:#ec4899; color:#fff;"><i class="fas fa-save"></i> Save Charter</button>
        </form>
    </div>
</div>

<script>
// FEATURE 13 & 14: Live Table Filtering
function filterClubs() {
    const sFilter = document.getElementById('filterStatus').value;
    const cFilter = document.getElementById('filterCat').value;
    const rows = document.querySelectorAll('#clubTableBody tr');
    
    rows.forEach(row => {
        // Skip filtering the hidden roster rows
        if(row.classList.contains('roster-row')) return;
        
        const rStat = row.getAttribute('data-stat');
        const rCat = row.getAttribute('data-cat');
        let show = true;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (cFilter !== 'All Categories' && rCat !== cFilter) show = false;
        row.style.display = show ? '' : 'none';
        
        // Hide associated roster if parent is hidden
        const rosterRow = row.nextElementSibling;
        if(!show && rosterRow && rosterRow.classList.contains('roster-row')) {
            rosterRow.style.display = 'none';
            row.querySelector('.fa-chevron-up').className = 'fas fa-chevron-down';
        }
    });
}

// INLINE TOGGLE ROSTER SCRIPT
function toggleRoster(id, btn) {
    const row = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
        icon.className = 'fas fa-chevron-up';
        btn.style.background = 'var(--text-dark)';
        btn.style.color = 'var(--main-bg)';
        systemToast('Pulling Membership Data...');
    } else {
        row.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        btn.style.background = '';
        btn.style.color = '';
    }
}

// FEATURE 10: Inline Data Binding
function openClubModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:#ec4899;"></i> Edit Charter';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('club_id').value = data.club_id || '';
        document.getElementById('club_name').value = data.club_name || '';
        document.getElementById('category').value = data.category || '';
        document.getElementById('president').value = data.president || '';
        document.getElementById('advisor').value = data.advisor || '';
        document.getElementById('members_count').value = data.members_count || '';
        document.getElementById('status').value = data.status || 'Active';
    } else {
        title.innerHTML = '<i class="fas fa-users-cog" style="color:#ec4899;"></i> Register Org';
        document.getElementById('edit_id').value = '';
        document.getElementById('club_id').value = '';
        document.getElementById('club_name').value = '';
        document.getElementById('category').value = '';
        document.getElementById('president').value = '';
        document.getElementById('advisor').value = '';
        document.getElementById('members_count').value = '';
        document.getElementById('status').value = 'Active';
    }
    
    modal.style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>