<?php
require 'db.php';
session_start();

$errors = [];
$success = "";
$successDetails = null;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['rental_meta'])) {
    $_SESSION['rental_meta'] = [
        'user_id' => 0,
        'start_date' => '',
        'end_date' => ''
    ];
}

$selected_user_id   = $_SESSION['rental_meta']['user_id'];
$start_date_value   = $_SESSION['rental_meta']['start_date'];
$end_date_value     = $_SESSION['rental_meta']['end_date'];

$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter_name     = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0) {
        $selected_user_id = (int)$_POST['user_id'];
        $_SESSION['rental_meta']['user_id'] = $selected_user_id;
    }

    if (isset($_POST['start_date'])) {
        $start_date_value = trim($_POST['start_date']);
        $_SESSION['rental_meta']['start_date'] = $start_date_value;
    }

    if (isset($_POST['end_date'])) {
        $end_date_value = trim($_POST['end_date']);
        $_SESSION['rental_meta']['end_date'] = $end_date_value;
    }

    if ($action === 'add_item') {
        $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
        if ($equipment_id <= 0) {
            $errors[] = "Select a gear type and size to add.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM equipment WHERE id = ? AND status = 'available'");
            $stmt->bind_param("i", $equipment_id);
            $stmt->execute();
            $stmt->bind_result($eid);
            if ($stmt->fetch()) {
                if (!in_array($eid, $_SESSION['cart'], true)) {
                    $_SESSION['cart'][] = $eid;
                }
            } else {
                $errors[] = "That item is no longer available.";
            }
            $stmt->close();
        }
    } elseif ($action === 'remove_item') {
        $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
        if ($equipment_id > 0 && !empty($_SESSION['cart'])) {
            $_SESSION['cart'] = array_values(array_filter(
                $_SESSION['cart'],
                function($id) use ($equipment_id) {
                    return (int)$id !== $equipment_id;
                }
            ));
        }
    } elseif ($action === 'confirm_rental') {
        if ($selected_user_id <= 0) {
            $errors[] = "Select a customer.";
        }
        if ($start_date_value === '' || $end_date_value === '') {
            $errors[] = "Enter start and end dates.";
        }
        if (empty($_SESSION['cart'])) {
            $errors[] = "Add at least one item to the cart.";
        }

        if (!$errors) {
            $start = DateTime::createFromFormat('Y-m-d', $start_date_value);
            $end   = DateTime::createFromFormat('Y-m-d', $end_date_value);
            if (!$start || !$end || $end < $start) {
                $errors[] = "Invalid date range.";
            } else {
                $days = $start->diff($end)->days + 1;

                $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
                $types        = str_repeat('i', count($_SESSION['cart']));
                $sql          = "SELECT id, daily_rate, name, category, size 
                                 FROM equipment 
                                 WHERE id IN ($placeholders) AND status = 'available'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$_SESSION['cart']);
                $stmt->execute();
                $result = $stmt->get_result();

                $equipmentData = [];
                while ($row = $result->fetch_assoc()) {
                    $equipmentData[$row['id']] = $row;
                }
                $stmt->close();

                if (count($equipmentData) !== count($_SESSION['cart'])) {
                    $errors[] = "One or more items in the cart are no longer available.";
                } else {
                    $conn->begin_transaction();
                    $ok          = true;
                    $total_price = 0.0;

                    $sd = $start->format('Y-m-d');
                    $ed = $end->format('Y-m-d');

                    $insertOrder = $conn->prepare("
                        INSERT INTO rental_orders (user_id, start_date, end_date, total_price, status) 
                        VALUES (?, ?, ?, 0, 'active')
                    ");
                    $insertOrder->bind_param("iss", $selected_user_id, $sd, $ed);
                    if (!$insertOrder->execute()) {
                        $ok = false;
                    }
                    $order_id = $insertOrder->insert_id;
                    $insertOrder->close();

                    if ($ok) {
                        $insertItem = $conn->prepare("
                            INSERT INTO rental_items (rental_id, equipment_id, daily_rate, line_total) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $updateEq = $conn->prepare("
                            UPDATE equipment SET status = 'rented' WHERE id = ?
                        ");

                        foreach ($_SESSION['cart'] as $eid) {
                            $row        = $equipmentData[$eid];
                            $daily_rate = (float)$row['daily_rate'];
                            $line_total = $daily_rate * $days;
                            $total_price += $line_total;

                            $insertItem->bind_param("iidd", $order_id, $eid, $daily_rate, $line_total);
                            if (!$insertItem->execute()) {
                                $ok = false;
                                break;
                            }

                            $updateEq->bind_param("i", $eid);
                            if (!$updateEq->execute()) {
                                $ok = false;
                                break;
                            }
                        }

                        $insertItem->close();
                        $updateEq->close();
                    }

                    if ($ok) {
                        $updateOrder = $conn->prepare("UPDATE rental_orders SET total_price = ? WHERE id = ?");
                        $updateOrder->bind_param("di", $total_price, $order_id);
                        if (!$updateOrder->execute()) {
                            $ok = false;
                        }
                        $updateOrder->close();
                    }

                    if ($ok) {
                        $conn->commit();

                        $userName = '';
                        $uStmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                        $uStmt->bind_param("i", $selected_user_id);
                        $uStmt->execute();
                        $uStmt->bind_result($userName);
                        $uStmt->fetch();
                        $uStmt->close();

                        $success = "Rental order created successfully.";
                        $successDetails = [
                            'order_id' => $order_id,
                            'customer' => $userName,
                            'start'    => $sd,
                            'end'      => $ed,
                            'total'    => $total_price,
                            'items'    => array_values($equipmentData),
                            'days'     => $days
                        ];

                        $_SESSION['cart'] = [];
                        $_SESSION['rental_meta'] = [
                            'user_id' => 0,
                            'start_date' => '',
                            'end_date' => ''
                        ];

                        $selected_user_id = 0;
                        $start_date_value = '';
                        $end_date_value   = '';
                    } else {
                        $conn->rollback();
                        $errors[] = "There was a problem creating the rental. Please try again.";
                    }
                }
            }
        }
    }
}

$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");

$equipResult = $conn->query("
    SELECT id, name, category, size 
    FROM equipment 
    WHERE status = 'available' 
    ORDER BY category, name, size
");

$equipmentTypes = [];
if ($equipResult && $equipResult->num_rows > 0) {
    while ($row = $equipResult->fetch_assoc()) {
        $key = $row['category'] . '|' . $row['name'];
        if (!isset($equipmentTypes[$key])) {
            $equipmentTypes[$key] = [
                'label' => $row['category'] . ' - ' . $row['name'],
                'items' => []
            ];
        }
        $equipmentTypes[$key]['items'][] = [
            'id'   => $row['id'],
            'size' => $row['size'] ?: 'Standard'
        ];
    }
}

$preselectedTypeKey = '';
if ($filter_category !== '' && $filter_name !== '') {
    $tmpKey = $filter_category . '|' . $filter_name;
    if (isset($equipmentTypes[$tmpKey])) {
        $preselectedTypeKey = $tmpKey;
    }
}
if ($preselectedTypeKey === '' && !empty($equipmentTypes)) {
    $keys = array_keys($equipmentTypes);
    $preselectedTypeKey = $keys[0];
}

$cartItems = [];
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $types        = str_repeat('i', count($_SESSION['cart']));
    $sql          = "SELECT id, name, category, size, daily_rate 
                     FROM equipment 
                     WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$_SESSION['cart']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
    $stmt->close();
}

include 'header.php';
?>
<h1 class="mb-4">New Rental (Cart)</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success && $successDetails): ?>
    <div class="alert alert-success mb-3">
        <h5 class="mb-1"><?php echo htmlspecialchars($success); ?></h5>
        <div class="mt-2">
            <div><strong>Order #:</strong> <?php echo htmlspecialchars($successDetails['order_id']); ?></div>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($successDetails['customer']); ?></div>
            <div><strong>Dates:</strong> <?php echo htmlspecialchars($successDetails['start']); ?> to <?php echo htmlspecialchars($successDetails['end']); ?> (<?php echo $successDetails['days']; ?> days)</div>
            <div class="mt-2"><strong>Items:</strong></div>
            <ul class="mb-1">
                <?php foreach ($successDetails['items'] as $item): ?>
                    <li><?php echo htmlspecialchars($item['category'] . ' - ' . $item['name'] . ' (' . ($item['size'] ?: 'Standard') . ')'); ?></li>
                <?php endforeach; ?>
            </ul>
            <div><strong>Total Price:</strong> $<?php echo number_format($successDetails['total'], 2); ?></div>
        </div>
        <div class="mt-3">
            <a href="index.php" class="btn btn-sm btn-outline-light bg-primary">Back to Dashboard</a>
            <a href="new_rental.php" class="btn btn-sm btn-outline-secondary">Start another order</a>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($equipmentTypes)): ?>
    <div class="alert alert-warning">No equipment is currently available to rent.</div>
<?php else: ?>
<form method="post" class="card p-3 shadow-sm mb-4">
    <div class="mb-3">
        <label class="form-label">Customer</label>
        <select name="user_id" class="form-select" required>
            <option value="">Select customer</option>
            <?php while ($u = $users->fetch_assoc()): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $u['id'] === $selected_user_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['full_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Start date</label>
        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date_value); ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">End date</label>
        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date_value); ?>" required>
    </div>

    <hr>

    <div class="mb-3">
        <label class="form-label">Gear type</label>
        <select name="equipment_type" id="equipment_type" class="form-select">
            <option value="">Select gear</option>
            <?php foreach ($equipmentTypes as $key => $data): ?>
                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $key === $preselectedTypeKey ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($data['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Size</label>
        <select name="equipment_id" id="equipment_id" class="form-select">
            <option value="">Select size</option>
        </select>
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" name="action" value="add_item" class="btn btn-outline-primary">
            Add item to cart
        </button>
        <button type="submit" name="action" value="confirm_rental" class="btn btn-primary">
            Confirm Rental Order
        </button>
    </div>
</form>

<?php if (!empty($cartItems)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">Current Cart</div>
        <div class="card-body">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Gear</th>
                        <th>Size</th>
                        <th>Rate ($/day)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['category'] . ' - ' . $item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                        <td><?php echo number_format($item['daily_rate'], 2); ?></td>
                        <td>
                            <form method="post" class="mb-0">
                                <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
                                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date_value); ?>">
                                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date_value); ?>">
                                <input type="hidden" name="equipment_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="action" value="remove_item" class="btn btn-sm btn-outline-danger">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Cart is currently empty. Add items above, then confirm the rental.</div>
<?php endif; ?>

<script>
const equipmentMap = <?php echo json_encode($equipmentTypes); ?>;

function updateSizes() {
    const typeSelect = document.getElementById('equipment_type');
    const sizeSelect = document.getElementById('equipment_id');
    sizeSelect.innerHTML = '';

    const key = typeSelect.value;
    if (!key || !equipmentMap[key]) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.text = 'Select size';
        sizeSelect.appendChild(opt);
        return;
    }

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.text = 'Select size';
    sizeSelect.appendChild(opt0);

    equipmentMap[key].items.forEach(function(item) {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.text = item.size;
        sizeSelect.appendChild(opt);
    });
}

document.getElementById('equipment_type').addEventListener('change', updateSizes);
updateSizes();
</script>
<?php endif; ?>

<?php
include 'footer.php';
$conn->close();
