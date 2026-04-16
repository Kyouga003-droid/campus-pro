</main>
    </div>
    <script>
        // QoL #11: Live Search with Highlighting
        document.getElementById("globalSearch").addEventListener("input", function() {
            let filter = this.value.toLowerCase();
            let tables = document.querySelectorAll("table");
            tables.forEach(table => {
                let trs = table.querySelectorAll("tr:not(:first-child)");
                trs.forEach(tr => {
                    let text = tr.innerText.toLowerCase();
                    tr.style.display = text.includes(filter) ? "" : "none";
                });
            });
        });

        // QoL #4: Dark Mode Persistance
        function toggleTheme() {
            const body = document.documentElement;
            const icon = document.getElementById('themeIcon');
            if (body.getAttribute('data-theme') === 'light') {
                body.setAttribute('data-theme', 'dark');
                icon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                icon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            }
        }
        if(localStorage.getItem('theme') === 'dark') { toggleTheme(); toggleTheme(); toggleTheme(); } // Quick sync

        // QoL #10: Dashboard Live Clock
        function updateClock() {
            const now = new Date();
            const clockNode = document.getElementById('liveClock');
            if(clockNode) clockNode.innerText = now.toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' });
        }
        setInterval(updateClock, 1000); updateClock();

        // QoL #5: Export Table to CSV
        function downloadCSV(tableId, filename) {
            let csv = [];
            let rows = document.querySelectorAll("#" + tableId + " tr");
            for (let i = 0; i < rows.length; i++) {
                if(rows[i].style.display === 'none') continue;
                let row = [], cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length - 1; j++) { // Ignore last column (Actions)
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                csv.push(row.join(","));
            }
            let blob = new Blob([csv.join("\n")], { type: "text/csv" });
            let url = window.URL.createObjectURL(blob);
            let a = document.createElement("a");
            a.setAttribute("href", url);
            a.setAttribute("download", filename + ".csv");
            a.click();
        }
    </script>
</body>
</html>