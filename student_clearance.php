<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'student_header.php';
include_once 'config.php';

$sid = $_SESSION['user_id'] ?? 'S26-0001';

$fin_res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM billing WHERE student_id='$sid' AND status!='Paid'");
$fin_hold = ($fin_res && mysqli_fetch_assoc($fin_res)['c'] > 0);

$lib_res = @mysqli_query($conn, "SELECT COUNT(*) as c FROM library_catalog WHERE borrowed_by='$sid' AND due_date < CURDATE()");
$lib_hold = ($lib_res && mysqli_fetch_assoc($lib_res)['c'] > 0);

$lab_hold = false; 
$reg_hold = false; 
$adv_hold = false; 

$total_reqs = 5;
$cleared_reqs = 5;

if($fin_hold) $cleared_reqs--;
if($lib_hold) $cleared_reqs--;
if($lab_hold) $cleared_reqs--;
if($reg_hold) $cleared_reqs--;
if($adv_hold) $cleared_reqs--;

$is_cleared = ($cleared_reqs === $total_reqs);
$clearance_pct = ($cleared_reqs / $total_reqs) * 100;
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 8px; }
    .page-sub { font-size: 1rem; color: var(--text-light); font-weight: 500; }
    
    .btn-action { background: var(--card-bg); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 12px; color: var(--text-dark); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.2s; box-shadow: var(--shadow-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; outline: none; }
    .btn-action:hover { transform: translateY(-2px); border-color: var(--brand-secondary); color: var(--brand-secondary); }

    .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
    @media (max-width: 1024px) { .grid-layout { grid-template-columns: 1fr; } }
    
    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 30px; box-shadow: var(--shadow-sm); transition: 0.3s; position: relative; overflow: hidden; }
    .card:hover { box-shadow: var(--shadow-md); border-color: var(--border-light); }

    .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .widget-title { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
    .widget-title i { color: var(--brand-secondary); }

    .status-hero { text-align: center; padding: 40px 20px; border-radius: 24px; margin-bottom: 30px; border: 1px solid var(--border-color); transition: 0.3s;}
    .hero-cleared { background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), transparent); border-color: rgba(16, 185, 129, 0.3); }
    .hero-pending { background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), transparent); border-color: rgba(245, 158, 11, 0.3); }
    
    .hero-icon { font-size: 4rem; margin-bottom: 20px; }
    .hero-cleared .hero-icon { color: #10b981; }
    .hero-pending .hero-icon { color: #f59e0b; }
    
    .hero-title { font-size: 2rem; font-weight: 800; color: var(--text-dark); margin-bottom: 10px; letter-spacing: -0.5px;}
    .hero-sub { font-size: 0.95rem; color: var(--text-light); font-weight: 500; }

    .dept-list { display: flex; flex-direction: column; gap: 15px; }
    .dept-item { display: flex; justify-content: space-between; align-items: center; padding: 25px; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 16px; transition: 0.2s; }
    .dept-item.hold { border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.02); }
    .dept-item.clear { border-left: 4px solid #10b981; }
    
    .di-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
    .di-sub { font-size: 0.85rem; color: var(--text-light); font-weight: 500; }
    
    .di-status { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 700; padding: 8px 16px; border-radius: 10px; }
    .di-status.hold { color: #ef4444; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); }
    .di-status.clear { color: #10b981; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); }

    .cert-box { background: var(--main-bg); border: 2px dashed var(--border-color); border-radius: 20px; padding: 40px; text-align: center; transition: 0.3s; }
    .cert-box.locked { opacity: 0.5; filter: grayscale(100%); pointer-events: none; }
    .cert-box i { font-size: 3rem; color: var(--brand-secondary); margin-bottom: 20px; }
    .btn-cert { background: var(--brand-secondary); color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);}
    .btn-cert:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3); }

    .prog-wrap { width: 100%; height: 8px; background: var(--border-color); border-radius: 4px; margin-top: 30px; overflow: hidden; }
    .prog-fill { height: 100%; background: #10b981; transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Institutional Clearance</h1>
        <div class="page-sub">Track your routing status across campus departments.</div>
    </div>
</div>

<div class="status-hero <?= $is_cleared ? 'hero-cleared' : 'hero-pending' ?>">
    <i class="fas <?= $is_cleared ? 'fa-check-circle' : 'fa-clock' ?> hero-icon"></i>
    <div class="hero-title"><?= $is_cleared ? 'Cleared for Enrollment' : 'Clearance Pending' ?></div>
    <div class="hero-sub"><?= $is_cleared ? 'All departmental holds have been lifted. You may download your certificate.' : 'You have active holds that must be resolved before proceeding.' ?></div>
    
    <div style="max-width:400px; margin: 0 auto;">
        <div class="prog-wrap"><div class="prog-fill" style="width: <?= $clearance_pct ?>%;"></div></div>
        <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:0.85rem; font-weight:700; color:var(--text-light);">
            <span>Progress</span>
            <span><?= $cleared_reqs ?> / <?= $total_reqs ?> Depts</span>
        </div>
    </div>
</div>

<div class="grid-layout">
    
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-file-signature"></i> Final Document</div>
            </div>
            <div class="cert-box <?= $is_cleared ? '' : 'locked' ?>">
                <i class="fas fa-certificate"></i>
                <h3 style="font-size:1.2rem; font-weight:800; color:var(--text-dark); margin-bottom:8px;">Clearance Certificate</h3>
                <p style="font-size:0.9rem; color:var(--text-light); line-height:1.5;">Digitally signed and verified by the Office of the Registrar.</p>
                <button class="btn-cert"><i class="fas fa-download"></i> Download PDF</button>
            </div>
            <?php if(!$is_cleared): ?>
                <div style="text-align:center; font-size:0.8rem; font-weight:600; color:#f59e0b; margin-top:15px;">
                    <i class="fas fa-lock"></i> Locked until all holds are resolved.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 30px;">
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-building"></i> Department Routing</div>
            </div>
            
            <div class="dept-list">
                
                <div class="dept-item <?= $fin_hold ? 'hold' : 'clear' ?>">
                    <div>
                        <div class="di-title">Accounting & Finance</div>
                        <div class="di-sub">Tuition and miscellaneous fees</div>
                        <?php if($fin_hold): ?>
                            <a href="student_billing.php" style="font-size:0.8rem; font-weight:700; color:var(--brand-secondary); text-decoration:none; display:inline-block; margin-top:8px;">Go to Ledger <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <div class="di-status <?= $fin_hold ? 'hold' : 'clear' ?>">
                        <i class="fas <?= $fin_hold ? 'fa-times-circle' : 'fa-check-circle' ?>"></i> <?= $fin_hold ? 'Hold' : 'Cleared' ?>
                    </div>
                </div>

                <div class="dept-item <?= $lib_hold ? 'hold' : 'clear' ?>">
                    <div>
                        <div class="di-title">University Library</div>
                        <div class="di-sub">Book returns and fines</div>
                    </div>
                    <div class="di-status <?= $lib_hold ? 'hold' : 'clear' ?>">
                        <i class="fas <?= $lib_hold ? 'fa-times-circle' : 'fa-check-circle' ?>"></i> <?= $lib_hold ? 'Hold' : 'Cleared' ?>
                    </div>
                </div>

                <div class="dept-item <?= $lab_hold ? 'hold' : 'clear' ?>">
                    <div>
                        <div class="di-title">Laboratory Management</div>
                        <div class="di-sub">Equipment accountability</div>
                    </div>
                    <div class="di-status <?= $lab_hold ? 'hold' : 'clear' ?>">
                        <i class="fas <?= $lab_hold ? 'fa-times-circle' : 'fa-check-circle' ?>"></i> <?= $lab_hold ? 'Hold' : 'Cleared' ?>
                    </div>
                </div>

                <div class="dept-item <?= $reg_hold ? 'hold' : 'clear' ?>">
                    <div>
                        <div class="di-title">Office of the Registrar</div>
                        <div class="di-sub">Document submissions</div>
                    </div>
                    <div class="di-status <?= $reg_hold ? 'hold' : 'clear' ?>">
                        <i class="fas <?= $reg_hold ? 'fa-times-circle' : 'fa-check-circle' ?>"></i> <?= $reg_hold ? 'Hold' : 'Cleared' ?>
                    </div>
                </div>

                <div class="dept-item <?= $adv_hold ? 'hold' : 'clear' ?>">
                    <div>
                        <div class="di-title">Academic Advising</div>
                        <div class="di-sub">Curriculum evaluation</div>
                    </div>
                    <div class="di-status <?= $adv_hold ? 'hold' : 'clear' ?>">
                        <i class="fas <?= $adv_hold ? 'fa-times-circle' : 'fa-check-circle' ?>"></i> <?= $adv_hold ? 'Hold' : 'Cleared' ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>