<?php 
include 'config.php'; 

// ==========================================
// 1. ADVANCED SCHEMA DEPLOYMENT
// ==========================================

// Club Master Table
$patch_clubs = "CREATE TABLE IF NOT EXISTS clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id VARCHAR(20) UNIQUE,
    club_name VARCHAR(150),
    category VARCHAR(50),
    president VARCHAR(100),
    advisor VARCHAR(100),
    members_count INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'Active',
    established_date DATE
)";
try { mysqli_query($conn, $patch_clubs); } catch (Exception $e) {}

$club_cols = [
    "meeting_schedule VARCHAR(100)", "meeting_room VARCHAR(50)", 
    "budget_allocated DECIMAL(10,2) DEFAULT 0.00", "budget_spent DECIMAL(10,2) DEFAULT 0.00", 
    "is_accepting_members BOOLEAN DEFAULT 1", "social_media_link VARCHAR(255)", "description TEXT"
];
foreach($club_cols as $c) { try { mysqli_query($conn, "ALTER TABLE clubs ADD COLUMN $c"); } catch (Exception $e) {} }

// Club Roster/Members Table
$patch_roster = "CREATE TABLE IF NOT EXISTS club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id VARCHAR(20),
    student_id VARCHAR(20),
    student_name VARCHAR(100),
    role VARCHAR(50) DEFAULT 'Member',
    status VARCHAR(20) DEFAULT 'Active',
    join_date DATE,
    contribution_points INT DEFAULT 0,
    attendance_score INT DEFAULT 100
)";
try { mysqli_query($conn, $patch_roster); } catch (Exception $e) {}

$roster_cols = [
    "student_id VARCHAR(20)", "student_name VARCHAR(100)", "role VARCHAR(50) DEFAULT 'Member'", 
    "status VARCHAR(20) DEFAULT 'Active'", "join_date DATE", 
    "contribution_points INT DEFAULT 0", "attendance_score INT DEFAULT 100"
];
foreach($roster_cols as $c) { try { mysqli_query($conn, "ALTER TABLE club_members ADD COLUMN $c"); } catch (Exception $e) {} }


// ==========================================
// 2. DATA SEEDING (EMPTY STATES)
// ==========================================
$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM clubs");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_data = [
        ['ORG-26-001', 'Software Engineering Society', 'Academic', 'Ada Lovelace', 'Dr. Alan Turing', 5000, 1200, 1],
        ['ORG-26-002', 'Campus Theatre Guild', 'Arts', 'William Shakespeare', 'Prof. Drama', 8000, 4500, 1],
        ['ORG-26-003', 'Varsity E-Sports', 'Sports', 'Lee Faker', 'Coach Smith', 15000, 14000, 0],
    ];
    foreach($seed_data as $s) {
        mysqli_query($conn, "INSERT INTO clubs (club_id, club_name, category, president, advisor, members_count, budget_allocated, budget_spent, is_accepting_members, status, established_date, meeting_schedule, meeting_room) VALUES ('{$s[0]}', '{$s[1]}', '{$s[2]}', '{$s[3]}', '{$s[4]}', 5, {$s[5]}, {$s[6]}, {$s[7]}, 'Active', CURDATE(), 'Fridays 4:00 PM', 'Room 101')");
        
        // Seed members for this club
        for($i=1; $i<=5; $i++) {
            $sid = "S26-" . rand(1000,9999);
            $sname = "Student " . rand(1, 100);
            $role = ($i==1) ? 'President' : (($i==2) ? 'Vice President' : 'Member');
            $pts = rand(10, 500);
            mysqli_query($conn, "INSERT INTO club_members (club_id, student_id, student_name, role, status, join_date, contribution_points) VALUES ('{$s[0]}', '$sid', '$sname', '$role', 'Active', CURDATE(), $pts)");
        }
    }
}


// ==========================================
// 3. ACTION CONTROLLERS (CLUBS & MEMBERS)
// ==========================================

// -- CLUB DELETION --
if(isset($_GET['del_club'])) {
    $id = intval($_GET['del_club']);
    $cid_res = mysqli_query($conn, "SELECT club_id FROM clubs WHERE id=$id");
    if($c_row = mysqli_fetch_assoc($cid_res)) {
        $cid = $c_row['club_id'];
        mysqli_query($conn, "DELETE FROM club_members WHERE club_id='$cid'"); // Cascade delete roster
        mysqli_query($conn, "DELETE FROM clubs WHERE id=$id");
    }
    header("Location: clubs.php"); exit();
}

// -- MEMBER DELETION --
if(isset($_GET['del_member']) && isset($_GET['roster'])) {
    $id = intval($_GET['del_member']);
    $roster_id = mysqli_real_escape_string($conn, $_GET['roster']);
    mysqli_query($conn, "DELETE FROM club_members WHERE id=$id");
    
    // Update count
    $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members WHERE club_id='$roster_id' AND status='Active'");
    $cnt = mysqli_fetch_assoc($count_res)['c'] ?? 0;
    mysqli_query($conn, "UPDATE clubs SET members_count=$cnt WHERE club_id='$roster_id'");
    
    header("Location: clubs.php?roster=$roster_id"); exit();
}

// -- MASS ACTIONS: CLUBS --
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_club_action'])) {
    if(!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        if ($_POST['mass_action_type'] === 'suspend') {
            mysqli_query($conn, "UPDATE clubs SET status = 'Suspended' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'close_recruitment') {
            mysqli_query($conn, "UPDATE clubs SET is_accepting_members = 0 WHERE id IN ($ids)");
        }
    }
    header("Location: clubs.php"); exit();
}

// -- MASS ACTIONS: MEMBERS --
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_member_action'])) {
    $roster_id = mysqli_real_escape_string($conn, $_POST['roster_id']);
    if(!empty($_POST['sel_member_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_member_ids']));
        if ($_POST['mass_action_type'] === 'suspend') {
            mysqli_query($conn, "UPDATE club_members SET status = 'Suspended' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'alumni') {
            mysqli_query($conn, "UPDATE club_members SET role = 'Alumni', status = 'Inactive' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM club_members WHERE id IN ($ids)");
        }
        
        // Update count
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members WHERE club_id='$roster_id' AND status='Active'");
        $cnt = mysqli_fetch_assoc($count_res)['c'] ?? 0;
        mysqli_query($conn, "UPDATE clubs SET members_count=$cnt WHERE club_id='$roster_id'");
    }
    header("Location: clubs.php?roster=$roster_id"); exit();
}

// -- SAVE CLUB --
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_club'])) {
    $cid = mysqli_real_escape_string($conn, $_POST['club_id']);
    $cn = mysqli_real_escape_string($conn, $_POST['club_name']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $pres = mysqli_real_escape_string($conn, $_POST['president']);
    $adv = mysqli_real_escape_string($conn, $_POST['advisor']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    $ms = mysqli_real_escape_string($conn, $_POST['meeting_schedule']);
    $mr = mysqli_real_escape_string($conn, $_POST['meeting_room']);
    $ba = floatval($_POST['budget_allocated']);
    $bs = floatval($_POST['budget_spent']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $sm = mysqli_real_escape_string($conn, $_POST['social_media_link']);
    $am = isset($_POST['is_accepting_members']) ? 1 : 0;
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE clubs SET club_id='$cid', club_name='$cn', category='$cat', president='$pres', advisor='$adv', status='$st', meeting_schedule='$ms', meeting_room='$mr', budget_allocated=$ba, budget_spent=$bs, description='$desc', social_media_link='$sm', is_accepting_members=$am WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO clubs (club_id, club_name, category, president, advisor, members_count, status, established_date, meeting_schedule, meeting_room, budget_allocated, budget_spent, description, social_media_link, is_accepting_members) VALUES ('$cid', '$cn', '$cat', '$pres', '$adv', 0, '$st', CURDATE(), '$ms', '$mr', $ba, $bs, '$desc', '$sm', $am)");
    }
    header("Location: clubs.php"); exit();
}

// -- SAVE MEMBER --
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_member'])) {
    $cid = mysqli_real_escape_string($conn, $_POST['roster_id']);
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $sn = mysqli_real_escape_string($conn, $_POST['student_name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $st = mysqli_real_escape_string($conn, $_POST['status']);
    $pts = intval($_POST['contribution_points']);
    $att = intval($_POST['attendance_score']);
    
    if(!empty($_POST['edit_member_id'])) {
        $id = intval($_POST['edit_member_id']);
        mysqli_query($conn, "UPDATE club_members SET student_id='$sid', student_name='$sn', role='$role', status='$st', contribution_points=$pts, attendance_score=$att WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO club_members (club_id, student_id, student_name, role, status, join_date, contribution_points, attendance_score) VALUES ('$cid', '$sid', '$sn', '$role', '$st', CURDATE(), $pts, $att)");
    }
    
    // Update count
    $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members WHERE club_id='$cid' AND status='Active'");
    $cnt = mysqli_fetch_assoc($count_res)['c'] ?? 0;
    mysqli_query($conn, "UPDATE clubs SET members_count=$cnt WHERE club_id='$cid'");
    
    header("Location: clubs.php?roster=$cid"); exit();
}

include 'header.php';

// Check which view is active
$is_roster_view = isset($_GET['roster']) && !empty($_GET['roster']);
$active_club_id = $is_roster_view ? mysqli_real_escape_string($conn, $_GET['roster']) : null;
$active_club_data = null;

if($is_roster_view) {
    $club_res = mysqli_query($conn, "SELECT * FROM clubs WHERE club_id='$active_club_id'");
    if($club_res && mysqli_num_rows($club_res) > 0) {
        $active_club_data = mysqli_fetch_assoc($club_res);
    } else {
        $is_roster_view = false; // Fallback if invalid
    }
}
?>

<style>
    /* UNIVERSAL MODERN UI SYSTEM */
    .page-header { margin-bottom: 30px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; margin-bottom: 5px; }
    .page-sub { color: var(--text-light); font-size: 1rem; font-weight: 500; }

    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--card-bg); padding: 25px; border: 1px solid var(--border-color); border-radius: 16px; display: flex; align-items: center; gap: 20px; box-shadow: var(--soft-shadow); transition: 0.3s; position: relative; overflow: hidden; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.08); border-color: var(--border-light); }
    
    .stat-icon { font-size: 1.6rem; color: var(--text-dark); display: flex; justify-content: center; align-items: center; width: 50px; height: 50px; background: var(--bg-grid); border-radius: 12px; border: 1px solid var(--border-color);}
    .stat-val { font-size: 2rem; font-weight: 800; color: var(--text-dark); line-height: 1; margin-bottom: 5px; letter-spacing: -0.5px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 20px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; flex-wrap: wrap; gap: 15px; box-shadow: var(--soft-shadow); }
    .flt-sel { border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 12px; background: var(--main-bg); color: var(--text-dark); font-weight: 500; font-size: 0.9rem; outline: none; transition: 0.2s; }
    .flt-sel:focus { border-color: var(--brand-secondary); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
    
    .cb-sel { width: 18px; height: 18px; accent-color: var(--text-dark); cursor: pointer; border-radius: 4px; }

    .table-responsive { border-radius: 16px; overflow: hidden; border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); background: var(--card-bg); }
    th { background: var(--main-bg); padding: 16px 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-light); border-bottom: 1px solid var(--border-color); letter-spacing: 0.5px; }
    td { padding: 16px 20px; border-bottom: 1px solid var(--border-light); font-size: 0.9rem; color: var(--text-dark); vertical-align: middle;}
    tr:hover td { background: var(--main-bg); }

    .status-pill { padding: 6px 12px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; border-radius: 20px; border: 1px solid currentColor; }
    .st-Active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .st-Suspended, .st-Inactive { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .avatar-wrap { display: flex; align-items: center; gap: 12px; }
    .avatar-mini { width: 36px; height: 36px; border-radius: 50%; background: var(--brand-secondary); color: var(--main-bg); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700; flex-shrink: 0; }
    
    .role-badge { font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:6px; background:var(--bg-grid); border:1px solid var(--border-color); color:var(--text-light); text-transform:uppercase;}
    .role-President { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .role-VP { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: #3b82f6; }

    .budget-bar-wrap { width: 100%; height: 6px; background: var(--border-light); border-radius: 3px; overflow: hidden; margin-top: 6px; }
    .budget-bar-fill { height: 100%; transition: width 0.4s ease; }
    
    .mono-num { font-family: monospace; font-size: 1rem; font-weight: 800; color: var(--text-dark); }

    .slide-drawer { position: fixed; top: 0; right: -600px; width: 100%; max-width: 550px; height: 100vh; background: var(--card-bg); box-shadow: -10px 0 30px rgba(0,0,0,0.1); z-index: 10000; transition: right 0.3s ease; display: flex; flex-direction: column; border-left: 1px solid var(--border-color); }
    .slide-drawer.open { right: 0; }
    .sd-head { padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(var(--card-bg-rgb), 0.9); backdrop-filter: blur(10px); }
    .sd-body { padding: 25px; overflow-y: auto; flex: 1; display:flex; flex-direction:column; gap:20px; }
    .sd-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.4); backdrop-filter: blur(4px); z-index: 9999; opacity: 0; pointer-events: none; transition: 0.3s; }
    .sd-overlay.show { opacity: 1; pointer-events: all; }
    
    .input-grp { margin-bottom:0; }
    .input-grp label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-light); margin-bottom: 8px; text-transform: uppercase; }

    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px; }
    .page-btn { background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 10px; font-weight: 600; cursor: pointer; color: var(--text-dark); transition: 0.2s; box-shadow: var(--soft-shadow); }
    .page-btn:hover { background: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); display: none; }
    .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: 15px; color: var(--text-dark); }
</style>

<?php if(!$is_roster_view): ?>
<?php
$total_orgs = getCount($conn, 'clubs');
$active_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(members_count) as c FROM clubs WHERE status='Active'"))['c'] ?? 0;
$recruiting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM clubs WHERE is_accepting_members=1 AND status='Active'"))['c'] ?? 0;
$budget_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(budget_allocated) as alloc, SUM(budget_spent) as spent FROM clubs WHERE status='Active'"));
$total_budget = $budget_data['alloc'] ?? 0;
?>

<div class="page-header">
    <h1 class="page-title">Organizations Directory</h1>
    <p class="page-sub">Manage campus charters, oversee operational budgets, and track student involvement.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
        <div><div class="stat-val"><?= $total_orgs ?></div><div class="stat-lbl">Total Organizations</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981;"><i class="fas fa-user-graduate"></i></div>
        <div><div class="stat-val"><?= number_format($active_members) ?></div><div class="stat-lbl">Active Members</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#3b82f6;"><i class="fas fa-door-open"></i></div>
        <div><div class="stat-val"><?= $recruiting ?></div><div class="stat-lbl">Currently Recruiting</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#f59e0b;"><i class="fas fa-wallet"></i></div>
        <div><div class="stat-val" style="font-size:1.6rem;">₱<?= number_format($total_budget, 0) ?></div><div class="stat-lbl">Total Budget Pool</div></div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
        <input type="text" id="searchClubLocal" onkeyup="filterClubs()" placeholder="&#xf002; Search orgs..." class="flt-sel" style="font-family:var(--body-font), 'Font Awesome 6 Free'; width:260px;">
        <select id="filterCat" class="flt-sel" onchange="filterClubs()">
            <option value="All">All Categories</option>
            <?php 
            $c_res = mysqli_query($conn, "SELECT DISTINCT category FROM clubs");
            while ($c = mysqli_fetch_assoc($c_res)) { if(!empty($c['category'])) echo "<option value='{$c['category']}'>{$c['category']}</option>"; }
            ?>
        </select>
    </div>
    <div style="display:flex; gap:15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('clubTable','clubs_export')"><i class="fas fa-download"></i> Export Data</button>
        <button type="button" class="btn-primary" onclick="openClubDrawer()"><i class="fas fa-plus"></i> Register Org</button>
    </div>
</form>

<form method="POST">
    <input type="hidden" name="mass_club_action" value="1">
    
    <div style="margin-bottom:20px; display:flex; gap:15px; align-items:center; background:var(--card-bg); padding:15px 25px; border:1px solid var(--border-color); border-radius:16px; box-shadow:var(--soft-shadow);">
        <span style="font-weight:700; font-size:0.95rem;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding:8px 15px;">
            <option value="suspend">Suspend Charters</option>
            <option value="close_recruitment">Close Recruitment</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation?')">Apply</button>
    </div>
    
    <div class="table-responsive">
        <table id="clubTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-club').forEach(c=>c.checked=this.checked)"></th>
                    <th>Organization Identity</th>
                    <th>Leadership</th>
                    <th>Operations</th>
                    <th>Financial Tracking</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = mysqli_query($conn, "SELECT * FROM clubs ORDER BY club_name ASC");
                while ($r = mysqli_fetch_assoc($res)) {
                    $js = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                    $dim_cls = ($r['status'] == 'Suspended') ? 'opacity:0.6; filter:grayscale(50%);' : '';
                    
                    $b_alloc = floatval($r['budget_allocated']);
                    $b_spent = floatval($r['budget_spent']);
                    $b_rem = $b_alloc - $b_spent;
                    $b_pct = $b_alloc > 0 ? min(100, ($b_spent / $b_alloc) * 100) : 0;
                    $b_color = $b_pct > 90 ? '#ef4444' : ($b_pct > 75 ? '#f59e0b' : '#10b981');
                    
                    $rec_txt = $r['is_accepting_members'] ? '<i class="fas fa-door-open" style="color:#10b981;"></i> Recruiting' : '<i class="fas fa-door-closed" style="color:var(--text-light);"></i> Closed';

                    echo "
                    <tr class='club-row' style='{$dim_cls}' data-cat='{$r['category']}'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$r['id']}' class='cb-sel cb-club'></td>
                        <td>
                            <strong style='font-size:1.05rem; color:var(--text-dark);'>{$r['club_name']}</strong><br>
                            <span style='font-family:monospace; font-size:0.8rem; color:var(--text-light);'>{$r['club_id']}</span>
                            <div style='font-size:0.75rem; font-weight:600; padding:2px 8px; border-radius:10px; background:var(--bg-grid); border:1px solid var(--border-color); display:inline-block; margin-top:6px;'>{$r['category']}</div>
                        </td>
                        <td>
                            <div style='font-weight:600; font-size:0.9rem;'>{$r['president']}</div>
                            <div style='font-size:0.75rem; color:var(--text-light); margin-bottom:8px;'>President</div>
                            <div style='font-weight:600; font-size:0.9rem;'>{$r['advisor']}</div>
                            <div style='font-size:0.75rem; color:var(--text-light);'>Advisor</div>
                        </td>
                        <td>
                            <div style='margin-bottom:8px;'><span class='status-pill st-{$r['status']}'>{$r['status']}</span></div>
                            <div style='font-size:0.8rem; font-weight:600; margin-bottom:4px;'>{$rec_txt}</div>
                            <div style='font-size:0.8rem; color:var(--text-light);'><i class='fas fa-users'></i> {$r['members_count']} Active</div>
                        </td>
                        <td style='min-width:200px;'>
                            <div style='display:flex; justify-content:space-between; margin-bottom:4px;'>
                                <span style='font-size:0.8rem; font-weight:600; color:var(--text-light);'>Remaining</span>
                                <span class='mono-num'>₱" . number_format($b_rem, 2) . "</span>
                            </div>
                            <div class='budget-bar-wrap'><div class='budget-bar-fill' style='width:{$b_pct}%; background:{$b_color};'></div></div>
                            <div style='font-size:0.75rem; color:var(--text-light); text-align:right; margin-top:4px;'>of ₱".number_format($b_alloc, 0)."</div>
                        </td>
                        <td class='action-col'>
                            <div style='display:flex; flex-direction:column; gap:8px;'>
                                <a href='?roster={$r['club_id']}' class='btn-action' style='background:var(--text-dark); color:var(--main-bg); justify-content:center; padding:6px 12px; font-size:0.8rem;'><i class='fas fa-users-cog'></i> Roster</a>
                                <div style='display:flex; gap:8px;'>
                                    <button type='button' class='btn-action' style='flex:1; justify-content:center; padding:6px;' onclick='openClubDrawer({$js})'><i class='fas fa-pen'></i></button>
                                    <a href='?del_club={$r['id']}' class='btn-action btn-trash' style='padding:6px 12px;' onclick='return confirm(\"Delete club and entire roster?\")'><i class='fas fa-trash'></i></a>
                                </div>
                            </div>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="emptyClubs" class="empty-state">
            <i class="fas fa-search-minus"></i>
            <div style="font-weight:600; font-size:1.1rem; color:var(--text-dark);">No organizations found</div>
        </div>
    </div>
</form>

<div class="sd-overlay" id="clubOverlay" onclick="closeClubDrawer()"></div>
<div class="slide-drawer" id="clubDrawer">
    <div class="sd-head">
        <h2 id="clubDrawerTitle" style="font-size:1.3rem; font-weight:800; color:var(--text-dark);"><i class="fas fa-building"></i> Org Profile</h2>
        <button type="button" class="btn-action" style="border:none; padding:8px; border-radius:50%;" onclick="closeClubDrawer()"><i class="fas fa-times"></i></button>
    </div>
    <div class="sd-body">
        <form method="POST">
            <input type="hidden" name="save_club" value="1">
            <input type="hidden" name="edit_id" id="c_edit_id" value="">
            
            <div class="input-grp">
                <label>Organization ID</label>
                <input type="text" name="club_id" id="c_club_id" readonly style="background:var(--bg-grid); cursor:not-allowed; font-family:monospace; font-weight:700;" required>
            </div>
            <div class="input-grp"><label>Organization Name</label><input type="text" name="club_name" id="c_club_name" required></div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="input-grp"><label>Category</label><input type="text" name="category" id="c_category" required></div>
                <div class="input-grp"><label>Status</label><select name="status" id="c_status" required><option value="Active">Active</option><option value="Suspended">Suspended</option></select></div>
            </div>

            <div style="padding:15px; border:1px solid var(--border-color); border-radius:12px; background:var(--bg-grid);">
                <div style="font-size:0.75rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:10px;">Leadership</div>
                <div class="input-grp"><label>Student President</label><input type="text" name="president" id="c_president" required></div>
                <div class="input-grp" style="margin-bottom:0;"><label>Faculty Advisor</label><input type="text" name="advisor" id="c_advisor" required></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="input-grp"><label>Meeting Room</label><input type="text" name="meeting_room" id="c_meeting_room"></div>
                <div class="input-grp"><label>Schedule</label><input type="text" name="meeting_schedule" id="c_meeting_schedule"></div>
            </div>

            <div style="padding:15px; border:1px solid #f59e0b; border-radius:12px; background:rgba(245,158,11,0.05);">
                <div style="font-size:0.75rem; font-weight:800; color:#f59e0b; text-transform:uppercase; margin-bottom:10px;"><i class="fas fa-wallet"></i> Financials</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="input-grp" style="margin-bottom:0;"><label>Allocated Budget (₱)</label><input type="number" step="0.01" min="0" name="budget_allocated" id="c_budget_allocated" required></div>
                    <div class="input-grp" style="margin-bottom:0;"><label>Budget Spent (₱)</label><input type="number" step="0.01" min="0" name="budget_spent" id="c_budget_spent" required></div>
                </div>
            </div>

            <div class="input-grp"><label>Social Media Link</label><input type="text" name="social_media_link" id="c_social_media_link"></div>
            <div class="input-grp"><label>Description</label><textarea name="description" id="c_description" style="height:80px; resize:none;"></textarea></div>

            <div class="input-grp" style="display:flex; align-items:center; gap:12px; padding:15px; border:1px solid var(--border-color); border-radius:12px;">
                <input type="checkbox" name="is_accepting_members" id="c_is_accepting_members" class="cb-sel">
                <label style="margin:0; cursor:pointer;" for="c_is_accepting_members">Actively Recruiting Members</label>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-save"></i> Save Profile</button>
        </form>
    </div>
</div>

<script>
function filterClubs() {
    const cFilter = document.getElementById('filterCat').value;
    const searchQ = document.getElementById('searchClubLocal').value.toLowerCase();
    let vis = 0;
    
    document.querySelectorAll('.club-row').forEach(el => {
        const rCat = el.getAttribute('data-cat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if(cFilter !== 'All' && rCat !== cFilter) show = false;
        if(searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        el.style.display = show ? '' : 'none';
        if(show) vis++;
    });
    
    document.getElementById('emptyClubs').style.display = vis === 0 ? 'block' : 'none';
}

function openClubDrawer(data = null) {
    document.getElementById('clubOverlay').classList.add('show');
    document.getElementById('clubDrawer').classList.add('open');
    if(data) {
        document.getElementById('c_edit_id').value = data.id;
        document.getElementById('c_club_id').value = data.club_id;
        document.getElementById('c_club_name').value = data.club_name;
        document.getElementById('c_category').value = data.category;
        document.getElementById('c_president').value = data.president;
        document.getElementById('c_advisor').value = data.advisor;
        document.getElementById('c_status').value = data.status;
        document.getElementById('c_meeting_schedule').value = data.meeting_schedule || '';
        document.getElementById('c_meeting_room').value = data.meeting_room || '';
        document.getElementById('c_budget_allocated').value = data.budget_allocated || 0;
        document.getElementById('c_budget_spent').value = data.budget_spent || 0;
        document.getElementById('c_description').value = data.description || '';
        document.getElementById('c_social_media_link').value = data.social_media_link || '';
        document.getElementById('c_is_accepting_members').checked = data.is_accepting_members == 1;
    } else {
        document.getElementById('c_edit_id').value = '';
        document.getElementById('c_club_id').value = 'ORG-' + new Date().getFullYear().toString().substr(-2) + '-' + Math.floor(1000 + Math.random() * 9000);
        document.getElementById('c_club_name').value = '';
        document.getElementById('c_category').value = '';
        document.getElementById('c_president').value = '';
        document.getElementById('c_advisor').value = '';
        document.getElementById('c_status').value = 'Active';
        document.getElementById('c_meeting_schedule').value = '';
        document.getElementById('c_meeting_room').value = '';
        document.getElementById('c_budget_allocated').value = '0';
        document.getElementById('c_budget_spent').value = '0';
        document.getElementById('c_description').value = '';
        document.getElementById('c_social_media_link').value = '';
        document.getElementById('c_is_accepting_members').checked = true;
    }
}
function closeClubDrawer() {
    document.getElementById('clubOverlay').classList.remove('show');
    document.getElementById('clubDrawer').classList.remove('open');
}
</script>

<?php else: ?>
<?php
$cname = htmlspecialchars($active_club_data['club_name']);
$t_mem = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members WHERE club_id='$active_club_id'"))['c'] ?? 0;
$a_mem = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members WHERE club_id='$active_club_id' AND status='Active'"))['c'] ?? 0;
$l_mem = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM club_members WHERE club_id='$active_club_id' AND role IN ('President', 'Vice President', 'Secretary', 'Treasurer')"))['c'] ?? 0;
$avg_pts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(contribution_points) as a FROM club_members WHERE club_id='$active_club_id' AND status='Active'"))['a'] ?? 0;
?>

<div class="page-header" style="display:flex; align-items:center; gap:20px;">
    <a href="clubs.php" class="btn-action" style="padding:12px; border-radius:50%;"><i class="fas fa-arrow-left" style="margin:0;"></i></a>
    <div>
        <div style="font-size:0.85rem; font-weight:700; color:var(--brand-secondary); text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Roster Management System</div>
        <h1 class="page-title" style="margin:0;"><?= $cname ?></h1>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div><div class="stat-val"><?= $t_mem ?></div><div class="stat-lbl">Total Records</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.1);"><i class="fas fa-user-check"></i></div>
        <div><div class="stat-val"><?= $a_mem ?></div><div class="stat-lbl">Active Roster</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#8b5cf6; background:rgba(139,92,246,0.1);"><i class="fas fa-crown"></i></div>
        <div><div class="stat-val"><?= $l_mem ?></div><div class="stat-lbl">Core Officers</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#f59e0b; background:rgba(245,158,11,0.1);"><i class="fas fa-star"></i></div>
        <div><div class="stat-val"><?= round($avg_pts) ?></div><div class="stat-lbl">Avg Contrib. Score</div></div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <input type="hidden" name="roster" value="<?= $active_club_id ?>">
    <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
        <input type="text" id="searchRosterLocal" onkeyup="filterRoster()" placeholder="&#xf002; Search members..." class="flt-sel" style="font-family:var(--body-font), 'Font Awesome 6 Free'; width:260px;">
        <select id="filterRole" class="flt-sel" onchange="filterRoster()">
            <option value="All">All Roles</option>
            <option value="President">President</option>
            <option value="Vice President">Vice President</option>
            <option value="Member">Member</option>
            <option value="Alumni">Alumni</option>
        </select>
        <select id="filterStatus" class="flt-sel" onchange="filterRoster()">
            <option value="All">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Suspended">Suspended</option>
        </select>
    </div>
    <div style="display:flex; gap:15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('rosterTable','roster_export')"><i class="fas fa-download"></i> Export Roster</button>
        <button type="button" class="btn-primary" onclick="openMemberDrawer()"><i class="fas fa-user-plus"></i> Add Member</button>
    </div>
</form>

<form method="POST">
    <input type="hidden" name="mass_member_action" value="1">
    <input type="hidden" name="roster_id" value="<?= $active_club_id ?>">
    
    <div style="margin-bottom:20px; display:flex; gap:15px; align-items:center; background:var(--card-bg); padding:15px 25px; border:1px solid var(--border-color); border-radius:16px; box-shadow:var(--soft-shadow);">
        <span style="font-weight:700; font-size:0.95rem;">Batch Member Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding:8px 15px;">
            <option value="suspend">Suspend Members</option>
            <option value="alumni">Convert to Alumni</option>
            <option value="delete">Remove from Roster</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation on roster?')">Apply</button>
    </div>

    <div class="table-responsive">
        <table id="rosterTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-mem').forEach(c=>c.checked=this.checked)"></th>
                    <th>Member Profile</th>
                    <th>Hierarchy Role</th>
                    <th>Metrics & Activity</th>
                    <th>Status</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = mysqli_query($conn, "SELECT * FROM club_members WHERE club_id='$active_club_id' ORDER BY CASE WHEN role='President' THEN 1 WHEN role='Vice President' THEN 2 WHEN role='Member' THEN 3 ELSE 4 END, student_name ASC");
                while ($r = mysqli_fetch_assoc($res)) {
                    $js = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                    $dim_cls = ($r['status'] != 'Active') ? 'opacity:0.6; filter:grayscale(50%);' : '';
                    
                    $role_cls = 'role-Member';
                    if($r['role'] == 'President') $role_cls = 'role-President';
                    if($r['role'] == 'Vice President') $role_cls = 'role-VP';

                    echo "
                    <tr class='roster-row' style='{$dim_cls}' data-role='{$r['role']}' data-stat='{$r['status']}'>
                        <td><input type='checkbox' name='sel_member_ids[]' value='{$r['id']}' class='cb-sel cb-mem'></td>
                        <td>
                            <div class='avatar-wrap'>
                                <div class='avatar-mini' style='width:40px; height:40px; font-size:1rem;'>" . substr($r['student_name'], 0, 1) . "</div>
                                <div>
                                    <strong style='font-size:1.05rem; color:var(--text-dark);'>{$r['student_name']}</strong><br>
                                    <span style='font-family:monospace; font-size:0.8rem; color:var(--text-light);'>{$r['student_id']}</span>
                                </div>
                            </div>
                        </td>
                        <td><span class='role-badge {$role_cls}'>{$r['role']}</span></td>
                        <td>
                            <div style='display:flex; gap:20px;'>
                                <div>
                                    <div style='font-size:0.75rem; color:var(--text-light); text-transform:uppercase; font-weight:600;'>Contrib. Points</div>
                                    <div class='mono-num' style='font-size:1.2rem; color:var(--brand-secondary);'>{$r['contribution_points']}</div>
                                </div>
                                <div>
                                    <div style='font-size:0.75rem; color:var(--text-light); text-transform:uppercase; font-weight:600;'>Attendance</div>
                                    <div class='mono-num' style='font-size:1.2rem; color:#10b981;'>{$r['attendance_score']}%</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class='status-pill st-{$r['status']}'>{$r['status']}</span><br>
                            <span style='font-size:0.75rem; color:var(--text-light); display:inline-block; margin-top:4px;'>Joined " . date('M d, Y', strtotime($r['join_date'])) . "</span>
                        </td>
                        <td class='action-col'>
                            <div style='display:flex; gap:8px;'>
                                <button type='button' class='btn-action' style='padding:8px 12px;' onclick='openMemberDrawer({$js})'><i class='fas fa-pen'></i> Edit</button>
                                <a href='?del_member={$r['id']}&roster={$active_club_id}' class='btn-action btn-trash' style='padding:8px 12px;' onclick='return confirm(\"Remove member from roster?\")'><i class='fas fa-user-times'></i></a>
                            </div>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="emptyRoster" class="empty-state">
            <i class="fas fa-users-slash"></i>
            <div style="font-weight:600; font-size:1.1rem; color:var(--text-dark);">Roster is empty</div>
            <div style="font-size:0.9rem; margin-top:5px;">Add members to begin tracking contributions.</div>
        </div>
    </div>
</form>

<div class="sd-overlay" id="memberOverlay" onclick="closeMemberDrawer()"></div>
<div class="slide-drawer" id="memberDrawer" style="max-width:450px;">
    <div class="sd-head">
        <h2 id="memberDrawerTitle" style="font-size:1.3rem; font-weight:800; color:var(--text-dark);"><i class="fas fa-user-plus"></i> Member Profile</h2>
        <button type="button" class="btn-action" style="border:none; padding:8px; border-radius:50%;" onclick="closeMemberDrawer()"><i class="fas fa-times"></i></button>
    </div>
    <div class="sd-body">
        <form method="POST">
            <input type="hidden" name="save_member" value="1">
            <input type="hidden" name="edit_member_id" id="m_edit_id" value="">
            <input type="hidden" name="roster_id" value="<?= $active_club_id ?>">
            
            <div class="input-grp"><label>Student ID</label><input type="text" name="student_id" id="m_student_id" required></div>
            <div class="input-grp"><label>Full Name</label><input type="text" name="student_name" id="m_student_name" required></div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="input-grp">
                    <label>Hierarchy Role</label>
                    <select name="role" id="m_role" required>
                        <option value="Member">Member</option>
                        <option value="President">President</option>
                        <option value="Vice President">Vice President</option>
                        <option value="Secretary">Secretary</option>
                        <option value="Treasurer">Treasurer</option>
                        <option value="Alumni">Alumni</option>
                    </select>
                </div>
                <div class="input-grp">
                    <label>Status</label>
                    <select name="status" id="m_status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div style="padding:15px; border:1px solid var(--border-color); border-radius:12px; background:var(--bg-grid); margin-top:10px;">
                <div style="font-size:0.75rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:10px;"><i class="fas fa-chart-line"></i> Activity Metrics</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="input-grp" style="margin-bottom:0;"><label>Contribution Pts</label><input type="number" name="contribution_points" id="m_contribution_points" required></div>
                    <div class="input-grp" style="margin-bottom:0;"><label>Attendance %</label><input type="number" min="0" max="100" name="attendance_score" id="m_attendance_score" required></div>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center; margin-top:20px;"><i class="fas fa-save"></i> Save Member</button>
        </form>
    </div>
</div>

<script>
function filterRoster() {
    const rFilter = document.getElementById('filterRole').value;
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchRosterLocal').value.toLowerCase();
    let vis = 0;
    
    document.querySelectorAll('.roster-row').forEach(el => {
        const rRole = el.getAttribute('data-role');
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if(rFilter !== 'All' && rRole !== rFilter) show = false;
        if(sFilter !== 'All' && rStat !== sFilter) show = false;
        if(searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        el.style.display = show ? '' : 'none';
        if(show) vis++;
    });
    
    document.getElementById('emptyRoster').style.display = vis === 0 ? 'block' : 'none';
}

function openMemberDrawer(data = null) {
    document.getElementById('memberOverlay').classList.add('show');
    document.getElementById('memberDrawer').classList.add('open');
    if(data) {
        document.getElementById('m_edit_id').value = data.id;
        document.getElementById('m_student_id').value = data.student_id;
        document.getElementById('m_student_name').value = data.student_name;
        document.getElementById('m_role').value = data.role;
        document.getElementById('m_status').value = data.status;
        document.getElementById('m_contribution_points').value = data.contribution_points;
        document.getElementById('m_attendance_score').value = data.attendance_score;
    } else {
        document.getElementById('m_edit_id').value = '';
        document.getElementById('m_student_id').value = 'S26-' + Math.floor(1000 + Math.random() * 9000);
        document.getElementById('m_student_name').value = '';
        document.getElementById('m_role').value = 'Member';
        document.getElementById('m_status').value = 'Active';
        document.getElementById('m_contribution_points').value = '0';
        document.getElementById('m_attendance_score').value = '100';
    }
}
function closeMemberDrawer() {
    document.getElementById('memberOverlay').classList.remove('show');
    document.getElementById('memberDrawer').classList.remove('open');
}

// Initial checks for empty states
document.addEventListener("DOMContentLoaded", () => {
    const rRows = document.querySelectorAll('.roster-row');
    if(rRows.length === 0) document.getElementById('emptyRoster').style.display = 'block';
});
</script>

<?php endif; ?>
<?php include 'footer.php'; ?>