<?php
require 'db.php';
include 'header.php';

$sql = "
    SELECT 
        name,
        category,
        GROUP_CONCAT(DISTINCT size ORDER BY size SEPARATOR ', ') AS sizes,
        MIN(daily_rate) AS daily_rate,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_count
    FROM equipment
    GROUP BY category, name
    ORDER BY category, name
";
$result = $conn->query($sql);
?>
<h1 class="mb-4">Browse Aokiji's Gear</h1>

<div class="row g-3">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <?php
                $img = 'default.jpg';
                if ($row['category'] === 'Skis') {
                    $img = 'skis.jpg';
                } elseif ($row['category'] === 'Snowboard') {
                    $img = 'snowboard.jpg';
                } elseif ($row['category'] === 'Helmet') {
                    $img = 'helmet.jpg';
                } elseif ($row['category'] === 'Accessories' && stripos($row['name'], 'goggle') !== false) {
                    $img = 'goggles.jpg';
                } elseif ($row['category'] === 'Camera') {
                    $img = 'gopro.jpg';
                } elseif ($row['category'] === 'Clothing' && stripos($row['name'], 'pants') !== false) {
                    $img = 'pants.jpg';
                } elseif ($row['category'] === 'Clothing' && stripos($row['name'], 'jacket') !== false) {
                    $img = 'jacket.jpg';
                }
                ?>
                <img src="images/<?php echo htmlspecialchars($img); ?>" class="card-img-top" style="height:180px;object-fit:cover;">
                <div class="card-body">
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($row['name']); ?></h5>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($row['category']); ?></p>
                    <p class="mb-1">
                        <strong>Available sizes:</strong>
                        <?php echo htmlspecialchars($row['sizes'] ?: 'Standard'); ?>
                    </p>
                    <p class="mb-1"><strong>From:</strong> $<?php echo number_format($row['daily_rate'], 2); ?> / day</p>
                    <p class="mb-2">
                        <span class="badge bg-<?php echo $row['available_count'] > 0 ? 'success' : 'secondary'; ?>">
                            <?php echo $row['available_count'] > 0 ? 'Available' : 'Not available'; ?>
                        </span>
                    </p>
                </div>
                <div class="card-footer bg-white">
                    <?php if ($row['available_count'] > 0): ?>
                        <a href="new_rental.php?category=<?php echo urlencode($row['category']); ?>&name=<?php echo urlencode($row['name']); ?>" class="btn btn-sm btn-primary w-100">
                            Rent this gear
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary w-100" disabled>Not available</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No equipment found.</p>
<?php endif; ?>
</div>
<?php
include 'footer.php';
$conn->close();
