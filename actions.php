<?php
include 'config.php';
$success = false;
$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

if(isset($_GET['clear_events'])) {
    mysqli_query($conn, "DELETE FROM events WHERE event_date < CURDATE()");
    logAction($conn, "Cleared all past events");
    $success = true;
}

if (isset($_GET['delete']) && isset($_GET['table'])) {
    $id = intval($_GET['delete']);
    $table = mysqli_real_escape_string($conn, $_GET['table']);
    
    // Safely check for photo before deleting
    try {
        $cols = @mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'photo_path'");
        if($cols && mysqli_num_rows($cols) > 0) {
            $check_photo = @mysqli_query($conn, "SELECT photo_path FROM `$table` WHERE id = $id");
            if($check_photo && mysqli_num_rows($check_photo) > 0) {
                $row = mysqli_fetch_assoc($check_photo);
                if(!empty($row['photo_path']) && file_exists($row['photo_path'])) unlink($row['photo_path']);
            }
        }
    } catch(Exception $e) {}

    mysqli_query($conn, "DELETE FROM `$table` WHERE id = $id");
    logAction($conn, "Deleted record ID $id from $table");
    $success = true;
}

if (isset($_GET['toggle']) && isset($_GET['table'])) {
    $id = intval($_GET['toggle']);
    $table = mysqli_real_escape_string($conn, $_GET['table']);
    $res = mysqli_query($conn, "SELECT status FROM `$table` WHERE id = $id");
    if($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $current = $row['status'];
        if ($table == 'attendance') $new = ($current == 'Present') ? 'Absent' : 'Present';
        elseif ($table == 'maintenance_req') $new = ($current == 'Fixed') ? 'Pending' : 'Fixed';
        elseif ($table == 'billing') $new = ($current == 'Paid') ? 'Unpaid' : 'Paid';
        else $new = ($current == 'Pending') ? 'Complete' : 'Pending';
        mysqli_query($conn, "UPDATE `$table` SET status = '$new' WHERE id = $id");
        logAction($conn, "Toggled status to $new in $table (ID: $id)");
    }
    $success = true;
}

if($success) {
    include 'header.php';
    showSuccessScreen($url);
    include 'footer.php';
    exit();
} else { header("Location: " . $url); exit(); }
?>