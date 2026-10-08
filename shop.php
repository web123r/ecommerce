<?php
include("database.php");
include("functions.php");
include("./includes/header.php");
include("./includes/navbar.php");

cart();

// Alert Handling
if (!empty($_SESSION['flash_alert'])) {
    renderAlert(
        $_SESSION['flash_alert']['message'], 
        $_SESSION['flash_alert']['type']
    );
    unset($_SESSION['flash_alert']);
}

// ---------------------------
// Filtering & Pagination Logic
// ---------------------------
$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$maxPrice = isset($_GET['rangeInput']) && (float)$_GET['rangeInput'] > 0 ? (float)$_GET['rangeInput'] : 0;
$sort = $_GET['sort'] ?? 'default';

// Pagination parameters
$limit = 9;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base conditions & bindings
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "items.NAME LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= 's';
}

if ($type !== '') {
    $conditions[] = "items.TYPE = ?";
    $params[] = $type;
    $types .= 's';
}

if ($maxPrice > 0) {
    $conditions[] = "items.PRICE <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Sorting resolution
$orderBy = 'ORDER BY items.id DESC';
switch ($sort) {
    case 'price_asc':
        $orderBy = 'ORDER BY items.PRICE ASC';
        break;
    case 'price_desc':
        $orderBy = 'ORDER BY items.PRICE DESC';
        break;
    case 'rating':
        $orderBy = 'ORDER BY items.RATINGS DESC';
        break;
    case 'name':
        $orderBy = 'ORDER BY items.NAME ASC';
        break;
}

// Count total matching items for pagination
$countSql = "SELECT COUNT(*) as total FROM items $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$countStmt->close();
$totalPages = ceil($totalItems / $limit);

// Fetch Paginated Items
$itemsSql = "SELECT items.*, images.path 
             FROM items 
             LEFT JOIN images ON items.id = images.item_id 
             $whereClause 
             $orderBy 
             LIMIT ? OFFSET ?";

$itemsStmt = $conn->prepare($itemsSql);
$currentTypes = $types . 'ii';
$currentParams = array_merge($params, [$limit, $offset]);
$itemsStmt->bind_param($currentTypes, ...$currentParams);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

// Helper to preserve query strings across pagination links
function getPaginationUrl($pageNumber) {
    $params = $_GET;
    $params['page'] = $pageNumber;
    return 'shop.php?' . http_build_query($params);
}
?>

<!-- Spinner Start -->
<div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-grow text-primary" role="status"></div>
</div>
<!-- Spinner End -->

<!-- Modal Search Start -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex align-items-center">
                <div class="input-group w-75 mx-auto d-flex">
                    <form action="shop.php" method="get" class="w-100 d-flex">
                        <input type="search" name="search" class="form-control p-3" placeholder="keywords" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="input-group-text p-3 border-0"><i class="fa fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Search End -->

<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Shop</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active text-white">Shop</li>
    </ol>
</div>
<!-- Single Page Header End -->

<!-- Fruits Shop Start-->
<div class="container-fluid fruite py-5">
    <div class="container py-5">
        <h1 class="mb-4">Fresh shop</h1>
        <div class="row g-4">
            <div class="col-lg-12">
                
                <!-- Top Filters Form -->
                <form method="get" action="shop.php" id="filterForm" class="row g-4 mb-4">
                    <?php if ($type !== ''): ?>
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <?php endif; ?>

                    <div class="col-xl-3">
                        <div class="input-group w-100 mx-auto d-flex">
                            <input type="search" name="search" class="form-control p-3" placeholder="keywords" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="input-group-text p-3 border-0" type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-6"></div>
                    <div class="col-xl-3">
                        <div class="bg-light ps-3 py-3 rounded d-flex justify-content-between">
                            <label for="sort" class="my-auto">Sorting:</label>
                            <select id="sort" name="sort" class="border-0 form-select-sm bg-light me-3" onchange="document.getElementById('filterForm').submit()">
                                <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Default</option>
                                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Popularity (Ratings)</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Product Name</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sidebar and Product Grid -->
                    <div class="row g-4 mt-2">
                        
                        <!-- Sidebar -->
                        <div class="col-lg-3">
                            <div class="row g-4">
                                <!-- Dynamic Categories -->
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <h4>Categories</h4>
                                        <ul class="list-unstyled fruite-categorie">
                                            <li>
                                                <div class="d-flex justify-content-between fruite-name">
                                                    <a href="shop.php"><i class="fas fa-apple-alt me-2"></i>All Products</a>
                                                    <span>(<?php echo $totalItems; ?>)</span>
                                                </div>
                                            </li>
                                            <?php getSidebarCategories($conn); ?>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Dynamic Price Filter -->
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <h4 class="mb-2">Max Price: $<span id="rangeValue"><?php echo $maxPrice > 0 ? number_format($maxPrice, 2) : '500'; ?></span></h4>
                                        <input type="range" class="form-range w-100" id="rangeInput" name="rangeInput" min="0" max="500" value="<?php echo $maxPrice > 0 ? $maxPrice : '500'; ?>" oninput="document.getElementById('rangeValue').innerText = this.value">
                                        <button type="submit" class="btn btn-primary rounded-pill btn-sm w-100 mt-2 text-white">Apply Filter</button>
                                    </div>
                                </div>

                                <!-- Dynamic Featured Products -->
                                <div class="col-lg-12">
                                    <h4 class="mb-3">Featured products</h4>
                                    <?php getFeaturedProducts($conn, 3); ?>
                                    <div class="d-flex justify-content-center my-4">
                                        <a href="shop.php" class="btn border border-secondary px-4 py-3 rounded-pill text-primary w-100">View All</a>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="position-relative">
                                        <img src="img/banner-fruits.jpg" class="img-fluid w-100 rounded" alt="Promo">
                                        <div class="position-absolute" style="top: 50%; right: 10px; transform: translateY(-50%);">
                                            <h3 class="text-secondary fw-bold">Fresh <br> Organic <br> Deals</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product List -->
                        <div class="col-lg-9">
                            <div class="row g-4 justify-content-start">
                                <?php if ($itemsResult && $itemsResult->num_rows > 0): ?>
                                    <?php while ($row = $itemsResult->fetch_assoc()): ?>
                                        <?php 
                                            $item_id = (int)$row['id'];
                                            $item_name = htmlspecialchars($row['NAME']);
                                            $item_type = htmlspecialchars(ucfirst($row['TYPE']));
                                            $item_price = (float)$row['PRICE'];
                                            $item_desc = htmlspecialchars(substr($row['DESCRIPTION'] ?? '', 0, 80));
                                            $image_path = !empty($row['path']) ? htmlspecialchars($row['path']) : 'img/vegetable-item-3.png';
                                        ?>
                                        <div class="col-md-6 col-lg-6 col-xl-4">
                                            <div class="rounded position-relative fruite-item border border-secondary">
                                                <div class="fruite-img">
                                                    <a href="shop-detail.php?id=<?php echo $item_id; ?>">
                                                        <img src="<?php echo $image_path; ?>" class="img-fluid w-100 rounded-top" style="height: 220px; object-fit: cover;" alt="<?php echo $item_name; ?>">
                                                    </a>
                                                </div>
                                                <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">
                                                    <?php echo $item_type; ?>
                                                </div>
                                                <div class="p-4 rounded-bottom">
                                                    <h4><a href="shop-detail.php?id=<?php echo $item_id; ?>" class="text-dark"><?php echo $item_name; ?></a></h4>
                                                    <p><?php echo $item_desc; ?>...</p>
                                                    <div class="d-flex justify-content-between flex-lg-wrap align-items-center">
                                                        <p class="text-dark fs-5 fw-bold mb-0">$<?php echo number_format($item_price, 2); ?> / kg</p>
                                                        <button type="submit" form="addForm_<?php echo $item_id; ?>" class="btn border border-secondary rounded-pill px-3 text-primary">
                                                            <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to cart
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 py-5 text-center">
                                        <h3>No products match your selected criteria.</h3>
                                        <a href="shop.php" class="btn btn-primary rounded-pill text-white mt-3">Reset Filters</a>
                                    </div>
                                <?php endif; ?>

                                <!-- Pagination Links -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="col-12">
                                        <div class="pagination d-flex justify-content-center mt-5">
                                            <?php if ($page > 1): ?>
                                                <a href="<?php echo getPaginationUrl($page - 1); ?>" class="rounded">&laquo;</a>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <a href="<?php echo getPaginationUrl($i); ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?> rounded">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>

                                            <?php if ($page < $totalPages): ?>
                                                <a href="<?php echo getPaginationUrl($page + 1); ?>" class="rounded">&raquo;</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </form>

                <!-- Independent Forms for Cart Actions -->
                <?php 
                if ($itemsResult && $itemsResult->num_rows > 0) {
                    $itemsResult->data_seek(0);
                    while ($row = $itemsResult->fetch_assoc()) {
                        $pId = (int)$row['id'];
                        echo '<form id="addForm_' . $pId . '" method="post" action="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" style="display:none;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="' . $pId . '">
                                <input type="hidden" name="quantity" value="1">
                              </form>';
                    }
                }
                $itemsStmt->close();
                ?>

            </div>
        </div>
    </div>
</div>
<!-- Fruits Shop End-->

<!-- Footer Start -->
<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5);">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="index.php">
                        <h1 class="text-primary mb-0">Fruitables</h1>
                        <p class="text-secondary mb-0">Fresh products</p>
                    </a>
                </div>
                <div class="col-lg-6">
                    <form action="#" class="position-relative mx-auto">
                        <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="email" placeholder="Your Email">
                        <button type="submit" class="btn btn-primary border-0 border-secondary py-3 px-4 position-absolute rounded-pill text-white" style="top: 0; right: 0;">Subscribe Now</button>
                    </form>
                </div>
                <div class="col-lg-3">
                    <div class="d-flex justify-content-end pt-3">
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-secondary btn-md-square rounded-circle" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-5">
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Why People Like us!</h4>
                    <p class="mb-4">Providing top quality farm goods, fresh vegetables, and organic fruits straight to your table.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Shop Info</h4>
                    <a class="btn-link" href="#">About Us</a>
                    <a class="btn-link" href="#">Contact Us</a>
                    <a class="btn-link" href="#">Privacy Policy</a>
                    <a class="btn-link" href="#">Terms & Condition</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Account</h4>
                    <a class="btn-link" href="#">My Account</a>
                    <a class="btn-link" href="cart.php">Shopping Cart</a>
                    <a class="btn-link" href="#">Wishlist</a>
                    <a class="btn-link" href="#">Order History</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Contact</h4>
                    <p>Address: 1429 Netus Rd, NY 48247</p>
                    <p>Email: Example@gmail.com</p>
                    <p>Phone: +0123 4567 8910</p>
                    <p>Payment Accepted</p>
                    <img src="img/payment.png" class="img-fluid" alt="Payment">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Footer End -->

<!-- Copyright Start -->
<div class="container-fluid copyright bg-dark py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-light">&copy; <?php echo date("Y"); ?> <a href="#">Fruitables</a>, All right reserved.</span>
            </div>
        </div>
    </div>
</div>
<!-- Copyright End -->

<!-- Back to Top -->
<a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>