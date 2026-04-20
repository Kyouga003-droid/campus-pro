<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'student_header.php';
include_once 'config.php';

$sid = $_SESSION['user_id'] ?? 'S26-0001';

try {
    mysqli_query($conn, "ALTER TABLE students ADD COLUMN gpa DECIMAL(3,2) DEFAULT 0.00, ADD COLUMN total_credits INT DEFAULT 0, ADD COLUMN meal_plan DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN advisor VARCHAR(100) DEFAULT 'Unassigned'");
} catch(Exception $e) {}

$stu_res = @mysqli_query($conn, "SELECT * FROM students WHERE student_id='$sid'");
$student = $stu_res ? mysqli_fetch_assoc($stu_res) : null;

$fin_res = @mysqli_query($conn, "SELECT SUM(net_amount - amount_paid) as deficit FROM billing WHERE student_id='$sid' AND status!='Paid'");
$deficit = $fin_res ? floatval(mysqli_fetch_assoc($fin_res)['deficit']) : 0;

$hour = date('H');
if ($hour < 12) $greeting = "Good morning";
elseif ($hour < 17) $greeting = "Good afternoon";
else $greeting = "Good evening";

$first_name = explode(' ', $student['first_name'] ?? $_SESSION['full_name'] ?? 'Student')[0];
$dept = $student['department'] ?? 'Computer Science';
$year = $student['year_level'] ?? '2nd Year';
$gpa = number_format($student['gpa'] ?? 3.85, 2);
$credits = intval($student['total_credits'] ?? 45);
$meal_plan = number_format($student['meal_plan'] ?? 1250.00, 2);
$advisor = htmlspecialchars($student['advisor'] ?? 'Dr. Alan Turing');
?>

<style>
    .grid-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    @media (max-width: 1200px) {
        .grid-layout { grid-template-columns: 1fr; }
    }
    
    .card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 30px;
        box-shadow: var(--shadow-sm);
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }
    
    .card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
        border-color: var(--border-light);
    }

    .hero-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        flex-wrap: wrap;
        gap: 20px;
        background: rgba(var(--card-bg-rgb, 255,255,255), 0.5);
        padding: 40px;
        border-radius: 24px;
        border: 1px solid var(--border-color);
    }
    
    .hero-greeting {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--text-dark);
        letter-spacing: -1px;
        margin-bottom: 8px;
    }
    
    .hero-sub {
        font-size: 1rem;
        color: var(--text-light);
        font-weight: 500;
    }

    .quick-stats {
        display: flex;
        gap: 15px;
    }
    
    .q-stat {
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        padding: 15px 25px;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }
    
    .qs-val {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1.2;
    }
    
    .qs-lbl {
        font-size: 0.75rem;
        color: var(--text-light);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .widget-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .widget-title i {
        color: var(--brand-secondary);
    }
    
    .widget-action {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--brand-secondary);
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 8px;
        background: rgba(59, 130, 246, 0.1);
        transition: 0.2s;
    }
    
    .widget-action:hover {
        background: rgba(59, 130, 246, 0.2);
    }

    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .class-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        transition: 0.2s;
        border-left: 4px solid var(--brand-secondary);
    }
    
    .class-item:hover {
        background: var(--card-bg);
        border-color: var(--border-light);
        transform: translateX(4px);
    }
    
    .ci-time {
        min-width: 85px;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--text-dark);
    }
    
    .ci-details {
        flex: 1;
    }
    
    .ci-code {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 4px;
    }
    
    .ci-name {
        font-size: 0.85rem;
        color: var(--text-light);
        font-weight: 500;
    }
    
    .ci-room {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-dark);
        background: var(--card-bg);
        padding: 6px 12px;
        border-radius: 20px;
        border: 1px solid var(--border-color);
    }

    .finance-alert {
        background: linear-gradient(to right, rgba(239, 68, 68, 0.1), transparent);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-left: 4px solid var(--danger);
        padding: 25px;
        border-radius: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .fa-val {
        font-size: 2rem;
        font-weight: 800;
        color: var(--danger);
        font-family: monospace;
        letter-spacing: -1px;
    }
    
    .btn-pay {
        background: var(--danger);
        color: #fff;
        text-decoration: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: 0.2s;
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
    }
    
    .btn-pay:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    .finance-clear {
        background: linear-gradient(to right, rgba(16, 185, 129, 0.05), transparent);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-left: 4px solid var(--success);
        padding: 25px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }

    .progress-wrap { margin-top: 15px; }
    .prog-bar { height: 8px; background: var(--main-bg); border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color); }
    .prog-fill { height: 100%; background: var(--brand-secondary); border-radius: 4px; }
    .prog-labels { display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: var(--text-light); margin-top: 10px; }

    .action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .action-btn-large {
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        padding: 20px;
        border-radius: 16px;
        text-decoration: none;
        color: var(--text-dark);
        display: flex;
        flex-direction: column;
        gap: 12px;
        transition: 0.2s;
    }
    
    .action-btn-large:hover {
        background: var(--card-bg);
        border-color: var(--brand-secondary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    
    .action-btn-large i {
        font-size: 1.5rem;
        color: var(--brand-secondary);
    }
    
    .action-btn-large span {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .clearance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid var(--border-light);
    }
    
    .clearance-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .c-status {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        background: var(--main-bg);
        border: 1px solid var(--border-color);
    }
    
    .c-status.ok { color: var(--success); border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.05);}
    .c-status.pending { color: var(--warning); border-color: rgba(245,158,11,0.3); background: rgba(245,158,11,0.05);}

    .barcode-wrap {
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        text-align: center;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        margin-top: 20px;
    }

    .task-input-wrap {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    .task-input-wrap input {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        background: var(--main-bg);
        color: var(--text-dark);
        font-size: 0.9rem;
        outline: none;
    }
    .task-input-wrap input:focus {
        border-color: var(--brand-secondary);
    }
    .task-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        margin-bottom: 8px;
    }
    .task-item.done span {
        text-decoration: line-through;
        opacity: 0.5;
    }
    
    .recent-grade-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: var(--main-bg);
        border-radius: 12px;
        margin-bottom: 10px;
        border: 1px solid var(--border-color);
    }
    
    .rg-val {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-dark);
        background: var(--card-bg);
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
    }
</style>

<div class="hero-section">
    <div>
        <h1 class="hero-greeting"><?= $greeting ?>, <?= htmlspecialchars($first_name) ?>.</h1>
        <div class="hero-sub"><?= htmlspecialchars($dept) ?> • <?= htmlspecialchars($year) ?></div>
    </div>
    <div class="quick-stats">
        <div class="q-stat">
            <div class="qs-val"><?= $gpa ?></div>
            <div class="qs-lbl">Cumulative GPA</div>
        </div>
        <div class="q-stat">
            <div class="qs-val"><?= $credits ?></div>
            <div class="qs-lbl">Credits Earned</div>
        </div>
        <div class="q-stat">
            <div class="qs-val">₱<?= $meal_plan ?></div>
            <div class="qs-lbl">Meal Plan Bal</div>
        </div>
    </div>
</div>

<div class="grid-layout">
    <div style="display: flex; flex-direction: column; gap: 30px;">
        
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="far fa-calendar-check"></i> Today's Schedule</div>
                <a href="#" class="widget-action">View Full Calendar</a>
            </div>
            <div class="schedule-list">
                <div class="class-item">
                    <div class="ci-time">09:00 AM</div>
                    <div class="ci-details">
                        <div class="ci-code">CS-301</div>
                        <div class="ci-name">Advanced Data Structures</div>
                    </div>
                    <div class="ci-room"><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> LAB-04</div>
                </div>
                <div class="class-item" style="border-left-color: var(--warning);">
                    <div class="ci-time">11:30 AM</div>
                    <div class="ci-details">
                        <div class="ci-code">MTH-205</div>
                        <div class="ci-name">Linear Algebra</div>
                    </div>
                    <div class="ci-room"><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> LEC-02</div>
                </div>
                <div class="class-item" style="border-left-color: var(--success);">
                    <div class="ci-time">02:00 PM</div>
                    <div class="ci-details">
                        <div class="ci-code">ENG-102</div>
                        <div class="ci-name">Technical Communication</div>
                    </div>
                    <div class="ci-room"><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> RM-105</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-tasks"></i> Academic Progress</div>
                <a href="#" class="widget-action">Audit Transcript</a>
            </div>
            
            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: 600; margin-bottom: 8px;">
                    <span>Degree Completion</span>
                    <span style="color:var(--brand-secondary);">45%</span>
                </div>
                <div class="prog-bar"><div class="prog-fill" style="width: 45%;"></div></div>
                <div class="prog-labels"><span>54 Credits Earned</span><span>120 Required</span></div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: 600; margin-bottom: 8px;">
                    <span>Current Semester Timeline</span>
                    <span style="color:var(--success);">Week 8 of 16</span>
                </div>
                <div class="prog-bar"><div class="prog-fill" style="width: 50%; background:var(--success);"></div></div>
            </div>
        </div>
        
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-clipboard-check"></i> Personal Tasks</div>
            </div>
            <div class="task-input-wrap">
                <input type="text" id="stuTaskInput" placeholder="Add an assignment or reminder..." onkeypress="if(event.key==='Enter') addStuTask()">
            </div>
            <div id="stuTaskList">
                <div class="task-item">
                    <input type="checkbox" style="width:18px; height:18px;" onchange="this.parentElement.classList.toggle('done')">
                    <span style="font-size:0.95rem; font-weight:500;">Submit CS-301 Project Phase 1</span>
                </div>
                <div class="task-item done">
                    <input type="checkbox" style="width:18px; height:18px;" checked onchange="this.parentElement.classList.toggle('done')">
                    <span style="font-size:0.95rem; font-weight:500;">Register for next semester courses</span>
                </div>
            </div>
        </div>

    </div>
    
    <div style="display: flex; flex-direction: column; gap: 30px;">
        
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-id-badge"></i> Digital ID Pass</div>
            </div>
            <div class="barcode-wrap">
                <svg id="barcode"></svg>
                <div style="font-family:monospace; font-size:1.2rem; font-weight:800; letter-spacing:4px; color:#0f172a; margin-top:10px;">
                    <?= htmlspecialchars($sid) ?>
                </div>
                <div style="font-size:0.8rem; color:var(--text-light); margin-top:5px; font-weight:600;">Scan at Library, Cafeteria, or Events</div>
            </div>
        </div>
        
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-wallet"></i> Financial Ledger</div>
            </div>
            <?php if($deficit > 0): ?>
                <div class="finance-alert">
                    <div>
                        <div style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--text-light); margin-bottom:5px;">Balance Due</div>
                        <div class="fa-val">₱<?= number_format($deficit, 2) ?></div>
                    </div>
                    <a href="#" class="btn-pay">Settle</a>
                </div>
            <?php else: ?>
                <div class="finance-clear">
                    <i class="fas fa-check-circle" style="font-size: 2.2rem; color: var(--success);"></i>
                    <div>
                        <div style="font-weight: 800; font-size: 1.1rem; color:var(--text-dark);">Accounts Settled</div>
                        <div style="font-size: 0.85rem; color: var(--text-light); font-weight:500;">No pending financial obligations.</div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="action-grid">
                <a href="#" class="action-btn-large">
                    <i class="fas fa-file-invoice"></i>
                    <span>Statements</span>
                </a>
                <a href="#" class="action-btn-large">
                    <i class="fas fa-utensils"></i>
                    <span>Top Up Meal Plan</span>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-award"></i> Recent Grades</div>
                <a href="#" class="widget-action">View All</a>
            </div>
            <div>
                <div class="recent-grade-row">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:var(--text-dark);">PHY-101</div>
                        <div style="font-size:0.85rem; color:var(--text-light);">Midterm Examination</div>
                    </div>
                    <div class="rg-val" style="color:var(--success); border-color:var(--success);">A</div>
                </div>
                <div class="recent-grade-row">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:var(--text-dark);">ENG-102</div>
                        <div style="font-size:0.85rem; color:var(--text-light);">Term Paper Draft</div>
                    </div>
                    <div class="rg-val">B+</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-clipboard-list"></i> Institutional Clearance</div>
            </div>
            <div>
                <div class="clearance-item">
                    <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-dark);">Library Accountability</span>
                    <span class="c-status ok"><i class="fas fa-check"></i> Cleared</span>
                </div>
                <div class="clearance-item">
                    <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-dark);">Laboratory Equipment</span>
                    <span class="c-status pending"><i class="fas fa-clock"></i> Pending Return</span>
                </div>
                <div class="clearance-item">
                    <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-dark);">Academic Advising</span>
                    <span class="c-status ok"><i class="fas fa-check"></i> <?= $advisor ?></span>
                </div>
            </div>
            <a href="#" class="btn-primary" style="width:100%; margin-top:20px; justify-content:center; background:var(--main-bg); color:var(--text-dark); border-color:var(--border-color);"><i class="fas fa-envelope"></i> Contact Advisor</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    try {
        JsBarcode("#barcode", "<?= htmlspecialchars($sid) ?>", {
            format: "CODE128",
            lineColor: "#0f172a",
            width: 2.5,
            height: 60,
            displayValue: false,
            background: "transparent"
        });
    } catch(e) {}
});

function addStuTask() {
    const inp = document.getElementById('stuTaskInput');
    const val = inp.value.trim();
    if(!val) return;
    
    const list = document.getElementById('stuTaskList');
    const div = document.createElement('div');
    div.className = 'task-item';
    div.innerHTML = `<input type="checkbox" style="width:18px; height:18px;" onchange="this.parentElement.classList.toggle('done')"> <span style="font-size:0.95rem; font-weight:500;">${val}</span>`;
    
    list.prepend(div);
    inp.value = '';
}
</script>

</main>
</body>
</html>