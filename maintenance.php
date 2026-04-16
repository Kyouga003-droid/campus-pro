<?php 
include 'config.php'; 
$success = false;
if(isset($_POST['add'])){
    $lc = mysqli_real_escape_string($conn, $_POST['lc']);
    $is = mysqli_real_escape_string($conn, $_POST['is']);
    mysqli_query($conn, "INSERT INTO maintenance_req (location, issue, status) VALUES ('$lc', '$is', 'Pending')");
    $success = true;
}
include 'header.php'; 
if($success){ showSuccessScreen("maintenance.php"); include 'footer.php'; exit(); }
?>
<div class="card">
    <h3><i class="fas fa-tools"></i> Maintenance Requests</h3>
    <form method="POST" class="form-grid">
        <input type="text" name="lc" placeholder="Location (e.g., Room 204)" required>
        <input type="text" name="is" placeholder="Describe the issue..." required>
        <button type="submit" name="add" class="btn-primary">Submit Request</button>
    </form>
</div>
<div class="card" style="padding: 0;">
    <div class="table-responsive">
        <table style="margin-top: 0; border: none;">
            <tr><th>Location</th><th>Issue Description</th><th>Status</th><th>Actions</th></tr>
            <?php
            $res = mysqli_query($conn, "SELECT * FROM maintenance_req ORDER BY id DESC");
            while($row = mysqli_fetch_assoc($res)){
                $isFixed = ($row['status'] == 'Fixed');
                $c = $isFixed ? '#065f46' : '#92400e';
                $bg = $isFixed ? '#d1fae5' : '#fef3c7';
                echo "<tr>
                        <td><strong>{$row['location']}</strong></td>
                        <td>{$row['issue']}</td>
                        <td><span class='status-pill' style='background:$bg; color:$c;'>{$row['status']}</span></td>
                        <td>
                            <a href='actions.php?table=maintenance_req&toggle={$row['id']}' class='btn-tog'><i class='fas fa-check-circle'></i> Toggle</a>
                            <a href='actions.php?table=maintenance_req&delete={$row['id']}' class='btn-del'><i class='fas fa-trash'></i></a>
                        </td>
                      </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='4' style='text-align:center;'>No pending requests.</td></tr>";
            ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>