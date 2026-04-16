<?php include 'config.php'; include 'header.php'; ?>

<div style="display: flex; gap: 20px;">
    <div class="card" style="flex: 1;">
        <h3>Schedule Appointment</h3>
        <form method="POST">
            <input type="text" name="client" placeholder="Client Name" required>
            <input type="text" name="purpose" placeholder="Purpose" required>
            <input type="date" name="appt_date" required>
            <button type="submit" name="add_appt">Book Appointment</button>
        </form>
    </div>

    <div class="card" style="flex: 1;">
        <h3>Order Products</h3>
        <form method="POST">
            <input type="text" name="prod" placeholder="Product Name" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <input type="date" name="order_date" required>
            <button type="submit" name="add_order">Place Order</button>
        </form>
    </div>
</div>

<?php
// Handle Appointment Logic
if(isset($_POST['add_appt'])) {
    $c = $_POST['client']; $p = $_POST['purpose']; $d = $_POST['appt_date'];
    mysqli_query($conn, "INSERT INTO appointments (client_name, purpose, appt_date) VALUES ('$c', '$p', '$d')");
}
// Handle Order Logic
if(isset($_POST['add_order'])) {
    $pr = $_POST['prod']; $price = $_POST['price']; $od = $_POST['order_date'];
    mysqli_query($conn, "INSERT INTO product_orders (product_name, price, order_date) VALUES ('$pr', '$price', '$od')");
}
?>

<div class="card" style="margin-top: 20px;">
    <h3>Upcoming Appointments & Orders</h3>
    <table>
        <tr><th>Type</th><th>Name/Product</th><th>Details</th><th>Date</th></tr>
        <?php
        // This query combines both tables to show a "Timeline" view
        $res = mysqli_query($conn, "SELECT 'Appointment' as type, client_name as name, purpose as info, appt_date as dt FROM appointments 
                                    UNION 
                                    SELECT 'Order' as type, product_name as name, CONCAT('$', price) as info, order_date as dt FROM product_orders 
                                    ORDER BY dt ASC");
        while($row = mysqli_fetch_assoc($res)) {
            echo "<tr><td>{$row['type']}</td><td>{$row['name']}</td><td>{$row['info']}</td><td>{$row['dt']}</td></tr>";
        }
        ?>
    </table>
</div>

<?php include 'footer.php'; ?>