<?php 
include 'config.php'; 

$patch_queries = [
    "ALTER TABLE appointments ADD COLUMN time_slot VARCHAR(50)",
    "ALTER TABLE appointments ADD COLUMN notes VARCHAR(255)",
    "ALTER TABLE appointments ADD COLUMN status VARCHAR(20) DEFAULT 'Pending'"
];
foreach($patch_queries as $q) { 
    try { mysqli_query($conn, $q); } catch (mysqli_sql_exception $e) { continue; }
}

$success = false;

if(isset($_GET['advance_status'])) {
    $id = intval($_GET['advance_status']);
    $res = mysqli_query($conn, "SELECT status FROM appointments WHERE id = $id");
    if($row = mysqli_fetch_assoc($res)) {
        $new_status = 'Pending';
        if($row['status'] == 'Pending') $new_status = 'Confirmed';
        elseif($row['status'] == 'Confirmed') $new_status = 'Completed';
        mysqli_query($conn, "UPDATE appointments SET status = '$new_status' WHERE id = $id");
        logAction($conn, "Updated appointment ID $id to $new_status");
        $success = true;
    }
}

if(isset($_POST['add'])){
    $cn = mysqli_real_escape_string($conn, $_POST['cn']);
    $pp = mysqli_real_escape_string($conn, $_POST['pp']); 
    $dt = mysqli_real_escape_string($conn, $_POST['dt']);
    $ts = mysqli_real_escape_string($conn, $_POST['ts']);
    $nt = mysqli_real_escape_string($conn, $_POST['nt']);
    
    mysqli_query($conn, "INSERT INTO appointments (client_name, purpose, appt_date, time_slot, notes, status) VALUES ('$cn', '$pp', '$dt', '$ts', '$nt', 'Pending')");
    logAction($conn, "Booked appointment for $cn");
    $success = true;
}

include 'header.php'; 
if($success){ showSuccessScreen("appointments.php"); include 'footer.php'; exit(); }

$today_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM appointments WHERE appt_date = CURDATE()"))['c'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM appointments WHERE status IN ('Pending', 'Confirmed')"))['c'];
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
    <div class="card" style="padding:15px 25px; margin:0; border-left: 4px solid var(--brand-primary); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <p style="font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:5px;">Today's Schedule</p>
            <h2 style="font-size:2rem; margin:0; color:var(--text-dark);"><?= $today_count ?> Meetings</h2>
        </div>
        <i class="fas fa-calendar-day" style="font-size:3rem; color:var(--brand-secondary); opacity:0.3;"></i>
    </div>
    <div class="card" style="padding:15px 25px; margin:0; border-left: 4px solid #f59e0b; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <p style="font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:5px;">Action Required</p>
            <h2 style="font-size:2rem; margin:0; color:var(--text-dark);"><?= $pending_count ?> Active</h2>
        </div>
        <i class="fas fa-clock" style="font-size:3rem; color:#fcd34d; opacity:0.3;"></i>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-calendar-plus"></i> Schedule Appointment</h3>
    <form method="POST" class="form-grid">
        <input type="text" name="cn" placeholder="Client / Visitor Name" required>
        <input type="text" name="pp" placeholder="Purpose of Meeting" required>
        <input type="date" name="dt" required>
        <select name="ts" required>
            <option value="" disabled selected>Select Time Slot</option>
            <option value="08:00 AM - 10:00 AM">08:00 AM - 10:00 AM</option>
            <option value="10:00 AM - 12:00 PM">10:00 AM - 12:00 PM</option>
            <option value="01:00 PM - 03:00 PM">01:00 PM - 03:00 PM</option>
            <option value="03:00 PM - 05:00 PM">03:00 PM - 05:00 PM</option>
        </select>
        <input type="text" name="nt" placeholder="Additional Notes (Optional)" style="grid-column: span 2;">
        <button type="submit" name="add" class="btn-primary" style="grid-column: 1 / -1;"><i class="fas fa-check"></i> Confirm Booking</button>
    </form>
</div>

<div class="card" style="padding: 0;">
    <div class="flex-between" style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); margin:0;">
        <h3 style="margin:0;"><i class="fas fa-list-alt"></i> Master Itinerary</h3>
        <button onclick="downloadCSV('apptTable', 'Appointments')" class="btn-action btn-export"><i class="fas fa-file-csv"></i> Export Data</button>
    </div>
    <div class="table-responsive">
        <table id="apptTable">
            <tr><th>Client Details</th><th>Schedule</th><th>Notes</th><th>Status</th><th>Actions</th></tr>
            <?php
            $res = mysqli_query($conn, "SELECT * FROM appointments ORDER BY appt_date ASC, time_slot ASC");
            while($row = mysqli_fetch_assoc($res)){
                $st = $row['status'];
                if($st == 'Pending') { $c = '#92400e'; $bg = '#fef3c7'; }
                elseif($st == 'Confirmed') { $c = '#1e40af'; $bg = '#dbeafe'; }
                else { $c = '#065f46'; $bg = '#d1fae5'; }
                
                $is_past = (strtotime($row['appt_date']) < strtotime(date('Y-m-d'))) && $st != 'Completed';
                $row_style = $is_past ? "opacity: 0.6; background: #f8fafc;" : "";

                echo "<tr style='$row_style'>
                        <td>
                            <strong style='font-size:1.05rem; display:block;'>{$row['client_name']}</strong>
                            <span style='font-size:0.8rem; color:var(--text-light);'>{$row['purpose']}</span>
                        </td>
                        <td>
                            <div style='font-weight:700; color:var(--text-dark);'>" . date('M d, Y', strtotime($row['appt_date'])) . "</div>
                            <div style='font-size:0.8rem; color:var(--brand-primary);'>{$row['time_slot']}</div>
                        </td>
                        <td style='font-size:0.85rem; font-style:italic; color:var(--text-light); width:25%;'>{$row['notes']}</td>
                        <td><span class='status-pill' style='background:$bg; color:$c;'>{$st}</span></td>
                        <td>
                            <a href='appointments.php?advance_status={$row['id']}' class='btn-action btn-tog'><i class='fas fa-arrow-right'></i> Update</a>
                            <a href='actions.php?table=appointments&delete={$row['id']}' class='btn-action btn-del'><i class='fas fa-trash'></i></a>
                        </td>
                      </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='5' style='text-align:center; padding:30px;'>No appointments scheduled.</td></tr>";
            ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>