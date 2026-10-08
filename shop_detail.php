<?php 
include("database.php");
include("functions.php"); 
include("./includes/header.php");
include("./includes/navbar.php");

cart();

// Flash alerts
if (!empty($_SESSION['flash_alert'])) {
    renderAlert(
        $_SESSION['flash_alert']['message'], 
        $_SESSION['flash_alert']['type']
    );
    unset($_SESSION['flash_alert']);
}

// Current product ID resolution
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
    <h1 class="text-center text-white display-6">Shop Detail</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
        <li class="breadcrumb-item active text-white">Detail</li>
    </ol>
</div>
<!-- Single Page Header End -->

<!-- Single Product Start -->
<div class="container-fluid py-5 mt-5">
    <div class="container py-5">
        <div class="row g-4 mb-5">
            
            <!-- Main Product Details -->
            <?php singlePage($conn, $product_id); ?>

            <!-- Sidebar -->
            <div class="col-lg-4 col-xl-3">
                <div class="row g-4 fruite">
                    <div class="col-lg-12">
                        <!-- Sidebar Search -->
                        <form action="shop.php" method="get">
                            <div class="input-group w-100 mx-auto d-flex mb-4">
                                <input type="text" class="form-control p-3" placeholder="keywords" name="search">
                                <button class="btn btn-primary p-3" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </form>

                        <!-- Dynamic Categories -->
                        <div class="mb-4">
                            <h4>Categories</h4>
                            <ul class="list-unstyled fruite-categorie">
                                <?php getSidebarCategories($conn); ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Dynamic Featured Products -->
                    <div class="col-lg-12">
                        <h4 class="mb-4">Featured products</h4>
                        <?php getFeaturedProducts($conn); ?>
                        <div class="d-flex justify-content-center my-4">
                            <a href="shop.php" class="btn border border-secondary px-4 py-3 rounded-pill text-primary w-100">View More</a>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="position-relative">
                            <img src="img/banner-fruits.jpg" class="img-fluid w-100 rounded" alt="Fresh Fruits">
                            <div class="position-absolute" style="top: 50%; right: 10px; transform: translateY(-50%);">
                                <h3 class="text-secondary fw-bold">Fresh <br> Fruits <br> Banner</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Related Products -->
        <h1 class="fw-bold mb-0">Related products</h1>
        <div class="vesitable">
            <div class="owl-carousel vegetable-carousel justify-content-center">
                <?php getRelatedProducts($conn, $product_id); ?>
            </div>
        </div>
    </div>
</div>
<!-- Single Product End -->

<!-- Footer Start -->
<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5);">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="#">
                        <h1 class="text-primary mb-0">Fruitables</h1>
                        <p class="text-secondary mb-0">Fresh products</p>
                    </a>
                </div>
                <div class="col-lg-6">
                    <form action="subscribe.php" method="POST" class="position-relative mx-auto">
                        <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="email" name="email" placeholder="Your Email" required>
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
                    <p class="mb-4">Providing organic, fresh farm supplies delivered directly to your doorstep with guaranteed quality.</p>
                    <a href="about.php" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Shop Info</h4>
                    <a class="btn-link" href="about.php">About Us</a>
                    <a class="btn-link" href="contact.php">Contact Us</a>
                    <a class="btn-link" href="#">Privacy Policy</a>
                    <a class="btn-link" href="#">Terms & Condition</a>
                    <a class="btn-link" href="#">Return Policy</a>
                    <a class="btn-link" href="#">FAQs & Help</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Account</h4>
                    <a class="btn-link" href="my-account.php">My Account</a>
                    <a class="btn-link" href="cart.php">Shopping Cart</a>
                    <a class="btn-link" href="wishlist.php">Wishlist</a>
                    <a class="btn-link" href="orders.php">Order History</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Contact</h4>
                    <p>Address: 1429 Netus Rd, NY 48247</p>
                    <p>Email: Example@gmail.com</p>
                    <p>Phone: +0123 4567 8910</p>
                    <p>Payment Accepted</p>
                    <img src="img/payment.png" class="img-fluid" alt="Payments">
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