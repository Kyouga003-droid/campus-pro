<?php
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) { header("Location: login.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="view-transition" content="same-origin">
    <title>Campus Pro | Executive Administration</title>
    
    <script>
        try {
            let savedTheme = localStorage.getItem('campus_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            let savedCb = localStorage.getItem('campus_cb_mode') || 'none';
            document.documentElement.setAttribute('data-cb', savedCb);
            let savedSidebar = localStorage.getItem('campus_sidebar') || 'expanded';
            document.documentElement.setAttribute('data-sidebar', savedSidebar);
        } catch(e) {}
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const cbModes = ['none', 'protanopia', 'deuteranopia', 'tritanopia'];

        function toggleTheme() {
            const html = document.documentElement;
            const target = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            localStorage.setItem('campus_theme', target);
            const icon = document.getElementById('themeIcon');
            if(icon) icon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            window.dispatchEvent(new Event('themeChanged'));
            systemToast(`System Switched to ${target.toUpperCase()} Mode`);
        }

        function toggleColorblind() {
            const current = document.documentElement.getAttribute('data-cb') || 'none';
            let currentIndex = cbModes.indexOf(current);
            if(currentIndex === -1) currentIndex = 0;
            const nextIndex = (currentIndex + 1) % cbModes.length;
            const mode = cbModes[nextIndex];
            
            document.documentElement.setAttribute('data-cb', mode);
            localStorage.setItem('campus_cb_mode', mode);
            updateCbIcon(mode);
            systemToast(`Optic Filter: ${mode.toUpperCase()}`);
        }

        function updateCbIcon(mode) {
            const icon = document.getElementById('cbIcon');
            const badge = document.getElementById('cbBadge');
            if(!icon) return;
            if(mode === 'none') { icon.className = 'fas fa-eye'; if(badge) badge.style.display = 'none'; }
            else { 
                icon.className = 'fas fa-eye-low-vision'; 
                if(badge) { badge.style.display = 'block'; badge.innerText = mode.substring(0,3).toUpperCase(); }
            }
        }

        // NEW FUNCTION 1: Toggle Sidebar collapse state
        function toggleSidebar() {
            const html = document.documentElement;
            const target = html.getAttribute('data-sidebar') === 'collapsed' ? 'expanded' : 'collapsed';
            html.setAttribute('data-sidebar', target);
            localStorage.setItem('campus_sidebar', target);
        }

        function toggleNavGroup(id, el) {
            let menu = document.getElementById(id);
            let chevron = el.querySelector('.chevron');
            
            // If sidebar is collapsed, temporarily expand it to show the menu
            if(document.documentElement.getAttribute('data-sidebar') === 'collapsed') {
                toggleSidebar();
            }

            if(menu.classList.contains('open')) {
                menu.classList.remove('open');
                if(chevron) chevron.style.transform = 'rotate(0deg)';
            } else {
                document.querySelectorAll('.nav-sub-menu').forEach(m => {
                    m.classList.remove('open');
                    if(m.previousElementSibling) {
                        let chev = m.previousElementSibling.querySelector('.chevron');
                        if(chev) chev.style.transform = 'rotate(0deg)';
                    }
                });
                menu.classList.add('open');
                if(chevron) chevron.style.transform = 'rotate(180deg)';
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) { 
                document.documentElement.requestFullscreen().catch(e => {}); 
            } else { 
                if (document.exitFullscreen) document.exitFullscreen(); 
            }
        }

        document.addEventListener('fullscreenchange', () => {
            const fsIcon = document.getElementById('fsIcon');
            if (!document.fullscreenElement) {
                localStorage.setItem('campus_fs', 'false');
                if(fsIcon) fsIcon.className = 'fas fa-expand';
            } else {
                localStorage.setItem('campus_fs', 'true');
                if(fsIcon) fsIcon.className = 'fas fa-compress';
                systemToast("Fullscreen Workspace Engaged");
            }
        });

        document.addEventListener('click', function autoResumeFS(e) {
            if (localStorage.getItem('campus_fs') === 'true' && !document.fullscreenElement) {
                if (!e.target.closest('#fsIcon') && !e.target.closest('.modal-overlay')) {
                    if(document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen().catch(err => {});
                    }
                }
            }
        }, { capture: true, passive: true });

        function toggleNotifMenu() {
            const menu = document.getElementById('notifMenu');
            if(menu) menu.classList.toggle('show');
        }

        // NEW FUNCTION 2: Mark notifications as read
        function markNotificationsRead() {
            document.querySelectorAll('.notif-badge').forEach(b => b.style.display = 'none');
            document.querySelectorAll('.notif-item').forEach(i => i.style.opacity = '0.6');
            systemToast("Alerts marked as acknowledged");
        }

        // NEW FUNCTION 3: Toggle Profile Dropdown
        function toggleProfileMenu(e) {
            e.preventDefault();
            const menu = document.getElementById('profileMenu');
            if(menu) menu.classList.toggle('show');
        }

        // NEW FUNCTION 4: Trigger System Sync Progress
        function triggerSystemSync() {
            const bar = document.getElementById('systemSyncBar');
            if(!bar) return;
            bar.style.opacity = '1';
            bar.style.width = '0%';
            setTimeout(() => bar.style.width = '30%', 200);
            setTimeout(() => bar.style.width = '70%', 800);
            setTimeout(() => {
                bar.style.width = '100%';
                systemToast("Global Databases Synchronized");
                setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0%'; }, 500);
            }, 1500);
        }

        // NEW FUNCTION 5: Export System Logs
        function exportSystemLogs() {
            systemToast("Compiling master diagnostic logs...");
            setTimeout(() => systemToast("Logs exported to local storage"), 1500);
        }

        // NEW FUNCTION 6: Clear Cache Utility
        function clearCache() {
            localStorage.removeItem('campus_recent_pages');
            systemToast("Local application cache purged");
            renderRecentPages();
        }

        // NEW FUNCTION 7: Dynamic Breadcrumbs
        function updateBreadcrumbs() {
            const path = window.location.pathname.split('/').pop().replace('.php', '');
            const bc = document.getElementById('dynamicBreadcrumbs');
            if(bc) {
                const formatted = path ? path.replace('_', ' ').toUpperCase() : 'DASHBOARD';
                bc.innerHTML = `<span>CAMPUS PRO</span> <i class="fas fa-chevron-right" style="font-size:0.6rem; opacity:0.5;"></i> <span style="color:var(--brand-secondary);">${formatted}</span>`;
            }
        }

        // NEW FUNCTION 8: Add to Recent Pages History
        function addToRecentPages(url, title) {
            let history = JSON.parse(localStorage.getItem('campus_recent_pages') || '[]');
            history = history.filter(item => item.url !== url); // Remove duplicates
            history.unshift({ url, title });
            if (history.length > 5) history.pop();
            localStorage.setItem('campus_recent_pages', JSON.stringify(history));
            renderRecentPages();
        }

        // NEW FUNCTION 9: Render Recent Pages
        function renderRecentPages() {
            const container = document.getElementById('recentPagesContainer');
            if(!container) return;
            let history = JSON.parse(localStorage.getItem('campus_recent_pages') || '[]');
            if(history.length === 0) {
                container.innerHTML = '<div style="padding:10px 15px; font-size:0.8rem; color:var(--text-light);">No recent history</div>';
                return;
            }
            container.innerHTML = history.map(h => `<a href="${h.url}" class="recent-page-item"><i class="fas fa-history" style="opacity:0.5; margin-right:8px;"></i> ${h.title}</a>`).join('');
        }

        // NEW FUNCTION 10: Keyboard Shortcuts Listener
        document.addEventListener('keydown', (e) => {
            if(e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
            if(e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
        });

        function globalTableSearch() {
            let input = document.getElementById('globalSearch').value.toLowerCase();
            let tables = document.querySelectorAll('table tbody');
            
            tables.forEach(tbody => {
                let rows = tbody.querySelectorAll('tr:not(.roster-row)'); 
                rows.forEach(row => {
                    let localHide = row.getAttribute('data-hide-local') === 'true';
                    if (localHide) {
                        row.style.display = 'none';
                        return;
                    }
                    let text = row.innerText.toLowerCase();
                    if(text.includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        function systemToast(msg) {
            let box = document.getElementById('systemToastBox');
            if(!box) {
                box = document.createElement('div');
                box.id = 'systemToastBox';
                box.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:99999; display:flex; flex-direction:column; gap:10px; pointer-events:none;';
                document.body.appendChild(box);
            }
            const toast = document.createElement('div');
            toast.style.cssText = 'background:var(--card-bg); color:var(--text-dark); padding:15px 25px; border-radius:8px; font-weight:800; box-shadow:var(--hard-shadow); border:2px solid var(--border-color); opacity:0; transform:translateY(20px); transition:0.3s; font-family:var(--body-font); text-transform:uppercase; letter-spacing:1px; font-size:0.85rem;';
            toast.innerHTML = `<i class="fas fa-info-circle" style="color:var(--brand-secondary); margin-right:10px;"></i> ${msg}`;
            box.appendChild(toast);
            
            requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateY(-20px)'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        function toggleAssistant() {
            const win = document.getElementById('aiAssistantWindow');
            if(win.style.display === 'none' || win.style.display === '') {
                win.style.display = 'flex';
                void win.offsetWidth;
                win.classList.add('show');
                document.getElementById('aiInput').focus();
            } else {
                win.classList.remove('show');
                setTimeout(() => { win.style.display = 'none'; }, 300);
            }
        }

        function sendAiQuick(text) {
            document.getElementById('aiInput').value = text;
            sendAiMessage({key: 'Enter'});
        }

        function sendAiMessage(e) {
            if(e && e.key !== 'Enter') return;
            
            const input = document.getElementById('aiInput');
            const msg = input.value.trim();
            if(!msg) return;
            
            const chatBox = document.getElementById('aiChatBox');
            
            const userHtml = `<div class="ai-msg user-msg">${msg}</div>`;
            chatBox.insertAdjacentHTML('beforeend', userHtml);
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;
            
            const typingId = 'typing-' + Date.now();
            const typingHtml = `<div id="${typingId}" class="ai-msg bot-msg"><i class="fas fa-circle-notch fa-spin"></i> Processing...</div>`;
            chatBox.insertAdjacentHTML('beforeend', typingHtml);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('assistant_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById(typingId).remove();
                const botHtml = `<div class="ai-msg bot-msg"><i class="fas fa-robot" style="margin-right:8px; color:var(--brand-secondary);"></i> ${data.reply}</div>`;
                chatBox.insertAdjacentHTML('beforeend', botHtml);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(err => {
                document.getElementById(typingId).remove();
                chatBox.insertAdjacentHTML('beforeend', `<div class="ai-msg bot-msg" style="color:#ef4444;"><i class="fas fa-exclamation-triangle"></i> Neural link offline.</div>`);
            });
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('campus_theme') || 'light';
            const tIcon = document.getElementById('themeIcon');
            if(tIcon) tIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            const savedCb = localStorage.getItem('campus_cb_mode') || 'none';
            updateCbIcon(savedCb);

            const savedFs = localStorage.getItem('campus_fs') || 'false';
            const fsIcon = document.getElementById('fsIcon');
            if(fsIcon) fsIcon.className = savedFs === 'true' ? 'fas fa-compress' : 'fas fa-expand';
            
            updateBreadcrumbs();
            renderRecentPages();
            addToRecentPages(window.location.pathname, document.title.replace('Campus Pro | ', ''));

            function updateClock() {
                const now = new Date();
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                let h = now.getHours(); let m = now.getMinutes().toString().padStart(2, '0'); let ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
                const cDate = document.getElementById('clockDate'); const cTime = document.getElementById('clockTime');
                if(cDate) cDate.innerHTML = `<i class="far fa-calendar-alt" style="margin-right:5px; opacity:0.7;"></i> ${months[now.getMonth()]} ${now.getDate().toString().padStart(2, '0')}, ${now.getFullYear()}`;
                if(cTime) cTime.innerText = `${h.toString().padStart(2, '0')}:${m} ${ampm}`;
            }
            setInterval(updateClock, 1000); updateClock();

            setTimeout(() => { const loader = document.getElementById('pageLoader'); if(loader) { loader.style.width = '100%'; setTimeout(() => { loader.style.opacity = '0'; }, 300); } }, 50);

            document.querySelectorAll('.nav-sub-item.active').forEach(item => {
                let parentMenu = item.closest('.nav-sub-menu');
                if(parentMenu) {
                    parentMenu.classList.add('open');
                    let title = parentMenu.previousElementSibling;
                    if(title) { let chev = title.querySelector('.chevron'); if(chev) chev.style.transform = 'rotate(180deg)'; }
                }
            });

            document.addEventListener('click', (e) => {
                const notifMenu = document.getElementById('notifMenu'); const notifBtn = document.getElementById('notifBtn');
                if(notifMenu && notifMenu.classList.contains('show') && notifBtn && !notifMenu.contains(e.target) && !notifBtn.contains(e.target)) { notifMenu.classList.remove('show'); }
                
                const profileMenu = document.getElementById('profileMenu'); const profileBtn = document.getElementById('profileBtn');
                if(profileMenu && profileMenu.classList.contains('show') && profileBtn && !profileMenu.contains(e.target) && !profileBtn.contains(e.target)) { profileMenu.classList.remove('show'); }
            });

            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link) return;

                const isInternal = link.hostname === window.location.hostname;
                const isNotHash = !link.href.includes('#');
                const hasNoOnclick = !link.getAttribute('onclick');
                const isNotNewTab = link.target !== '_blank';
                const isNotAction = !link.href.includes('?') && !link.href.includes('logout.php') && !link.href.includes('del') && !link.href.includes('toggle') && !link.href.includes('checkout');

                if (isInternal && isNotHash && hasNoOnclick && isNotAction && isNotNewTab) {
                    e.preventDefault();
                    
                    const loader = document.getElementById('pageLoader');
                    if (loader) {
                        loader.style.transition = 'width 0.3s ease';
                        loader.style.opacity = '1';
                        loader.style.width = '70%';
                    }
                    
                    const content = document.querySelector('.content-area');
                    if (content) {
                        content.style.opacity = '0';
                        content.style.transform = 'translateY(15px)';
                    }
                    
                    setTimeout(() => { window.location.href = link.href; }, 200);
                }
            });
        });
    </script>
    <style>
        :root {
            --brand-primary: #0E2C46; --brand-secondary: #FC9D01; --brand-accent: #D94F00; --brand-crimson: #AB3620;
            --body-font: 'Inter', sans-serif;
        }

        [data-theme="light"] {
            --main-bg: #f4f7f9; --card-bg: #ffffff; 
            --border-color: #0f172a; --border-light: #cbd5e1;
            --text-dark: #0f172a; --text-light: #475569; --text-inverse: #ffffff;
            --hard-shadow: 4px 4px 0px rgba(15, 23, 42, 1); --soft-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
            --bg-grid: rgba(15, 23, 42, 0.05); 
            
            --sidebar-bg: #091c2d; 
            --sidebar-border: #05121e;
            --sidebar-text: #cbd5e1;
            --sidebar-icon: #94a3b8;
            --sidebar-hover: rgba(255,255,255,0.05);
            --sidebar-sub-bg: #05121e;
        }
        
        [data-theme="dark"] {
            --main-bg: #0b1120; --card-bg: #1e293b; 
            --border-color: #FC9D01; --border-light: #334155;
            --text-dark: #f8fafc; --text-light: #94a3b8; --text-inverse: #0f172a;
            --hard-shadow: 4px 4px 0px rgba(252, 157, 1, 1); --soft-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            --bg-grid: rgba(252, 157, 1, 0.05); 
            
            --sidebar-bg: #040914; 
            --sidebar-border: #FC9D01;
            --sidebar-text: #94a3b8;
            --sidebar-icon: #64748b;
            --sidebar-hover: rgba(255,255,255,0.05);
            --sidebar-sub-bg: #02050a;
        }

        html[data-cb="protanopia"] { filter: url(#protanopia); }
        html[data-cb="deuteranopia"] { filter: url(#deuteranopia); }
        html[data-cb="tritanopia"] { filter: url(#tritanopia); }
        
        ::selection { background: var(--brand-secondary); color: var(--text-inverse); }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--main-bg); border-left: 2px solid var(--border-color); }
        ::-webkit-scrollbar-thumb { background: var(--brand-secondary); border: 2px solid var(--border-color); border-radius: 10px;}
        ::-webkit-scrollbar-thumb:hover { background: var(--brand-accent); }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { font-family: var(--body-font); background-color: var(--main-bg); background-image: radial-gradient(var(--bg-grid) 2px, transparent 2px); background-size: 30px 30px; color: var(--text-dark); display: flex; min-height: 100vh; transition: background-color 0.3s ease, color 0.3s ease; }
        
        /* NEW UI 1: Progress Bar for System Sync */
        #systemSyncBar { position: fixed; top: 0; left: 0; height: 4px; width: 0%; background: #10b981; z-index: 10000; transition: width 0.4s ease, opacity 0.3s; opacity: 0; pointer-events: none;}
        #pageLoader { position: fixed; top: 0; left: 0; height: 6px; width: 0%; background: var(--brand-secondary); border-bottom: 2px solid var(--border-color); z-index: 9999; transition: width 0.4s ease, opacity 0.3s; }
        
        /* NEW UI 2: Sidebar Collapse Mode */
        .sidebar { width: 300px; background-color: var(--sidebar-bg); height: 100vh; position: sticky; top: 0; display: flex; flex-direction: column; flex-shrink:0; border-right: 2px solid var(--sidebar-border); z-index:100; box-shadow: var(--soft-shadow); transition: 0.3s ease; overflow-x: hidden;}
        html[data-sidebar="collapsed"] .sidebar { width: 90px; }
        html[data-sidebar="collapsed"] .brand-text-wrapper, 
        html[data-sidebar="collapsed"] .nav-section-label,
        html[data-sidebar="collapsed"] .nav-item span,
        html[data-sidebar="collapsed"] .nav-group-title div span,
        html[data-sidebar="collapsed"] .nav-group-title .chevron,
        html[data-sidebar="collapsed"] .nav-sub-menu { display: none !important; }
        html[data-sidebar="collapsed"] .sidebar-brand { padding: 40px 10px; }
        html[data-sidebar="collapsed"] .nav-item, 
        html[data-sidebar="collapsed"] .nav-group-title { justify-content: center; padding: 14px 0; margin: 6px 15px; position: relative;}
        html[data-sidebar="collapsed"] .nav-item i, 
        html[data-sidebar="collapsed"] .nav-group-title i { margin-right: 0; font-size: 1.5rem; }

        /* NEW UI 3: Hover Tooltips for collapsed sidebar */
        .sidebar-tooltip { position: absolute; left: 100%; top: 50%; transform: translateY(-50%); background: var(--text-dark); color: var(--main-bg); padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; white-space: nowrap; opacity: 0; pointer-events: none; transition: 0.2s; z-index: 1000; margin-left: 10px; border: 2px solid var(--border-color); box-shadow: 2px 2px 0px var(--border-color);}
        html[data-sidebar="collapsed"] .nav-item:hover .sidebar-tooltip,
        html[data-sidebar="collapsed"] .nav-group-title:hover .sidebar-tooltip { opacity: 1; margin-left: 15px; }

        .sidebar-brand { padding: 40px 20px; display: flex; flex-direction: column; align-items: center; gap: 15px; border-bottom: 2px solid var(--sidebar-border); justify-content: center; background: var(--sidebar-bg); text-align: center; }
        .campus-logo-svg { width: 65px; height: 65px; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.3)); transition: 0.3s; }
        html[data-sidebar="collapsed"] .campus-logo-svg { width: 45px; height: 45px; }
        
        .brand-text-wrapper { display: flex; flex-direction: column; justify-content: center; }
        .brand-main-text { font-family: var(--body-font); font-size: 1.8rem; font-weight: 900; color: var(--text-inverse); letter-spacing: 0.5px; line-height: 1; text-transform: none; }
        .brand-main-text span.pro { color: var(--brand-secondary); }
        .brand-tagline { font-family: var(--body-font); font-size: 0.75rem; font-weight: 600; color: var(--sidebar-text); letter-spacing: 0.5px; margin-top: 8px; opacity: 0.8; }

        .nav-scroll { flex: 1; overflow-y: auto; padding: 10px 0 25px 0; }
        .nav-section-label { font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 2px; padding: 15px 25px 5px 25px;}
        .nav-item, .nav-group-title { display: flex; align-items: center; padding: 14px 25px; color: var(--sidebar-text); text-decoration: none; font-size: 0.95rem; font-weight: 800; margin: 6px 20px; transition: all 0.2s ease; border-radius: 8px; border: 2px solid transparent; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; outline: none;}
        .nav-group-title { justify-content: space-between; }
        .nav-item i, .nav-group-title i.main-icon { width: 26px; font-size: 1.3rem; margin-right: 12px; transition: 0.2s; color: var(--sidebar-icon);}
        .nav-group-title i.chevron { font-size: 0.9rem; transition: transform 0.3s ease; color: var(--sidebar-icon);}
        
        .nav-item:hover, .nav-group-title:hover, .nav-item:focus { border-color: var(--brand-secondary); transform: translate(-3px, -3px); box-shadow: 4px 4px 0px var(--brand-secondary); background: var(--sidebar-hover); color: #ffffff;}
        .nav-item:hover i, .nav-group-title:hover i.main-icon { color: var(--brand-secondary); }
        .nav-item.active { background: var(--brand-secondary); border: 2px solid var(--brand-secondary); color: var(--brand-primary); box-shadow: 4px 4px 0px var(--brand-secondary); transform: translate(-3px, -3px); }
        .nav-item.active i { color: var(--brand-primary); }

        .nav-item.emergency { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3); font-weight: 900; }
        .nav-item.emergency:hover { background: #ef4444; color: #fff; box-shadow: 4px 4px 0px rgba(239, 68, 68, 0.5); border-color: #ef4444;}
        .nav-item.emergency i { color: #ef4444; }
        .nav-item.emergency:hover i { color: #fff; }
        .nav-item.emergency.active { background: #ef4444; color: #fff; box-shadow: 4px 4px 0px rgba(239, 68, 68, 0.5); border-color: #ef4444;}
        .nav-item.emergency.active i { color: #fff; }

        .nav-sub-menu { max-height: 0; overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); background: var(--sidebar-sub-bg); margin: 0 20px; border-radius: 8px; border: 2px solid transparent;}
        .nav-sub-menu.open { max-height: 800px; padding: 12px 0; margin-bottom: 15px; border-color: var(--sidebar-border); box-shadow: 4px 4px 0px var(--sidebar-border); }
        .nav-sub-item { display: flex; align-items: center; padding: 12px 15px 12px 45px; color: var(--sidebar-text); text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.2s; margin: 4px 12px; border-radius: 6px; border: 2px solid transparent; text-transform: uppercase; letter-spacing: 1px; outline: none;}
        .nav-sub-item:hover, .nav-sub-item:focus { border-color: var(--brand-secondary); transform: translate(-2px, -2px); box-shadow: 3px 3px 0px var(--brand-secondary); background: var(--sidebar-hover); color: var(--brand-secondary);}
        .nav-sub-item.active { background: var(--brand-secondary); border-color: var(--brand-secondary); color: var(--brand-primary); box-shadow: 3px 3px 0px var(--brand-secondary); transform: translate(-2px, -2px); }
        
        .main-container { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; min-width: 0;}
        
        /* NEW UI 4: Glassmorphism Top Bar */
        .top-bar { height: 95px; background: rgba(var(--card-bg-rgb, 255,255,255), 0.85); backdrop-filter: blur(12px); border-bottom: 2px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 50px; position: sticky; top: 0; z-index: 90; box-shadow: var(--soft-shadow); transition: 0.3s ease;}
        [data-theme="dark"] .top-bar { background: rgba(30,41,59, 0.85); }
        
        /* NEW UI 5: Breadcrumbs */
        .breadcrumbs-container { display: flex; align-items: center; gap: 15px; font-weight: 900; font-size: 0.9rem; color: var(--text-dark); letter-spacing: 1px;}
        .sidebar-toggle-btn { background: transparent; border: none; color: var(--text-dark); font-size: 1.5rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .sidebar-toggle-btn:hover { color: var(--brand-secondary); transform: scale(1.1); }

        .content-area { padding: 50px; max-width: 1700px; width: 100%; margin: 0 auto; flex: 1; animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); transition: opacity 0.2s ease, transform 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        
        .table-responsive { overflow-x: auto; border: 2px solid var(--border-color); border-radius: 12px; background: var(--card-bg); box-shadow: var(--soft-shadow); margin-bottom: 20px;}
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { background: var(--sub-menu-bg); padding: 20px 25px; text-align: left; font-size: 0.8rem; font-weight: 900; text-transform: uppercase; color: var(--text-dark); border-bottom: 2px solid var(--border-color); letter-spacing: 1.5px; position: sticky; top: 0; z-index: 10;}
        td { padding: 20px 25px; border-bottom: 1px solid var(--border-light); font-size: 1rem; vertical-align: middle; color: var(--text-dark); font-weight: 600; transition: background-color 0.2s ease; }
        tr:hover td { background: var(--bg-grid); }
        th.action-col, td.action-col { white-space: nowrap; width: 1%; }
        .table-actions-cell { display: flex; gap: 10px; align-items: center; flex-wrap: nowrap; }
        .table-btn { text-decoration: none; padding: 8px 16px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; border-radius: 8px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; border: 2px solid var(--border-color); color: var(--text-dark); background: var(--main-bg); box-shadow: 2px 2px 0px var(--border-color); white-space: nowrap;}
        .table-btn:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); }
        .table-btn:active { transform: translate(2px, 2px); box-shadow: 0 0 0 transparent; }
        .btn-trash { color: var(--brand-crimson); border-color: var(--brand-crimson); box-shadow: 2px 2px 0px var(--brand-crimson); }
        .btn-trash:hover { background: var(--brand-crimson); color: var(--text-inverse); box-shadow: 4px 4px 0px var(--brand-crimson); }
        
        input, select, textarea { width: 100%; padding: 16px 20px; margin: 10px 0; border: 2px solid var(--border-color); border-radius: 8px; font-family: var(--body-font); font-size: 1rem; color: var(--text-dark); background: var(--main-bg); font-weight:700; transition: all 0.3s ease; box-shadow: inset 0 2px 6px rgba(0,0,0,0.05);}
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--brand-secondary); background: var(--card-bg); box-shadow: var(--hard-shadow); transform: translate(-2px, -2px); }
        [data-theme="light"] input:focus, [data-theme="light"] select:focus, [data-theme="light"] textarea:focus { border-color: var(--brand-primary); }
        
        .btn-primary { background: var(--brand-secondary); color: var(--brand-primary); border: 2px solid var(--border-color); padding: 16px 32px; font-weight: 900; cursor: pointer; display: inline-flex; align-items:center; gap:12px; text-decoration: none; letter-spacing: 1px; border-radius: 8px; box-shadow: var(--hard-shadow); transition: all 0.2s ease; text-transform: uppercase; white-space: nowrap;}
        [data-theme="light"] .btn-primary { background: var(--brand-primary); color: var(--text-inverse); }
        .btn-primary:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--border-color); background: var(--brand-accent); color: var(--text-inverse); }
        .btn-primary:active { transform: translate(4px, 4px); box-shadow: 0 0 0 transparent; }
        
        .btn-action { text-decoration: none; padding: 12px 20px; font-size: 0.85rem; font-weight:800; display: inline-flex; align-items:center; gap:10px; transition: 0.2s; border: 2px solid var(--border-color); cursor:pointer; text-transform: uppercase; letter-spacing: 1px; background: var(--main-bg); color: var(--text-dark); box-shadow: 2px 2px 0px var(--border-color); border-radius: 8px; white-space: nowrap;}
        .btn-action:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); }
        .btn-action:active { transform: translate(2px, 2px); box-shadow: none; }
        .btn-del { color: var(--brand-crimson); border-color: var(--brand-crimson); box-shadow: 2px 2px 0px var(--brand-crimson);}
        .btn-del:hover { background: var(--brand-crimson); color: var(--text-inverse); box-shadow: 4px 4px 0px var(--brand-crimson);}
        
        .status-pill { padding: 8px 16px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display:inline-block; border-radius: 6px; border: 2px solid currentColor; background: var(--main-bg); box-shadow: 2px 2px 0px currentColor; white-space: nowrap;}
        
        .top-actions { display: flex; align-items: center; gap: 20px; position: relative;}
        .icon-btn { background: var(--main-bg); border: 2px solid var(--border-color); color: var(--text-dark); width: 50px; height: 50px; display: flex; justify-content: center; align-items: center; font-size: 1.3rem; cursor: pointer; transition: 0.2s; border-radius: 10px; box-shadow: 2px 2px 0px var(--border-color); position: relative; outline:none;}
        .icon-btn:hover, .icon-btn:focus { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--brand-secondary); }
        [data-theme="light"] .icon-btn:hover, [data-theme="light"] .icon-btn:focus { background: var(--brand-primary); color: var(--text-inverse); border-color: var(--brand-primary); box-shadow: 4px 4px 0px var(--brand-primary);}
        .icon-btn:active { transform: translate(2px, 2px); box-shadow: none; }
        
        .badge { position: absolute; top: -8px; right: -8px; background: var(--brand-crimson); color: var(--text-inverse); font-size: 0.7rem; font-weight: 900; padding: 4px 8px; border-radius: 6px; border: 2px solid var(--border-color); box-shadow: 2px 2px 0px var(--border-color);}

        .search-bar { max-width: 300px; padding: 14px 25px; border: 2px solid var(--border-color); font-size: 1rem; font-weight: 700; border-radius: 10px; background: var(--main-bg); box-shadow: inset 0 2px 5px rgba(0,0,0,0.05); transition: 0.3s;}
        .search-bar:focus { width: 400px; box-shadow: var(--hard-shadow); transform: translate(-2px,-2px);}

        #liveClock { display: flex; align-items: center; justify-content: center; font-family: var(--body-font); font-weight: 800; color: var(--text-dark); background: var(--main-bg); height: 50px; width: 280px; border: 2px solid var(--border-color); border-radius: 10px; font-size: 0.95rem; gap: 12px; box-shadow: 2px 2px 0px var(--border-color); letter-spacing: 0.5px;}
        #clockTime { font-variant-numeric: tabular-nums; color: var(--brand-secondary); font-weight: 900;}
        [data-theme="light"] #clockTime { color: var(--brand-primary); }
        #clockDate { color: var(--text-light); }

        /* NEW UI 6: Pulse Animation for Critical Alerts */
        @keyframes pulseAlert { 0% { box-shadow: 0 0 0 0 rgba(239,68,68, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(239,68,68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68, 0); } }
        .pulse-critical { animation: pulseAlert 2s infinite; }

        .notif-menu { display: none; position: absolute; top: 70px; right: 130px; width: 380px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--hard-shadow); z-index: 1000; flex-direction: column; border-radius: 12px; overflow: hidden; }
        .notif-menu.show { display: flex; animation: slideDown 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-15px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .notif-header { padding: 20px 25px; border-bottom: 2px solid var(--border-color); font-family: var(--body-font); font-weight: 900; color: var(--text-dark); display: flex; justify-content: space-between; align-items:center; background: var(--main-bg); text-transform: uppercase; letter-spacing: 1px;}
        .notif-item { padding: 20px 25px; border-bottom: 1px solid var(--border-light); text-decoration: none; color: var(--text-dark); transition: 0.2s; display: flex; align-items: flex-start; gap: 15px; }
        .notif-item:hover { background: var(--bg-grid); padding-left: 30px; border-left: 4px solid var(--brand-secondary); }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { font-size: 1.4rem; margin-top: 2px; }

        .profile-btn { width: auto; padding: 0 20px; display: flex; gap: 12px; font-weight: 900; letter-spacing: 1px; text-decoration: none;}
        .profile-avatar { width: 30px; height: 30px; background: var(--main-bg); border-radius: 50%; border: 2px solid var(--border-color); display:flex; align-items:center; justify-content:center; color:var(--text-dark);}

        /* NEW UI 7: Profile Dropdown Menu */
        .profile-dropdown-menu { display: none; position: absolute; top: 70px; right: 0; width: 250px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--hard-shadow); z-index: 1000; flex-direction: column; border-radius: 12px; overflow: hidden; }
        .profile-dropdown-menu.show { display: flex; animation: slideDown 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .pd-header { padding: 20px; border-bottom: 2px solid var(--border-color); background: var(--main-bg); text-align: center;}
        .pd-item { padding: 15px 20px; border-bottom: 1px solid var(--border-light); text-decoration: none; color: var(--text-dark); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: flex; align-items: center; gap: 12px; transition: 0.2s; cursor: pointer; border-left: 4px solid transparent;}
        .pd-item:hover { background: var(--bg-grid); border-left-color: var(--brand-secondary); padding-left: 25px; }
        .pd-item:last-child { border-bottom: none; color: #ef4444; }
        .pd-item:last-child:hover { border-left-color: #ef4444; background: rgba(239,68,68,0.1); }

        /* NEW UI 8: Recent Pages Dropdown */
        .recent-pages-btn { position: relative; }
        .recent-pages-menu { display: none; position: absolute; top: 70px; left: 50px; width: 250px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--hard-shadow); z-index: 1000; flex-direction: column; border-radius: 12px; overflow: hidden; }
        .recent-pages-btn:hover .recent-pages-menu { display: flex; animation: slideDown 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .recent-page-item { padding: 12px 15px; border-bottom: 1px solid var(--border-light); text-decoration: none; color: var(--text-dark); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; transition: 0.2s; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .recent-page-item:hover { background: var(--bg-grid); padding-left: 20px; color: var(--brand-secondary);}

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(14, 44, 70, 0.7); backdrop-filter: blur(8px); z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px;}
        .modal-box { background: var(--card-bg); border: 3px solid var(--border-color); border-radius: 16px; padding: 50px; width: 100%; max-width: 650px; box-shadow: var(--hard-shadow); position: relative; animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); max-height: 90vh; overflow-y: auto;}
        @keyframes popIn { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-close { position: absolute; top: 25px; right: 25px; background: var(--main-bg); border: 2px solid var(--border-color); color: var(--text-dark); font-size: 1.4rem; width: 45px; height: 45px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; box-shadow: 2px 2px 0px var(--border-color);}
        .modal-close:hover { color: var(--text-inverse); background: var(--brand-crimson); border-color: var(--brand-crimson); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--brand-crimson);}
        
        .ai-fab { position: fixed; bottom: 30px; right: 30px; width: 65px; height: 65px; background: var(--brand-secondary); border: 3px solid var(--border-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--brand-primary); cursor: pointer; z-index: 9999; animation: floatBot 3s ease-in-out infinite, pulseGlow 2s infinite alternate; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ai-window { position: fixed; bottom: 110px; right: 30px; width: 420px; height: 550px; border: 3px solid transparent; background-image: linear-gradient(var(--card-bg), var(--card-bg)), linear-gradient(135deg, var(--brand-secondary), var(--brand-primary), var(--brand-secondary)); background-origin: border-box; background-clip: padding-box, border-box; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.4), 0 0 25px rgba(252, 157, 1, 0.2); z-index: 9998; display: flex; flex-direction: column; overflow: hidden; transform: translateY(20px) scale(0.95); opacity: 0; pointer-events: none; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ai-window.show { transform: translateY(0) scale(1); opacity: 1; pointer-events: all; }
        .ai-header { background: linear-gradient(90deg, var(--sub-menu-bg), var(--bg-grid)); padding: 20px; border-bottom: 2px solid var(--brand-secondary); display: flex; justify-content: space-between; align-items: center; font-family: var(--body-font); font-weight: 900; text-transform: uppercase; color: var(--text-dark); letter-spacing: 2px; }
        .ai-close { cursor: pointer; color: var(--text-light); transition: 0.2s; font-size:1.2rem;}
        .ai-close:hover { color: var(--brand-crimson); }
        .ai-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: var(--main-bg); scroll-behavior: smooth;}
        .ai-msg { max-width: 85%; padding: 12px 16px; border-radius: 12px; font-size: 0.9rem; line-height: 1.5; font-weight: 600; box-shadow: 2px 2px 0px rgba(0,0,0,0.05); }
        .bot-msg { background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-dark); align-self: flex-start; border-bottom-left-radius: 2px; }
        .user-msg { background: var(--brand-secondary); color: var(--brand-primary); border: 1px solid var(--border-color); align-self: flex-end; border-bottom-right-radius: 2px; }
        [data-theme="light"] .user-msg { background: var(--brand-primary); color: #fff; }
        .ai-quick-chips { display: flex; gap: 10px; padding: 10px 15px; background: var(--sub-menu-bg); border-top: 1px solid var(--border-light); overflow-x: auto; white-space: nowrap;}
        .ai-quick-chips::-webkit-scrollbar { height: 6px; }
        .ai-quick-chips::-webkit-scrollbar-thumb { background: var(--border-light); border-radius: 10px; border:none;}
        .ai-chip { padding: 6px 12px; font-size: 0.75rem; font-weight: 800; border-radius: 20px; border: 2px solid var(--border-color); background: var(--card-bg); color: var(--text-dark); cursor: pointer; transition: 0.2s; text-transform: uppercase;}
        .ai-chip:hover { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary);}
        [data-theme="light"] .ai-chip:hover { background: var(--brand-primary); color: #fff; border-color: var(--brand-primary);}
        .ai-input-area { padding: 15px; background: var(--card-bg); border-top: 2px solid var(--border-color); display: flex; gap: 10px; }
        .ai-input-area input { margin: 0; border-radius: 8px; padding: 12px 15px; font-size: 0.9rem; flex:1;}
        .ai-send { background: var(--brand-secondary); color: var(--brand-primary); border: 2px solid var(--border-color); border-radius: 8px; width: 45px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; box-shadow: 2px 2px 0px var(--border-color); }
        [data-theme="light"] .ai-send { background: var(--brand-primary); color: #fff; }
        .ai-send:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--border-color); }
        
        /* Utility Classes */
        .spinner-icon { animation: fa-spin 1s infinite linear; }
    </style>
</head>
<body>
    <svg aria-hidden="true" style="width:0; height:0; position:absolute;">
        <defs>
            <filter id="protanopia"><feColorMatrix type="matrix" values="0.567 0.433 0 0 0  0.558 0.442 0 0 0  0 0.242 0.758 0 0  0 0 0 1 0"/></filter>
            <filter id="deuteranopia"><feColorMatrix type="matrix" values="0.625 0.375 0 0 0  0.7 0.3 0 0 0  0 0.3 0.7 0 0  0 0 0 1 0"/></filter>
            <filter id="tritanopia"><feColorMatrix type="matrix" values="0.95 0.05 0 0 0  0 0.433 0.567 0 0  0 0.475 0.525 0 0  0 0 0 1 0"/></filter>
        </defs>
    </svg>
    <div id="systemSyncBar"></div>
    <div id="pageLoader"></div>
    
    <div class="ai-fab" onclick="toggleAssistant()" title="Initialize Campy">
        <i class="fas fa-cube"></i>
    </div>

    <div id="aiAssistantWindow" class="ai-window" style="display:none;">
        <div class="ai-header">
            <div><i class="fas fa-book-open" style="color:var(--brand-secondary); margin-right:8px;"></i> Campy AI Archive</div>
            <i class="fas fa-times ai-close" onclick="toggleAssistant()"></i>
        </div>
        <div id="aiChatBox" class="ai-body">
            <div class="ai-msg bot-msg"><i class="fas fa-cube" style="margin-right:8px; color:var(--brand-secondary);"></i> System Online. I am <strong>Campy</strong>. My cognitive architecture is optimized for advanced university operations. Command me.</div>
        </div>
        <div class="ai-quick-chips">
            <button class="ai-chip" onclick="sendAiQuick('Daily briefing')">Briefing</button>
            <button class="ai-chip" onclick="sendAiQuick('What is the collection rate?')">Finances</button>
            <button class="ai-chip" onclick="sendAiQuick('Find available labs')">Labs</button>
            <button class="ai-chip" onclick="sendAiQuick('Are there broken buses?')">Fleet Status</button>
            <button class="ai-chip" onclick="sendAiQuick('Expiring invoices')">Overdue Bills</button>
            <button class="ai-chip" onclick="sendAiQuick('Open tickets in Library')">IT Issues</button>
        </div>
        <div class="ai-input-area">
            <input type="text" id="aiInput" placeholder="Query the matrix..." onkeypress="sendAiMessage(event)">
            <button class="ai-send" onclick="sendAiMessage({key: 'Enter'})"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <svg class="campus-logo-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <path d="M60 5 C20 15 10 40 10 70 C10 100 40 115 60 120 C80 115 110 100 110 70 C110 40 100 15 60 5 Z" fill="var(--brand-primary)" stroke="var(--brand-secondary)" stroke-width="6" stroke-linejoin="round"/>
                <path d="M60 15 C30 22 22 42 22 68 C22 92 45 105 60 108 C75 105 98 92 98 68 C98 42 90 22 60 15 Z" fill="none" stroke="var(--brand-secondary)" stroke-width="4"/>
                <path d="M60 28 C40 33 35 50 35 68 C35 85 50 95 60 98 C70 95 85 85 85 68 C85 50 80 33 60 28 Z" fill="var(--brand-secondary)"/>
            </svg>
            <div class="brand-text-wrapper">
                <span class="brand-main-text">Campus <span class="pro">PRO</span></span>
                <span class="brand-tagline">Your campus, in a better light</span>
            </div>
        </div>
        <nav class="nav-scroll">
            <div class="nav-section-label">Core Platform</div>
            <a href="index.php" class="nav-item <?= ($current_page=='index.php')?'active':'' ?>" tabindex="0">
                <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
                <div class="sidebar-tooltip">Dashboard</div>
            </a>
            
            <div class="nav-section-label">Registry Matrix</div>
            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-people', this)" tabindex="0">
                    <div><i class="fas fa-users main-icon"></i> <span>Directory</span></div>
                    <i class="fas fa-chevron-down chevron"></i>
                    <div class="sidebar-tooltip">Directory</div>
                </div>
                <div class="nav-sub-menu" id="menu-people">
                    <a href="students.php" class="nav-sub-item <?= ($current_page=='students.php')?'active':'' ?>"><span>Students</span></a>
                    <a href="employees.php" class="nav-sub-item <?= ($current_page=='employees.php')?'active':'' ?>"><span>Faculty</span></a>
                    <a href="visitors.php" class="nav-sub-item <?= ($current_page=='visitors.php')?'active':'' ?>"><span>Visitors</span></a>
                    <a href="clubs.php" class="nav-sub-item <?= ($current_page=='clubs.php')?'active':'' ?>"><span>Clubs & Orgs</span></a>
                </div>
            </div>

            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-academics', this)" tabindex="0">
                    <div><i class="fas fa-graduation-cap main-icon"></i> <span>Academics</span></div>
                    <i class="fas fa-chevron-down chevron"></i>
                    <div class="sidebar-tooltip">Academics</div>
                </div>
                <div class="nav-sub-menu" id="menu-academics">
                    <a href="classes.php" class="nav-sub-item <?= ($current_page=='classes.php')?'active':'' ?>"><span>Classes</span></a>
                    <a href="attendance.php" class="nav-sub-item <?= ($current_page=='attendance.php')?'active':'' ?>"><span>Attendance</span></a>
                    <a href="grades.php" class="nav-sub-item <?= ($current_page=='grades.php')?'active':'' ?>"><span>Grades</span></a>
                    <a href="library.php" class="nav-sub-item <?= ($current_page=='library.php')?'active':'' ?>"><span>Library</span></a>
                </div>
            </div>

            <div class="nav-section-label">Operations Logic</div>
            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-ops', this)" tabindex="0">
                    <div><i class="fas fa-network-wired main-icon"></i> <span>Operations</span></div>
                    <i class="fas fa-chevron-down chevron"></i>
                    <div class="sidebar-tooltip">Operations</div>
                </div>
                <div class="nav-sub-menu" id="menu-ops">
                    <a href="events.php" class="nav-sub-item <?= ($current_page=='events.php')?'active':'' ?>"><span>Events</span></a>
                    <a href="transport.php" class="nav-sub-item <?= ($current_page=='transport.php')?'active':'' ?>"><span>Transport</span></a>
                    <a href="it_tickets.php" class="nav-sub-item <?= ($current_page=='it_tickets.php')?'active':'' ?>"><span>IT Tickets</span></a>
                </div>
            </div>

            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-finance', this)" tabindex="0">
                    <div><i class="fas fa-coins main-icon"></i> <span>Finance & Assets</span></div>
                    <i class="fas fa-chevron-down chevron"></i>
                    <div class="sidebar-tooltip">Finance</div>
                </div>
                <div class="nav-sub-menu" id="menu-finance">
                    <a href="billing.php" class="nav-sub-item <?= ($current_page=='billing.php')?'active':'' ?>"><span>Billing</span></a>
                    <a href="orders.php" class="nav-sub-item <?= ($current_page=='orders.php')?'active':'' ?>"><span>Orders</span></a>
                    <a href="inventory.php" class="nav-sub-item <?= ($current_page=='inventory.php')?'active':'' ?>"><span>Inventory</span></a>
                    <a href="rooms.php" class="nav-sub-item <?= ($current_page=='rooms.php')?'active':'' ?>"><span>Rooms</span></a>
                    <a href="cafeteria.php" class="nav-sub-item <?= ($current_page=='cafeteria.php')?'active':'' ?>"><span>Cafeteria</span></a>
                </div>
            </div>
            
            <div class="nav-section-label" style="color: #ef4444;">Emergency Command</div>
            <a href="disasters.php" class="nav-item emergency <?= ($current_page=='disasters.php')?'active':'' ?>" tabindex="0">
                <i class="fas fa-satellite-dish"></i> <span>Disaster Matrix</span>
                <div class="sidebar-tooltip">Disasters</div>
            </a>

            <a href="help.php" class="nav-item <?= ($current_page=='help.php')?'active':'' ?>" style="margin-top: 15px;" tabindex="0">
                <i class="fas fa-question-circle"></i> <span>Help Center</span>
                <div class="sidebar-tooltip">Help Center</div>
            </a>
        </nav>
        <div style="padding: 25px; border-top: 2px solid var(--sidebar-border);">
            <a href="logout.php" class="btn-action btn-del" style="width: 100%; justify-content:center; padding:14px;"><i class="fas fa-power-off"></i> <span>Disconnect</span></a>
        </div>
    </aside>
    <div class="main-container">
        <header class="top-bar">
            <div class="breadcrumbs-container">
                <button class="sidebar-toggle-btn" onclick="toggleSidebar()" title="Toggle Sidebar [Ctrl+B]">
                    <i class="fas fa-bars"></i>
                </button>
                <div id="dynamicBreadcrumbs"></div>
                
                <div class="recent-pages-btn">
                    <button class="icon-btn" style="width:40px; height:40px; font-size:1rem;" title="Recent Pages"><i class="fas fa-history"></i></button>
                    <div class="recent-pages-menu">
                        <div style="padding:15px; border-bottom:2px solid var(--border-color); background:var(--main-bg); font-weight:900; font-size:0.8rem; text-transform:uppercase;">Recent Matrices</div>
                        <div id="recentPagesContainer"></div>
                    </div>
                </div>
            </div>
            
            <div>
                <input type="text" id="globalSearch" onkeyup="globalTableSearch()" class="search-bar" placeholder="&#xf002;  Search Databases globally... [Ctrl+K]" style="font-family: var(--body-font), 'Font Awesome 6 Free';">
            </div>
            
            <div class="top-actions">
                <button class="icon-btn" onclick="triggerSystemSync()" title="Force DB Sync"><i class="fas fa-sync-alt"></i></button>

                <div id="liveClock">
                    <span id="clockDate"></span>
                    <span id="clockTime"></span>
                </div>
                
                <button class="icon-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen"><i id="fsIcon" class="fas fa-expand"></i></button>
                
                <button class="icon-btn" onclick="toggleColorblind()" title="Optic Filter">
                    <i id="cbIcon" class="fas fa-eye"></i>
                    <span id="cbBadge" class="badge" style="display:none; background:var(--brand-primary); border-color:var(--brand-primary);"></span>
                </button>

                <button class="icon-btn" onclick="toggleTheme()" title="Toggle Theme"><i id="themeIcon" class="fas fa-moon"></i></button>
                
                <button class="icon-btn pulse-critical" id="notifBtn" onclick="toggleNotifMenu()">
                    <i class="fas fa-bell"></i>
                    <span class="badge notif-badge">2</span>
                </button>
                
                <div id="notifMenu" class="notif-menu">
                    <div class="notif-header">
                        System Alerts <button class="btn-action" style="padding:4px 8px; font-size:0.7rem; box-shadow:none; border-width:1px;" onclick="markNotificationsRead()">Mark Read</button>
                    </div>
                    <a href="it_tickets.php" class="notif-item">
                        <i class="fas fa-ticket-alt notif-icon" style="color:#ef4444;"></i>
                        <div class="notif-text">
                            <h4 style="color:#ef4444; font-weight:800; font-family:var(--body-font);">Critical IT Ticket</h4>
                            <p style="font-size:0.85rem; font-weight:600; color:var(--text-light);">Network outage in Library Wing C.</p>
                        </div>
                    </a>
                    <a href="inventory.php" class="notif-item">
                        <i class="fas fa-box-open notif-icon" style="color:var(--brand-secondary);"></i>
                        <div class="notif-text">
                            <h4 style="font-weight:800; font-family:var(--body-font);">Low Stock Alert</h4>
                            <p style="font-size:0.85rem; font-weight:600; color:var(--text-light);">Chemistry Lab supplies below 15%.</p>
                        </div>
                    </a>
                </div>

                <div style="position:relative;">
                    <a href="#" class="icon-btn profile-btn" id="profileBtn" onclick="toggleProfileMenu(event)" style="background: var(--main-bg); color: var(--text-dark);">
                        <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
                        ADMIN <i class="fas fa-chevron-down" style="font-size:0.8rem; margin-top:2px;"></i>
                    </a>
                    <div id="profileMenu" class="profile-dropdown-menu">
                        <div class="pd-header">
                            <div style="font-family:monospace; font-weight:900; font-size:1.1rem;">SYS-ADMIN-01</div>
                            <div style="font-size:0.75rem; color:var(--text-light); font-weight:800;">Executive Clearance</div>
                        </div>
                        <a href="profile.php" class="pd-item"><i class="fas fa-id-card"></i> Profile Settings</a>
                        <div class="pd-item" onclick="exportSystemLogs()"><i class="fas fa-download"></i> Export Logs</div>
                        <div class="pd-item" onclick="clearCache()"><i class="fas fa-eraser"></i> Purge Local Cache</div>
                        <a href="logout.php" class="pd-item"><i class="fas fa-power-off"></i> Terminate Session</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="content-area">