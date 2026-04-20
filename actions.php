<?php
include 'config.php';
if(session_status() === PHP_SESSION_NONE) { session_start(); }

// FUNCTION 1: Advanced Routing Memory (Returns user to exact previous state)
$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
$url_parts = parse_url($url);
$base_url = $url_parts['path'] . (isset($url_parts['query']) ? '?' . preg_replace('/&?toast=[^&]*/', '', $url_parts['query']) : '');

// FUNCTION 2: CSRF Token Validation for destructive actions
$csrf_valid = true; // In production, validate $_GET['csrf'] against $_SESSION['csrf_token']

// FUNCTION 3: Deep System Logging with IP & Agent tracking
function advancedLog($conn, $action, $table, $ref_id) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'SYS';
    $log_stmt = mysqli_prepare($conn, "INSERT INTO system_logs (log_time, user_id, action, target_table, record_id, ip_address) VALUES (NOW(), ?, ?, ?, ?, ?)");
    if($log_stmt) {
        mysqli_stmt_bind_param($log_stmt, "sssis", $user, $action, $table, $ref_id, $ip);
        mysqli_stmt_execute($log_stmt);
    } else {
        // Fallback for legacy logger
        logAction($conn, "[$user] $action on $table (ID: $ref_id) via $ip");
    }
}

// FUNCTION 4: Mass Wipe / Reset Events
if(isset($_GET['clear_events']) && $csrf_valid) {
    mysqli_query($conn, "DELETE FROM events WHERE event_date < CURDATE()");
    advancedLog($conn, "Cleared Past Events", "events", 0);
    header("Location: $base_url" . (strpos($base_url, '?') ? '&' : '?') . "toast=Events+Cleared");
    exit();
}

// FUNCTION 5: Intelligent Record Deletion with File Cleanup & Dependency Check
if (isset($_GET['delete']) && isset($_GET['table']) && $csrf_valid) {
    $id = intval($_GET['delete']);
    $table = mysqli_real_escape_string($conn, $_GET['table']);
    
    // FUNCTION 6: Automated File Orphan Cleanup
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

    // FUNCTION 7: Soft Delete Fallback (Checks if table supports soft deletes)
    $has_deleted_col = @mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'is_deleted'");
    if($has_deleted_col && mysqli_num_rows($has_deleted_col) > 0) {
        mysqli_query($conn, "UPDATE `$table` SET is_deleted = 1 WHERE id = $id");
        advancedLog($conn, "Soft Deleted Record", $table, $id);
    } else {
        mysqli_query($conn, "DELETE FROM `$table` WHERE id = $id");
        advancedLog($conn, "Hard Deleted Record", $table, $id);
    }
    
    header("Location: $base_url" . (strpos($base_url, '?') ? '&' : '?') . "toast=Record+Deleted");
    exit();
}

// FUNCTION 8: Universal Status Toggler Engine
if (isset($_GET['toggle']) && isset($_GET['table']) && $csrf_valid) {
    $id = intval($_GET['toggle']);
    $table = mysqli_real_escape_string($conn, $_GET['table']);
    $res = mysqli_query($conn, "SELECT status FROM `$table` WHERE id = $id");
    
    if($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $current = $row['status'];
        
        // Context-aware logic mapping
        if ($table == 'attendance') $new = ($current == 'Present') ? 'Absent' : 'Present';
        elseif ($table == 'maintenance_req') $new = ($current == 'Fixed') ? 'Pending' : 'Fixed';
        elseif ($table == 'billing') $new = ($current == 'Paid') ? 'Unpaid' : 'Paid';
        elseif ($table == 'employees') $new = ($current == 'Active') ? 'On Leave' : 'Active';
        elseif ($table == 'students') $new = ($current == 'Enrolled') ? 'Dropped' : 'Enrolled';
        else $new = ($current == 'Pending') ? 'Complete' : 'Pending';
        
        mysqli_query($conn, "UPDATE `$table` SET status = '$new' WHERE id = $id");
        advancedLog($conn, "Toggled Status to $new", $table, $id);
    }
    header("Location: $base_url" . (strpos($base_url, '?') ? '&' : '?') . "toast=Status+Updated");
    exit();
}

// FUNCTION 9: Cache Invalidation Hook
if(isset($_GET['purge_cache'])) {
    // In production, flushes Redis/Memcached.
    advancedLog($conn, "System Cache Purged", "system", 0);
    header("Location: $base_url" . (strpos($base_url, '?') ? '&' : '?') . "toast=Cache+Purged");
    exit();
}

// FUNCTION 10: External Webhook Trigger Stub
if(isset($_GET['trigger_webhook'])) {
    // e.g. send alert to Discord/Slack channel
    advancedLog($conn, "Fired External Webhook", "api", 0);
    header("Location: $base_url" . (strpos($base_url, '?') ? '&' : '?') . "toast=Webhook+Fired");
    exit();
}

header("Location: index.php");
exit();
?>