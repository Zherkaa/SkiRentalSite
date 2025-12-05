<?php
require 'db.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;

    if ($rental_id <= 0) {
        $errors[] = "Select a rental order to return.";
    } else {
        $conn->begin_transaction();
        $ok = true;

        $eqStmt = $conn->prepare("
            SELECT equipment_id 
            FROM rental_items 
            WHERE rental_id = ?
        ");
        $eqStmt->bind_param("i", $rental_id);
        $eqStmt->execute();
        $eqResult = $eqStmt->get_result();

        $equipmentIds = [];
        while ($row = $eqResult->fetch_assoc()) {
            $equipmentIds[] = (int)$row['equipment_id'];
        }
        $eqStmt->close();

        if (empty($equipmentIds)) {
            $errors[] = "No items found for this rental order.";
            $ok = false;
        } else {
            $updateOrder = $conn->prepare("UPDATE rental_orders SET status = 'returned' WHERE id = ?");
            $updateOrder->bind_param("i", $rental_id);
            if (!$updateOrder->execute()) {
                $ok = false;
            }
            $updateOrder->close();

            if ($ok) {
                $placeholders = implode(',', array_fill(0, count($equipmentIds), '?'));
                $types = str_repeat('i', count($equipmentIds));
                $sql = "UPDATE equipment SET status = 'available' WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$equipmentIds);
                if (!$stmt->execute()) {
                    $ok = false;
                }
                $stmt->close();
            }
        }

        if ($ok && !$errors) {
            $conn->commit();
            $success = "Rental order returned successfully. All associated gear is now available.";
        } else {
            $conn->rollback();
            if (!$errors) {
                $errors[] = "There was a problem returning the rental order.";
            }
        }
    }
}

$active = $conn->query("
    SELECT 
        ro.id,
        u.full_name,
        ro.start_date,
        ro.end_date,
        ro.total_price,
        COUNT(ri.id) AS item_count
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    LEFT JOIN rental_items ri ON ri.rental_id = ro.id
    WHERE ro.status = 'active'
    GROUP BY ro.id, u.full_name, ro.start_date, ro.end_date, ro.total_price
    ORDER BY ro.start_date DESC, ro.id DESC
");

include 'header.php';
?>
<h1 class="mb-4">Return Rental Order</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($active && $active->num_rows > 0): ?>
<form method="post" class="card p-3 shadow-sm">
    <div class="mb-3">
        <label class="form-label">Select active rental order</label>
        <select name="rental_id" class="form-select" required>
            <option value="">-- Select rental order --</option>
            <?php while ($row = $active->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>">
                    <?php
                    echo '#'.$row['id'].' - '.htmlspecialchars($row['full_name'])
                        .' ('.$row['item_count'].' item'.($row['item_count'] != 1 ? 's' : '').') '
                        .'['.$row['start_date'].' to '.$row['end_date'].'] - $'
                        .number_format($row['total_price'], 2);
                    ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-danger">Return Selected Order</button>
</form>
<?php else: ?>
    <p>No active rental orders to return.</p>
<?php endif; ?>

<?php
include 'footer.php';
$conn->close();
