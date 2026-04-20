<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'student_header.php';
include_once 'config.php';

$sid = $_SESSION['user_id'] ?? 'S26-0001';

$patch_queries = [
    "ALTER TABLE students ADD COLUMN phone VARCHAR(20)",
    "ALTER TABLE students ADD COLUMN address TEXT",
    "ALTER TABLE students ADD COLUMN emergency_contact_name VARCHAR(100)",
    "ALTER TABLE students ADD COLUMN emergency_contact_phone VARCHAR(20)",
    "ALTER TABLE students ADD COLUMN bio TEXT",
    "ALTER TABLE students ADD COLUMN linkedin_url VARCHAR(255)",
    "ALTER TABLE students ADD COLUMN github_url VARCHAR(255)",
    "ALTER TABLE students ADD COLUMN dietary_prefs VARCHAR(100) DEFAULT 'None'",
    "ALTER TABLE students ADD COLUMN two_factor_enabled BOOLEAN DEFAULT 0",
    "ALTER TABLE students ADD COLUMN notif_email BOOLEAN DEFAULT 1",
    "ALTER TABLE students ADD COLUMN notif_sms BOOLEAN DEFAULT 0"
];
foreach($patch_queries as $q) { try { mysqli_query($conn, $q); } catch (Exception $e) {} }

$patch_logs = "CREATE TABLE IF NOT EXISTS student_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    action_type VARCHAR(100),
    ip_address VARCHAR(50),
    device_info VARCHAR(255),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
)";
try { mysqli_query($conn, $patch_logs); } catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general'])) {
        $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
        $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $link = mysqli_real_escape_string($conn, $_POST['linkedin_url']);
        $git = mysqli_real_escape_string($conn, $_POST['github_url']);
        
        mysqli_query($conn, "UPDATE students SET first_name='$fname', last_name='$lname', email='$email', phone='$phone', address='$address', bio='$bio', linkedin_url='$link', github_url='$git' WHERE student_id='$sid'");
        
        $_SESSION['full_name'] = $fname . ' ' . $lname;
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);
        mysqli_query($conn, "INSERT INTO student_activity_logs (student_id, action_type, ip_address, device_info) VALUES ('$sid', 'Updated General Profile', '$ip', '$ua')");
        
        header("Location: student_profile.php?tab=general&success=1");
        exit();
    }
    
    if (isset($_POST['update_security'])) {
        $em_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
        $em_phone = mysqli_real_escape_string($conn, $_POST['emergency_contact_phone']);
        $tfa = isset($_POST['two_factor_enabled']) ? 1 : 0;
        
        mysqli_query($conn, "UPDATE students SET emergency_contact_name='$em_name', emergency_contact_phone='$em_phone', two_factor_enabled=$tfa WHERE student_id='$sid'");
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);
        mysqli_query($conn, "INSERT INTO student_activity_logs (student_id, action_type, ip_address, device_info) VALUES ('$sid', 'Updated Security Settings', '$ip', '$ua')");
        
        header("Location: student_profile.php?tab=security&success=1");
        exit();
    }

    if (isset($_POST['update_prefs'])) {
        $diet = mysqli_real_escape_string($conn, $_POST['dietary_prefs']);
        $n_email = isset($_POST['notif_email']) ? 1 : 0;
        $n_sms = isset($_POST['notif_sms']) ? 1 : 0;
        
        mysqli_query($conn, "UPDATE students SET dietary_prefs='$diet', notif_email=$n_email, notif_sms=$n_sms WHERE student_id='$sid'");
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);
        mysqli_query($conn, "INSERT INTO student_activity_logs (student_id, action_type, ip_address, device_info) VALUES ('$sid', 'Updated Preferences', '$ip', '$ua')");
        
        header("Location: student_profile.php?tab=preferences&success=1");
        exit();
    }
}

$stu_res = @mysqli_query($conn, "SELECT * FROM students WHERE student_id='$sid'");
$student = $stu_res ? mysqli_fetch_assoc($stu_res) : [];

$fields_to_check = ['first_name', 'last_name', 'email', 'phone', 'address', 'bio', 'emergency_contact_name', 'emergency_contact_phone'];
$filled = 0;
foreach($fields_to_check as $f) {
    if(!empty($student[$f])) $filled++;
}
$completion_pct = round(($filled / count($fields_to_check)) * 100);

$active_tab = $_GET['tab'] ?? 'general';

$logs_res = @mysqli_query($conn, "SELECT * FROM student_activity_logs WHERE student_id='$sid' ORDER BY timestamp DESC LIMIT 10");
$logs = [];
if($logs_res) {
    while($r = mysqli_fetch_assoc($logs_res)) {
        $logs[] = $r;
    }
}
?>

<style>
    .profile-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 40px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .profile-layout { grid-template-columns: 1fr; }
    }

    .sidebar-menu {
        position: sticky;
        top: 100px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .menu-item {
        padding: 14px 20px;
        border-radius: 12px;
        color: var(--text-light);
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid transparent;
    }

    .menu-item:hover {
        background: var(--card-bg);
        color: var(--text-dark);
        box-shadow: var(--shadow-sm);
        border-color: var(--border-light);
    }

    .menu-item.active {
        background: var(--card-bg);
        color: var(--brand-secondary);
        border-color: var(--border-color);
        box-shadow: var(--shadow-md);
    }

    .menu-item i {
        font-size: 1.1rem;
        width: 24px;
        text-align: center;
    }

    .profile-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 40px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .banner-area {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 120px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
        border-bottom: 1px solid var(--border-color);
        z-index: 1;
    }

    .avatar-upload-zone {
        position: relative;
        z-index: 2;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: var(--brand-secondary);
        border: 4px solid var(--card-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #fff;
        font-weight: 800;
        margin-top: 40px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-md);
        cursor: pointer;
        transition: 0.2s;
    }
    .avatar-upload-zone:hover {
        transform: scale(1.05);
    }
    .avatar-upload-zone::after {
        content: '\f030';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        bottom: 0;
        right: 0;
        background: var(--text-dark);
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        border: 2px solid var(--card-bg);
    }

    .completion-widget {
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-top: 20px;
    }

    .circ-prog {
        position: relative;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: conic-gradient(var(--success) calc(var(--val) * 1%), var(--border-color) 0);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .circ-prog::before {
        content: '';
        position: absolute;
        width: 48px;
        height: 48px;
        background: var(--main-bg);
        border-radius: 50%;
    }
    .circ-val {
        position: relative;
        font-weight: 800;
        font-size: 0.9rem;
        color: var(--text-dark);
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }

    .float-input {
        position: relative;
        margin-bottom: 5px;
    }
    .float-input input, .float-input textarea, .float-input select {
        width: 100%;
        padding: 20px 16px 8px;
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: 0.95rem;
        color: var(--text-dark);
        font-family: var(--body-font);
        outline: none;
        transition: 0.2s;
    }
    .float-input textarea {
        min-height: 100px;
        resize: vertical;
    }
    .float-input label {
        position: absolute;
        top: 14px;
        left: 16px;
        font-size: 0.95rem;
        color: var(--text-light);
        pointer-events: none;
        transition: 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        font-weight: 500;
    }
    .float-input input:focus, .float-input textarea:focus, .float-input select:focus,
    .float-input input:not(:placeholder-shown), .float-input textarea:not(:placeholder-shown) {
        border-color: var(--brand-secondary);
        background: var(--card-bg);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .float-input input:focus ~ label, .float-input textarea:focus ~ label,
    .float-input input:not(:placeholder-shown) ~ label, .float-input textarea:not(:placeholder-shown) ~ label {
        top: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--brand-secondary);
    }

    .toggle-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        margin-bottom: 15px;
    }
    
    .ios-toggle {
        appearance: none;
        width: 44px;
        height: 24px;
        background: var(--border-color);
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        outline: none;
        transition: 0.3s;
    }
    .ios-toggle:checked {
        background: var(--success);
    }
    .ios-toggle::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: #fff;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .ios-toggle:checked::after {
        transform: translateX(20px);
    }

    .btn-save {
        background: var(--text-dark);
        color: var(--main-bg);
        border: none;
        padding: 14px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .danger-zone {
        border: 1px solid rgba(239, 68, 68, 0.3);
        background: rgba(239, 68, 68, 0.05);
        border-radius: 16px;
        padding: 25px;
        margin-top: 40px;
    }

    .device-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid var(--border-light);
    }
    .device-item:last-child { border-bottom: none; }
    .device-icon {
        width: 40px;
        height: 40px;
        background: var(--main-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: var(--text-dark);
    }

    .tab-content { display: none; animation: fadeIn 0.3s ease forwards; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .sticky-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        position: sticky;
        top: 75px;
        background: rgba(var(--card-bg-rgb, 255,255,255), 0.9);
        backdrop-filter: blur(10px);
        padding: 15px 0;
        z-index: 10;
        border-bottom: 1px solid var(--border-light);
    }

    .mono-id {
        font-family: monospace;
        background: var(--main-bg);
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        font-size: 0.85rem;
    }
</style>

<div class="profile-layout">
    
    <div class="sidebar-menu">
        <div class="menu-item <?= $active_tab=='general' ? 'active' : '' ?>" onclick="switchTab('general')"><i class="fas fa-user"></i> General Info</div>
        <div class="menu-item <?= $active_tab=='security' ? 'active' : '' ?>" onclick="switchTab('security')"><i class="fas fa-shield-alt"></i> Security & Access</div>
        <div class="menu-item <?= $active_tab=='preferences' ? 'active' : '' ?>" onclick="switchTab('preferences')"><i class="fas fa-sliders-h"></i> Preferences</div>
        <div class="menu-item <?= $active_tab=='activity' ? 'active' : '' ?>" onclick="switchTab('activity')"><i class="fas fa-history"></i> Activity Log</div>
    </div>

    <div class="content-wrapper">
        
        <div id="tab-general" class="tab-content <?= $active_tab=='general' ? 'active' : '' ?>">
            <form method="POST" class="profile-card">
                <input type="hidden" name="update_general" value="1">
                <div class="banner-area"></div>
                
                <div class="sticky-header" style="background:transparent; border:none; margin-bottom:0; top:0;">
                    <div class="avatar-upload-zone">
                        <?= substr($student['first_name'] ?? 'S', 0, 1) ?>
                    </div>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                </div>

                <div style="margin-bottom: 30px;">
                    <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--text-dark); margin-bottom: 5px;"><?= htmlspecialchars($student['first_name'] ?? '') ?> <?= htmlspecialchars($student['last_name'] ?? '') ?></h2>
                    <div style="color: var(--text-light); font-weight: 500;">
                        <span class="mono-id"><?= htmlspecialchars($student['student_id'] ?? '') ?></span> • <?= htmlspecialchars($student['department'] ?? '') ?>
                    </div>
                </div>

                <div class="completion-widget" style="--val: <?= $completion_pct ?>;">
                    <div class="circ-prog"><div class="circ-val"><?= $completion_pct ?>%</div></div>
                    <div>
                        <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-dark);">Profile Completion</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Complete your profile to unlock all campus services.</div>
                    </div>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 40px 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Personal Details</h3>
                
                <div class="form-grid">
                    <div class="float-input">
                        <input type="text" name="first_name" id="fname" value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" placeholder=" ">
                        <label for="fname">First Name</label>
                    </div>
                    <div class="float-input">
                        <input type="text" name="last_name" id="lname" value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" placeholder=" ">
                        <label for="lname">Last Name</label>
                    </div>
                    <div class="float-input">
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" placeholder=" ">
                        <label for="email">Campus Email</label>
                    </div>
                    <div class="float-input">
                        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" placeholder=" ">
                        <label for="phone">Mobile Number</label>
                    </div>
                    <div class="float-input" style="grid-column: span 2;">
                        <input type="text" name="address" id="address" value="<?= htmlspecialchars($student['address'] ?? '') ?>" placeholder=" ">
                        <label for="address">Permanent Address</label>
                    </div>
                    <div class="float-input" style="grid-column: span 2;">
                        <textarea name="bio" id="bio" placeholder=" "><?= htmlspecialchars($student['bio'] ?? '') ?></textarea>
                        <label for="bio">Student Bio / Objective</label>
                    </div>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 40px 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Social Links</h3>
                <div class="form-grid">
                    <div class="float-input">
                        <input type="url" name="linkedin_url" id="linkedin" value="<?= htmlspecialchars($student['linkedin_url'] ?? '') ?>" placeholder=" ">
                        <label for="linkedin">LinkedIn URL</label>
                    </div>
                    <div class="float-input">
                        <input type="url" name="github_url" id="github" value="<?= htmlspecialchars($student['github_url'] ?? '') ?>" placeholder=" ">
                        <label for="github">GitHub URL</label>
                    </div>
                </div>
            </form>
        </div>

        <div id="tab-security" class="tab-content <?= $active_tab=='security' ? 'active' : '' ?>">
            <form method="POST" class="profile-card">
                <input type="hidden" name="update_security" value="1">
                <div class="sticky-header">
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">Security & Access</h2>
                    <button type="submit" class="btn-save"><i class="fas fa-shield-alt"></i> Update Security</button>
                </div>

                <div class="toggle-row">
                    <div>
                        <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-dark); margin-bottom: 4px;">Two-Factor Authentication</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Require a secondary code when logging in from new devices.</div>
                    </div>
                    <input type="checkbox" class="ios-toggle" name="two_factor_enabled" <?= !empty($student['two_factor_enabled']) ? 'checked' : '' ?>>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 40px 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Emergency Contacts</h3>
                <div class="form-grid">
                    <div class="float-input">
                        <input type="text" name="emergency_contact_name" id="em_name" value="<?= htmlspecialchars($student['emergency_contact_name'] ?? '') ?>" placeholder=" ">
                        <label for="em_name">Contact Name</label>
                    </div>
                    <div class="float-input">
                        <input type="text" name="emergency_contact_phone" id="em_phone" value="<?= htmlspecialchars($student['emergency_contact_phone'] ?? '') ?>" placeholder=" ">
                        <label for="em_phone">Contact Number</label>
                    </div>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 40px 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Change Password</h3>
                <div class="form-grid">
                    <div class="float-input">
                        <input type="password" name="current_password" id="cur_pass" placeholder=" ">
                        <label for="cur_pass">Current Password</label>
                    </div>
                    <div></div>
                    <div class="float-input">
                        <input type="password" name="new_password" id="new_pass" placeholder=" ">
                        <label for="new_pass">New Password</label>
                    </div>
                    <div class="float-input">
                        <input type="password" name="confirm_password" id="conf_pass" placeholder=" ">
                        <label for="conf_pass">Confirm New Password</label>
                    </div>
                </div>

                <div class="danger-zone">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #ef4444; margin-bottom: 10px;">Danger Zone</h3>
                    <p style="font-size: 0.85rem; color: var(--text-dark); margin-bottom: 20px; opacity: 0.8;">Freezing your account will immediately revoke all portal access and require administrative intervention to restore.</p>
                    <button type="button" class="btn-save" style="background: #ef4444; color: #fff;" onclick="alert('Contacting admin to freeze account.')">Request Account Freeze</button>
                </div>
            </form>
        </div>

        <div id="tab-preferences" class="tab-content <?= $active_tab=='preferences' ? 'active' : '' ?>">
            <form method="POST" class="profile-card">
                <input type="hidden" name="update_prefs" value="1">
                <div class="sticky-header">
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">Preferences</h2>
                    <button type="submit" class="btn-save"><i class="fas fa-sliders-h"></i> Save Preferences</button>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Communications</h3>
                
                <div class="toggle-row">
                    <div>
                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-dark); margin-bottom: 4px;">Email Notifications</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Receive campus updates via your student email.</div>
                    </div>
                    <input type="checkbox" class="ios-toggle" name="notif_email" <?= (!isset($student['notif_email']) || $student['notif_email']) ? 'checked' : '' ?>>
                </div>

                <div class="toggle-row">
                    <div>
                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-dark); margin-bottom: 4px;">SMS Alerts</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Receive critical alerts via text message.</div>
                    </div>
                    <input type="checkbox" class="ios-toggle" name="notif_sms" <?= !empty($student['notif_sms']) ? 'checked' : '' ?>>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 40px 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Campus Life</h3>
                <div class="float-input" style="max-width: 400px;">
                    <select name="dietary_prefs" id="dietary">
                        <option value="None" <?= ($student['dietary_prefs']??'')=='None' ? 'selected':'' ?>>None Standard</option>
                        <option value="Vegetarian" <?= ($student['dietary_prefs']??'')=='Vegetarian' ? 'selected':'' ?>>Vegetarian</option>
                        <option value="Vegan" <?= ($student['dietary_prefs']??'')=='Vegan' ? 'selected':'' ?>>Vegan</option>
                        <option value="Halal" <?= ($student['dietary_prefs']??'')=='Halal' ? 'selected':'' ?>>Halal</option>
                        <option value="Gluten-Free" <?= ($student['dietary_prefs']??'')=='Gluten-Free' ? 'selected':'' ?>>Gluten-Free</option>
                    </select>
                    <label for="dietary">Dietary Restrictions (Cafeteria)</label>
                </div>
            </form>
        </div>

        <div id="tab-activity" class="tab-content <?= $active_tab=='activity' ? 'active' : '' ?>">
            <div class="profile-card">
                <div class="sticky-header">
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">Activity Log</h2>
                    <button type="button" class="btn-save" style="background:var(--main-bg); color:var(--text-dark); border:1px solid var(--border-color);"><i class="fas fa-download"></i> Download GDPR Data</button>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Active Sessions</h3>
                <div class="device-item">
                    <div class="device-icon"><i class="fas fa-desktop"></i></div>
                    <div style="flex:1;">
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-dark);">Windows PC • Chrome</div>
                        <div style="font-size: 0.8rem; color: var(--success); font-weight: 600;">Current Session</div>
                    </div>
                    <div class="mono-id"><?= $_SERVER['REMOTE_ADDR'] ?></div>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 40px 0 20px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Recent Activity</h3>
                
                <?php if(empty($logs)): ?>
                    <div style="text-align:center; padding: 40px; color:var(--text-light);">
                        <i class="fas fa-history" style="font-size:2rem; opacity:0.3; margin-bottom:10px;"></i>
                        <div>No recent activity recorded.</div>
                    </div>
                <?php else: ?>
                    <?php foreach($logs as $l): ?>
                        <div class="device-item">
                            <div class="device-icon" style="background: transparent; border:none; color:var(--text-light);"><i class="fas fa-bolt"></i></div>
                            <div style="flex:1;">
                                <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-dark);"><?= htmlspecialchars($l['action_type']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-light);"><?= htmlspecialchars($l['device_info']) ?></div>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-light); text-align:right;">
                                <div><?= date('M d, Y', strtotime($l['timestamp'])) ?></div>
                                <div><?= date('h:i A', strtotime($l['timestamp'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    
    event.currentTarget.classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
    
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);
}

document.querySelectorAll('input, textarea, select').forEach(el => {
    el.addEventListener('change', () => {
        const btn = el.closest('form').querySelector('.btn-save');
        if(btn) {
            btn.style.background = 'var(--brand-secondary)';
            btn.style.color = '#fff';
            btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Unsaved Changes';
        }
    });
});

const urlParams = new URLSearchParams(window.location.search);
if(urlParams.get('success') === '1') {
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof systemToast !== 'undefined') systemToast('Profile updated successfully.');
    });
}
</script>

<?php include 'footer.php'; ?>