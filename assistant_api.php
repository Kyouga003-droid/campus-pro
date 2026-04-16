<?php
include 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = isset($data['message']) ? trim($data['message']) : '';
$msg_lower = strtolower($message);

if (empty($message)) {
    echo json_encode(['reply' => "I am active. State your query."]);
    exit();
}

$reply = "";

if (preg_match('/\b(hi|h[e]*llo|h[e]*y|greet[i]*ngs|morn[i]*ng|aft[e]*rnoon|yo|sup)\b/i', $msg_lower)) {
    $reply = "I am Campy, the Omni-System Intelligence. My cognitive architecture is optimized for advanced university operations. Command me.";
}
elseif (preg_match('/\b(br[i]*ef[i]*ng|s[u]*mm[a]*ry|status|ov[e]*rv[i]*ew|r[e]*port|stats)\b/i', $msg_lower)) {
    $q_students = mysqli_fetch_assoc(@mysqli_query($conn, "SELECT COUNT(*) as c FROM students WHERE status='Enrolled'"))['c'] ?: 0;
    $q_def = mysqli_fetch_assoc(@mysqli_query($conn, "SELECT SUM(net_amount - amount_paid) as d FROM billing WHERE status!='Paid'"))['d'] ?: 0;
    $q_tix = mysqli_fetch_assoc(@mysqli_query($conn, "SELECT COUNT(*) as c FROM it_tickets WHERE status='Open'"))['c'] ?: 0;
    $q_ev = mysqli_fetch_assoc(@mysqli_query($conn, "SELECT COUNT(*) as c FROM events WHERE event_date = CURDATE()"))['c'] ?: 0;
    $reply = "<strong>Executive Command Briefing:</strong><br><br>• Active Scholars: {$q_students}<br>• Outstanding Deficit: ₱" . number_format($q_def, 2) . "<br>• Open IT Tickets: {$q_tix}<br>• Campus Events Today: {$q_ev}<br><br>All campus matrix parameters are nominal.";
}
elseif (preg_match('/(?:who teach[e]*s|instruct[o]*r|d[e]*ta[i]*ls for|who is t[e]*ach[i]*ng)\s+([a-z0-9]+)/i', $msg_lower, $matches)) {
    $code = mysqli_real_escape_string($conn, strtoupper(trim($matches[1])));
    $res = @mysqli_query($conn, "SELECT instructor, room, schedule, enrolled, capacity FROM classes WHERE class_code LIKE '%$code%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $reply = "Class <strong>{$code}</strong> is taught by <strong>{$row['instructor']}</strong> in room {$row['room']}. It is scheduled for {$row['schedule']} and currently has {$row['enrolled']} out of {$row['capacity']} students enrolled.";
    } else {
        $reply = "I could not find an active class matching '{$code}'.";
    }
}
elseif (preg_match('/what class[e]*s do[e]*s (.*) t[e]*ach/i', $msg_lower, $matches)) {
    $prof = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT class_code, subject_name FROM classes WHERE instructor LIKE '%$prof%' AND status='Active'");
    if($res && mysqli_num_rows($res) > 0) {
        $reply = "Instructor {$prof} is assigned to:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• {$r['class_code']}: {$r['subject_name']}<br>";
        }
    } else {
        $reply = "I cannot find any active classes assigned to {$prof}.";
    }
}
elseif (preg_match('/(?:do[e]*s|is) (.*) ow[e]*|bal[a]*nc[e]* for (.*)/i', $msg_lower, $matches)) {
    $student = mysqli_real_escape_string($conn, trim(!empty($matches[1]) ? $matches[1] : $matches[2]));
    $res = @mysqli_query($conn, "SELECT SUM(net_amount - amount_paid) as deficit FROM billing WHERE student_name LIKE '%$student%' OR student_id LIKE '%$student%'");
    $def = $res ? floatval(mysqli_fetch_assoc($res)['deficit']) : 0;
    if($def > 0) {
        $reply = "Yes, {$student} currently has an outstanding balance of <strong>₱" . number_format($def, 2) . "</strong>.";
    } else {
        $reply = "No, {$student} has no outstanding financial obligations.";
    }
}
elseif (preg_match('/(?:bus[i]*est|h[a]*rd[e]*st work[i]*ng) (?:t[e]*ach[e]*r|[i]*nstruct[o]*r|pr[o]*f)/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT instructor, COUNT(*) as c FROM classes WHERE status='Active' GROUP BY instructor ORDER BY c DESC LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "The instructor handling the highest course load is <strong>{$r['instructor']}</strong>, currently managing {$r['c']} active classes.";
    } else {
        $reply = "Faculty workload data is unavailable.";
    }
}
elseif (preg_match('/\b(most [e]*xp[e]*ns[i]*v[e]*|h[i]*gh[e]*st p[r]*[i]*c[e]*)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT item_name, vendor, amount FROM orders WHERE status!='Canceled' ORDER BY amount DESC LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "The highest-valued active procurement order is <strong>{$r['item_name']}</strong> from {$r['vendor']}, costing ₱" . number_format($r['amount'], 2) . ".";
    } else {
        $reply = "Procurement data is empty.";
    }
}
elseif (preg_match('/\b(c[a]*mpus ut[i]*l[i]*zat[i]*on|gl[o]*b[a]*l s[e]*at[s]*)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT SUM(enrolled) as e, SUM(capacity) as c FROM classes WHERE status='Active'");
    if($res) {
        $r = mysqli_fetch_assoc($res);
        $pct = $r['c'] > 0 ? round(($r['e'] / $r['c']) * 100, 1) : 0;
        $reply = "Global academic utilization is at <strong>{$pct}%</strong>, with " . number_format($r['e']) . " seats occupied out of a total maximum capacity of " . number_format($r['c']) . " across all active classes.";
    } else {
        $reply = "Academic load metrics are offline.";
    }
}
elseif (preg_match('/att[e]*nd[a]*nc[e]* (?:rat[e]*|p[e]*rc[e]*nt) for (.*)/i', $msg_lower, $matches)) {
    $cls = mysqli_real_escape_string($conn, strtoupper(trim($matches[1])));
    $res_tot = @mysqli_query($conn, "SELECT COUNT(*) as t FROM attendance WHERE class_code LIKE '%$cls%'");
    $res_pr = @mysqli_query($conn, "SELECT COUNT(*) as p FROM attendance WHERE class_code LIKE '%$cls%' AND status='Present'");
    if($res_tot && $res_pr) {
        $tot = mysqli_fetch_assoc($res_tot)['t'];
        $pr = mysqli_fetch_assoc($res_pr)['p'];
        if($tot > 0) {
            $rate = round(($pr / $tot) * 100, 1);
            $reply = "The attendance rate for {$cls} is currently <strong>{$rate}%</strong> based on {$tot} total logs.";
        } else {
            $reply = "No attendance records have been processed for {$cls}.";
        }
    }
}
elseif (preg_match('/how m[a]*ny (1a|1b|2a|2b|3a|4a) st[u]*d[e]*nts?/i', $msg_lower, $matches)) {
    $yr = mysqli_real_escape_string($conn, strtoupper(trim($matches[1])));
    $res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM students WHERE year_level='$yr' AND status='Enrolled'");
    $count = $res ? mysqli_fetch_assoc($res)['c'] : 0;
    $reply = "There are <strong>{$count} scholars</strong> currently enrolled in section {$yr}.";
}
elseif (preg_match('/how m[a]*ny s[e]*ats in (.*)/i', $msg_lower, $matches)) {
    $rm = mysqli_real_escape_string($conn, strtoupper(trim($matches[1])));
    $res = @mysqli_query($conn, "SELECT capacity, room_name FROM campus_rooms WHERE room_number LIKE '%$rm%' OR room_name LIKE '%$rm%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "{$r['room_name']} has a maximum capacity of <strong>{$r['capacity']} seats</strong>.";
    } else {
        $reply = "Facility {$rm} does not exist in the registry.";
    }
}
elseif (preg_match('/how m[u]*ch d[i]*d (.*) sp[e]*nd/i', $msg_lower, $matches)) {
    $dept = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT SUM(amount) as s FROM orders WHERE department LIKE '%$dept%' AND status!='Canceled'");
    $spend = $res ? floatval(mysqli_fetch_assoc($res)['s']) : 0;
    $reply = "The {$dept} department has spent a total of <strong>₱" . number_format($spend, 2) . "</strong> on active procurement orders.";
}
elseif (preg_match('/(?:ord[e]*rs|p[u]*rchas[e]*) from (.*)/i', $msg_lower, $matches)) {
    $ven = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT item_name, status FROM orders WHERE vendor LIKE '%$ven%'");
    if($res && mysqli_num_rows($res) > 0) {
        $reply = "We have the following orders with {$ven}:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• {$r['item_name']} ({$r['status']})<br>";
        }
    } else {
        $reply = "There are no purchase orders logged for {$ven}.";
    }
}
elseif (preg_match('/fl[e]*t batt[e]*ry|av[e]*rag[e]* batt[e]*ry|charge/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT AVG(battery_level) as b FROM transport WHERE status='Operational'");
    $avg = $res ? round(mysqli_fetch_assoc($res)['b'], 1) : 0;
    $reply = "The operational fleet currently has an average charge/fuel level of <strong>{$avg}%</strong>.";
}
elseif (preg_match('/who dr[i]*v[e]*s (.*)/i', $msg_lower, $matches)) {
    $veh = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT driver_name, driver_contact FROM transport WHERE vehicle_type LIKE '%$veh%' OR vehicle_plate LIKE '%$veh%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "That vehicle is assigned to <strong>{$r['driver_name']}</strong>. Contact: {$r['driver_contact']}.";
    } else {
        $reply = "I cannot locate a driver assigned to {$veh}.";
    }
}
elseif (preg_match('/do w[e]* hav[e]* (.*) b[o]*k|s[e]*arch b[o]*k (.*)/i', $msg_lower, $matches)) {
    $bk = mysqli_real_escape_string($conn, trim(!empty($matches[1]) ? $matches[1] : $matches[2]));
    $res = @mysqli_query($conn, "SELECT title, status FROM library_catalog WHERE title LIKE '%$bk%' OR author LIKE '%$bk%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "Yes, we have <strong>'{$r['title']}'</strong> in the archive. Current status: {$r['status']}.";
    } else {
        $reply = "I could not find '{$bk}' in the digital library catalog.";
    }
}
elseif (preg_match('/how f[u]*ll is (.*)/i', $msg_lower, $matches)) {
    $ev = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT event_name, rsvp_count, max_capacity FROM events WHERE event_name LIKE '%$ev%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "<strong>{$r['event_name']}</strong> currently has {$r['rsvp_count']} RSVPs out of a maximum capacity of {$r['max_capacity']}.";
    } else {
        $reply = "I cannot locate an event matching '{$ev}'.";
    }
}
elseif (preg_match('/(?:how m[a]*ny st[u]*d[e]*nts|how m[a]*ny sch[o]*lars) in (.*)/i', $msg_lower, $matches)) {
    $cls = mysqli_real_escape_string($conn, strtoupper(trim($matches[1])));
    $res = @mysqli_query($conn, "SELECT subject_name, enrolled FROM classes WHERE class_code LIKE '%$cls%' OR subject_name LIKE '%$cls%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "{$r['subject_name']} currently has <strong>{$r['enrolled']} students</strong> enrolled.";
    } else {
        $reply = "I cannot find a class matching '{$cls}'.";
    }
}
elseif (preg_match('/\b(exp[i]*r[i]*ng|ov[e]*rdu[e]*|unpa[i]*d b[i]*lls|d[e]*bt)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM billing WHERE status!='Paid' AND due_date < CURDATE()");
    $c = $res ? mysqli_fetch_assoc($res)['c'] : 0;
    $reply = "There are currently <strong>{$c} overdue invoices</strong> past their collection date in the ledger.";
}
elseif (preg_match('/op[e]*n t[i]*ck[e]*ts in (.*)/i', $msg_lower, $matches)) {
    $loc = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM it_tickets WHERE location LIKE '%$loc%' AND status='Open'");
    $c = $res ? mysqli_fetch_assoc($res)['c'] : 0;
    $reply = "There are <strong>{$c} open IT issues</strong> located in {$loc}.";
}
elseif (preg_match('/\b(tot[a]*l c[a]*mpus c[a]*pac[i]*ty|tot[a]*l s[e]*ats)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT SUM(capacity) as c FROM campus_rooms WHERE status='Available'");
    $c = $res ? mysqli_fetch_assoc($res)['c'] : 0;
    $reply = "Across all available facilities, the total physical campus capacity is currently <strong>{$c} seats</strong>.";
}
elseif (preg_match('/r[e]*ma[i]*n[i]*ng b[u]*dg[e]*t for (.*)/i', $msg_lower, $matches)) {
    $dept = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT SUM(amount) as s, SUM(budget_limit) as b FROM orders WHERE department LIKE '%$dept%' AND status!='Canceled'");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $rem = floatval($r['b']) - floatval($r['s']);
        $reply = "The projected remaining procurement budget for the {$dept} sector is <strong>₱" . number_format($rem, 2) . "</strong> based on current PO allocations.";
    } else {
        $reply = "No procurement data found for {$dept}.";
    }
}
elseif (preg_match('/\b(b[i]*gg[e]*st|l[a]*rg[e]*st) d[e]*p[a]*rtm[e]*nt\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT department, COUNT(*) as c FROM students WHERE status='Enrolled' GROUP BY department ORDER BY c DESC LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "The largest department by enrollment is <strong>{$r['department']}</strong> with {$r['c']} active students.";
    } else {
        $reply = "Demographic correlation failed.";
    }
}
elseif (preg_match('/\b(tot[a]*l val[u]*[e]*|h[a]*rdw[a]*r[e]* val[u]*[e]*|ass[e]*t val[u]*[e]*)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT SUM(amount) as s FROM orders WHERE category='Hardware' AND status='Delivered'");
    $s = $res ? floatval(mysqli_fetch_assoc($res)['s']) : 0;
    $reply = "The total value of all delivered hardware assets currently stands at <strong>₱" . number_format($s, 2) . "</strong>.";
}
elseif (preg_match('/ev[e]*nts in (.*)/i', $msg_lower, $matches)) {
    $loc = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT event_name, event_date FROM events WHERE location LIKE '%$loc%' AND event_date >= CURDATE() LIMIT 3");
    if($res && mysqli_num_rows($res) > 0) {
        $reply = "Upcoming events in {$loc}:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• {$r['event_name']} (" . date('M d', strtotime($r['event_date'])) . ")<br>";
        }
    } else {
        $reply = "There are no scheduled events for {$loc}.";
    }
}
elseif (preg_match('/wh[i]*ch t[e]*ach[e]*rs ar[e]* in (.*)/i', $msg_lower, $matches)) {
    $rm = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT DISTINCT instructor FROM classes WHERE room LIKE '%$rm%' AND status='Active'");
    if($res && mysqli_num_rows($res) > 0) {
        $reply = "Instructors assigned to {$rm}:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• {$r['instructor']}<br>";
        }
    } else {
        $reply = "No active instructors are assigned to {$rm}.";
    }
}
elseif (preg_match('/wh[o]* is ov[e]*rdu[e]*/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT student_name, (net_amount - amount_paid) as def FROM billing WHERE status!='Paid' AND due_date < CURDATE() LIMIT 3");
    if($res && mysqli_num_rows($res) > 0) {
        $reply = "Here are some of the overdue scholars:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• {$r['student_name']} (Owes ₱" . number_format($r['def'], 2) . ")<br>";
        }
    } else {
        $reply = "There are currently no overdue accounts.";
    }
}
elseif (preg_match('/(?:f[i]*nd|ar[e]* th[e]*r[e]*|show m[e]*|av[a]*[i]*labl[e]*) (lab|class|auditorium|lounge|meeting)s?/i', $msg_lower, $matches)) {
    $type = mysqli_real_escape_string($conn, $matches[1]);
    $res = @mysqli_query($conn, "SELECT room_number, room_name FROM campus_rooms WHERE room_type LIKE '%$type%' AND status='Available' LIMIT 3");
    if($res && mysqli_num_rows($res) > 0) {
        $reply = "Yes, here are some available facilities right now:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• <strong>{$r['room_number']}</strong> ({$r['room_name']})<br>";
        }
    } else {
        $reply = "There are currently no facilities of that type marked as 'Available'.";
    }
}
elseif (preg_match('/\b(col[l]*[e]*ction|fin[a]*nc[e]*|m[o]*n[e]*y|r[e]*v[e]*nu[e]*|d[e]*f[i]*c[i]*t)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT SUM(net_amount) as t_net, SUM(amount_paid) as t_paid FROM billing");
    if($res) {
        $row = mysqli_fetch_assoc($res);
        $net = $row['t_net'] ?: 0;
        $paid = $row['t_paid'] ?: 0;
        $rate = $net > 0 ? round(($paid / $net) * 100, 1) : 0;
        $deficit = $net - $paid;
        $reply = "The current university collection rate is <strong>{$rate}%</strong>. We have collected ₱" . number_format($paid, 2) . " out of a projected ₱" . number_format($net, 2) . ". The outstanding campus deficit is <strong>₱" . number_format($deficit, 2) . "</strong>.";
    } else {
        $reply = "Financial telemetry is currently offline.";
    }
}
elseif (preg_match('/\b(brok[e]*n b[u]*s[e]*s|ma[i]*nt[e]*n[a]*nc[e]*|brok[e]*n v[e]*h[i]*cl[e]*s)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT vehicle_plate, vehicle_type FROM transport WHERE status='In Maintenance'");
    if($res && mysqli_num_rows($res) > 0) {
        $count = mysqli_num_rows($res);
        $reply = "There are <strong>{$count} vehicles</strong> currently locked in maintenance:<br>";
        while($r = mysqli_fetch_assoc($res)) {
            $reply .= "• {$r['vehicle_plate']} ({$r['vehicle_type']})<br>";
        }
    } else {
        $reply = "Great news! All registered campus vehicles are currently fully operational and active on routes.";
    }
}
elseif (preg_match('/(?:f[i]*nd|s[e]*arch|info|who is) (?:st[u]*d[e]*nt|sch[o]*lar) (.*)/i', $msg_lower, $matches)) {
    $query = mysqli_real_escape_string($conn, trim($matches[1]));
    $res = @mysqli_query($conn, "SELECT student_id, first_name, last_name, course, year_level FROM students WHERE first_name LIKE '%$query%' OR last_name LIKE '%$query%' OR student_id LIKE '%$query%' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $r = mysqli_fetch_assoc($res);
        $reply = "Record found: <strong>{$r['first_name']} {$r['last_name']}</strong> (ID: {$r['student_id']}). They are currently enrolled in {$r['course']} - Year {$r['year_level']}.";
    } else {
        $reply = "I could not locate a student matching '{$query}' in the active registry.";
    }
}
elseif (preg_match('/\b(t[i]*ck[e]*t|it|s[u]*pp[o]*rt|f[i]*x|b[u]*g)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM it_tickets WHERE status='Open'");
    $open = $res ? mysqli_fetch_assoc($res)['c'] : 0;
    $reply = "There are currently <strong>{$open} open IT support tickets</strong>.";
}
elseif (preg_match('/\b(ev[e]*nt|sch[e]*dul[e]*|happ[e]*n|t[o]*day|upcom[i]*ng)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT event_name, event_time FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) {
        $ev = mysqli_fetch_assoc($res);
        $time = date('h:i A', strtotime($ev['event_time']));
        $reply = "The next upcoming event is <strong>'{$ev['event_name']}'</strong> at {$time}.";
    } else {
        $reply = "There are no upcoming events scheduled in the near future.";
    }
}
elseif (preg_match('/\b(b[o]*k|l[i]*brary|arch[i]*v[e]*|lost)\b/i', $msg_lower)) {
    $res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE status='Lost / Damaged'");
    $lost = $res ? mysqli_fetch_assoc($res)['c'] : 0;
    $reply = "The library currently has <strong>{$lost} volumes</strong> flagged as Lost or Damaged.";
}
elseif (preg_match('/\b(h[e]*lp|c[o]*mm[a]*nds|capab[i]*l[i]*t[i]*es)\b/i', $msg_lower)) {
    $reply = "I am <strong>Campy</strong>. Try asking me complex questions like:<br><br>• <em>'What classes does Dr. Alan Turing teach?'</em><br>• <em>'Does James owe money?'</em><br>• <em>'How many seats in LB-101?'</em><br>• <em>'Who drives CP-2026A?'</em><br>• <em>'Give me a status report'</em>";
}
else {
    $reply = "Command unverified. Try asking for a 'status report' or ask about specific database entities like students, vehicles, or instructors.";
}

usleep(400000);
echo json_encode(['reply' => $reply]);
?>