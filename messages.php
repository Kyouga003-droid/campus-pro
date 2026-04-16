<?php 
include 'config.php'; 
$success = false;
if(isset($_POST['add'])){
    $sd = mysqli_real_escape_string($conn, $_POST['sd']);
    $rc = mysqli_real_escape_string($conn, $_POST['rc']); 
    $ms = mysqli_real_escape_string($conn, $_POST['ms']);
    mysqli_query($conn, "INSERT INTO messages (sender, receiver, content) VALUES ('$sd', '$rc', '$ms')");
    $success = true;
}
include 'header.php'; 
if($success){ showSuccessScreen("messages.php"); include 'footer.php'; exit(); }
?>
<div class="card">
    <h3><i class="fas fa-envelope-open-text"></i> Staff Messaging</h3>
    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: start;">
        <input type="text" name="sd" placeholder="Sender (Your Name)" required>
        <input type="text" name="rc" placeholder="Receiver (Staff Name)" required>
        <textarea name="ms" placeholder="Type your message here..." required style="grid-column: span 2; height: 80px; resize: vertical; border-radius: 8px; padding: 12px; border: 1px solid var(--border-color);"></textarea>
        <button type="submit" name="add" class="btn-primary" style="grid-column: span 2;">Send Message <i class="fas fa-paper-plane" style="margin-left: 5px;"></i></button>
    </form>
</div>
<div class="card" style="padding: 0;">
    <div class="table-responsive">
        <table style="margin-top: 0; border: none;">
            <tr><th>From</th><th>To</th><th>Message</th><th>Actions</th></tr>
            <?php
            $res = mysqli_query($conn, "SELECT * FROM messages ORDER BY id DESC");
            while($row = mysqli_fetch_assoc($res)){
                echo "<tr>
                        <td><span class='status-pill' style='background:#f1f5f9; color:#475569;'>{$row['sender']}</span></td>
                        <td><span class='status-pill' style='background:#f1f5f9; color:#475569;'>{$row['receiver']}</span></td>
                        <td style='color: #64748b; font-style: italic;'>\"{$row['content']}\"</td>
                        <td><a href='actions.php?table=messages&delete={$row['id']}' class='btn-del'><i class='fas fa-trash'></i> Delete</a></td>
                      </tr>";
            }
            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='4' style='text-align:center;'>Inbox is empty.</td></tr>";
            ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>