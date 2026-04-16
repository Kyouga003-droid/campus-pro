<?php
include 'config.php';

$patch = ["ticket_id VARCHAR(20)", "issue VARCHAR(150)", "category VARCHAR(50)", "location VARCHAR(50)", "reported_by VARCHAR(50)", "assigned_tech VARCHAR(50)", "priority VARCHAR(20)", "status VARCHAR(20) DEFAULT 'Open'", "created_at DATETIME"];
foreach($patch as $p) { try { mysqli_query($conn, "ALTER TABLE it_tickets ADD COLUMN $p"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM it_tickets WHERE id = $id");
    header("Location: it_tickets.php");
    exit();
}

if(isset($_GET['resolve'])) {
    $tid = intval($_GET['resolve']);
    mysqli_query($conn, "UPDATE it_tickets SET status = 'Resolved' WHERE id = $tid");
    header("Location: it_tickets.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_ticket'])) {
    $tid = "IT-" . date('Y') . "-" . str_pad(rand(100,999), 3, "0", STR_PAD_LEFT);
    $iss = mysqli_real_escape_string($conn, $_POST['issue']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $loc = mysqli_real_escape_string($conn, $_POST['location']);
    $rep = mysqli_real_escape_string($conn, $_POST['reported_by']);
    $pri = mysqli_real_escape_string($conn, $_POST['priority']);
    $tech = "Unassigned";
    
    mysqli_query($conn, "INSERT INTO it_tickets (ticket_id, issue, category, location, reported_by, assigned_tech, priority, status, created_at) VALUES ('$tid', '$iss', '$cat', '$loc', '$rep', '$tech', '$pri', 'Open', NOW())");
    header("Location: it_tickets.php");
    exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM it_tickets");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $issues = ['Projector Bulb Blown', 'WiFi Deadzone', 'Login Credentials Failed', 'Printer Paper Jam', 'Software License Expired', 'Blue Screen of Death', 'No Audio in Speakers'];
    $cats = ['Hardware', 'Network', 'Software', 'Hardware', 'Software', 'Hardware', 'Hardware'];
    $locs = ['Lecture Hall A', 'Library 2nd Floor', 'Admin Office', 'CS Lab 1', 'Faculty Room 3', 'Registrar', 'Auditorium'];
    $reps = ['Dr. Smith', 'Student 2026-0012', 'Mary Jane', 'Prof. Davis', 'Dr. Adams', 'Clerk Wilson', 'Dean Evans'];
    $techs = ['Tech Mark', 'Tech Sarah', 'Unassigned', 'Tech Mark', 'Tech John', 'Unassigned', 'Tech Sarah'];
    
    for($i=0; $i<7; $i++) {
        $tid = "IT-" . date('Y') . "-" . str_pad($i+1, 3, "0", STR_PAD_LEFT);
        $iss = $issues[$i]; $cat = $cats[$i]; $loc = $locs[$i]; $rep = $reps[$i]; $tech = $techs[$i];
        $pri = ($i % 3 == 0) ? 'Critical' : 'Standard';
        $stat = ($i == 4 || $i == 6) ? 'Resolved' : 'Open';
        $time = date('Y-m-d H:i:s', strtotime('-'.rand(1, 48).' hours'));
        
        mysqli_query($conn, "INSERT INTO it_tickets (ticket_id, issue, category, location, reported_by, assigned_tech, priority, status, created_at) VALUES ('$tid', '$iss', '$cat', '$loc', '$rep', '$tech', '$pri', '$stat', '$time')");
    }
}

include 'header.php';

$total = getCount($conn, 'it_tickets');
$open = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM it_tickets WHERE status='Open'"))['c'];
$critical = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM it_tickets WHERE priority='Critical' AND status='Open'"))['c'];
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: var(--card-bg); padding: 30px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--hard-shadow); border-color: var(--brand-secondary); }
    [data-theme="dark"] .stat-card:hover { border-color: var(--brand-primary); }
    .stat-icon { font-size: 2.5rem; color: var(--brand-secondary); opacity: 0.9; }
    [data-theme="light"] .stat-icon { color: var(--brand-primary); }
    .stat-val { font-size: 2.2rem; font-weight: 900; font-family: var(--heading-font); color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.85rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 12px;}
    
    .status-open { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: #f59e0b; }
    .status-resolved { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .pri-crit { color: var(--brand-crimson); font-weight: 900; }
    .pri-std { color: var(--text-light); font-weight: 700; }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid #8b5cf6;">
    <h1 style="color: #8b5cf6; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">IT Support Desk</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage network issues, hardware repairs, and software licensing.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-ticket-alt stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Tickets Logged</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-spinner stat-icon" style="color:#f59e0b;"></i>
        <div>
            <div class="stat-val" style="color:#f59e0b;"><?= $open ?></div>
            <div class="stat-lbl">Open Issues</div>
        </div>
    </div>
    <div class="stat-card" style="<?= $critical > 0 ? 'border-color: var(--brand-crimson);' : '' ?>">
        <i class="fas fa-exclamation-circle stat-icon" style="<?= $critical > 0 ? 'color:var(--brand-crimson);' : '' ?>"></i>
        <div>
            <div class="stat-val" style="<?= $critical > 0 ? 'color:var(--brand-crimson);' : '' ?>"><?= $critical ?></div>
            <div class="stat-lbl">Critical Priority</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px;">
        <select id="filterStatus" onchange="filterMatrix()" style="width: auto; padding: 10px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option>All Statuses</option>
            <option>Open</option>
            <option>Resolved</option>
        </select>
        <select id="filterCat" onchange="filterMatrix()" style="width: auto; padding: 10px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option>All Categories</option>
            <option>Hardware</option>
            <option>Software</option>
            <option>Network</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button class="btn-action" onclick="systemToast('Exporting Tickets to CSV...')"><i class="fas fa-file-csv"></i> Export CSV</button>
        <button class="btn-primary" style="margin:0; padding: 10px 20px;" onclick="openModal()"><i class="fas fa-plus"></i> New Ticket</button>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:1%;">Ticket ID</th>
                <th>Issue Description</th>
                <th>Reported By / Location</th>
                <th>Priority</th>
                <th>Status</th>
                <th class="action-col">Action</th>
            </tr>
        </thead>
        <tbody id="filterTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT *, TIMESTAMPDIFF(HOUR, created_at, NOW()) as age FROM it_tickets ORDER BY status ASC, priority ASC, created_at DESC");
            while($row = mysqli_fetch_assoc($res)) {
                $stat_class = $row['status'] == 'Open' ? 'status-open' : 'status-resolved';
                $pri_class = $row['priority'] == 'Critical' ? 'pri-crit' : 'pri-std';
                
                $icon = 'fa-laptop';
                if($row['category'] == 'Network') $icon = 'fa-wifi';
                if($row['category'] == 'Software') $icon = 'fa-code';
                
                $age_str = $row['status'] == 'Resolved' ? "Done" : $row['age'] . "h ago";

                echo "
                <tr data-stat='{$row['status']}' data-cat='{$row['category']}'>
                    <td style='font-family:monospace; font-weight:800; color:var(--brand-secondary); font-size:1.1rem;'>{$row['ticket_id']}<br><span style='font-size:0.75rem; color:var(--text-light); font-family:var(--body-font);'><i class='far fa-clock'></i> {$age_str}</span></td>
                    <td><strong style='color:var(--text-dark); font-size:1.05rem;'><i class='fas {$icon}' style='color:var(--brand-secondary); margin-right:8px;'></i> {$row['issue']}</strong><br><span style='font-size:0.8rem; color:var(--text-light);'>Tech: {$row['assigned_tech']}</span></td>
                    <td><strong style='color:var(--text-dark);'>{$row['reported_by']}</strong><br><span style='font-size:0.8rem; font-weight:800; color:var(--text-light);'><i class='fas fa-map-marker-alt'></i> {$row['location']}</span></td>
                    <td class='{$pri_class}'>{$row['priority']}</td>
                    <td><span class='status-pill {$stat_class}'>{$row['status']}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>";
                        if($row['status'] == 'Open') {
                            echo "<a href='?resolve={$row['id']}' class='table-btn btn-resolve'><i class='fas fa-check'></i> Resolve</a>";
                        } else {
                            echo "<span class='table-btn btn-closed'><i class='fas fa-check-double'></i> Closed</span>";
                        }
                echo "<a href='?del={$row['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
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
        <button class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-ticket-alt" style="color:var(--brand-secondary);"></i> Create IT Ticket</h2>
        <form method="POST">
            <input type="hidden" name="save_ticket" value="1">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="issue" placeholder="Issue Description" style="grid-column: span 2;" required>
                
                <select name="category" required>
                    <option value="" disabled selected>Select Category</option>
                    <option value="Hardware">Hardware</option>
                    <option value="Software">Software</option>
                    <option value="Network">Network</option>
                </select>
                
                <select name="priority" required>
                    <option value="Standard">Standard Priority</option>
                    <option value="Critical">Critical Priority</option>
                </select>
                
                <input type="text" name="reported_by" placeholder="Reported By (Name)" required>
                <input type="text" name="location" placeholder="Location (e.g. Room 101)" required>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px;"><i class="fas fa-paper-plane"></i> Submit Ticket</button>
        </form>
    </div>
</div>

<script>
function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const cFilter = document.getElementById('filterCat').value;
    const rows = document.querySelectorAll('#filterTableBody tr');
    
    rows.forEach(row => {
        const rStat = row.getAttribute('data-stat');
        const rCat = row.getAttribute('data-cat');
        let show = true;
        if (sFilter !== 'All Statuses' && rStat !== sFilter) show = false;
        if (cFilter !== 'All Categories' && rCat !== cFilter) show = false;
        row.style.display = show ? '' : 'none';
    });
}

function openModal() {
    document.getElementById('crudModal').style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>