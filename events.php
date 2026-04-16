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

$cols = ["event_name VARCHAR(150)", "event_date DATE", "event_time TIME", "location VARCHAR(150)", "organizer VARCHAR(100)", "department VARCHAR(50) DEFAULT 'General'", "description TEXT", "rsvp_count INT DEFAULT 0", "max_capacity INT DEFAULT 100"];
foreach($cols as $c) { try { mysqli_query($conn, "ALTER TABLE events ADD COLUMN $c"); } catch (Exception $e) {} }

if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM events WHERE id = $id");
    header("Location: events.php"); exit();
}

if(isset($_GET['wipe_past'])) {
    mysqli_query($conn, "DELETE FROM events WHERE event_date < CURDATE()");
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
    $rv = intval($_POST['rsvp_count']);
    $mc = intval($_POST['max_capacity']);
    
    if(!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        mysqli_query($conn, "UPDATE events SET event_name='$en', event_date='$ed', event_time='$et', location='$lc', organizer='$og', department='$dp', description='$ds', rsvp_count=$rv, max_capacity=$mc WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO events (event_name, event_date, event_time, location, organizer, department, description, rsvp_count, max_capacity) VALUES ('$en', '$ed', '$et', '$lc', '$og', '$dp', '$ds', $rv, $mc)");
    }
    header("Location: events.php"); exit();
}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM events");
if(mysqli_fetch_assoc($check)['c'] == 0) {
    $seed_events = [
        ['Freshman Orientation', 'Auditorium', 'Admin Board', 'Administration', 'Welcome ceremony for the incoming 2026 batch.', 2, 450, 500],
        ['Tech Career Fair', 'Main Quad', 'CS Dept', 'Computer Studies', 'Networking event with top tech companies.', 5, 200, 300],
        ['Board of Regents Meeting', 'Conference Room A', 'Office of the President', 'Administration', 'Quarterly financial and operational review.', 0, 15, 20],
        ['Engineering Symposium', 'Lecture Hall 1', 'Engineering Council', 'Engineering', 'Guest lectures on sustainable infrastructure.', 12, 120, 150],
        ['Business Pitch Deck', 'Business Wing', 'Business Society', 'Business', 'Final year students pitch startup ideas.', 8, 85, 100],
        ['Campus Maintenance Audit', 'All Facilities', 'Facilities Dept', 'Operations', 'Full systemic check of air conditioning units.', -5, 10, 10],
        ['Art & Design Exhibit', 'Campus Gallery', 'Arts Council', 'Academics', 'Showcase of student multimedia projects.', 15, 40, 100],
        ['Midterm Examinations', 'All Classrooms', 'Registrar', 'Academics', 'Standardized testing period begins.', 20, 1500, 1500],
        ['Alumni Homecoming', 'Grand Hall', 'Alumni Association', 'Administration', 'Annual gathering of previous graduating batches.', 30, 800, 1000],
        ['IT Server Upgrades', 'Server Room', 'IT Desk', 'Operations', 'Scheduled downtime for core database patching.', -2, 5, 5],
        ['Mental Health Seminar', 'Student Center', 'Counseling', 'Academics', 'Open forum on stress management.', 3, 45, 80],
        ['Cybersecurity Hackathon', 'Lab 3', 'CS Dept', 'Computer Studies', '12-hour coding challenge.', 7, 60, 60],
        ['Robotics Showcase', 'Engineering Hub', 'Eng Society', 'Engineering', 'Demonstration of autonomous drones.', 14, 90, 150],
        ['Spring Concert', 'Amphitheater', 'Music Dept', 'Arts & Sciences', 'Live performances by student bands.', 25, 300, 400],
        ['Debate Finals', 'Lecture Hall 2', 'Debate Club', 'Academics', 'Inter-departmental debate championship.', 4, 110, 120],
        ['Startup Incubator', 'Business Wing', 'Business Society', 'Business', 'Networking with local investors.', 18, 55, 60],
        ['Fire Drill Training', 'Campus Wide', 'Security', 'Operations', 'Mandatory evacuation procedures.', 1, 2000, 2000],
        ['Faculty Mixer', 'Lounge', 'HR Dept', 'Administration', 'End of month faculty get-together.', 6, 35, 50],
        ['Math Olympiad', 'Main Quad', 'Math Society', 'Academics', 'Annual calculus and algebra competition.', 22, 75, 100],
        ['Job Fair Setup', 'Main Quad', 'Operations', 'Operations', 'Tent and booth installation for vendors.', 4, 20, 20]
    ];
    foreach($seed_events as $item) {
        $days = $item[5];
        $ed = date('Y-m-d', strtotime(($days >= 0 ? '+' : '').$days.' days'));
        $et = date('H:i:s', strtotime(rand(8, 16).':00:00'));
        mysqli_query($conn, "INSERT INTO events (event_name, event_date, event_time, location, organizer, department, description, rsvp_count, max_capacity) VALUES ('{$item[0]}', '$ed', '$et', '{$item[1]}', '{$item[2]}', '{$item[3]}', '{$item[4]}', {$item[6]}, {$item[7]})");
    }
}

include 'header.php';

$total = getCount($conn, 'events');
$upcoming = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM events WHERE event_date >= CURDATE()"))['c'];
$concluded = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM events WHERE event_date < CURDATE()"))['c'];
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
    
    .status-upcoming { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: #3b82f6; }
    .status-today { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .status-concluded { background: var(--main-bg); color: var(--text-light); border-color: var(--text-light); }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid var(--brand-accent);">
    <h1 style="color: var(--brand-accent); font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Campus Event Planner</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Master schedule for academic, operational, and organizational events.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <i class="fas fa-calendar-check stat-icon"></i>
        <div>
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">Total Events Logged</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-bolt stat-icon" style="color:#3b82f6;"></i>
        <div>
            <div class="stat-val" style="color:#3b82f6;"><?= $upcoming ?></div>
            <div class="stat-lbl">Upcoming Schedule</div>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-history stat-icon" style="color:var(--text-light);"></i>
        <div>
            <div class="stat-val" style="color:var(--text-light);"><?= $concluded ?></div>
            <div class="stat-lbl">Past / Concluded</div>
        </div>
    </div>
</div>

<div class="ctrl-bar">
    <div style="display:flex; gap: 15px; flex-wrap:wrap; align-items:center;">
        <input type="text" id="searchEventLocal" onkeyup="filterEvents()" placeholder="&#xf002; Search Title or Location..." style="font-family: var(--body-font), 'Font Awesome 6 Free'; width: 250px; padding: 12px 20px; font-weight: 600; border-width: 2px; margin:0; border-radius:8px;">
        <select id="filterStatus" onchange="filterEvents()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Statuses">All Timelines</option>
            <option value="Upcoming">Upcoming</option>
            <option value="Happening Today">Today</option>
            <option value="Concluded">Concluded</option>
        </select>
        <select id="filterDept" onchange="filterEvents()" style="width: auto; padding: 12px 20px; font-weight: 800; border-width: 2px; margin:0;">
            <option value="All Departments">All Departments</option>
            <option value="Administration">Administration</option>
            <option value="Computer Studies">Computer Studies</option>
            <option value="Business">Business</option>
            <option value="Engineering">Engineering</option>
            <option value="Operations">Operations</option>
            <option value="Academics">Academics</option>
        </select>
    </div>
    <div style="display:flex; gap: 15px;">
        <a href="?wipe_past=true" class="btn-action btn-del" onclick="return confirm('Wipe all past events permanently?');"><i class="fas fa-broom"></i> Wipe Past</a>
        <button class="btn-action" onclick="systemToast('Exporting Schedule to CSV...')"><i class="fas fa-file-export"></i> Export</button>
        <button class="btn-primary" style="margin:0; padding: 12px 25px; background:var(--brand-accent); border-color:var(--brand-accent);" onclick="openEventModal()"><i class="fas fa-bullhorn"></i> Publish Event</button>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width:25%;">Event Details</th>
                <th>Organizer</th>
                <th>Schedule & Location</th>
                <th style="width:18%;">RSVP / Capacity</th>
                <th>Status</th>
                <th class="action-col">Actions</th>
            </tr>
        </thead>
        <tbody id="eventTableBody">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date ASC, event_time ASC");
            while($row = mysqli_fetch_assoc($res)) {
                $dept = isset($row['department']) ? $row['department'] : 'General';
                $event_ts = strtotime($row['event_date']);
                $today_ts = strtotime(date('Y-m-d'));
                $diff_days = round(($event_ts - $today_ts) / (60 * 60 * 24));
                
                if($event_ts < $today_ts) { $st = 'Concluded'; $st_class = 'status-concluded'; $countdown = "Past Event"; }
                elseif($event_ts == $today_ts) { $st = 'Happening Today'; $st_class = 'status-today'; $countdown = "Today"; }
                else { $st = 'Upcoming'; $st_class = 'status-upcoming'; $countdown = "In $diff_days Days"; }

                $row_style = $st == 'Concluded' ? "opacity: 0.65; filter: grayscale(50%);" : "";
                $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $f_date = date('M d, Y', strtotime($row['event_date']));
                $f_time = date('h:i A', strtotime($row['event_time']));
                
                $rsv = intval($row['rsvp_count']);
                $max = intval($row['max_capacity']);
                $pct = $max > 0 ? ($rsv / $max) * 100 : 0;
                $bar_color = $pct > 90 ? '#ef4444' : ($pct > 60 ? '#f59e0b' : '#10b981');

                echo "
                <tr style='$row_style' data-stat='{$st}' data-dept='{$dept}'>
                    <td>
                        <strong style='color:var(--text-dark); font-size:1.15rem; font-family:var(--heading-font);'>{$row['event_name']}</strong>
                        <div style='font-size:0.8rem; color:var(--text-light); margin-top:8px; line-height:1.5; font-weight:600;'>{$row['description']}</div>
                    </td>
                    <td>
                        <strong style='color:var(--brand-secondary);'>{$row['organizer']}</strong><br>
                        <span style='font-size:0.75rem; text-transform:uppercase; color:var(--text-light); font-weight:800;'>{$dept}</span>
                    </td>
                    <td>
                        <div style='font-weight:800; color:var(--text-dark); font-size:1.05rem;'><i class='fas fa-calendar-day' style='color:var(--brand-secondary); margin-right:8px;'></i> {$f_date}</div>
                        <div style='font-size:0.85rem; color:var(--text-light); font-weight:700; margin-top:6px;'><i class='fas fa-clock' style='color:var(--brand-primary); margin-right:8px;'></i> {$f_time} <span style='margin-left:10px; padding:2px 6px; background:var(--bg-grid); border-radius:4px;'>{$countdown}</span></div>
                        <div style='font-size:0.85rem; color:var(--brand-accent); font-weight:800; margin-top:6px;'><i class='fas fa-map-marker-alt' style='margin-right:8px;'></i> {$row['location']}</div>
                    </td>
                    <td>
                        <div style='font-weight:900; color:var(--text-dark);'>{$rsv} / {$max}</div>
                        <div style='width: 100%; height: 6px; background: var(--border-light); margin-top: 6px; border-radius:4px; overflow:hidden;'><div style='height:100%; width:{$pct}%; background:{$bar_color};'></div></div>
                    </td>
                    <td><span class='status-pill {$st_class}'>{$st}</span></td>
                    <td class='action-col'>
                        <div class='table-actions-cell'>
                            <button class='table-btn btn-resolve' onclick='openEventModal($js_data)'><i class='fas fa-pen'></i></button>
                            <a href='?del={$row['id']}' class='table-btn btn-trash'><i class='fas fa-trash'></i></a>
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
        <h2 id="modalTitle" style="font-size: 1.8rem; color: var(--text-dark); margin-bottom: 25px; text-transform: uppercase; font-family: var(--heading-font);"><i class="fas fa-bullhorn" style="color:var(--brand-accent);"></i> Publish Event</h2>
        <form method="POST">
            <input type="hidden" name="save_event" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="event_name" id="event_name" placeholder="Official Event Title" style="grid-column: span 2;" required>
                
                <input type="text" name="organizer" id="organizer" placeholder="Organizer (e.g. CS Council)" required>
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

                <input type="date" name="event_date" id="event_date" required>
                <input type="time" name="event_time" id="event_time" required>
                
                <input type="text" name="location" id="location" placeholder="Venue / Location Details" style="grid-column: span 2;" required>
                
                <input type="number" name="rsvp_count" id="rsvp_count" placeholder="Current RSVPs" value="0" required>
                <input type="number" name="max_capacity" id="max_capacity" placeholder="Max Capacity" required>

                <textarea name="description" id="description" placeholder="Event Description / Notes" style="grid-column: span 2; height: 80px; resize: vertical;" required></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 25px; background:var(--brand-accent); border-color:var(--brand-accent);"><i class="fas fa-save"></i> Save Schedule</button>
        </form>
    </div>
</div>

<script>
function filterEvents() {
    const sFilter = document.getElementById('filterStatus').value;
    const dFilter = document.getElementById('filterDept').value;
    const searchQ = document.getElementById('searchEventLocal').value.toLowerCase();
    const rows = document.querySelectorAll('#eventTableBody tr');
    
    rows.forEach(row => {
        const rStat = row.getAttribute('data-stat');
        const rDept = row.getAttribute('data-dept');
        const rText = row.cells[0].innerText.toLowerCase() + " " + row.cells[2].innerText.toLowerCase();
        
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

function openEventModal(data = null) {
    const modal = document.getElementById('crudModal');
    const title = document.getElementById('modalTitle');
    
    if(data) {
        title.innerHTML = '<i class="fas fa-pen" style="color:var(--brand-accent);"></i> Edit Event';
        document.getElementById('edit_id').value = data.id;
        document.getElementById('event_name').value = data.event_name || '';
        document.getElementById('organizer').value = data.organizer || '';
        document.getElementById('department').value = data.department || 'General';
        document.getElementById('event_date').value = data.event_date || '';
        document.getElementById('event_time').value = data.event_time || '';
        document.getElementById('location').value = data.location || '';
        document.getElementById('rsvp_count').value = data.rsvp_count || '0';
        document.getElementById('max_capacity').value = data.max_capacity || '100';
        document.getElementById('description').value = data.description || '';
    } else {
        title.innerHTML = '<i class="fas fa-bullhorn" style="color:var(--brand-accent);"></i> Publish Event';
        document.getElementById('edit_id').value = '';
        document.getElementById('event_name').value = '';
        document.getElementById('organizer').value = '';
        document.getElementById('department').value = 'General';
        document.getElementById('event_date').value = '';
        document.getElementById('event_time').value = '';
        document.getElementById('location').value = '';
        document.getElementById('rsvp_count').value = '0';
        document.getElementById('max_capacity').value = '100';
        document.getElementById('description').value = '';
    }
    modal.style.display = 'flex';
}
</script>
<?php include 'footer.php'; ?>