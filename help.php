<?php 
include 'config.php'; 
include 'header.php'; 
?>

<style>
    .faq-container { display: flex; flex-direction: column; gap: 15px; }
    .faq-item { background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 12px; overflow: hidden; transition: 0.3s; box-shadow: var(--soft-shadow);}
    .faq-item:hover { border-color: var(--brand-secondary); box-shadow: var(--hard-shadow); transform: translate(-2px, -2px); }
    [data-theme="light"] .faq-item:hover { border-color: var(--brand-primary); }
    
    .faq-question { padding: 25px 30px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 800; font-size: 1.05rem; color: var(--text-dark); user-select: none; }
    .faq-question i { color: var(--brand-secondary); transition: transform 0.3s ease; font-size: 1.2rem;}
    [data-theme="light"] .faq-question i { color: var(--brand-primary); }
    
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), padding 0.4s ease; background: var(--sub-menu-bg); border-top: 1px solid transparent;}
    .faq-answer-inner { padding: 0 30px 25px 30px; color: var(--text-light); line-height: 1.6; font-size: 0.95rem; font-weight: 600;}
    
    .faq-item.open .faq-answer { max-height: 500px; border-top-color: var(--border-light); padding-top: 20px;}
    .faq-item.open .faq-question i { transform: rotate(180deg); }
    
    .help-icon-wrap { width: 70px; height: 70px; border-radius: 16px; background: var(--sub-menu-bg); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; color: var(--brand-primary); margin-bottom: 20px; border: 2px solid var(--border-color); box-shadow: 2px 2px 0px var(--border-color); transition: 0.3s;}
    .card:hover .help-icon-wrap { transform: scale(1.1) translateY(-5px); box-shadow: 4px 4px 0px var(--border-color); }
</style>

<div class="card" style="margin-bottom: 30px; padding: 40px; border-top: 10px solid var(--brand-primary);">
    <h1 style="color: var(--brand-primary); font-size:3rem; margin-bottom:10px; font-family: var(--heading-font); letter-spacing: 2px; text-transform: uppercase;">System Help Center</h1>
    <p style="color: var(--text-light); font-weight: 600; font-size:1.15rem;">Master the basics, navigate modules, and troubleshoot common issues.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px; margin-bottom: 50px;">
    <div class="card" style="padding:40px; margin:0; text-align:center; display:flex; flex-direction:column; align-items:center;">
        <div class="help-icon-wrap" style="color:#3b82f6; border-color:#3b82f6;"><i class="fas fa-book-open"></i></div>
        <h3 style="margin-bottom:15px; font-size:1.2rem; justify-content:center; border:none; padding:0;">Getting Started</h3>
        <p style="font-size:0.9rem; color:var(--text-light); font-weight:600;">Learn how to navigate the dashboard and configure initial system settings.</p>
    </div>
    <div class="card" style="padding:40px; margin:0; text-align:center; display:flex; flex-direction:column; align-items:center;">
        <div class="help-icon-wrap" style="color:var(--brand-secondary); border-color:var(--brand-secondary);"><i class="fas fa-database"></i></div>
        <h3 style="margin-bottom:15px; font-size:1.2rem; justify-content:center; color:var(--brand-secondary); border:none; padding:0;">Data Management</h3>
        <p style="font-size:0.9rem; color:var(--text-light); font-weight:600;">Add, edit, and organize students, employees, and inventory seamlessly.</p>
    </div>
    <div class="card" style="padding:40px; margin:0; text-align:center; display:flex; flex-direction:column; align-items:center;">
        <div class="help-icon-wrap" style="color:var(--brand-crimson); border-color:var(--brand-crimson);"><i class="fas fa-tools"></i></div>
        <h3 style="margin-bottom:15px; font-size:1.2rem; justify-content:center; color:var(--brand-crimson); border:none; padding:0;">Troubleshooting</h3>
        <p style="font-size:0.9rem; color:var(--text-light); font-weight:600;">Quick solutions to common operational errors and display issues.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
    <div>
        <h3 style="color:var(--text-dark); margin-bottom:25px; font-family:var(--heading-font); font-size:1.4rem; text-transform:uppercase; border-bottom: 2px solid var(--border-light); padding-bottom: 15px;"><i class="fas fa-graduation-cap" style="color:var(--brand-primary); margin-right:12px;"></i> System Basics</h3>
        <div class="faq-container">
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">How do I enroll a new student? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">Navigate to <strong>Directory > Students</strong>. Click the "New Scholar" button. Fill out the comprehensive registration form, selecting the appropriate department and semester. Click "Save Record" to finalize.</div></div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">How do I assign a student to a class? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">Go to <strong>Academics > Classes</strong>. First, ensure the class is created. Use the "Open Class" form. Once created, you can track enrollments via the live progress bars in the matrix.</div></div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">How does the auto-database patcher work? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">The system is designed to heal itself. If a new feature requires a database column that doesn't exist, the system silently detects the missing data structure and builds it automatically the moment you load the page.</div></div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">How do I export data to CSV? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">Look for the <i class="fas fa-file-csv" style="color:var(--text-dark);"></i> Export Data button located at the top right of almost every major data table (Students, Inventory, Events). Clicking it hooks into the download function.</div></div>
            </div>
        </div>
    </div>

    <div>
        <h3 style="color:var(--text-dark); margin-bottom:25px; font-family:var(--heading-font); font-size:1.4rem; text-transform:uppercase; border-bottom: 2px solid var(--border-light); padding-bottom: 15px;"><i class="fas fa-wrench" style="color:var(--brand-crimson); margin-right:12px;"></i> Troubleshooting</h3>
        <div class="faq-container">
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">The dashboard charts are not loading. <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">The charts require active data to render. Ensure you have at least one enrolled student and at least one paid billing invoice. Once data exists, the visual matrix will automatically generate the geometry.</div></div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">Dropdown lists are completely empty. <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">The system uses strict relational data mapping. If you cannot filter or select a department, it means there are no records mapped to that specific attribute. You must add core records before linking them elsewhere.</div></div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">How do I clear old calendar events? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">Click the <strong>Wipe Past</strong> button on the Events module. This triggers a server-side cleanup that permanently deletes any event where the date is older than the current server date.</div></div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('open'); systemToast('Accessing FAQ Document...')">
                <div class="faq-question">What does "Live Sync" on the Neural Log mean? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer"><div class="faq-answer-inner">The system automatically logs major actions (creating, deleting, toggling statuses). The "Live Sync" indicator confirms that the live audit trail is successfully connected to the database telemetry.</div></div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>