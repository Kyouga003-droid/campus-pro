<?php 
include 'config.php'; 
include 'header.php'; 

$faqs = [
    [
        'id' => 'faq-1',
        'cat' => 'System',
        'icon' => 'fa-database',
        'q' => 'Why cannot I select a specific department in the dropdowns?',
        'a' => 'The system uses strict relational data mapping. If you cannot filter or select a department, it means there are no records mapped to that specific attribute. You must add core records (like Students or Faculty) before linking them elsewhere.'
    ],
    [
        'id' => 'faq-2',
        'cat' => 'Operations',
        'icon' => 'fa-calendar-times',
        'q' => 'How do I clear old calendar events?',
        'a' => 'Click the "Wipe Past" button on the Events module. This triggers a server-side cleanup that permanently deletes any event where the date is older than the current server date.'
    ],
    [
        'id' => 'faq-3',
        'cat' => 'System',
        'icon' => 'fa-satellite-dish',
        'q' => 'What does "Live Sync" on the Neural Log mean?',
        'a' => 'The system automatically logs major actions (creating, deleting, toggling statuses). The "Live Sync" indicator confirms that the live audit trail is successfully connected to the database telemetry.'
    ],
    [
        'id' => 'faq-4',
        'cat' => 'Finance',
        'icon' => 'fa-file-invoice-dollar',
        'q' => 'How is the Net Amount calculated on invoices?',
        'a' => 'The system uses a live arithmetic engine: (Base Amount + Tax) - (Discount + Scholarship Grant). If the Amount Paid equals or exceeds this Net Amount, the invoice automatically switches to "Paid".'
    ],
    [
        'id' => 'faq-5',
        'cat' => 'Registry',
        'icon' => 'fa-id-badge',
        'q' => 'What happens when I terminate an employee?',
        'a' => 'Using the batch action "Terminate Contract" will immediately change the faculty status to Terminated, dim their row in the registry, and forcefully overwrite their contract expiry date to the current date.'
    ],
    [
        'id' => 'faq-6',
        'cat' => 'Academics',
        'icon' => 'fa-clock',
        'q' => 'How does the attendance scanner calculate lateness?',
        'a' => 'If a student is logged via the barcode scanner after 08:15 AM, the system automatically tags them as "Late" and calculates the exact minutes elapsed since 08:00 AM.'
    ],
    [
        'id' => 'faq-7',
        'cat' => 'Registry',
        'icon' => 'fa-users',
        'q' => 'How do I promote a batch of students to the next year level?',
        'a' => 'In the Student Registry, select the checkboxes of the target students, choose "Promote Year Level" from the Batch Action dropdown, and click Execute. 1st Year becomes 2nd Year, etc.'
    ]
];
?>

<style>
    .help-hero { background: linear-gradient(135deg, var(--card-bg) 0%, var(--bg-grid) 100%); padding: 60px 40px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center; margin-bottom: 40px; box-shadow: var(--soft-shadow); position: relative; overflow: hidden; }
    .help-hero-title { font-size: 2.5rem; font-weight: 800; color: var(--text-dark); margin-bottom: 15px; letter-spacing: -0.5px; }
    .help-hero-sub { font-size: 1rem; color: var(--text-light); max-width: 600px; margin: 0 auto 30px; line-height: 1.6; }
    
    .sys-status-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 8px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 20px; }
    .status-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.2); } 100% { opacity: 1; transform: scale(1); } }

    .help-search-wrap { position: relative; max-width: 700px; margin: 0 auto; z-index: 10; }
    .help-search-input { width: 100%; padding: 20px 25px 20px 55px; font-size: 1.1rem; border-radius: 12px; border: 1px solid var(--border-color); background: rgba(var(--card-bg-rgb, 255,255,255), 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); transition: 0.3s; color: var(--text-dark); }
    .help-search-input:focus { border-color: var(--brand-secondary); box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15); outline: none; }
    .help-search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: var(--text-light); pointer-events: none; }

    .cat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .cat-card { background: var(--card-bg); border: 1px solid var(--border-color); padding: 25px 20px; border-radius: 12px; text-align: center; cursor: pointer; transition: 0.2s; box-shadow: var(--soft-shadow); }
    .cat-card:hover, .cat-card.active { border-color: var(--brand-secondary); transform: translateY(-3px); }
    .cat-card.active { background: var(--bg-grid); }
    .cat-icon { font-size: 2rem; color: var(--brand-secondary); margin-bottom: 15px; }
    .cat-title { font-weight: 600; font-size: 1rem; color: var(--text-dark); }

    .help-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 10px; }
    .view-title { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); }
    
    .faq-container { display: flex; flex-direction: column; gap: 12px; }
    .faq-item { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; transition: 0.3s; box-shadow: var(--soft-shadow); }
    .faq-item:hover { border-color: var(--text-light); }
    
    .faq-question { padding: 20px 25px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 600; font-size: 1.05rem; color: var(--text-dark); user-select: none; transition: 0.2s; }
    .faq-q-text { display: flex; align-items: center; gap: 15px; }
    .faq-q-icon { color: var(--brand-secondary); font-size: 1.1rem; width: 20px; text-align: center; }
    .faq-chevron { color: var(--text-light); transition: transform 0.3s ease; font-size: 1rem; }
    .faq-item.open .faq-question { background: var(--bg-grid); }
    .faq-item.open .faq-chevron { transform: rotate(180deg); color: var(--text-dark); }
    
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s ease, padding 0.4s ease; background: var(--card-bg); }
    .faq-item.open .faq-answer { max-height: 800px; }
    .faq-answer-inner { padding: 0 25px 25px 60px; font-size: 0.95rem; color: var(--text-light); line-height: 1.6; }
    
    .faq-tools { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border-light); }
    .faq-rating { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; font-weight: 500; color: var(--text-light); }
    .rate-btn { background: var(--main-bg); border: 1px solid var(--border-color); color: var(--text-dark); padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-size: 0.8rem; }
    .rate-btn:hover { background: var(--border-color); }
    .rate-btn.active-yes { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981; }
    .rate-btn.active-no { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
    
    .copy-link-btn { background: transparent; border: none; color: var(--brand-secondary); cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
    .copy-link-btn:hover { opacity: 0.7; }

    .highlight { background: rgba(245, 158, 11, 0.3); color: var(--text-dark); padding: 2px 4px; border-radius: 4px; }
    
    .empty-state { text-align: center; padding: 60px 20px; display: none; }
    .empty-state i { font-size: 3rem; color: var(--border-color); margin-bottom: 15px; }
    .empty-state h3 { font-size: 1.2rem; color: var(--text-dark); margin-bottom: 8px; }
    .empty-state p { color: var(--text-light); font-size: 0.9rem; margin-bottom: 20px; }

    .recent-tag { font-size: 0.65rem; background: var(--border-color); color: var(--text-dark); padding: 2px 6px; border-radius: 4px; margin-left: 10px; font-weight: 600; text-transform: uppercase; display: none; }

    .floating-help-menu { position: fixed; bottom: 100px; right: 30px; display: flex; flex-direction: column; gap: 10px; z-index: 90; }
    .fhm-btn { width: 50px; height: 50px; border-radius: 50%; background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-dark); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; box-shadow: var(--soft-shadow); transition: 0.2s; }
    .fhm-btn:hover { background: var(--brand-secondary); color: #fff; border-color: var(--brand-secondary); transform: translateY(-3px); }
</style>

<div class="help-hero">
    <div class="sys-status-badge" id="sysStatusBadge">
        <div class="status-dot"></div> All Systems Operational
    </div>
    <h1 class="help-hero-title">How can we help you?</h1>
    <p class="help-hero-sub">Search our knowledge base for instantaneous answers regarding registry management, billing arithmetic, or system diagnostics.</p>
    
    <div class="help-search-wrap">
        <i class="fas fa-search help-search-icon"></i>
        <input type="text" class="help-search-input" id="faqSearch" placeholder="Describe your issue..." onkeyup="filterFaqs()">
    </div>
</div>

<div class="cat-grid">
    <div class="cat-card active" onclick="setCategory('All', this)">
        <i class="fas fa-globe cat-icon"></i>
        <div class="cat-title">All Topics</div>
    </div>
    <div class="cat-card" onclick="setCategory('System', this)">
        <i class="fas fa-server cat-icon"></i>
        <div class="cat-title">System & Logs</div>
    </div>
    <div class="cat-card" onclick="setCategory('Registry', this)">
        <i class="fas fa-users cat-icon"></i>
        <div class="cat-title">Registry</div>
    </div>
    <div class="cat-card" onclick="setCategory('Finance', this)">
        <i class="fas fa-wallet cat-icon"></i>
        <div class="cat-title">Finance</div>
    </div>
</div>

<div class="help-controls">
    <div class="view-title" id="viewTitle">Frequently Asked Questions</div>
    <div style="display:flex; gap: 10px;">
        <button class="btn-action" style="padding: 8px 15px; font-size: 0.8rem;" onclick="toggleAll(true)"><i class="fas fa-angle-double-down"></i> Expand All</button>
        <button class="btn-action" style="padding: 8px 15px; font-size: 0.8rem;" onclick="toggleAll(false)"><i class="fas fa-angle-double-up"></i> Collapse All</button>
    </div>
</div>

<div class="faq-container" id="faqContainer">
    <?php foreach($faqs as $f): ?>
    <div class="faq-item" id="<?= $f['id'] ?>" data-cat="<?= $f['cat'] ?>">
        <div class="faq-question" onclick="toggleFaq(this)">
            <div class="faq-q-text">
                <i class="fas <?= $f['icon'] ?> faq-q-icon"></i>
                <span class="q-content"><?= $f['q'] ?></span>
                <span class="recent-tag">Recent</span>
            </div>
            <i class="fas fa-chevron-down faq-chevron"></i>
        </div>
        <div class="faq-answer">
            <div class="faq-answer-inner">
                <span class="a-content"><?= $f['a'] ?></span>
                <div class="faq-tools">
                    <div class="faq-rating">
                        Was this helpful? 
                        <button class="rate-btn" onclick="rateFaq(this, 'yes')">Yes</button>
                        <button class="rate-btn" onclick="rateFaq(this, 'no')">No</button>
                    </div>
                    <button class="copy-link-btn" onclick="copyLink('<?= $f['id'] ?>')">
                        <i class="fas fa-link"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="empty-state" id="emptyState">
    <i class="fas fa-search-minus"></i>
    <h3>No matching articles found</h3>
    <p>Try adjusting your search terms or submit a direct support ticket.</p>
    <button class="btn-primary" onclick="openTicketModal()"><i class="fas fa-headset"></i> Contact Support</button>
</div>

<div class="floating-help-menu">
    <button class="fhm-btn" title="Ask AI Assistant" onclick="toggleAssistant()"><i class="fas fa-robot"></i></button>
    <button class="fhm-btn" title="Submit Ticket" onclick="openTicketModal()"><i class="fas fa-ticket-alt"></i></button>
    <button class="fhm-btn" title="Scroll to Top" onclick="window.scrollTo({top:0, behavior:'smooth'})"><i class="fas fa-arrow-up"></i></button>
</div>

<div id="ticketModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 500px;">
        <button type="button" class="modal-close" onclick="document.getElementById('ticketModal').style.display='none';"><i class="fas fa-times"></i></button>
        <h2 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 20px; font-weight: 700;"><i class="fas fa-headset" style="color:var(--brand-secondary);"></i> Support Ticket</h2>
        <p style="font-size:0.9rem; color:var(--text-light); margin-bottom:20px;">Describe your issue in detail. The administration team typically responds within 2 hours.</p>
        
        <form id="ticketForm" onsubmit="submitTicket(event)">
            <select required style="width:100%; margin-bottom:15px;">
                <option value="" disabled selected>Select Issue Category</option>
                <option value="access">Account Access / Permissions</option>
                <option value="bug">System Bug / Error</option>
                <option value="data">Data Discrepancy</option>
                <option value="other">Other</option>
            </select>
            <input type="text" placeholder="Brief Subject" required style="width:100%; margin-bottom:15px;">
            <textarea placeholder="Provide specific details, error codes, or IDs..." required style="width:100%; height:120px; resize:none; margin-bottom:20px;"></textarea>
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-paper-plane"></i> Submit Request</button>
        </form>
    </div>
</div>

<script>
let currentCategory = 'All';

function setCategory(cat, el) {
    currentCategory = cat;
    document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('viewTitle').innerText = cat === 'All' ? 'Frequently Asked Questions' : `${cat} related FAQs`;
    document.getElementById('faqSearch').value = '';
    filterFaqs();
}

function filterFaqs() {
    const term = document.getElementById('faqSearch').value.toLowerCase();
    const items = document.querySelectorAll('.faq-item');
    let visibleCount = 0;

    items.forEach(item => {
        const qEl = item.querySelector('.q-content');
        const aEl = item.querySelector('.a-content');
        const originalQ = qEl.getAttribute('data-orig') || qEl.innerText;
        const originalA = aEl.getAttribute('data-orig') || aEl.innerText;
        
        if(!qEl.hasAttribute('data-orig')) qEl.setAttribute('data-orig', originalQ);
        if(!aEl.hasAttribute('data-orig')) aEl.setAttribute('data-orig', originalA);

        const cat = item.getAttribute('data-cat');
        const matchSearch = originalQ.toLowerCase().includes(term) || originalA.toLowerCase().includes(term);
        const matchCat = currentCategory === 'All' || cat === currentCategory;

        if(matchSearch && matchCat) {
            item.style.display = 'block';
            visibleCount++;
            
            if(term.length > 2) {
                const regex = new RegExp(`(${term})`, 'gi');
                qEl.innerHTML = originalQ.replace(regex, '<span class="highlight">$1</span>');
                aEl.innerHTML = originalA.replace(regex, '<span class="highlight">$1</span>');
                if(!item.classList.contains('open')) item.classList.add('open');
            } else {
                qEl.innerHTML = originalQ;
                aEl.innerHTML = originalA;
            }
        } else {
            item.style.display = 'none';
            qEl.innerHTML = originalQ;
            aEl.innerHTML = originalA;
        }
    });

    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
    document.getElementById('faqContainer').style.display = visibleCount === 0 ? 'none' : 'flex';
}

function toggleFaq(el) {
    const parent = el.parentElement;
    parent.classList.toggle('open');
    
    if(parent.classList.contains('open')) {
        const id = parent.id;
        let recent = JSON.parse(localStorage.getItem('campus_recent_faqs') || '[]');
        if(!recent.includes(id)) {
            recent.push(id);
            localStorage.setItem('campus_recent_faqs', JSON.stringify(recent));
        }
        parent.querySelector('.recent-tag').style.display = 'inline-block';
    }
}

function toggleAll(expand) {
    document.querySelectorAll('.faq-item').forEach(item => {
        if(item.style.display !== 'none') {
            if(expand) item.classList.add('open');
            else item.classList.remove('open');
        }
    });
}

function rateFaq(btn, rating) {
    const wrap = btn.parentElement;
    wrap.querySelectorAll('.rate-btn').forEach(b => b.className = 'rate-btn');
    btn.classList.add(`active-${rating}`);
    systemToast("Feedback recorded. Thank you.");
}

function copyLink(id) {
    const url = window.location.href.split('#')[0] + '#' + id;
    navigator.clipboard.writeText(url).then(() => {
        systemToast("Anchor link copied to clipboard");
    });
}

function openTicketModal() {
    document.getElementById('ticketModal').style.display = 'flex';
}

function submitTicket(e) {
    e.preventDefault();
    document.getElementById('ticketModal').style.display = 'none';
    e.target.reset();
    systemToast("Ticket #4095 submitted successfully");
}

function checkSystemStatus() {
    setTimeout(() => {
        const badge = document.getElementById('sysStatusBadge');
        const r = Math.random();
        if(r > 0.9) {
            badge.style.background = 'rgba(245, 158, 11, 0.1)';
            badge.style.color = '#f59e0b';
            badge.style.borderColor = 'rgba(245, 158, 11, 0.2)';
            badge.innerHTML = '<div class="status-dot" style="background:#f59e0b; box-shadow: 0 0 8px #f59e0b;"></div> Partial Degradation';
        } else {
            badge.style.background = 'rgba(16, 185, 129, 0.1)';
            badge.style.color = '#10b981';
            badge.style.borderColor = 'rgba(16, 185, 129, 0.2)';
            badge.innerHTML = '<div class="status-dot"></div> All Systems Operational';
        }
    }, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
    checkSystemStatus();
    
    const recent = JSON.parse(localStorage.getItem('campus_recent_faqs') || '[]');
    recent.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.querySelector('.recent-tag').style.display = 'inline-block';
    });

    const hash = window.location.hash.substring(1);
    if(hash) {
        const target = document.getElementById(hash);
        if(target) {
            target.classList.add('open');
            setTimeout(() => target.scrollIntoView({behavior: 'smooth', block: 'center'}), 500);
        }
    }
});
</script>

<?php include 'footer.php'; ?>