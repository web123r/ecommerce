<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("database.php");
include("functions.php");

if (isset($_POST['add_name'], $_POST['add_email'], $_POST['add_password'])) {
    $name     = trim($_POST['add_name']);
    $email    = trim($_POST['add_email']);
    $password = password_hash($_POST['add_password'], PASSWORD_DEFAULT); 

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM `users` WHERE `email` = ? LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();

    if ($checkRes->num_rows > 0) {
        $errorMessage = "An account with that email already exists.";
    } else {
        $query = "INSERT INTO `users` (`name`, `email`, `password`, `isAdmin`) VALUES (?, ?, ?, 0)";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            $_SESSION['flash_alert'] = [
                'message' => 'Account created successfully! You can now log in.',
                'type'    => 'success'
            ];
            header("Location: login.php");
            exit();
        } else {
            $errorMessage = 'Error creating account: ' . $stmt->error;
        }
        $stmt->close();
    }
    $checkStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        
        <div class="card border-0 shadow-lg rounded-4 p-4 p-sm-5 bg-white">
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-person-plus-fill fs-2"></i>
            </div>
            <h3 class="fw-bold">Create Account</h3>
            <p class="text-muted small">Fill in the details below to get started</p>
          </div>

          <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger py-2 small" role="alert">
              <?php echo htmlspecialchars($errorMessage); ?>
            </div>
          <?php endif; ?>

          <form id="signupForm" novalidate method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="mb-3">
              <label for="name" class="form-label fw-semibold small">Full Name</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i class="bi bi-person"></i>
                </span>
                <input type="text" class="form-control bg-light border-start-0 ps-0" id="name" placeholder="John Doe" required minlength="2" name="add_name" value="<?php echo htmlspecialchars($_POST['add_name'] ?? ''); ?>">
                <div class="invalid-feedback">Please enter your full name.</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label fw-semibold small">Email Address</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i class="bi bi-envelope"></i>
                </span>
                <input type="email" class="form-control bg-light border-start-0 ps-0" id="email" placeholder="name@example.com" required name="add_email" value="<?php echo htmlspecialchars($_POST['add_email'] ?? ''); ?>">
                <div class="invalid-feedback">Please provide a valid email.</div>
              </div>
            </div>

            <div class="mb-4">
              <label for="password" class="form-label fw-semibold small">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i class="bi bi-lock"></i>
                </span>
                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password" placeholder="••••••••" required minlength="6" name="add_password">
                <div class="invalid-feedback">Password must be at least 6 characters.</div>
              </div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 rounded-3 fw-semibold">
              Sign Up
            </button>
          </form>

          <!-- Back to Login Navigation -->
          <div class="text-center mt-4 pt-2 border-top">
            <span class="small text-muted">Already have an account? </span>
            <a href="login.php" class="small text-decoration-none fw-semibold text-success">Log In</a>
          </div>
          
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>