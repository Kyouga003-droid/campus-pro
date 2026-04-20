<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'student_header.php';
include_once 'config.php';

$sid = $_SESSION['user_id'] ?? 'S26-0001';

$check_billing = mysqli_query($conn, "SELECT COUNT(*) as c FROM billing WHERE student_id='$sid'");
if ($check_billing && mysqli_fetch_assoc($check_billing)['c'] == 0) {
    $yr = date('y');
    $seed = [
        ["INV-$yr-1001", "Tuition Fee", 45000.00, 0, 45000.00, 45000.00, "Paid", date('Y-m-d', strtotime('-60 days'))],
        ["INV-$yr-1002", "Laboratory Fee", 8500.00, 0, 8500.00, 4000.00, "Partial", date('Y-m-d', strtotime('+15 days'))],
        ["INV-$yr-1003", "Miscellaneous Fee", 5200.00, 1000.00, 4200.00, 0.00, "Unpaid", date('Y-m-d', strtotime('-5 days'))],
        ["INV-$yr-1004", "Library Fine", 150.00, 0, 150.00, 0.00, "Unpaid", date('Y-m-d', strtotime('+5 days'))]
    ];
    foreach($seed as $s) {
        mysqli_query($conn, "INSERT INTO billing (invoice_id, student_id, student_name, department, fee_type, amount, discount, net_amount, amount_paid, status, due_date) VALUES ('{$s[0]}', '$sid', '{$_SESSION['full_name']}', 'Computer Studies', '{$s[1]}', {$s[2]}, {$s[3]}, {$s[4]}, {$s[5]}, '{$s[6]}', '{$s[7]}')");
    }
}

$res = mysqli_query($conn, "SELECT * FROM billing WHERE student_id='$sid' ORDER BY due_date ASC");
$invoices = [];
$total_due = 0;
$total_paid = 0;
$total_fees = 0;
$fee_breakdown = [];

$now = new DateTime();
$now->setTime(0,0,0);

while ($row = mysqli_fetch_assoc($res)) {
    $invoices[] = $row;
    $bal = $row['net_amount'] - $row['amount_paid'];
    if ($row['status'] !== 'Paid' && $row['status'] !== 'Refunded') {
        $total_due += $bal;
    }
    $total_paid += $row['amount_paid'];
    $total_fees += $row['net_amount'];
    
    $ft = $row['fee_type'];
    if(!isset($fee_breakdown[$ft])) $fee_breakdown[$ft] = 0;
    $fee_breakdown[$ft] += $row['net_amount'];
}

$chart_labels = json_encode(array_keys($fee_breakdown));
$chart_data = json_encode(array_values($fee_breakdown));
$pay_pct = $total_fees > 0 ? min(100, ($total_paid / $total_fees) * 100) : 100;
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 8px; }
    .page-sub { font-size: 1rem; color: var(--text-light); font-weight: 500; }
    
    .btn-action { background: var(--card-bg); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 12px; color: var(--text-dark); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.2s; box-shadow: var(--shadow-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; outline: none; }
    .btn-action:hover { transform: translateY(-2px); border-color: var(--brand-secondary); color: var(--brand-secondary); }
    .btn-primary { background: var(--text-dark); color: var(--main-bg); border: 1px solid var(--text-dark); padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 0.95rem; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; outline: none;}
    .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: var(--shadow-md); }

    .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
    @media (max-width: 1024px) { .grid-layout { grid-template-columns: 1fr; } }
    
    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 30px; box-shadow: var(--shadow-sm); transition: 0.3s; position: relative; overflow: hidden; }
    .card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: var(--border-light); }

    .hero-balance { background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); border-radius: 24px; padding: 40px; color: #fff; margin-bottom: 30px; box-shadow: var(--shadow-md); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .hero-balance::after { content: '\f0d6'; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; right: 20px; bottom: -20px; font-size: 12rem; opacity: 0.1; transform: rotate(-15deg); }
    
    .hb-val { font-size: 3.5rem; font-weight: 900; line-height: 1; letter-spacing: -1px; margin-bottom: 8px; font-family: monospace; }
    .hb-lbl { font-size: 0.9rem; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

    .prog-wrap { width: 100%; height: 6px; background: rgba(255,255,255,0.2); border-radius: 3px; margin-top: 20px; overflow: hidden; }
    .prog-fill { height: 100%; background: #10b981; }

    .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .widget-title { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
    .widget-title i { color: var(--brand-secondary); }

    .invoice-list { display: flex; flex-direction: column; gap: 15px; }
    .invoice-item { display: flex; align-items: center; justify-content: space-between; padding: 20px; background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 16px; transition: 0.2s; }
    .invoice-item:hover { background: var(--card-bg); border-color: var(--border-light); transform: translateX(4px); box-shadow: var(--shadow-sm); }
    
    .inv-id { font-family: monospace; font-size: 0.8rem; font-weight: 700; color: var(--text-light); margin-bottom: 4px; }
    .inv-type { font-size: 1.05rem; font-weight: 700; color: var(--text-dark); }
    .inv-date { font-size: 0.8rem; font-weight: 600; color: var(--text-light); margin-top: 6px; display: flex; align-items: center; gap: 5px;}
    
    .inv-amt { font-family: monospace; font-size: 1.2rem; font-weight: 800; color: var(--text-dark); text-align: right; }
    
    .badge { padding: 4px 10px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border-radius: 6px; letter-spacing: 0.5px; display: inline-block; margin-top: 6px; }
    .badge.pass { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge.fail { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .badge.warn { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

    .overdue-text { color: #ef4444; font-weight: 700; }

    .empty-state { text-align: center; padding: 50px 20px; color: var(--text-light); }
    .empty-state i { font-size: 3rem; opacity: 0.2; margin-bottom: 15px; color: var(--text-dark); }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Financial Ledger</h1>
        <div class="page-sub">Review statements, settle balances, and track payment history.</div>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn-action" onclick="window.print()"><i class="fas fa-print"></i> Print SOA</button>
        <button class="btn-primary"><i class="fas fa-credit-card"></i> Pay Online</button>
    </div>
</div>

<div class="hero-balance">
    <div style="z-index: 2;">
        <div class="hb-lbl">Total Outstanding Balance</div>
        <div class="hb-val">₱<?= number_format($total_due, 2) ?></div>
        <div style="display:flex; gap:15px; margin-top:15px;">
            <div>
                <div style="font-size:0.75rem; opacity:0.8; text-transform:uppercase; font-weight:600;">Total Assessed</div>
                <div style="font-family:monospace; font-weight:700; font-size:1.1rem;">₱<?= number_format($total_fees, 2) ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem; opacity:0.8; text-transform:uppercase; font-weight:600;">Total Paid</div>
                <div style="font-family:monospace; font-weight:700; font-size:1.1rem; color:#10b981;">₱<?= number_format($total_paid, 2) ?></div>
            </div>
        </div>
        <div class="prog-wrap"><div class="prog-fill" style="width: <?= $pay_pct ?>%;"></div></div>
    </div>
</div>

<div class="grid-layout">
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-file-invoice-dollar"></i> Current Invoices</div>
            </div>
            <?php if(empty($invoices)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color:#10b981; opacity:1;"></i>
                    <div style="font-weight:600; font-size:1.1rem; color:var(--text-dark); margin-top:10px;">No active invoices</div>
                </div>
            <?php else: ?>
                <div class="invoice-list">
                    <?php foreach($invoices as $inv): 
                        $bal = $inv['net_amount'] - $inv['amount_paid'];
                        $b_cls = 'pass';
                        if($inv['status'] == 'Unpaid') $b_cls = 'fail';
                        if($inv['status'] == 'Partial') $b_cls = 'warn';
                        
                        $dd = new DateTime($inv['due_date']);
                        $is_overdue = ($dd < $now && $inv['status'] != 'Paid');
                        $date_str = date('M d, Y', strtotime($inv['due_date']));
                        $date_html = $is_overdue ? "<span class='overdue-text'><i class='fas fa-exclamation-circle'></i> Overdue: {$date_str}</span>" : "<i class='far fa-clock'></i> Due: {$date_str}";
                    ?>
                        <div class="invoice-item" <?= $is_overdue ? 'style="border-left:4px solid #ef4444;"' : '' ?>>
                            <div>
                                <div class="inv-id"><?= htmlspecialchars($inv['invoice_id']) ?></div>
                                <div class="inv-type"><?= htmlspecialchars($inv['fee_type']) ?></div>
                                <div class="inv-date"><?= $date_html ?></div>
                                <div class="badge <?= $b_cls ?>"><?= htmlspecialchars($inv['status']) ?></div>
                            </div>
                            <div>
                                <div class="inv-amt">₱<?= number_format($bal, 2) ?></div>
                                <div style="font-size:0.75rem; color:var(--text-light); text-align:right; margin-top:4px; font-weight:600;">of ₱<?= number_format($inv['net_amount'], 2) ?></div>
                                <?php if($inv['status'] != 'Paid'): ?>
                                    <div style="text-align:right; margin-top:10px;"><button class="btn-action" style="padding:6px 12px; font-size:0.75rem;">Pay</button></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <div class="card">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-chart-pie"></i> Fee Breakdown</div>
            </div>
            <div style="height: 250px; width: 100%; position: relative;">
                <canvas id="feeChart"></canvas>
            </div>
        </div>

        <div class="card" style="background:var(--main-bg);">
            <div class="widget-header">
                <div class="widget-title"><i class="fas fa-utensils"></i> Meal Plan</div>
            </div>
            <div style="text-align:center; padding: 20px 0;">
                <div style="font-size:0.85rem; font-weight:600; color:var(--text-light); text-transform:uppercase; margin-bottom:10px;">Available Balance</div>
                <div style="font-family:monospace; font-size:2.5rem; font-weight:800; color:var(--brand-secondary); margin-bottom:20px;">₱1,250.00</div>
                <button class="btn-action" style="width:100%; justify-content:center;"><i class="fas fa-plus"></i> Add Funds</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const ctx = document.getElementById('feeChart');
    if (ctx) {
        const getChartColor = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#0f172a';
        let labels = <?= $chart_labels ?>;
        let data = <?= $chart_data ?>;
        
        if (labels.length === 0) {
            labels = ['No Fees'];
            data = [1];
        }

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'], borderWidth: 0 }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels:{color: getChartColor(), font:{family:"'Inter', sans-serif"}}} },
                cutout: '75%'
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>