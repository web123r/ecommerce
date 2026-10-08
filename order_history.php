<?php
require_once("database.php");
require_once("functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logout(); 

$userId = (int)($_SESSION['ID'] ?? 0);
if ($userId === 0) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
?>

<div id="spinner" class=" w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-grow text-primary" role="status"></div>
</div>

<?php include 'includes/navbar.php'; ?>

<!-- Single Page Header -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">My Orders</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active text-white">Order History</li>
    </ol>
</div>

<!-- Orders Table -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Name</th>
                        <th scope="col">Price</th>
                        <th scope="col">Quantity</th>
                        <th scope="col" class="text-end" colspan="2">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                
                     <?php displayUserOrderHistory($conn, $userId); ?> 
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>

<?php include 'includes/footer.php'; ?>