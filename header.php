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
        } catch(e) {}
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
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

        function toggleNavGroup(id, el) {
            let menu = document.getElementById(id);
            let chevron = el.querySelector('.chevron');
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
            
            function updateClock() {
                const now = new Date();
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
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
            --heading-font: 'Playfair Display', serif; --body-font: 'Inter', sans-serif;
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
        #pageLoader { position: fixed; top: 0; left: 0; height: 6px; width: 0%; background: var(--brand-secondary); border-bottom: 2px solid var(--border-color); z-index: 9999; transition: width 0.4s ease, opacity 0.3s; }
        
        .sidebar { width: 300px; background-color: var(--sidebar-bg); height: 100vh; position: sticky; top: 0; display: flex; flex-direction: column; flex-shrink:0; border-right: 2px solid var(--sidebar-border); z-index:100; box-shadow: var(--soft-shadow); transition: 0.3s ease;}
        .sidebar-brand { padding: 35px 25px; font-size: 1.8rem; font-weight: 900; text-transform: uppercase; display: flex; align-items: center; gap: 15px; border-bottom: 2px solid var(--sidebar-border); justify-content: center; letter-spacing: 1.5px; font-family: var(--heading-font); color: var(--brand-secondary); background: var(--sidebar-bg); }
        
        .campus-logo-svg { width: 45px; height: 45px; }
        .nav-scroll { flex: 1; overflow-y: auto; padding: 10px 0 25px 0; }
        .nav-section-label { font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 2px; padding: 15px 25px 5px 25px;}
        .nav-item, .nav-group-title { display: flex; align-items: center; padding: 14px 25px; color: var(--sidebar-text); text-decoration: none; font-size: 0.95rem; font-weight: 800; margin: 6px 20px; transition: all 0.2s ease; border-radius: 8px; border: 2px solid transparent; cursor: pointer; text-transform: uppercase; letter-spacing: 1px;}
        .nav-group-title { justify-content: space-between; }
        .nav-item i, .nav-group-title i.main-icon { width: 26px; font-size: 1.3rem; margin-right: 12px; transition: 0.2s; color: var(--sidebar-icon);}
        .nav-group-title i.chevron { font-size: 0.9rem; transition: transform 0.3s ease; color: var(--sidebar-icon);}
        
        .nav-item:hover, .nav-group-title:hover { border-color: var(--brand-secondary); transform: translate(-3px, -3px); box-shadow: 4px 4px 0px var(--brand-secondary); background: var(--sidebar-hover); color: #ffffff;}
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
        .nav-sub-item { display: flex; align-items: center; padding: 12px 15px 12px 45px; color: var(--sidebar-text); text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.2s; margin: 4px 12px; border-radius: 6px; border: 2px solid transparent; text-transform: uppercase; letter-spacing: 1px;}
        .nav-sub-item:hover { border-color: var(--brand-secondary); transform: translate(-2px, -2px); box-shadow: 3px 3px 0px var(--brand-secondary); background: var(--sidebar-hover); color: var(--brand-secondary);}
        .nav-sub-item.active { background: var(--brand-secondary); border-color: var(--brand-secondary); color: var(--brand-primary); box-shadow: 3px 3px 0px var(--brand-secondary); transform: translate(-2px, -2px); }
        
        .sidebar .btn-del { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444; }
        .sidebar .btn-del:hover { background: #ef4444; color: #ffffff; border-color: #ef4444;}
        
        .main-container { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; min-width: 0;}
        .top-bar { height: 95px; background: var(--card-bg); border-bottom: 2px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 50px; position: sticky; top: 0; z-index: 90; box-shadow: var(--soft-shadow); transition: 0.3s ease;}
        .content-area { padding: 50px; max-width: 1700px; width: 100%; margin: 0 auto; flex: 1; animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); transition: opacity 0.2s ease, transform 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        
        h1, h2, h3, h4 { font-family: var(--heading-font); font-weight: 900; letter-spacing: 0.5px; transition: color 0.3s ease; }
        
        .card { background: var(--card-bg); padding: 40px; border: 2px solid var(--border-color); box-shadow: var(--soft-shadow); margin-bottom: 40px; border-radius: 16px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: visible;} 
        .card:hover { transform: translateY(-5px); box-shadow: var(--hard-shadow), var(--soft-shadow); }
        
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
        .btn-closed { opacity: 0.5; pointer-events: none; box-shadow: none; transform: none;}
        
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
        .icon-btn { background: var(--main-bg); border: 2px solid var(--border-color); color: var(--text-dark); width: 50px; height: 50px; display: flex; justify-content: center; align-items: center; font-size: 1.3rem; cursor: pointer; transition: 0.2s; border-radius: 10px; box-shadow: 2px 2px 0px var(--border-color); position: relative;}
        .icon-btn:hover { background: var(--brand-secondary); color: var(--brand-primary); border-color: var(--brand-secondary); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--brand-secondary); }
        [data-theme="light"] .icon-btn:hover { background: var(--brand-primary); color: var(--text-inverse); border-color: var(--brand-primary); box-shadow: 4px 4px 0px var(--brand-primary);}
        .icon-btn:active { transform: translate(2px, 2px); box-shadow: none; }
        
        .badge { position: absolute; top: -8px; right: -8px; background: var(--brand-crimson); color: var(--text-inverse); font-size: 0.7rem; font-weight: 900; padding: 4px 8px; border-radius: 6px; border: 2px solid var(--border-color); box-shadow: 2px 2px 0px var(--border-color);}

        .search-bar { max-width: 500px; padding: 14px 25px; border: 2px solid var(--border-color); font-size: 1rem; font-weight: 700; border-radius: 10px; background: var(--main-bg); box-shadow: inset 0 2px 5px rgba(0,0,0,0.05); transition: 0.3s;}
        .search-bar:focus { width: 550px; box-shadow: var(--hard-shadow); transform: translate(-2px,-2px);}

        #liveClock { display: flex; align-items: center; justify-content: center; font-family: var(--body-font); font-weight: 800; color: var(--text-dark); background: var(--main-bg); height: 50px; width: 320px; border: 2px solid var(--border-color); border-radius: 10px; font-size: 0.95rem; gap: 12px; box-shadow: 2px 2px 0px var(--border-color); letter-spacing: 0.5px;}
        #clockTime { font-variant-numeric: tabular-nums; color: var(--brand-secondary); font-weight: 900;}
        [data-theme="light"] #clockTime { color: var(--brand-primary); }
        #clockDate { color: var(--text-light); }

        .notif-menu { display: none; position: absolute; top: 70px; right: 130px; width: 380px; background: var(--card-bg); border: 2px solid var(--border-color); box-shadow: var(--hard-shadow); z-index: 1000; flex-direction: column; border-radius: 12px; overflow: hidden; }
        .notif-menu.show { display: flex; animation: slideDown 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-15px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .notif-header { padding: 20px 25px; border-bottom: 2px solid var(--border-color); font-family: var(--heading-font); font-weight: 900; color: var(--text-dark); display: flex; justify-content: space-between; align-items:center; background: var(--main-bg); text-transform: uppercase; letter-spacing: 1px;}
        .notif-item { padding: 20px 25px; border-bottom: 1px solid var(--border-light); text-decoration: none; color: var(--text-dark); transition: 0.2s; display: flex; align-items: flex-start; gap: 15px; }
        .notif-item:hover { background: var(--bg-grid); padding-left: 30px; border-left: 4px solid var(--brand-secondary); }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { font-size: 1.4rem; margin-top: 2px; }

        .profile-btn { width: auto; padding: 0 20px; display: flex; gap: 12px; font-weight: 900; letter-spacing: 1px; text-decoration: none;}
        .profile-avatar { width: 30px; height: 30px; background: var(--main-bg); border-radius: 50%; border: 2px solid var(--border-color); display:flex; align-items:center; justify-content:center; color:var(--text-dark);}

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(14, 44, 70, 0.7); backdrop-filter: blur(8px); z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px;}
        .modal-box { background: var(--card-bg); border: 3px solid var(--border-color); border-radius: 16px; padding: 50px; width: 100%; max-width: 650px; box-shadow: var(--hard-shadow); position: relative; animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); max-height: 90vh; overflow-y: auto;}
        @keyframes popIn { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-close { position: absolute; top: 25px; right: 25px; background: var(--main-bg); border: 2px solid var(--border-color); color: var(--text-dark); font-size: 1.4rem; width: 45px; height: 45px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; box-shadow: 2px 2px 0px var(--border-color);}
        .modal-close:hover { color: var(--text-inverse); background: var(--brand-crimson); border-color: var(--brand-crimson); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--brand-crimson);}
        .modal-close:active { transform: translate(2px, 2px); box-shadow: none; }

        @keyframes floatBot { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 10px var(--brand-secondary), 0 0 20px inset var(--brand-secondary); } 50% { box-shadow: 0 0 30px var(--brand-secondary), 0 0 35px inset var(--brand-secondary); } }
        @keyframes ringSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .ai-fab { position: fixed; bottom: 30px; right: 30px; width: 65px; height: 65px; background: var(--brand-secondary); border: 3px solid var(--border-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--brand-primary); cursor: pointer; z-index: 9999; animation: floatBot 3s ease-in-out infinite, pulseGlow 2s infinite alternate; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ai-fab::before { content: ''; position: absolute; top: -8px; left: -8px; right: -8px; bottom: -8px; border: 2px dashed var(--border-color); border-radius: 50%; animation: ringSpin 15s linear infinite; pointer-events: none; opacity: 0.5;}
        [data-theme="light"] .ai-fab { background: var(--brand-primary); color: #fff; }
        [data-theme="light"] { @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 10px var(--brand-primary), 0 0 20px inset var(--brand-primary); } 50% { box-shadow: 0 0 30px var(--brand-primary), 0 0 35px inset var(--brand-primary); } } }
        .ai-fab:active { transform: translateY(0) scale(0.95); animation: none; box-shadow: none; }

        .ai-window { position: fixed; bottom: 110px; right: 30px; width: 420px; height: 550px; border: 3px solid transparent; background-image: linear-gradient(var(--card-bg), var(--card-bg)), linear-gradient(135deg, var(--brand-secondary), var(--brand-primary), var(--brand-secondary)); background-origin: border-box; background-clip: padding-box, border-box; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.4), 0 0 25px rgba(252, 157, 1, 0.2); z-index: 9998; display: flex; flex-direction: column; overflow: hidden; transform: translateY(20px) scale(0.95); opacity: 0; pointer-events: none; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ai-window.show { transform: translateY(0) scale(1); opacity: 1; pointer-events: all; }

        .ai-header { background: linear-gradient(90deg, var(--sub-menu-bg), var(--bg-grid)); padding: 20px; border-bottom: 2px solid var(--brand-secondary); display: flex; justify-content: space-between; align-items: center; font-family: var(--heading-font); font-weight: 900; text-transform: uppercase; color: var(--text-dark); letter-spacing: 2px; }
        .ai-close { cursor: pointer; color: var(--text-light); transition: 0.2s; font-size:1.2rem;}
        .ai-close:hover { color: var(--brand-crimson); }

        .ai-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: var(--main-bg); scroll-behavior: smooth;}
        .ai-msg { max-width: 85%; padding: 12px 16px; border-radius: 12px; font-size: 0.9rem; line-height: 1.5; font-weight: 600; box-shadow: 2px 2px 0px rgba(0,0,0,0.05); }
        
        .bot-msg { background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-dark); align-self: flex-start; border-bottom-left-radius: 2px; }
        .bot-msg a { color: var(--brand-secondary); font-weight: 800; text-decoration: none; }
        [data-theme="light"] .bot-msg a { color: var(--brand-primary); }
        .bot-msg a:hover { text-decoration: underline; }

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
        .ai-send:active { transform: translate(2px, 2px); box-shadow: none; }
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
            <button class="ai-chip" onclick="sendAiQuick('How many seats in LH-201')">Room Capacity</button>
            <button class="ai-chip" onclick="sendAiQuick('Who teaches CS101')">Faculty Check</button>
            <button class="ai-chip" onclick="sendAiQuick('Lost books')">Lost Volumes</button>
            <button class="ai-chip" onclick="sendAiQuick('Average fleet battery')">Battery Health</button>
            <button class="ai-chip" onclick="sendAiQuick('Upcoming events')">Schedule</button>
            <button class="ai-chip" onclick="sendAiQuick('Does James owe money')">Student Balance</button>
            <button class="ai-chip" onclick="sendAiQuick('How many 2A students')">Year Demographics</button>
            <button class="ai-chip" onclick="sendAiQuick('How much did Engineering spend')">Budget Spend</button>
            <button class="ai-chip" onclick="sendAiQuick('Orders from Cisco')">Vendor Orders</button>
            <button class="ai-chip" onclick="sendAiQuick('Who drives CP-2026A')">Driver Lookup</button>
            <button class="ai-chip" onclick="sendAiQuick('Do we have 1984 book')">Book Search</button>
            <button class="ai-chip" onclick="sendAiQuick('How full is Math Olympiad')">Event RSVP</button>
            <button class="ai-chip" onclick="sendAiQuick('Total campus capacity')">Campus Size</button>
            <button class="ai-chip" onclick="sendAiQuick('Search student Smith')">Find Scholar</button>
            <button class="ai-chip" onclick="sendAiQuick('Biggest department')">Demographics</button>
            <button class="ai-chip" onclick="sendAiQuick('Total hardware value')">Asset Valuation</button>
            <button class="ai-chip" onclick="sendAiQuick('Busiest instructor')">Faculty Load</button>
            <button class="ai-chip" onclick="sendAiQuick('Most expensive order')">Top Spending</button>
            <button class="ai-chip" onclick="sendAiQuick('Global seat utilization')">Room Analytics</button>
            <button class="ai-chip" onclick="sendAiQuick('Attendance rate for ENG101')">Roll Call Math</button>
        </div>
        <div class="ai-input-area">
            <input type="text" id="aiInput" placeholder="Query the matrix..." onkeypress="sendAiMessage(event)">
            <button class="ai-send" onclick="sendAiMessage({key: 'Enter'})"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <svg class="campus-logo-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <path d="M60 5 L105 25 L105 75 L60 115 L15 75 L15 25 Z" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/>
                <path d="M60 15 L95 30 L95 70 L60 100 L25 70 L25 30 Z" fill="none" stroke="var(--brand-secondary)" stroke-width="2" stroke-dasharray="6 4"/>
                <path d="M60 85 L40 70 L40 45 L60 60 Z" fill="currentColor"/>
                <path d="M60 85 L80 70 L80 45 L60 60 Z" fill="currentColor"/>
                <path d="M60 60 L60 85" stroke="var(--main-bg)" stroke-width="2"/>
                <circle cx="60" cy="35" r="8" fill="var(--brand-secondary)"/>
                <circle cx="60" cy="35" r="14" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M60 21 L60 28 M60 42 L60 49 M46 35 L53 35 M67 35 L74 35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>CAMPUS PRO</span>
        </div>
        <nav class="nav-scroll">
            <div class="nav-section-label">Core Platform</div>
            <a href="index.php" class="nav-item <?= ($current_page=='index.php')?'active':'' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
            
            <div class="nav-section-label">Registry Matrix</div>
            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-people', this)">
                    <div><i class="fas fa-users main-icon"></i> Directory</div>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="nav-sub-menu" id="menu-people">
                    <a href="students.php" class="nav-sub-item <?= ($current_page=='students.php')?'active':'' ?>"><i class="fas fa-user-graduate"></i> Students</a>
                    <a href="employees.php" class="nav-sub-item <?= ($current_page=='employees.php')?'active':'' ?>"><i class="fas fa-id-badge"></i> Faculty</a>
                    <a href="visitors.php" class="nav-sub-item <?= ($current_page=='visitors.php')?'active':'' ?>"><i class="fas fa-id-card-clip"></i> Visitors</a>
                    <a href="clubs.php" class="nav-sub-item <?= ($current_page=='clubs.php')?'active':'' ?>"><i class="fas fa-users-cog"></i> Clubs & Orgs</a>
                </div>
            </div>

            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-academics', this)">
                    <div><i class="fas fa-graduation-cap main-icon"></i> Academics</div>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="nav-sub-menu" id="menu-academics">
                    <a href="classes.php" class="nav-sub-item <?= ($current_page=='classes.php')?'active':'' ?>"><i class="fas fa-chalkboard-teacher"></i> Classes</a>
                    <a href="attendance.php" class="nav-sub-item <?= ($current_page=='attendance.php')?'active':'' ?>"><i class="fas fa-calendar-check"></i> Attendance</a>
                    <a href="grades.php" class="nav-sub-item <?= ($current_page=='grades.php')?'active':'' ?>"><i class="fas fa-award"></i> Grades</a>
                    <a href="library.php" class="nav-sub-item <?= ($current_page=='library.php')?'active':'' ?>"><i class="fas fa-book-reader"></i> Library</a>
                </div>
            </div>

            <div class="nav-section-label">Operations Logic</div>
            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-ops', this)">
                    <div><i class="fas fa-network-wired main-icon"></i> Operations</div>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="nav-sub-menu" id="menu-ops">
                    <a href="events.php" class="nav-sub-item <?= ($current_page=='events.php')?'active':'' ?>"><i class="fas fa-calendar-alt"></i> Events</a>
                    <a href="transport.php" class="nav-sub-item <?= ($current_page=='transport.php')?'active':'' ?>"><i class="fas fa-bus"></i> Transport</a>
                    <a href="it_tickets.php" class="nav-sub-item <?= ($current_page=='it_tickets.php')?'active':'' ?>"><i class="fas fa-ticket-alt"></i> IT Tickets</a>
                </div>
            </div>

            <div class="nav-group">
                <div class="nav-group-title" onclick="toggleNavGroup('menu-finance', this)">
                    <div><i class="fas fa-coins main-icon"></i> Finance & Assets</div>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="nav-sub-menu" id="menu-finance">
                    <a href="billing.php" class="nav-sub-item <?= ($current_page=='billing.php')?'active':'' ?>"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                    <a href="orders.php" class="nav-sub-item <?= ($current_page=='orders.php')?'active':'' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
                    <a href="inventory.php" class="nav-sub-item <?= ($current_page=='inventory.php')?'active':'' ?>"><i class="fas fa-boxes"></i> Inventory</a>
                    <a href="rooms.php" class="nav-sub-item <?= ($current_page=='rooms.php')?'active':'' ?>"><i class="fas fa-door-closed"></i> Rooms</a>
                    <a href="cafeteria.php" class="nav-sub-item <?= ($current_page=='cafeteria.php')?'active':'' ?>"><i class="fas fa-utensils"></i> Cafeteria</a>
                </div>
            </div>
            
            <div class="nav-section-label" style="color: #ef4444;">Emergency Command</div>
            <a href="disasters.php" class="nav-item emergency <?= ($current_page=='disasters.php')?'active':'' ?>"><i class="fas fa-satellite-dish"></i> Disaster Matrix</a>

            <a href="help.php" class="nav-item <?= ($current_page=='help.php')?'active':'' ?>" style="margin-top: 15px;"><i class="fas fa-question-circle"></i> Help Center</a>
        </nav>
        <div style="padding: 25px; border-top: 2px solid var(--sidebar-border);">
            <a href="logout.php" class="btn-action btn-del" style="width: 100%; justify-content:center; padding:14px;"><i class="fas fa-power-off"></i> Disconnect</a>
        </div>
    </aside>
    <div class="main-container">
        <header class="top-bar">
            <div>
                <input type="text" id="globalSearch" onkeyup="globalTableSearch()" class="search-bar" placeholder="&#xf002;  Search Databases globally..." style="font-family: var(--body-font), 'Font Awesome 6 Free';">
            </div>
            <div class="top-actions">
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
                
                <button class="icon-btn" id="notifBtn" onclick="toggleNotifMenu()">
                    <i class="fas fa-bell"></i>
                    <span class="badge">2</span>
                </button>
                
                <div id="notifMenu" class="notif-menu">
                    <div class="notif-header">
                        Alerts <span class="status-pill" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444;">2 New</span>
                    </div>
                    <a href="it_tickets.php" class="notif-item">
                        <i class="fas fa-ticket-alt notif-icon" style="color:#ef4444;"></i>
                        <div class="notif-text">
                            <h4 style="color:#ef4444; font-weight:800; font-family:var(--heading-font);">Critical IT Ticket</h4>
                            <p>Network outage in Library Wing C.</p>
                        </div>
                    </a>
                    <a href="inventory.php" class="notif-item">
                        <i class="fas fa-box-open notif-icon" style="color:var(--brand-secondary);"></i>
                        <div class="notif-text">
                            <h4 style="font-weight:800; font-family:var(--heading-font);">Low Stock Alert</h4>
                            <p>Chemistry Lab supplies below 15%.</p>
                        </div>
                    </a>
                </div>

                <a href="profile.php" class="icon-btn profile-btn" style="background: var(--main-bg); color: var(--text-dark); cursor:pointer;">
                    <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
                    ADMIN
                </a>
            </div>
        </header>
        <main class="content-area">