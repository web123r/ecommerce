<?php
$subtotal = 0;

function addItems($conn)
{
    try {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            die("File upload error or no file selected.");
        }

        $filename = $_FILES["image"]["name"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExts = ["jpg", "png", "jpeg", "gif", "webp"];

        if (!in_array($ext, $allowedExts)) {
            die("Invalid file extension.");
        }

        $tempname = $_FILES["image"]["tmp_name"];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tempname);
        finfo_close($finfo);

        $allowedMimes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        if (!in_array($mime, $allowedMimes)) {
            die("Invalid file format.");
        }

        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }

        $uniqueFilename = uniqid('img_', true) . '.' . $ext;
        $destination = "uploads/" . $uniqueFilename;

        if (!move_uploaded_file($tempname, $destination)) {
            die("Failed to move uploaded file.");
        }

        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = strtolower(trim($_POST['type'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 5);

        $query = "INSERT INTO `items` (`NAME`, `DESCRIPTION`, `TYPE`, `PRICE`, `RATINGS`) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssdi", $name, $description, $type, $price, $rating);

        if ($stmt->execute()) {
            $itemId = $stmt->insert_id;
            $image_query = "INSERT INTO `images` (`item_id`, `image`, `path`) VALUES (?, ?, ?)";
            $image_stmt = $conn->prepare($image_query);
            $image_stmt->bind_param("iss", $itemId, $uniqueFilename, $destination);
            $image_stmt->execute();
            $image_stmt->close();

            echo "Item and image stored successfully!";
            header("Location: admin.php");
            exit;
        } else {
            unlink($destination);
            echo "Database error: " . htmlspecialchars($stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}

function displayItems($conn, $query, $displayFormat)
{
    if ($displayFormat === 'fruit') {
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            echo '<div class="row g-4"><div class="col-lg-12"><div class="row g-4">';

            while ($row = $result->fetch_assoc()) {
                $item_id = (int) $row['id'];
                $item_name = $row['NAME'];
                $item_description = $row['DESCRIPTION'];
                $item_type = $row['TYPE'];
                $item_price = (float) $row['PRICE'];
                $image_path = !empty($row['path']) ? $row['path'] : 'img/vegetable-item-3.png';
                ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="rounded position-relative fruite-item">
                        <div class="fruite-img">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" class="img-fluid w-100 rounded-top"
                                alt="<?php echo htmlspecialchars($item_name); ?>">
                        </div>
                        <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">
                            <?php echo htmlspecialchars(ucfirst($item_type)); ?>
                        </div>
                        <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                            <h4><a href="shop_detail.php?id=<?php echo $item_id; ?>"><?php echo htmlspecialchars($item_name); ?></a>
                            </h4>
                            <p><?php echo htmlspecialchars(substr($item_description, 0, 80)); ?>...</p>
                            <div class="d-flex justify-content-between flex-lg-wrap">
                                <p class="text-dark fs-5 fw-bold mb-0">$<?php echo number_format($item_price, 2); ?> / kg</p>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $item_id; ?>">
                                    <button class="btn border border-secondary rounded-pill px-3 text-primary" type="submit">
                                        <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div></div></div>';
        } else {
            echo "<p>No items found</p>";
        }
    }

    if ($displayFormat === 'ratings') {
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            echo '<div class="row g-4"><div class="col-lg-12"><div class="row g-4">';

            while ($row = $result->fetch_assoc()) {
                $item_id = (int) $row['id'];
                $item_name = $row['NAME'] ?? '';
                $item_price = (float) ($row['PRICE'] ?? 0);
                $image_path = !empty($row['path']) ? $row['path'] : 'img/avatar.jpg';
                $image_ratings = max(0, min(5, (int) ($row['RATINGS'] ?? 0)));
                $image_negative_rating = 5 - $image_ratings;
                ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="p-4 rounded bg-light">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" class="img-fluid rounded-circle w-100" alt="">
                            </div>
                            <div class="col-6">
                                <a href="shop-detail.php?id=<?php echo $item_id; ?>"
                                    class="h5"><?php echo htmlspecialchars($item_name); ?></a>
                                <div class="d-flex my-3">
                                    <?php
                                    for ($i = 0; $i < $image_ratings; $i++) {
                                        echo '<i class="fas fa-star text-primary"></i>';
                                    }
                                    for ($i = 0; $i < $image_negative_rating; $i++) {
                                        echo '<i class="far fa-star text-primary"></i>';
                                    }
                                    ?>
                                </div>
                                <h4 class="mb-3">$<?php echo number_format($item_price, 2); ?></h4>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $item_id; ?>">
                                    <button class="btn border border-secondary rounded-pill px-3 text-primary" type="submit">
                                        <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div></div></div>';
        }
    }

    if ($displayFormat === 'checkout') {
        global $subtotal;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $cart = $_SESSION['cart'] ?? [];

        if (!is_array($cart) || empty($cart)) {
            echo '<tr><td colspan="6" class="text-center py-4">Your cart is currently empty.</td></tr>';
            return;
        }

        $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($id) => $id > 0));

        if (empty($ids)) {
            echo '<tr><td colspan="6" class="text-center py-4">Your cart has invalid product references.</td></tr>';
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $checkoutSql = "SELECT items.id, items.NAME, items.PRICE, images.path 
                        FROM items 
                        LEFT JOIN images ON items.id = images.item_id 
                        WHERE items.id IN ($placeholders)";

        $stmt = $conn->prepare($checkoutSql);

        if (!$stmt) {
            echo '<tr><td colspan="6" class="text-center text-danger py-4">Database error: ' . htmlspecialchars($conn->error) . '</td></tr>';
            return;
        }

        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo '<tr><td colspan="6" class="text-center py-4">No matching products found in database.</td></tr>';
            $stmt->close();
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $itemId = (int) $row['id'];
            $qty = (int) ($cart[$itemId] ?? 1);
            $price = (float) $row['PRICE'];
            $rowSubtotal = $price * $qty;
            ?>
            <tr>
                <th scope="row">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($row['path'] ?? 'img/vegetable-item-3.png'); ?>"
                            class="img-fluid me-5 rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="">
                    </div>
                </th>
                <td>
                    <p class="mb-0 mt-4"><?php echo htmlspecialchars($row['NAME']); ?></p>
                </td>
                <td>
                    <p class="mb-0 mt-4">$<?php echo number_format($price, 2); ?></p>
                </td>
                <td>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="d-inline">
                        <div class="input-group mt-4" style="width: 130px;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo $itemId; ?>">
                            <input type="number" name="quantity" class="form-control form-control-sm text-center"
                                value="<?php echo $qty; ?>" min="1">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                        </div>
                    </form>
                </td>
                <td>
                    <p class="mb-0 mt-4">$<?php echo number_format($rowSubtotal, 2); ?></p>
                </td>
                <td>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="d-inline">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?php echo $itemId; ?>">
                        <button type="submit" class="btn btn-md rounded-circle bg-light border mt-4">
                            <i class="fa fa-times text-danger"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php
        }
        $stmt->close();
    }
}

function renderAlert($message, $type = 'success', $duration = 4000)
{
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
    $safeDuration = (int) $duration;

    echo <<<HTML
    <div id="alert-top-container" style="
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        width: 90%;
        max-width: 480px;
        pointer-events: none;
    ">
        <div class="alert alert-{$safeType} alert-dismissible fade show shadow-sm" role="alert" style="pointer-events: auto;">
            <span>{$safeMessage}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>

    <script>
    (function() {
        const alertBox = document.querySelector('#alert-top-container .alert');
        if (alertBox && {$safeDuration} > 0) {
            setTimeout(() => {
                if (window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(alertBox).close();
                } else {
                    alertBox.classList.remove('show');
                    setTimeout(() => alertBox.remove(), 150);
                }
            }, {$safeDuration});
        }
    })();
    </script>
HTML;
}

function cart()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    if ($productId > 0 && !empty($action)) {
        switch ($action) {
            case 'add':
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] += $quantity;
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }
                $_SESSION['flash_alert'] = [
                    'message' => 'Added item to cart. Total quantity: ' . $_SESSION['cart'][$productId],
                    'type' => 'success'
                ];
                break;

            case 'update':
                if ($quantity > 0) {
                    $_SESSION['cart'][$productId] = $quantity;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
                $_SESSION['flash_alert'] = [
                    'message' => 'Cart updated successfully.',
                    'type' => 'success'
                ];
                break;

            case 'remove':
                unset($_SESSION['cart'][$productId]);
                $_SESSION['flash_alert'] = [
                    'message' => 'Item removed from cart.',
                    'type' => 'warning'
                ];
                break;

            case 'clear':
                $_SESSION['cart'] = [];
                $_SESSION['flash_alert'] = [
                    'message' => 'Cart cleared.',
                    'type' => 'info'
                ];
                break;
        }

        $redirectUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: ' . filter_var($redirectUrl, FILTER_SANITIZE_URL));
        exit;
    }
}

function displayTotal($subtotal, $shipping = 7.00)
{
    $grandTotal = $subtotal > 0 ? ($subtotal + $shipping) : 0.00;
    ?>
    <div class="row g-4 justify-content-end">
        <div class="col-8"></div>
        <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
            <div class="bg-light rounded">
                <div class="p-4">
                    <h1 class="display-6 mb-4">Cart <span class="fw-normal">Total</span></h1>
                    <div class="d-flex justify-content-between mb-4">
                        <h5 class="mb-0 me-4">Subtotal:</h5>
                        <p class="mb-0">$<?php echo number_format($subtotal, 2); ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-0 me-4">Shipping</h5>
                        <div>
                            <p class="mb-0">
                                <?php echo $subtotal > 0 ? "Flat rate: $" . number_format($shipping, 2) : "$0.00"; ?>
                            </p>
                        </div>
                    </div>
                    <p class="mb-0 text-end">Shipping to Nigeria.</p>
                </div>
                <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                    <h5 class="mb-0 ps-4 me-4">Total</h5>
                    <p class="mb-0 pe-4">$<?php echo number_format($grandTotal, 2); ?></p>
                </div>
                <a href="checkout.php"
                    class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 ms-4">
                    Proceed Checkout
                </a>
            </div>
        </div>
    </div>
    <?php
}

function getCartSubtotal($conn)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart) || empty($cart)) {
        return 0.00;
    }

    $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($id) => $id > 0));
    if (empty($ids)) {
        return 0.00;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT id, PRICE FROM items WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $subtotal = 0.00;
    while ($row = $result->fetch_assoc()) {
        $itemId = (int) $row['id'];
        $qty = (int) ($cart[$itemId] ?? 0);
        $subtotal += (float) $row['PRICE'] * $qty;
    }
    $stmt->close();

    return $subtotal;
}

function singlePage($conn, $productId = 0)
{
    $row = null;

    if ($productId > 0) {
        $stmt = $conn->prepare('SELECT items.*, images.path FROM items LEFT JOIN images ON items.id = images.item_id WHERE items.id = ? LIMIT 1');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } elseif (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $search = trim($_GET['search']);
        $stmt = $conn->prepare('SELECT items.*, images.path FROM items LEFT JOIN images ON items.id = images.item_id WHERE items.NAME LIKE CONCAT("%", ?, "%") LIMIT 1');
        $stmt->bind_param('s', $search);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $res = $conn->query('SELECT items.*, images.path FROM items LEFT JOIN images ON items.id = images.item_id ORDER BY items.id ASC LIMIT 1');
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
        }
    }

    if ($row) {
        $item_id = (int) $row['id'];
        $item_name = $row['NAME'];
        $item_type = $row['TYPE'];
        $item_price = (float) $row['PRICE'];
        $item_desc = $row['DESCRIPTION'] ?? '';
        $image_path = !empty($row['path']) ? $row['path'] : 'img/single-item.jpg';
        $rating = max(1, min(5, (int) ($row['RATINGS'] ?? 5)));
        ?>
        <div class="col-lg-8 col-xl-9">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="border rounded">
                        <a href="#">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" class="img-fluid rounded"
                                alt="<?php echo htmlspecialchars($item_name); ?>">
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($item_name); ?></h4>
                    <p class="mb-3">Category: <?php echo htmlspecialchars(ucfirst($item_type)); ?></p>
                    <h5 class="fw-bold mb-3">$<?php echo number_format($item_price, 2); ?></h5>
                    <div class="d-flex mb-4">
                        <?php
                        for ($i = 0; $i < $rating; $i++) {
                            echo '<i class="fa fa-star text-secondary"></i>';
                        }
                        for ($i = 0; $i < (5 - $rating); $i++) {
                            echo '<i class="far fa-star text-secondary"></i>';
                        }
                        ?>
                    </div>
                    <p class="mb-4"><?php echo htmlspecialchars($item_desc); ?></p>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $item_id; ?>">
                        <div class="input-group quantity mb-4" style="width: 130px;">
                            <input type="number" name="quantity" class="form-control form-control-sm text-center border-0"
                                value="1" min="1">
                        </div>
                        <button type="submit" class="btn border border-secondary rounded-pill px-4 py-2 mb-4 text-primary">
                            <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to cart
                        </button>
                    </form>
                </div>
                <div class="col-lg-12">
                    <nav>
                        <div class="nav nav-tabs mb-3">
                            <button class="nav-link active border-white border-bottom-0" type="button" role="tab"
                                id="nav-about-tab" data-bs-toggle="tab" data-bs-target="#nav-about" aria-controls="nav-about"
                                aria-selected="true">Description</button>
                            <button class="nav-link border-white border-bottom-0" type="button" role="tab" id="nav-mission-tab"
                                data-bs-toggle="tab" data-bs-target="#nav-mission" aria-controls="nav-mission"
                                aria-selected="false">Reviews</button>
                        </div>
                    </nav>
                    <div class="tab-content mb-5">
                        <div class="tab-pane active" id="nav-about" role="tabpanel" aria-labelledby="nav-about-tab">
                            <p><?php echo htmlspecialchars($item_desc); ?></p>
                        </div>
                        <div class="tab-pane" id="nav-mission" role="tabpanel" aria-labelledby="nav-mission-tab">
                            <div class="d-flex">
                                <img src="img/avatar.jpg" class="img-fluid rounded-circle p-3"
                                    style="width: 100px; height: 100px;" alt="">
                                <div>
                                    <p class="mb-2" style="font-size: 14px;">Verified Buyer</p>
                                    <h5>Customer Review</h5>
                                    <div class="d-flex mb-3">
                                        <i class="fa fa-star text-secondary"></i>
                                        <i class="fa fa-star text-secondary"></i>
                                        <i class="fa fa-star text-secondary"></i>
                                        <i class="fa fa-star text-secondary"></i>
                                        <i class="fa fa-star text-secondary"></i>
                                    </div>
                                    <p>Excellent product quality and quick shipping.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="col-lg-8 col-xl-9"><h3>No item found.</h3></div>';
    }
}

function getSidebarCategories($conn)
{
    $sql = "SELECT `TYPE`, COUNT(id) AS total FROM `items` GROUP BY `TYPE`";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cat = htmlspecialchars($row['TYPE']);
            $count = (int) $row['total'];
            echo '<li>
                    <div class="d-flex justify-content-between fruite-name">
                        <a href="shop.php?type=' . urlencode($row['TYPE']) . '">
                            <i class="fas fa-apple-alt me-2"></i>' . ucfirst($cat) . '
                        </a>
                        <span>(' . $count . ')</span>
                    </div>
                  </li>';
        }
    } else {
        echo '<li><span>No categories found</span></li>';
    }
}

function getFeaturedProducts($conn, $limit = 3)
{
    $stmt = $conn->prepare("SELECT items.id, items.NAME, items.PRICE, items.RATINGS, images.path 
                            FROM items 
                            LEFT JOIN images ON items.id = images.item_id 
                            ORDER BY items.RATINGS DESC, items.id DESC 
                            LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $itemId = (int) $row['id'];
        $itemName = htmlspecialchars($row['NAME']);
        $itemPrice = (float) $row['PRICE'];
        $imagePath = !empty($row['path']) ? htmlspecialchars($row['path']) : 'img/featur-1.jpg';
        $rating = max(0, min(5, (int) ($row['RATINGS'] ?? 5)));

        echo '<div class="d-flex align-items-center justify-content-start mb-3">
                <div class="rounded me-4" style="width: 100px; height: 100px;">
                    <img src="' . $imagePath . '" class="img-fluid rounded" style="width: 100px; height: 100px; object-fit: cover;" alt="' . $itemName . '">
                </div>
                <div>
                    <h6 class="mb-2"><a href="shop_detail.php?id=' . $itemId . '">' . $itemName . '</a></h6>
                    <div class="d-flex mb-2">';
        for ($i = 0; $i < $rating; $i++) {
            echo '<i class="fa fa-star text-secondary"></i>';
        }
        for ($i = 0; $i < (5 - $rating); $i++) {
            echo '<i class="far fa-star text-secondary"></i>';
        }
        echo '      </div>
                    <div class="d-flex mb-2">
                        <h5 class="fw-bold me-2">$' . number_format($itemPrice, 2) . '</h5>
                    </div>
                </div>
              </div>';
    }
    $stmt->close();
}

function getRelatedProducts($conn, $current_id)
{
    $type = '';
    if ($current_id > 0) {
        $tstmt = $conn->prepare("SELECT `TYPE` FROM items WHERE id = ?");
        $tstmt->bind_param("i", $current_id);
        $tstmt->execute();
        $res = $tstmt->get_result()->fetch_assoc();
        $type = $res['TYPE'] ?? '';
        $tstmt->close();
    }

    if (!empty($type)) {
        $stmt = $conn->prepare("SELECT items.*, images.path 
                                FROM items 
                                LEFT JOIN images ON items.id = images.item_id 
                                WHERE items.TYPE = ? AND items.id != ? 
                                LIMIT 8");
        $stmt->bind_param("si", $type, $current_id);
    } else {
        $stmt = $conn->prepare("SELECT items.*, images.path 
                                FROM items 
                                LEFT JOIN images ON items.id = images.item_id 
                                WHERE items.id != ? 
                                LIMIT 8");
        $stmt->bind_param("i", $current_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $itemId = (int) $item['id'];
            $name = htmlspecialchars($item['NAME']);
            $itemType = htmlspecialchars(ucfirst($item['TYPE']));
            $desc = htmlspecialchars(substr($item['DESCRIPTION'] ?? '', 0, 75));
            $price = (float) $item['PRICE'];
            $imgPath = !empty($item['path']) ? htmlspecialchars($item['path']) : 'img/vegetable-item-6.jpg';

            echo '<div class="border border-primary rounded position-relative vesitable-item">
                    <div class="vesitable-img">
                        <img src="' . $imgPath . '" class="img-fluid w-100 rounded-top" style="height: 200px; object-fit: cover;" alt="' . $name . '">
                    </div>
                    <div class="text-white bg-primary px-3 py-1 rounded position-absolute" style="top: 10px; right: 10px;">' . $itemType . '</div>
                    <div class="p-4 pb-0 rounded-bottom">
                        <h4><a href="shop-detail.php?id=' . $itemId . '">' . $name . '</a></h4>
                        <p>' . $desc . '...</p>
                        <div class="d-flex justify-content-between flex-lg-wrap">
                            <p class="text-dark fs-5 fw-bold mb-0">$' . number_format($price, 2) . ' / kg</p>
                            <form method="post" action="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="' . $itemId . '">
                                <button type="submit" class="btn border border-secondary rounded-pill px-3 py-1 mb-4 text-primary">
                                    <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to cart
                                </button>
                            </form>
                 </div>
                    </div>
                  </div>';
        }
    } else {
        echo '<p class="text-center w-100">No related products found.</p>';
    }
    $stmt->close();
}

function logout() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        
        unset($_SESSION['ID'], $_SESSION['Name'], $_SESSION['isAdmin']);
        
        $_SESSION['flash_alert'] = [
            'message' => 'You have been successfully logged out.',
            'type'    => 'info'
        ];
        
        header("Location: index.php");
        exit();
    }
}

function processOrder($conn, $orderItemsTable = 'order_items')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }


    $userId = $_SESSION['ID'] ?? null;
    if (!$userId) {
        $_SESSION['flash_alert'] = [
            'message' => 'Please log in to complete your order.',
            'type' => 'danger'
        ];
        header('Location: login.php');
        exit;
    }


    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart) || empty($cart)) {
        $_SESSION['flash_alert'] = [
            'message' => 'Your cart is empty.',
            'type' => 'warning'
        ];
        return false;
    }

    $productIds = array_values(array_filter(array_map('intval', array_keys($cart)), fn($id) => $id > 0));
    if (empty($productIds)) {
        return false;
    }

 $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('i', count($productIds));
$sql = "SELECT id, PRICE FROM items WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $itemsToOrder = [];
    $totalAmount = 0.00;

    while ($row = $result->fetch_assoc()) {
        $pId = (int) $row['id'];
        $qty = (int) ($cart[$pId] ?? 1);
        $price = (float) $row['PRICE'];

        if ($qty > 0) {
            $totalAmount += ($price * $qty);
            $itemsToOrder[] = [
                'product_id' => $pId,
                'price' => $price,
                'quantity' => $qty
            ];
        }
    }
    $stmt->close();

    if (empty($itemsToOrder)) {
        return false;
    }


    $conn->begin_transaction();

    try {

        $orderStatus = 0;
        $orderSql = "INSERT INTO `orders` (`user_id`, `total_amount`, `status`, `created_at`) VALUES (?, ?, ?, NOW())";
        $orderStmt = $conn->prepare($orderSql);
        $orderStmt->bind_param("idi", $userId, $totalAmount, $orderStatus);
        $orderStmt->execute();

        $orderId = $orderStmt->insert_id;
        $orderStmt->close();


        $itemSql = "INSERT INTO `{$orderItemsTable}` (`order_id`, `product_id`, `price`, `quantity`, `created_at`) VALUES (?, ?, ?, ?, NOW())";
        $itemStmt = $conn->prepare($itemSql);

        foreach ($itemsToOrder as $item) {
            $itemStmt->bind_param(
                "iidi",
                $orderId,
                $item['product_id'],
                $item['price'],
                $item['quantity']
            );
            $itemStmt->execute();
        }
        $itemStmt->close();


        $conn->commit();


        $_SESSION['cart'] = [];
        $_SESSION['flash_alert'] = [
            'message' => "Order #{$orderId} placed successfully!",
            'type' => 'success'
        ];

        return $orderId;

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['flash_alert'] = [
            'message' => 'Order failed: ' . htmlspecialchars($e->getMessage()),
            'type' => 'danger'
        ];

        return false;
    }
}

function  isAdmin($conn, $userId){
    $userId = htmlspecialchars($userId);
    $query = "SELECT * FROM users WHERE ID =".$userId;
    if($result =$conn->query($query)){
        $row = $result->fetch_assoc();
        $isAdmin = $row["isAdmin"];
        return $isAdmin;

}
}


function displayUserOrderHistory($conn, $userId)
{
    $orderSql = "SELECT id, total_amount, status, created_at 
                 FROM orders 
                 WHERE user_id = ? 
                 ORDER BY id DESC";

    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("i", $userId);
    $orderStmt->execute();
    $ordersResult = $orderStmt->get_result();

    if ($ordersResult->num_rows === 0) {
        echo '<tr><td colspan="6" class="text-center py-5 text-muted">You have no order history yet.</td></tr>';
        $orderStmt->close();
        return;
    }

    while ($order = $ordersResult->fetch_assoc()) {
        $orderId = (int)$order['id'];
        $orderTotal = (float)$order['total_amount'];
        $orderDate = date("M d, Y", strtotime($order['created_at']));
        $status = strtolower(trim($order['status'] ?? 0));

        
        $badgeClass = match ((int) $status) {
    1       => 'bg-success',
    0      => 'bg-danger',
    default => 'bg-warning text-dark'
};
      $textStatus = match ((int) $status) {
    1       => 'Approved',
    0       => 'Pending',
    default => 'I no sabi '
};

        
        $itemsSql = "SELECT order_items.quantity, order_items.price, items.NAME, images.path FROM order_items JOIN items ON order_items.product_id = items.id LEFT JOIN images ON items.id = images.item_id WHERE order_items.order_id =?";
        
        $itemStmt = $conn->prepare($itemsSql);
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $itemsResult = $itemStmt->get_result();
        ?>

       
        <tr class="table-light border-top border-2">
            <td colspan="3" class="py-3">
                <strong>Order #<?php echo $orderId; ?></strong> 
                <span class="text-muted ms-2">(<?php echo $orderDate; ?>)</span>
                <span class="badge ms-2 <?php echo $badgeClass; ?>">
                    <?php echo ucfirst(htmlspecialchars($textStatus)); ?>
                </span>
            </td>
            <td class="py-3 text-muted"></td>
            <td colspan="2" class="py-3 text-end">
                <strong>Order Total: $<?php echo number_format($orderTotal, 2); ?></strong>
            </td>
        </tr>

        <?php while ($item = $itemsResult->fetch_assoc()): 
            $itemTotal = (float)$item['price'] * (int)$item['quantity'];
            $imageSrc = !empty($item['path']) ? htmlspecialchars($item['path']) : 'img/vegetable-item-2.jpg';
        ?>
            <tr>
                <td style="width: 80px;">
                    <img src="<?php echo $imageSrc; ?>" class="img-fluid rounded-circle" style="width: 50px; height: 50px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['NAME']); ?>">
                </td>
                <td class="align-middle">
                    <span class="fw-bold"><?php echo htmlspecialchars($item['NAME']); ?></span>
                </td>
                <td class="align-middle">$<?php echo number_format($item['price'], 2); ?></td>
                <td class="align-middle"><?php echo (int)$item['quantity']; ?></td>
                <td class="align-middle text-end" colspan="2">$<?php echo number_format($itemTotal, 2); ?></td>
            </tr>
        <?php endwhile; ?>
        <?php
        $itemStmt->close();
    }
    $orderStmt->close();
}
?>