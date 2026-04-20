<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'student_header.php';
include_once 'config.php';

$sid = $_SESSION['user_id'] ?? 'S26-0001';

$patch = "CREATE TABLE IF NOT EXISTS student_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    class_code VARCHAR(50),
    class_name VARCHAR(150),
    instructor VARCHAR(100),
    room VARCHAR(50),
    day_of_week VARCHAR(20),
    start_time TIME,
    end_time TIME,
    color_code VARCHAR(20) DEFAULT '#3b82f6'
)";
try { mysqli_query($conn, $patch); } catch(Exception $e) {}

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM student_schedules WHERE student_id='$sid'");
if ($check && mysqli_fetch_assoc($check)['c'] == 0) {
    $seed = [
        ['CS-301', 'Advanced Data Structures', 'Dr. Alan Turing', 'LAB-04', 'Monday', '09:00:00', '11:00:00', '#3b82f6'],
        ['MTH-205', 'Linear Algebra', 'Prof. John Von Neumann', 'LEC-02', 'Monday', '11:30:00', '13:00:00', '#f59e0b'],
        ['ENG-102', 'Technical Communication', 'Dr. Mary Shelley', 'RM-105', 'Monday', '14:00:00', '15:30:00', '#10b981'],
        ['CS-301', 'Advanced Data Structures', 'Dr. Alan Turing', 'LAB-04', 'Wednesday', '09:00:00', '11:00:00', '#3b82f6'],
        ['MTH-205', 'Linear Algebra', 'Prof. John Von Neumann', 'LEC-02', 'Wednesday', '11:30:00', '13:00:00', '#f59e0b'],
        ['PHY-101', 'University Physics', 'Dr. Albert Einstein', 'LAB-01', 'Tuesday', '10:00:00', '13:00:00', '#8b5cf6'],
        ['PHY-101', 'University Physics', 'Dr. Albert Einstein', 'LAB-01', 'Thursday', '10:00:00', '13:00:00', '#8b5cf6'],
        ['CS-305', 'Software Engineering', 'Prof. Ada Lovelace', 'LAB-02', 'Friday', '13:00:00', '16:00:00', '#ec4899']
    ];
    foreach($seed as $s) {
        mysqli_query($conn, "INSERT INTO student_schedules (student_id, class_code, class_name, instructor, room, day_of_week, start_time, end_time, color_code) VALUES ('$sid', '{$s[0]}', '{$s[1]}', '{$s[2]}', '{$s[3]}', '{$s[4]}', '{$s[5]}', '{$s[6]}', '{$s[7]}')");
    }
}

$schedule_data = ['Monday'=>[], 'Tuesday'=>[], 'Wednesday'=>[], 'Thursday'=>[], 'Friday'=>[], 'Saturday'=>[]];
$res = mysqli_query($conn, "SELECT * FROM student_schedules WHERE student_id='$sid' ORDER BY start_time ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $schedule_data[$row['day_of_week']][] = $row;
}
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 8px; }
    .page-sub { font-size: 1rem; color: var(--text-light); font-weight: 500; }
    
    .btn-action { background: var(--card-bg); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 12px; color: var(--text-dark); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.2s; box-shadow: var(--shadow-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-action:hover { transform: translateY(-2px); border-color: var(--brand-secondary); color: var(--brand-secondary); }

    .day-nav { display: flex; gap: 10px; margin-bottom: 30px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
    .day-nav::-webkit-scrollbar { display: none; }
    
    .day-tab { padding: 12px 24px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; font-weight: 600; color: var(--text-light); cursor: pointer; transition: 0.2s; box-shadow: var(--shadow-sm); white-space: nowrap; }
    .day-tab:hover { border-color: var(--text-light); color: var(--text-dark); }
    .day-tab.active { background: var(--text-dark); color: var(--main-bg); border-color: var(--text-dark); }

    .schedule-container { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 24px; padding: 40px; box-shadow: var(--shadow-md); position: relative; min-height: 400px; }
    
    .timeline-wrap { position: relative; padding-left: 120px; }
    .timeline-wrap::before { content: ''; position: absolute; left: 90px; top: 0; bottom: 0; width: 2px; background: var(--border-light); }

    .class-card { position: relative; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; margin-bottom: 25px; transition: 0.3s; box-shadow: var(--shadow-sm); border-left: 6px solid var(--color); }
    .class-card:hover { transform: translateX(5px); box-shadow: var(--shadow-md); border-color: var(--border-light); }
    .class-card::before { content: ''; position: absolute; left: -34px; top: 30px; width: 12px; height: 12px; border-radius: 50%; background: var(--color); border: 3px solid var(--card-bg); box-shadow: 0 0 0 2px var(--color); }

    .cc-time-lbl { position: absolute; left: -120px; top: 26px; width: 80px; text-align: right; font-weight: 700; font-size: 0.9rem; color: var(--text-dark); }
    
    .cc-code { font-size: 0.85rem; font-weight: 700; color: var(--color); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .cc-title { font-size: 1.25rem; font-weight: 800; color: var(--text-dark); margin-bottom: 12px; letter-spacing: -0.5px; }
    
    .cc-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .cc-meta { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 500; color: var(--text-light); }
    .cc-meta i { color: var(--text-dark); opacity: 0.7; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
    .empty-state i { font-size: 3rem; opacity: 0.2; margin-bottom: 15px; color: var(--text-dark); }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Course Schedule</h1>
        <div class="page-sub">Manage your weekly academic timeline and venue locations.</div>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn-action" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        <button class="btn-action"><i class="fas fa-calendar-plus"></i> Export ICS</button>
    </div>
</div>

<div class="day-nav">
    <?php foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
        <div class="day-tab" onclick="switchDay('<?= $day ?>')" id="tab-<?= $day ?>"><?= $day ?></div>
    <?php endforeach; ?>
</div>

<div class="schedule-container">
    <?php foreach($schedule_data as $day => $classes): ?>
        <div class="day-view" id="view-<?= $day ?>" style="display:none;">
            <?php if(empty($classes)): ?>
                <div class="empty-state">
                    <i class="far fa-calendar-times"></i>
                    <div style="font-weight:600; font-size:1.1rem; color:var(--text-dark);">No Classes Scheduled</div>
                    <div style="font-size:0.9rem; margin-top:5px;">You have a free day on <?= $day ?>.</div>
                </div>
            <?php else: ?>
                <div class="timeline-wrap">
                    <?php foreach($classes as $c): 
                        $st = date('h:i A', strtotime($c['start_time']));
                        $et = date('h:i A', strtotime($c['end_time']));
                    ?>
                        <div class="class-card" style="--color: <?= $c['color_code'] ?>;">
                            <div class="cc-time-lbl"><?= $st ?></div>
                            <div class="cc-code"><?= htmlspecialchars($c['class_code']) ?></div>
                            <div class="cc-title"><?= htmlspecialchars($c['class_name']) ?></div>
                            <div class="cc-meta-grid">
                                <div class="cc-meta"><i class="far fa-clock"></i> <?= $st ?> - <?= $et ?></div>
                                <div class="cc-meta"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['room']) ?></div>
                                <div class="cc-meta"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($c['instructor']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
function switchDay(day) {
    document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.day-view').forEach(v => v.style.display = 'none');
    
    document.getElementById('tab-' + day).classList.add('active');
    document.getElementById('view-' + day).style.display = 'block';
}

document.addEventListener('DOMContentLoaded', () => {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    let today = days[new Date().getDay()];
    if(today === 'Sunday') today = 'Monday';
    switchDay(today);
});
</script>

<?php include 'footer.php'; ?>