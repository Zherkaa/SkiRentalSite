<?php
require 'db.php';
include 'header.php';

$active = $conn->query("
    SELECT 
        ro.id AS order_id,
        u.full_name,
        e.name,
        e.category,
        e.size,
        ro.start_date,
        ro.end_date,
        ri.line_total
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN rental_items ri ON ri.rental_id = ro.id
    JOIN equipment e ON ri.equipment_id = e.id
    WHERE ro.status = 'active'
    ORDER BY ro.start_date DESC, ro.id DESC
");

$available = $conn->query("
    SELECT id, name, category, size, daily_rate 
    FROM equipment 
    WHERE status = 'available' 
    ORDER BY category, name, size
");

$totalActiveOrders = 0;
$totalRevenue = 0.00;
$totalAvailable = 0;
$totalRented = 0;

$resA = $conn->query("SELECT COUNT(*) AS c FROM rental_orders WHERE status = 'active'");
if ($resA && $rowA = $resA->fetch_assoc()) {
    $totalActiveOrders = (int)$rowA['c'];
}

$resR = $conn->query("SELECT IFNULL(SUM(total_price),0) AS s FROM rental_orders");
if ($resR && $rowR = $resR->fetch_assoc()) {
    $totalRevenue = (float)$rowR['s'];
}

$resAv = $conn->query("SELECT COUNT(*) AS c FROM equipment WHERE status = 'available'");
if ($resAv && $rowAv = $resAv->fetch_assoc()) {
    $totalAvailable = (int)$rowAv['c'];
}

$resRt = $conn->query("SELECT COUNT(*) AS c FROM equipment WHERE status = 'rented'");
if ($resRt && $rowRt = $resRt->fetch_assoc()) {
    $totalRented = (int)$rowRt['c'];
}
?>

<div class="p-4 mb-4 rounded-3 text-white" style="background: linear-gradient(135deg, rgba(0,123,255,0.85), rgba(23,162,184,0.9)), url('images/hero.jpg') center/cover no-repeat;">
    <div class="container-fluid py-3">
        <h1 class="display-5 fw-bold">Aokiji's Ice Gear Rentals</h1>
        <p class="col-md-8 fs-5 mb-0">
            Manage multi-item ski and snowboard rentals with cart-style bookings for the Grand Line’s finest.
        </p>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Active Orders</h6>
                <h3 class="mb-0"><?php echo $totalActiveOrders; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Total Revenue ($)</h6>
                <h3 class="mb-0"><?php echo number_format($totalRevenue, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Available Gear</h6>
                <h3 class="mb-0"><?php echo $totalAvailable; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Rented Gear</h6>
                <h3 class="mb-0"><?php echo $totalRented; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">Active Rentals (Items)</div>
            <div class="card-body">
                <?php if ($active && $active->num_rows > 0): ?>
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Gear</th>
                                <th>Size</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Item Total ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $active->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category'] . ' - ' . $row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['size']); ?></td>
                                <td><?php echo $row['start_date']; ?></td>
                                <td><?php echo $row['end_date']; ?></td>
                                <td><?php echo number_format($row['line_total'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="mb-0">No active rentals right now.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">Available Gear (Quick View)</div>
            <div class="card-body">
                <?php if ($available && $available->num_rows > 0): ?>
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Gear</th>
                                <th>Size</th>
                                <th>Rate ($/day)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $available->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['category'] . ' - ' . $row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['size']); ?></td>
                                <td><?php echo number_format($row['daily_rate'], 2); ?></td>
                                <td>
                                    <a href="new_rental.php?category=<?php echo urlencode($row['category']); ?>&name=<?php echo urlencode($row['name']); ?>" class="btn btn-sm btn-success">
                                        Add to Rental
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="mb-0">All gear is currently rented out.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
