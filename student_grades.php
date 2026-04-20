<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'student_header.php';
include_once 'config.php';

$sid = $_SESSION['user_id'] ?? 'S26-0001';

$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM grades WHERE student_id='$sid'");
if ($check && mysqli_fetch_assoc($check)['c'] == 0) {
    $seed = [
        ['CS-101', 'Intro to Programming', 'A', 'Excellent', '1st Semester', 'Dr. Smith', 3, 4.00],
        ['MTH-101', 'Calculus I', 'B+', 'Passed', '1st Semester', 'Prof. Euler', 3, 3.50],
        ['ENG-101', 'English Composition', 'A', 'Excellent', '1st Semester', 'Dr. Austen', 3, 4.00],
        ['CS-201', 'Data Structures', 'B', 'Passed', '2nd Semester', 'Dr. Turing', 4, 3.00],
        ['PHY-101', 'Physics I', 'C+', 'Passed', '2nd Semester', 'Dr. Newton', 4, 2.50]
    ];
    foreach($seed as $s) {
        mysqli_query($conn, "INSERT INTO grades (student_id, class_code, grade_value, remarks, term_semester, professor_name, credit_hours, numeric_equivalent, is_published, last_updated) VALUES ('$sid', '{$s[0]}', '{$s[2]}', '{$s[3]}', '{$s[4]}', '{$s[5]}', {$s[6]}, {$s[7]}, 1, NOW())");
    }
}

$stu_res = @mysqli_query($conn, "SELECT gpa, total_credits FROM students WHERE student_id='$sid'");
$student = $stu_res ? mysqli_fetch_assoc($stu_res) : ['gpa' => 3.85, 'total_credits' => 45];
$cgpa = number_format($student['gpa'] ?? 3.85, 2);
$tc = intval($student['total_credits'] ?? 45);

$res = mysqli_query($conn, "SELECT * FROM grades WHERE student_id='$sid' AND is_published=1 ORDER BY term_semester DESC, class_code ASC");
$grades = [];
$terms = [];
while ($row = mysqli_fetch_assoc($res)) {
    $grades[] = $row;
    if(!in_array($row['term_semester'], $terms)) {
        $terms[] = $row['term_semester'];
    }
}
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 8px; }
    .page-sub { font-size: 1rem; color: var(--text-light); font-weight: 500; }
    
    .btn-action { background: var(--card-bg); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 12px; color: var(--text-dark); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.2s; box-shadow: var(--shadow-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-action:hover { transform: translateY(-2px); border-color: var(--brand-secondary); color: var(--brand-secondary); }

    .gpa-card { background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); border-radius: 24px; padding: 40px; color: #fff; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; box-shadow: var(--shadow-md); flex-wrap: wrap; gap: 30px; position: relative; overflow: hidden; }
    .gpa-card::after { content: '\f0a3'; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; right: -20px; bottom: -40px; font-size: 15rem; opacity: 0.1; transform: rotate(-15deg); }
    
    .gpa-val { font-size: 4rem; font-weight: 900; line-height: 1; letter-spacing: -2px; margin-bottom: 8px; }
    .gpa-lbl { font-size: 1rem; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

    .term-section { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 24px; padding: 30px; box-shadow: var(--shadow-sm); margin-bottom: 30px; }
    .term-title { font-size: 1.4rem; font-weight: 800; color: var(--text-dark); margin-bottom: 25px; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; }
    
    .grade-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .grade-card { background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; transition: 0.3s; position: relative; overflow: hidden; }
    .grade-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--border-light); }
    
    .gc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    .gc-code { font-family: monospace; font-size: 0.9rem; font-weight: 700; color: var(--text-light); }
    .gc-cred { font-size: 0.75rem; font-weight: 600; background: var(--card-bg); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color); }
    
    .gc-grade { font-size: 2.5rem; font-weight: 900; color: var(--text-dark); line-height: 1; margin-bottom: 5px; }
    
    .badge { padding: 4px 10px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border-radius: 6px; letter-spacing: 0.5px; display: inline-block; margin-bottom: 15px; }
    .badge.pass { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge.fail { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
    .empty-state i { font-size: 3rem; opacity: 0.2; margin-bottom: 15px; color: var(--text-dark); }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Academic Transcript</h1>
        <div class="page-sub">View your official evaluations and degree progress.</div>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn-action" onclick="window.print()"><i class="fas fa-print"></i> Unofficial Transcript</button>
    </div>
</div>

<div class="gpa-card">
    <div style="z-index: 2;">
        <div class="gpa-val"><?= $cgpa ?></div>
        <div class="gpa-lbl">Cumulative GPA</div>
    </div>
    <div style="z-index: 2; text-align: right;">
        <div class="gpa-val" style="font-size: 3rem;"><?= $tc ?></div>
        <div class="gpa-lbl">Total Credits Earned</div>
    </div>
</div>

<?php if(empty($terms)): ?>
    <div class="empty-state">
        <i class="fas fa-file-signature"></i>
        <div style="font-weight:600; font-size:1.1rem; color:var(--text-dark);">No Published Grades</div>
        <div style="font-size:0.9rem; margin-top:5px;">Your instructors have not released any grades to the portal yet.</div>
    </div>
<?php else: ?>
    <?php foreach($terms as $term): ?>
        <div class="term-section">
            <div class="term-title"><?= htmlspecialchars($term) ?></div>
            <div class="grade-grid">
                <?php foreach($grades as $g): ?>
                    <?php if($g['term_semester'] == $term): 
                        $pass = ($g['remarks'] == 'Passed' || $g['remarks'] == 'Excellent');
                        $b_cls = $pass ? 'pass' : 'fail';
                    ?>
                        <div class="grade-card">
                            <div class="gc-header">
                                <div class="gc-code"><?= htmlspecialchars($g['class_code']) ?></div>
                                <div class="gc-cred"><?= intval($g['credit_hours']) ?> CR</div>
                            </div>
                            <div class="gc-grade"><?= htmlspecialchars($g['grade_value']) ?></div>
                            <div class="badge <?= $b_cls ?>"><?= htmlspecialchars($g['remarks']) ?></div>
                            <div style="font-size: 0.85rem; font-weight: 500; color: var(--text-light);"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($g['professor_name']) ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>