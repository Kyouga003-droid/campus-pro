<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

$patch = "CREATE TABLE IF NOT EXISTS admin_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    full_name VARCHAR(100),
    designation VARCHAR(100),
    contact_email VARCHAR(100),
    theme_color VARCHAR(20) DEFAULT '#FC9D01',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
try { mysqli_query($conn, $patch); } catch (Exception $e) {}

// Ensure default admin profile exists
$check = mysqli_query($conn, "SELECT COUNT(*) as c FROM admin_profiles WHERE username='admin'");
if($check && mysqli_fetch_assoc($check)['c'] == 0) {
    mysqli_query($conn, "INSERT INTO admin_profiles (username, full_name, designation, contact_email) VALUES ('admin', 'System Administrator', 'Root Access', 'admin@campus.edu')");
}

$user = 'admin'; // Hardcoded for this iteration, would normally pull from $_SESSION
$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fn = mysqli_real_escape_string($conn, $_POST['full_name']);
    $ds = mysqli_real_escape_string($conn, $_POST['designation']);
    $em = mysqli_real_escape_string($conn, $_POST['contact_email']);
    $tc = mysqli_real_escape_string($conn, $_POST['theme_color']);
    
    mysqli_query($conn, "UPDATE admin_profiles SET full_name='$fn', designation='$ds', contact_email='$em', theme_color='$tc' WHERE username='$user'");
    $msg = "Profile configuration successfully updated in the matrix.";
    if(function_exists('logAction')) logAction($conn, $user, 'Updated Personal Profile');
}

$res = mysqli_query($conn, "SELECT * FROM admin_profiles WHERE username='$user'");
$profile = mysqli_fetch_assoc($res);

include 'header.php';
?>

<style>
    .profile-container { display: flex; gap: 40px; flex-wrap: wrap; margin-bottom: 40px; }
    
    .profile-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 40px; box-shadow: var(--soft-shadow); flex: 1; min-width: 300px; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; overflow: hidden;}
    .profile-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 120px; background: linear-gradient(135deg, var(--brand-secondary), var(--brand-primary)); z-index: 0; opacity: 0.2; }
    
    .avatar-lg { width: 150px; height: 150px; background: var(--main-bg); border: 4px solid <?= htmlspecialchars($profile['theme_color']) ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: <?= htmlspecialchars($profile['theme_color']) ?>; z-index: 1; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative;}
    .avatar-lg::after { content: '\f030'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; bottom: 0; right: 0; background: var(--card-bg); color: var(--text-dark); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 2px solid var(--border-color); cursor: pointer; transition: 0.2s; box-shadow: 2px 2px 0px var(--border-color);}
    .avatar-lg::after:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); color: var(--brand-secondary); }

    .pc-name { font-family: var(--heading-font); font-size: 2.2rem; font-weight: 900; color: var(--text-dark); margin-bottom: 5px; z-index: 1; letter-spacing: 1px;}
    .pc-role { font-size: 1rem; font-weight: 800; color: var(--brand-secondary); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; z-index: 1;}
    .pc-tag { display: inline-block; background: var(--bg-grid); padding: 8px 16px; border-radius: 8px; font-family: monospace; font-size: 0.9rem; font-weight: 900; color: var(--text-light); border: 2px dashed var(--border-light); z-index: 1;}

    .settings-card { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; padding: 40px; box-shadow: var(--soft-shadow); flex: 2; min-width: 500px; }
    .sc-title { font-family: var(--heading-font); font-size: 1.8rem; font-weight: 900; color: var(--text-dark); margin-bottom: 30px; display: flex; align-items: center; gap: 15px; border-bottom: 2px solid var(--border-light); padding-bottom: 15px;}
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .input-group { display: flex; flex-direction: column; gap: 10px; }
    .input-group label { font-size: 0.85rem; font-weight: 900; color: var(--text-dark); text-transform: uppercase; letter-spacing: 1px; }
    
    .color-picker { -webkit-appearance: none; -moz-appearance: none; appearance: none; width: 100%; height: 55px; background: var(--main-bg); border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; padding: 5px; transition: 0.2s;}
    .color-picker::-webkit-color-swatch-wrapper { padding: 0; }
    .color-picker::-webkit-color-swatch { border: none; border-radius: 4px; }
    .color-picker:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); }

    .success-box { background: rgba(16, 185, 129, 0.1); border: 2px solid #10b981; color: #10b981; padding: 15px 20px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px; animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);}
    @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    .security-box { margin-top: 40px; padding: 30px; border: 2px dashed #ef4444; border-radius: 12px; background: rgba(239, 68, 68, 0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;}
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid <?= htmlspecialchars($profile['theme_color']) ?>;">
    <h1 style="color: <?= htmlspecialchars($profile['theme_color']) ?>; font-size:2.8rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">Identity Configuration</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.1rem;">Manage your system credentials, contact routing, and visual signature.</p>
</div>

<?php if(!empty($msg)): ?>
    <div class="success-box">
        <i class="fas fa-check-circle"></i> <?= $msg ?>
    </div>
<?php endif; ?>

<div class="profile-container">
    <div class="profile-card">
        <div class="avatar-lg">
            <i class="fas fa-user-astronaut"></i>
        </div>
        <div class="pc-name"><?= htmlspecialchars($profile['full_name']) ?></div>
        <div class="pc-role"><?= htmlspecialchars($profile['designation']) ?></div>
        <div class="pc-tag"><i class="fas fa-fingerprint" style="margin-right:8px; color:var(--brand-secondary);"></i> ID: <?= htmlspecialchars($profile['username']) ?></div>
        
        <div style="margin-top: 30px; width: 100%; text-align: left; padding: 20px; background: var(--main-bg); border-radius: 12px; border: 1px solid var(--border-light);">
            <div style="font-size: 0.75rem; font-weight: 900; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Security Clearance</div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-weight:700; font-size:0.9rem;"><span>Clearance Level:</span> <span style="color:#10b981;">Omega / Root</span></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-weight:700; font-size:0.9rem;"><span>Last Sync:</span> <span><?= date('M d, Y', strtotime($profile['last_updated'])) ?></span></div>
            <div style="display:flex; justify-content:space-between; font-weight:700; font-size:0.9rem;"><span>Account Status:</span> <span style="color:#10b981;">Active</span></div>
        </div>
    </div>

    <div class="settings-card">
        <div class="sc-title">
            <i class="fas fa-sliders-h" style="color:var(--brand-secondary);"></i> Global Parameters
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="form-grid">
                <div class="input-group">
                    <label for="full_name">Public Designation Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($profile['full_name']) ?>" required>
                </div>
                
                <div class="input-group">
                    <label for="designation">Official Role / Title</label>
                    <input type="text" id="designation" name="designation" value="<?= htmlspecialchars($profile['designation']) ?>" required>
                </div>

                <div class="input-group">
                    <label for="contact_email">Routing Email Address</label>
                    <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($profile['contact_email']) ?>" required>
                </div>

                <div class="input-group">
                    <label for="theme_color">Visual Signature (Hex)</label>
                    <input type="color" id="theme_color" name="theme_color" class="color-picker" value="<?= htmlspecialchars($profile['theme_color']) ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 35px; padding: 18px 40px;"><i class="fas fa-save"></i> Synchronize Profile Data</button>
        </form>

        <div class="security-box">
            <div>
                <h3 style="color:#ef4444; font-family:var(--heading-font); font-weight:900; margin-bottom:5px; font-size:1.3rem;">Authentication Key</h3>
                <p style="color:var(--text-light); font-size:0.9rem; font-weight:600;">Update your cipher. Requires current key verification.</p>
            </div>
            <button class="btn-action" style="border-color:#ef4444; color:#ef4444;" onclick="systemToast('Redirecting to secure Cipher update matrix...')"><i class="fas fa-key"></i> Modify Password</button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>