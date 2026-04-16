<?php include 'config.php'; include 'header.php'; ?>
<div class="card">
    <h3><i class="fas fa-user-plus"></i> Enroll New Student</h3>
    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: center;">
        <input type="text" name="n" placeholder="Student Name" required style="margin:0;">
        <input type="email" name="e" placeholder="Student Email" required style="margin:0;">
        <button type="submit" name="add" class="btn-primary" style="margin:0;">Enroll Student</button>
    </form>
</div>

<?php 
if(isset($_POST['add'])){ 
    $n = mysqli_real_escape_string($conn, $_POST['n']);
    $e = mysqli_real_escape_string($conn, $_POST['e']); 
    mysqli_query($conn,"INSERT INTO students (name, email) VALUES ('$n','$e')"); 
} 
?>

<div class="card" style="padding: 0; overflow-x: auto;">
    <table style="margin-top: 0; box-shadow: none;">
        <tr><th>Name</th><th>Email</th><th>Actions</th></tr>
        <?php 
        $r = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
        while($row = mysqli_fetch_assoc($r)){ 
            echo "<tr>
                    <td><strong>{$row['name']}</strong></td>
                    <td>{$row['email']}</td>
                    <td><a href='actions.php?table=students&delete={$row['id']}' class='btn-del' onclick=\"return confirm('Delete this student?')\"><i class='fas fa-trash'></i> Delete</a></td>
                  </tr>";
        } 
        if(mysqli_num_rows($r) == 0) echo "<tr><td colspan='3' style='text-align:center;'>No students enrolled yet.</td></tr>";
        ?>
    </table>
</div>
<?php include 'footer.php'; ?>