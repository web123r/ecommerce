<?php
// checkout.php
require_once("database.php");
require_once("functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logout(); // Ensures the user is logged in
cart();

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $orderId = processOrder($conn, 'order_items');

    if ($orderId) {
        header("Location: index.php");
        exit;
    }
}

// Display and clear flash alerts
if (!empty($_SESSION['flash_alert'])) {
    renderAlert(
        $_SESSION['flash_alert']['message'], 
        $_SESSION['flash_alert']['type']
    );
    unset($_SESSION['flash_alert']);
}

include 'includes/header.php';
?>

<!-- Spinner Start -->
<div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-grow text-primary" role="status"></div>
</div>
<!-- Spinner End -->

<?php include 'includes/navbar.php'; ?>

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
                        <input type="search" name="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                        <button type="submit" id="search-icon-1" class="input-group-text p-3 border-0"><i class="fa fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Search End -->

<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Checkout</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
        <li class="breadcrumb-item active text-white">Checkout</li>
    </ol>
</div>
<!-- Single Page Header End -->

<!-- Checkout Page Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <h1 class="mb-4">Billing Details</h1>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="checkoutForm">
            <div class="row g-5">
                <div class="col-md-12 col-lg-6 col-xl-7">
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <div class="form-item w-100">
                                <label class="form-label my-3">First Name<sup>*</sup></label>
                                <input type="text" name="first_name" class="form-control"  >
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <div class="form-item w-100">
                                <label class="form-label my-3">Last Name<sup>*</sup></label>
                                <input type="text" name="last_name" class="form-control"  >
                            </div>
                        </div>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Company Name (Optional)</label>
                        <input type="text" name="company_name" class="form-control">
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Address <sup>*</sup></label>
                        <input type="text" name="address" class="form-control" placeholder="House Number and Street Name"  >
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Town / City<sup>*</sup></label>
                        <input type="text" name="city" class="form-control"  >
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Country<sup>*</sup></label>
                        <input type="text" name="country" class="form-control" value="Nigeria"  >
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Postcode / ZIP</label>
                        <input type="text" name="postcode" class="form-control">
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Mobile Phone<sup>*</sup></label>
                        <input type="tel" name="phone" class="form-control"  >
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Email Address<sup>*</sup></label>
                        <input type="email" name="email" class="form-control"  >
                    </div>
                    <div class="form-item mt-3">
                        <textarea name="order_notes" class="form-control" spellcheck="false" cols="30" rows="5" placeholder="Order Notes (Optional)"></textarea>
                    </div>
                </div>

                <!-- Order Summary Table and Payment Options -->
                <div class="col-md-12 col-lg-6 col-xl-5">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Products</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $cart = $_SESSION['cart'] ?? [];
                                $subtotal = 0.00;
                                $shipping = 15.00;

                                $cartIds = array_values(array_filter(array_map('intval', array_keys($cart)), fn($id) => $id > 0));

                                if (!empty($cartIds)):
                                    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
                                    $types = str_repeat('i', count($cartIds));

                                    $summarySql = "SELECT items.id, items.NAME, items.PRICE, images.path 
                                                   FROM items 
                                                   LEFT JOIN images ON items.id = images.item_id 
                                                   WHERE items.id IN ($placeholders)";
                                    $stmt = $conn->prepare($summarySql);
                                    $stmt->bind_param($types, ...$cartIds);
                                    $stmt->execute();
                                    $cartResult = $stmt->get_result();

                                    while ($item = $cartResult->fetch_assoc()):
                                        $itemId = (int)$item['id'];
                                        $qty = (int)($cart[$itemId] ?? 1);
                                        $price = (float)$item['PRICE'];
                                        $rowTotal = $price * $qty;
                                        $subtotal += $rowTotal;
                                        $imagePath = !empty($item['path']) ? htmlspecialchars($item['path']) : 'img/vegetable-item-2.jpg';
                                ?>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center mt-2">
                                                    <img src="<?php echo $imagePath; ?>" class="img-fluid rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['NAME']); ?>">
                                                </div>
                                            </th>
                                            <td class="py-4"><?php echo htmlspecialchars($item['NAME']); ?></td>
                                            <td class="py-4">$<?php echo number_format($price, 2); ?></td>
                                            <td class="py-4"><?php echo $qty; ?></td>
                                            <td class="py-4">$<?php echo number_format($rowTotal, 2); ?></td>
                                        </tr>
                                <?php 
                                    endwhile;
                                    $stmt->close();
                                else:
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Your cart is currently empty.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php $grandTotal = $subtotal > 0 ? ($subtotal + $shipping) : 0.00; ?>

                                <tr>
                                    <th scope="row"></th>
                                    <td class="py-3"></td>
                                    <td class="py-3"></td>
                                    <td class="py-3">
                                        <p class="mb-0 text-dark">Subtotal</p>
                                    </td>
                                    <td class="py-3">
                                        <div class="py-1 border-bottom border-top">
                                            <p class="mb-0 text-dark fw-bold">$<?php echo number_format($subtotal, 2); ?></p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"></th>
                                    <td class="py-3">
                                        <p class="mb-0 text-dark">Shipping</p>
                                    </td>
                                    <td colspan="3" class="py-3">
                                        <div class="form-check text-start">
                                            <input type="radio" class="form-check-input bg-primary border-0" id="Shipping-1" name="shipping_method" value="15.00" checked>
                                            <label class="form-check-label" for="Shipping-1">Flat Rate: $<?php echo number_format($shipping, 2); ?></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"></th>
                                    <td class="py-3">
                                        <p class="mb-0 text-dark text-uppercase fw-bold">TOTAL</p>
                                    </td>
                                    <td class="py-3"></td>
                                    <td class="py-3"></td>
                                    <td class="py-3">
                                        <div class="py-1 border-bottom border-top">
                                            <p class="mb-0 text-dark fw-bold">$<?php echo number_format($grandTotal, 2); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payment Options -->
                    <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                        <div class="col-12">
                            <div class="form-check text-start my-2">
                                <input type="radio" class="form-check-input bg-primary border-0" id="Payment-Transfer" name="payment_method" value="bank_transfer" checked>
                                <label class="form-check-label" for="Payment-Transfer">Direct Bank Transfer</label>
                            </div>
                            <p class="text-start text-muted small">Make payment directly into our bank account. Please use your Order ID as the reference.</p>
                        </div>
                    </div>
                    <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                        <div class="col-12">
                            <div class="form-check text-start my-2">
                                <input type="radio" class="form-check-input bg-primary border-0" id="Payment-COD" name="payment_method" value="cod">
                                <label class="form-check-label" for="Payment-COD">Cash On Delivery</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                        <div class="col-12">
                            <div class="form-check text-start my-2">
                                <input type="radio" class="form-check-input bg-primary border-0" id="Payment-Paypal" name="payment_method" value="paypal">
                                <label class="form-check-label" for="Payment-Paypal">Paypal / Online Payment</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                        <button type="submit" name="place_order" class="btn btn-primary border-secondary py-3 px-4 text-uppercase w-100 text-white" <?php echo empty($cartIds) ? 'disabled' : ''; ?>>
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Checkout Page End -->

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
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-secondary btn-md-square rounded-circle" href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-5">
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Why People Like us!</h4>
                    <p class="mb-4">Providing verified organic greens and quality farm produce straight from vetted local sources to your door.</p>
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
                    <img src="img/payment.png" class="img-fluid" alt="Payment Methods">
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

<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>
</html>