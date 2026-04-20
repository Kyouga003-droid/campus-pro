<?php 
include 'config.php'; 

$patch = "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150),
    event_date DATE,
    event_time TIME,
    location VARCHAR(150),
    organizer VARCHAR(100),
    department VARCHAR(50) DEFAULT 'General',
    description TEXT,
    rsvp_count INT DEFAULT 0,
    max_capacity INT DEFAULT 100
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

$cols = [
    "event_name VARCHAR(150)", "event_date DATE", "event_time TIME", 
    "location VARCHAR(150)", "organizer VARCHAR(100)", 
    "department VARCHAR(50) DEFAULT 'General'", "description TEXT", 
    "rsvp_count INT DEFAULT 0", "max_capacity INT DEFAULT 100",
    "event_status VARCHAR(20) DEFAULT 'Upcoming'", 
    "meeting_url VARCHAR(255)", 
    "banner_image VARCHAR(255)", 
    "target_audience VARCHAR(50) DEFAULT 'All'", 
    "sponsor_info VARCHAR(150)", 
    "is_featured BOOLEAN DEFAULT 0"
];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE events ADD COLUMN $c"); } catch (Exception $e) {} }

// Auto-archive past events seamlessly
mysqli_query($conn, "UPDATE events SET event_status = 'Completed' WHERE event_date < CURDATE() AND event_status = 'Upcoming'");

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM events WHERE id = $id");
    header("Location: events.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_action'])) {
    if(!empty($_POST['sel_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['sel_ids']));
        if ($_POST['mass_action_type'] === 'cancel') {
            mysqli_query($conn, "UPDATE events SET event_status = 'Canceled' WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'feature') {
            mysqli_query($conn, "UPDATE events SET is_featured = 1 WHERE id IN ($ids)");
        } elseif ($_POST['mass_action_type'] === 'delete') {
            mysqli_query($conn, "DELETE FROM events WHERE id IN ($ids)");
        }
    }
    header("Location: events.php"); exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_event'])) {
    $en = mysqli_real_escape_string($conn, $_POST['event_name']);
    $ed = mysqli_real_escape_string($conn, $_POST['event_date']);
    $et = mysqli_real_escape_string($conn, $_POST['event_time']);
    $lc = mysqli_real_escape_string($conn, $_POST['location']);
    $og = mysqli_real_escape_string($conn, $_POST['organizer']);
    $dp = mysqli_real_escape_string($conn, $_POST['department']);
    $ds = mysqli_real_escape_string($conn, $_POST['description']);
    $rc = intval($_POST['rsvp_count']);
    $mc = intval($_POST['max_capacity']);
    
    $es = mysqli_real_escape_string($conn, $_POST['event_status']);
    $mu = mysqli_real_escape_string($conn, $_POST['meeting_url']);
    $ta = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $si = mysqli_real_escape_string($conn, $_POST['sponsor_info']);
    $if = isset($_POST['is_featured']) ? 1 : 0;

    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE events SET event_name='$en', event_date='$ed', event_time='$et', location='$lc', organizer='$og', department='$dp', description='$ds', rsvp_count=$rc, max_capacity=$mc, event_status='$es', meeting_url='$mu', target_audience='$ta', sponsor_info='$si', is_featured=$if WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO events (event_name, event_date, event_time, location, organizer, department, description, rsvp_count, max_capacity, event_status, meeting_url, target_audience, sponsor_info, is_featured) VALUES ('$en', '$ed', '$et', '$lc', '$og', '$dp', '$ds', $rc, $mc, '$es', '$mu', '$ta', '$si', $if)");
    }
    header("Location: events.php"); exit();
}

include 'header.php';

$total = getCount($conn, 'events');
$upcoming = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM events WHERE event_status='Upcoming'"))['c'] ?? 0;
$this_week = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"))['c'] ?? 0;
$full = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM events WHERE rsvp_count >= max_capacity AND event_status='Upcoming'"))['c'] ?? 0;
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--card-bg); padding: 25px; border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); display: flex; align-items: center; gap: 20px; border-radius: 16px; transition: 0.2s;}
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .stat-icon { font-size: 2rem; color: var(--brand-secondary); display:flex; align-items:center; justify-content:center; width:50px; height:50px; background:var(--main-bg); border-radius:12px; }
    .stat-val { font-size: 1.8rem; font-weight: 700; color: var(--text-dark); line-height: 1; margin-bottom: 4px; }
    .stat-lbl { font-size: 0.8rem; font-weight: 500; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px;}

    .ctrl-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 25px; background: var(--card-bg); border: 1px solid var(--border-color); box-shadow: var(--soft-shadow); border-radius: 16px; flex-wrap: wrap; gap: 15px;}
    
    .status-pill { padding: 6px 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 20px; display:inline-block; }
    .st-Upcoming { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
    .st-Ongoing { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .st-Completed { background: var(--bg-grid); color: var(--text-light); border: 1px solid var(--border-color); }
    .st-Canceled { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    
    .view-toggle { display: flex; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; padding:2px;}
    .view-btn { padding: 8px 16px; cursor: pointer; color: var(--text-light); transition: 0.2s; font-size: 1rem; border:none; background:transparent; border-radius: 18px;}
    .view-btn:hover { color: var(--text-dark); }
    .view-btn.active-view { background: var(--card-bg); color: var(--text-dark); box-shadow: var(--soft-shadow);}
    
    .data-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .data-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--soft-shadow); transition: 0.3s; display: flex; flex-direction: column; position: relative; overflow: hidden;}
    .data-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-color: var(--text-light); }
    
    .dc-cover { width: 100%; height: 120px; background: var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--text-light); position:relative;}
    .dc-body { padding: 20px; display:flex; flex-direction:column; flex:1; }
    .dc-title { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px;}
    .dc-sub { font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px;}
    
    .rsvp-wrap { display:flex; align-items:center; gap:10px; margin-top:auto;}
    .rsvp-ring { width: 40px; height: 40px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; background: conic-gradient(var(--brand-secondary) var(--pct), var(--main-bg) 0); position:relative;}
    .rsvp-ring::after { content:''; position:absolute; width:32px; height:32px; background:var(--card-bg); border-radius:50%;}
    .rsvp-text { font-size:0.8rem; color:var(--text-light); }
    .rsvp-ring span { z-index:2; color:var(--text-dark); }
    
    .cb-sel { width: 18px; height: 18px; accent-color: var(--text-dark); cursor: pointer; }
    .flt-sel { border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 20px; background: transparent; color: var(--text-dark); font-weight: 500; font-size: 0.9rem; outline:none; }
    .flt-sel:focus { border-color: var(--text-light); }

    .pagination-ctrl { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px;}
    .page-btn { background: var(--card-bg); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; color: var(--text-dark); transition: 0.2s; box-shadow: var(--soft-shadow);}
    .page-btn:hover { background: var(--main-bg); }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow:none;}

    .feat-badge { position:absolute; top:10px; left:10px; background:#f59e0b; color:#fff; padding:4px 8px; border-radius:6px; font-size:0.7rem; font-weight:700; z-index:2;}
    .timer-text { font-size:0.8rem; color:#f59e0b; font-weight:600; margin-top:4px; display:inline-block;}
    
    /* Hover Table Action Reveal */
    .row-actions { opacity: 0; transition: 0.2s; transform: translateX(10px); }
    tr:hover .row-actions { opacity: 1; transform: translateX(0); }
</style>

<div style="margin-bottom: 30px;">
    <h1 style="font-size: 2.2rem; font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px;">Events & Calendar</h1>
    <p style="color: var(--text-light); font-size: 1rem; margin-top: 5px;">Manage campus events, ticketing, RSVPs, and virtual meetings.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Total Events</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#3b82f6;"><i class="fas fa-clock"></i></div>
        <div><div class="stat-val"><?= $upcoming ?></div><div class="stat-lbl">Upcoming</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b981;"><i class="fas fa-calendar-week"></i></div>
        <div><div class="stat-val"><?= $this_week ?></div><div class="stat-lbl">This Week</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#ef4444;"><i class="fas fa-ticket-alt"></i></div>
        <div><div class="stat-val"><?= $full ?></div><div class="stat-lbl">Sold Out</div></div>
    </div>
</div>

<form method="GET" class="ctrl-bar">
    <div style="display:flex; gap: 15px; align-items:center; flex-wrap:wrap;">
        <div class="view-toggle">
            <button type="button" id="btnViewTable" class="view-btn active-view" onclick="setView('table')"><i class="fas fa-list"></i></button>
            <button type="button" id="btnViewGrid" class="view-btn" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        </div>
        <input type="text" id="searchEventLocal" onkeyup="filterMatrix()" placeholder="Search events..." class="flt-sel" style="width: 250px;">
        <select id="filterStatus" class="flt-sel" onchange="filterMatrix()">
            <option value="All">All Statuses</option>
            <option value="Upcoming">Upcoming</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Completed">Completed</option>
            <option value="Canceled">Canceled</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <button type="button" class="btn-action" onclick="downloadCSV('eventTable', 'events_export')"><i class="fas fa-download"></i> Export</button>
        <button type="button" class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New Event</button>
    </div>
</form>

<form method="POST" id="massForm">
    <input type="hidden" name="mass_action" value="1">
    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center; background: var(--card-bg); padding: 15px 25px; border: 1px solid var(--border-color); border-radius: 12px; box-shadow:var(--soft-shadow);">
        <span style="font-weight: 600; font-size:0.9rem;">Batch Action:</span>
        <select name="mass_action_type" class="flt-sel" style="padding: 8px 15px;">
            <option value="cancel">Cancel Events</option>
            <option value="feature">Mark as Featured</option>
            <option value="delete">Delete Records</option>
        </select>
        <button type="submit" class="btn-action" style="padding:8px 16px;" onclick="return confirm('Execute batch operation?')">Apply</button>
    </div>

    <div id="tableView" class="table-responsive">
        <table id="eventTable">
            <thead>
                <tr>
                    <th style="width:1%;"><input type="checkbox" class="cb-sel" onclick="document.querySelectorAll('.cb-item').forEach(c => c.checked = this.checked)"></th>
                    <th>Event Details</th>
                    <th>Schedule & Location</th>
                    <th>RSVP Status</th>
                    <th>State</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody id="filterTableBody">
                <?php
                $res = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date ASC, event_time ASC");
                $all_data = [];
                $now = new DateTime();
                
                while($row = mysqli_fetch_assoc($res)) {
                    $all_data[] = $row;
                    $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    $pct = $row['max_capacity'] > 0 ? min(100, ($row['rsvp_count'] / $row['max_capacity']) * 100) : 0;
                    
                    $ev_date = new DateTime($row['event_date']);
                    $diff = $now->diff($ev_date);
                    $timer = "";
                    if($row['event_status'] == 'Upcoming') {
                        if($diff->invert) { $timer = "Past"; }
                        else { $timer = "In " . $diff->days . " days"; }
                    }
                    
                    $feat = $row['is_featured'] ? "<i class='fas fa-star' style='color:#f59e0b;' title='Featured'></i>" : "";

                    echo "
                    <tr class='paginate-row filter-target' data-stat='{$row['event_status']}'>
                        <td><input type='checkbox' name='sel_ids[]' value='{$row['id']}' class='cb-item cb-sel'></td>
                        <td>
                            <strong style='font-size:1.05rem; color:var(--text-dark);'>{$row['event_name']}</strong> {$feat}<br>
                            <span style='font-size:0.8rem; color:var(--text-light);'>Org: {$row['organizer']}</span>
                        </td>
                        <td>
                            <div style='font-weight:500;'><i class='far fa-calendar'></i> " . date('M d, Y', strtotime($row['event_date'])) . " at " . date('h:i A', strtotime($row['event_time'])) . "</div>
                            <div style='font-size:0.8rem; color:var(--text-light); margin-top:4px;'><i class='fas fa-map-marker-alt'></i> {$row['location']}</div>
                            " . ($timer ? "<div class='timer-text'><i class='fas fa-stopwatch'></i> $timer</div>" : "") . "
                        </td>
                        <td>
                            <div style='font-size:0.9rem; font-weight:600;'>{$row['rsvp_count']} / {$row['max_capacity']}</div>
                            <div style='width:100px; height:4px; background:var(--main-bg); border-radius:2px; margin-top:5px; overflow:hidden;'>
                                <div style='height:100%; background:var(--brand-secondary); width:{$pct}%;'></div>
                            </div>
                        </td>
                        <td><span class='status-pill st-{$row['event_status']}'>{$row['event_status']}</span></td>
                        <td class='action-col'>
                            <div class='table-actions-cell row-actions'>
                                <button type='button' class='table-btn' title='Export ICS'><i class='far fa-calendar-plus'></i></button>
                                <button type='button' class='table-btn' onclick='openModal({$js})'><i class='fas fa-pen'></i></button>
                                <a href='?del={$row['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
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
        foreach($all_data as $row) {
            $js = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            $pct = $row['max_capacity'] > 0 ? min(100, ($row['rsvp_count'] / $row['max_capacity']) * 100) : 0;
            
            echo "
            <div class='data-card paginate-card filter-target' data-stat='{$row['event_status']}'>
                <div class='dc-cover'>
                    " . ($row['is_featured'] ? "<div class='feat-badge'>Featured</div>" : "") . "
                    <i class='far fa-image'></i>
                </div>
                <div class='dc-body'>
                    <div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;'>
                        <div class='dc-title'>{$row['event_name']}</div>
                        <span class='status-pill st-{$row['event_status']}' style='font-size:0.65rem;'>{$row['event_status']}</span>
                    </div>
                    <div class='dc-sub'><i class='far fa-calendar'></i> " . date('M d, Y', strtotime($row['event_date'])) . "</div>
                    <div class='dc-sub' style='margin-bottom:20px;'><i class='fas fa-map-marker-alt'></i> {$row['location']}</div>
                    
                    <div class='rsvp-wrap'>
                        <div class='rsvp-ring' style='--pct: {$pct}%'><span>".round($pct)."%</span></div>
                        <div class='rsvp-text'>
                            <strong style='color:var(--text-dark);'>{$row['rsvp_count']}</strong> attending<br>
                            of {$row['max_capacity']} capacity
                        </div>
                        
                        <div style='margin-left:auto; display:flex; gap:5px;'>
                            <button type='button' class='table-btn' onclick='openModal({$js})'><i class='fas fa-pen'></i></button>
                            <a href='?del={$row['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
                        </div>
                    </div>
                </div>
            </div>";
        }
        ?>
    </div>

    <div class="pagination-ctrl">
        <button type="button" class="page-btn" id="prevPage" onclick="changePage(-1)"><i class="fas fa-chevron-left"></i> Prev</button>
        <span style="font-weight:600; font-size:0.9rem;" id="pageIndicator">Page 1</span>
        <button type="button" class="page-btn" id="nextPage" onclick="changePage(1)">Next <i class="fas fa-chevron-right"></i></button>
    </div>
</form>

<div id="crudModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 700px;">
        <button type="button" class="modal-close" onclick="document.getElementById('crudModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 25px;"><i class="fas fa-bullhorn"></i> Event Configuration</h2>
        
        <form method="POST">
            <input type="hidden" name="save_event" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="event_name" id="event_name" placeholder="Event Title" required style="grid-column: span 2;">
                
                <input type="date" name="event_date" id="event_date" required>
                <input type="time" name="event_time" id="event_time" required>
                
                <input type="text" name="location" id="location" placeholder="Physical Location" required>
                <input type="text" name="meeting_url" id="meeting_url" placeholder="Virtual Link (Optional)">
                
                <input type="text" name="organizer" id="organizer" placeholder="Organizer / Host" required>
                <select name="department" id="department" required>
                    <option value="General">General / All</option>
                    <option value="Computer Studies">Computer Studies</option>
                    <option value="Business">Business</option>
                    <option value="Engineering">Engineering</option>
                </select>

                <select name="event_status" id="event_status" required>
                    <option value="Upcoming">Status: Upcoming</option>
                    <option value="Ongoing">Status: Ongoing</option>
                    <option value="Completed">Status: Completed</option>
                    <option value="Canceled">Status: Canceled</option>
                </select>
                <select name="target_audience" id="target_audience">
                    <option value="All">Audience: All Campus</option>
                    <option value="Students">Audience: Students Only</option>
                    <option value="Faculty">Audience: Faculty Only</option>
                    <option value="Public">Audience: Public</option>
                </select>
                
                <input type="number" name="rsvp_count" id="rsvp_count" placeholder="Current RSVPs" value="0">
                <input type="number" name="max_capacity" id="max_capacity" placeholder="Max Capacity" value="100" required>
                
                <input type="text" name="sponsor_info" id="sponsor_info" placeholder="Sponsor / Funding Source" style="grid-column: span 2;">
                <textarea name="description" id="description" placeholder="Event Description..." style="grid-column: span 2; height:80px; resize:none;"></textarea>

                <div style="grid-column: span 2; display:flex; gap: 20px; padding:15px; border:1px solid var(--border-color); border-radius:8px; background:var(--main-bg);">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="is_featured" id="is_featured" class="cb-sel">
                        <label for="is_featured" style="font-weight:500; font-size:0.85rem; cursor:pointer;">Feature on Dashboard</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; justify-content:center;">Save Event</button>
        </form>
    </div>
</div>

<script>
let currentView = 'table';
let currentPage = 1;
const itemsPerPage = 8;

function setView(view) {
    currentView = view;
    const table = document.getElementById('tableView');
    const grid = document.getElementById('gridView');
    const btnTable = document.getElementById('btnViewTable');
    const btnGrid = document.getElementById('btnViewGrid');
    
    if(view === 'grid') {
        table.style.display = 'none'; grid.style.display = 'grid';
        btnGrid.classList.add('active-view'); btnTable.classList.remove('active-view');
        localStorage.setItem('campus_event_view', 'grid');
    } else {
        table.style.display = 'block'; grid.style.display = 'none';
        btnTable.classList.add('active-view'); btnGrid.classList.remove('active-view');
        localStorage.setItem('campus_event_view', 'table');
    }
    paginate();
}

function paginate() {
    const selector = currentView === 'table' ? '.paginate-row' : '.paginate-card';
    const items = Array.from(document.querySelectorAll(selector)).filter(i => !i.hasAttribute('data-hide-local'));
    const totalPages = Math.ceil(items.length / itemsPerPage) || 1;
    
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    document.getElementById('pageIndicator').innerText = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;
    
    document.querySelectorAll(selector).forEach(item => item.style.display = 'none');
    items.forEach((item, index) => {
        if (index >= (currentPage - 1) * itemsPerPage && index < currentPage * itemsPerPage) {
            item.style.display = currentView === 'table' ? 'table-row' : 'flex';
        }
    });
}

function changePage(delta) { currentPage += delta; paginate(); }

function filterMatrix() {
    const sFilter = document.getElementById('filterStatus').value;
    const searchQ = document.getElementById('searchEventLocal').value.toLowerCase();
    
    document.querySelectorAll('.filter-target').forEach(el => {
        const rStat = el.getAttribute('data-stat');
        const rText = el.innerText.toLowerCase();
        let show = true;
        
        if (sFilter !== 'All' && rStat !== sFilter) show = false;
        if (searchQ !== '' && !rText.includes(searchQ)) show = false;
        
        if(show) el.removeAttribute('data-hide-local'); 
        else el.setAttribute('data-hide-local', 'true'); 
    });
    currentPage = 1;
    paginate();
}

function openModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen"></i> Edit Event';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('event_name').value = data.event_name;
        document.getElementById('event_date').value = data.event_date;
        document.getElementById('event_time').value = data.event_time;
        document.getElementById('location').value = data.location;
        document.getElementById('organizer').value = data.organizer;
        document.getElementById('department').value = data.department;
        document.getElementById('description').value = data.description;
        document.getElementById('rsvp_count').value = data.rsvp_count || 0;
        document.getElementById('max_capacity').value = data.max_capacity || 100;
        document.getElementById('event_status').value = data.event_status || 'Upcoming';
        document.getElementById('meeting_url').value = data.meeting_url || '';
        document.getElementById('target_audience').value = data.target_audience || 'All';
        document.getElementById('sponsor_info').value = data.sponsor_info || '';
        document.getElementById('is_featured').checked = data.is_featured == 1;
    } else {
        title.innerHTML = '<i class="fas fa-bullhorn"></i> Publish Event';
        document.getElementById('edit_id').value = '';
        document.getElementById('event_name').value = '';
        document.getElementById('event_date').value = '';
        document.getElementById('event_time').value = '';
        document.getElementById('location').value = '';
        document.getElementById('organizer').value = '';
        document.getElementById('department').value = 'General';
        document.getElementById('description').value = '';
        document.getElementById('rsvp_count').value = '0';
        document.getElementById('max_capacity').value = '100';
        document.getElementById('event_status').value = 'Upcoming';
        document.getElementById('meeting_url').value = '';
        document.getElementById('target_audience').value = 'All';
        document.getElementById('sponsor_info').value = '';
        document.getElementById('is_featured').checked = false;
    }
    modal.style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => { 
    setView(localStorage.getItem('campus_event_view') || 'table'); 
});
</script>

<?php include 'footer.php'; ?>