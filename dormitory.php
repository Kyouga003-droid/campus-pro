<?php 
include 'config.php'; 

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    room_number VARCHAR(50), 
    building VARCHAR(100), 
    room_type VARCHAR(50), 
    capacity INT, 
    occupant VARCHAR(100), 
    check_in DATE, 
    expected_out DATE, 
    status VARCHAR(20) DEFAULT 'Vacant'
)");

$success = false;

if(isset($_GET['vacate'])) {
    $id = intval($_GET['vacate']);
    mysqli_query($conn, "UPDATE rooms SET status = 'Vacant', occupant = NULL, check_in = NULL, expected_out = NULL WHERE id = $id");
    logAction($conn, "Vacated room ID $id");
    $success = true;
}

if(isset($_POST['add'])){
    $rn = mysqli_real_escape_string($conn, $_POST['rn']);
    $bd = mysqli_real_escape_string($conn, $_POST['bd']);
    $rt = mysqli_real_escape_string($conn, $_POST['rt']);
    $cap = intval($_POST['cap']);
    
    mysqli_query($conn, "INSERT INTO rooms (room_number, building, room_type, capacity, status) VALUES ('$rn', '$bd', '$rt', '$cap', 'Vacant')");
    logAction($conn, "Registered new room: $bd - $rn");
    $success = true;
}

if(isset($_POST['assign'])){
    $id = intval($_POST['room_id']);
    $oc = mysqli_real_escape_string($conn, $_POST['oc']);
    $ci = mysqli_real_escape_string($conn, $_POST['ci']);
    $eo = mysqli_real_escape_string($conn, $_POST['eo']);
    
    mysqli_query($conn, "UPDATE rooms SET occupant = '$oc', check_in = '$ci', expected_out = '$eo', status = 'Occupied' WHERE id = $id");
    logAction($conn, "Assigned $oc to room ID $id");
    $success = true;
}

include 'header.php'; 
if($success){ showSuccessScreen("rooms.php"); include 'footer.php'; exit(); }

$total_rooms = getCount($conn, 'rooms');
$occupied_rooms = $total_rooms > 0 ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM rooms WHERE status='Occupied'"))['c'] : 0;
$vacant_rooms = $total_rooms - $occupied_rooms;
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card" style="padding:15px 25px; margin:0; border-left: 4px solid var(--brand-primary);">
        <p style="font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:5px;">Total Properties</p>
        <h2 style="font-size:2.2rem; margin:0; color:var(--text-dark);"><?= $total_rooms ?> Rooms</h2>
    </div>
    <div class="card" style="padding:15px 25px; margin:0; border-left: 4px solid #f59e0b;">
        <p style="font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:5px;">Current Occupancy</p>
        <h2 style="font-size:2.2rem; margin:0; color:#d97706;"><?= $occupied_rooms ?> Occupied</h2>
    </div>
    <div class="card" style="padding:15px 25px; margin:0; border-left: 4px solid #10b981;">
        <p style="font-size:0.8rem; font-weight:800; color:var(--text-light); text-transform:uppercase; margin-bottom:5px;">Available Units</p>
        <h2 style="font-size:2.2rem; margin:0; color:#065f46;"><?= $vacant_rooms ?> Vacant</h2>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-bottom:30px;">
    <div class="card" style="margin:0;">
        <h3><i class="fas fa-door-closed"></i> Register New Room Property</h3>
        <form method="POST">
            <input type="text" name="rn" placeholder="Room Number (e.g., 101-A)" required>
            <input type="text" name="bd" placeholder="Building / Hall Name" required>
            <select name="rt" required>
                <option value="" disabled selected>Select Room Type</option>
                <option value="Standard Single">Standard Single</option>
                <option value="Standard Double">Standard Double</option>
                <option value="Premium Suite">Premium Suite</option>
                <option value="Communal Bunk">Communal Bunk</option>
            </select>
            <input type="number" name="cap" placeholder="Max Occupant Capacity" required>
            <button type="submit" name="add" class="btn-primary" style="width:100%; margin-top:10px;"><i class="fas fa-plus"></i> Add to Database</button>
        </form>
    </div>

    <div class="card" style="margin:0; background:linear-gradient(135deg, #1e1b4b, #312e81); border:none;">
        <h3 style="color:white;"><i class="fas fa-key"></i> Assign Occupant to Vacancy</h3>
        <form method="POST">
            <select name="room_id" required style="background:rgba(255,255,255,0.1); color:white; border-color:rgba(255,255,255,0.2);">
                <option value="" disabled selected style="color:black;">Select Vacant Room</option>
                <?php
                $vacants = @mysqli_query($conn, "SELECT id, building, room_number FROM rooms WHERE status='Vacant' ORDER BY building, room_number");
                if($vacants) while($v = mysqli_fetch_assoc($vacants)) echo "<option value='{$v['id']}' style='color:black;'>{$v['building']} - Room {$v['room_number']}</option>";
                ?>
            </select>
            <select name="oc" required style="background:rgba(255,255,255,0.1); color:white; border-color:rgba(255,255,255,0.2);">
                <option value="" disabled selected style="color:black;">Select Student</option>
                <?php
                $sts = @mysqli_query($conn, "SELECT name FROM students WHERE status='Enrolled' ORDER BY name");
                if($sts) while($s = mysqli_fetch_assoc($sts)) echo "<option value='{$s['name']}' style='color:black;'>{$s['name']}</option>";
                ?>
            </select>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <input type="date" name="ci" title="Check-In Date" required style="background:rgba(255,255,255,0.1); color:white; border-color:rgba(255,255,255,0.2);">
                <input type="date" name="eo" title="Expected Move-Out Date" required style="background:rgba(255,255,255,0.1); color:white; border-color:rgba(255,255,255,0.2);">
            </div>
            <button type="submit" name="assign" class="btn-primary" style="width:100%; margin-top:10px; background:#10b981;"><i class="fas fa-user-check"></i> Assign Room</button>
        </form>
    </div>
</div>

<div class="card" style="padding: 0;">
    <div class="flex-between" style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); margin:0;">
        <h3 style="margin:0;"><i class="fas fa-building"></i> Master Housing Roster</h3>
        <button onclick="downloadCSV('roomTable', 'Housing_Roster')" class="btn-action btn-export"><i class="fas fa-file-csv"></i> Export Housing Data</button>
    </div>
    <div class="table-responsive">
        <table id="roomTable">
            <tr><th>Room & Building</th><th>Room Specs</th><th>Occupant Details</th><th>Status</th><th>Actions</th></tr>
            <?php
            $res = mysqli_query($conn, "SELECT * FROM rooms ORDER BY building ASC, room_number ASC");
            $current_bldg = "";
            while($row = mysqli_fetch_assoc($res)){
                if($row['building'] != $current_bldg) {
                    echo "<tr style='background: #f8fafc;'><td colspan='5' style='font-weight:800; color: #334155; border-left: 4px solid var(--brand-accent); padding:12px 20px;'><i class='fas fa-city' style='color:var(--brand-primary); margin-right:8px;'></i> {$row['building']}</td></tr>";
                    $current_bldg = $row['building'];
                }

                $st = $row['status'];
                $c = $st == 'Occupied' ? '#92400e' : '#065f46';
                $bg = $st == 'Occupied' ? '#fef3c7' : '#d1fae5';
                
                $occStr = $st == 'Occupied' ? "<strong style='font-size:1.05rem; display:block; color:var(--text-dark);'>{$row['occupant']}</strong><span style='font-size:0.8rem; color:var(--text-light);'><i class='fas fa-calendar-check'></i> In: " . date('M d, Y', strtotime($row['check_in'])) . "</span>" : "<span style='font-style:italic; color:var(--text-light);'>Unassigned</span>";

                echo "<tr>
                        <td>
                            <strong style='font-size:1.1rem; color:var(--brand-primary);'>Room {$row['room_number']}</strong>
                        </td>
                        <td>
                            <div style='font-weight:700; color:var(--text-dark);'>{$row['room_type']}</div>
                            <div style='font-size:0.85rem; color:var(--text-light);'><i class='fas fa-users' style='width:15px;'></i> {$row['capacity']} Pax Max</div>
                        </td>
                        <td>{$occStr}</td>
                        <td><span class='status-pill' style='background:$bg; color:$c;'>{$st}</span></td>
                        <td>";
                if($st == 'Occupied') {
                    echo "<a href='rooms.php?vacate={$row['id']}' class='btn-action' style='background:#fee2e2; color:#ef4444; margin-right:10px;' onclick=\"return confirm('Evict occupant?')\"><i class='fas fa-sign-out-alt'></i> Vacate</a>";
                }
                echo "<a href='actions.php?table=rooms&delete={$row['id']}' class='btn-action btn-del'><i class='fas fa-trash'></i></a>
                        </td>
                      </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='5' style='text-align:center; padding:30px;'>No rooms registered in the database.</td></tr>";
            ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>