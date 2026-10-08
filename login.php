<?php
// 1. Session MUST start at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");
require_once("functions.php");

// If already logged in, send them straight home
if (!empty($_SESSION["ID"])) {
    header("Location: index.php");
    exit();
}

$errorMessage = "";

// 2. Process Login POST Request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login_email"])) {
    $email = trim($_POST["login_email"]);
    $password = $_POST["login_password"] ?? '';

    // Query user by email
    $query = "SELECT id, name, password, isAdmin FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password (supports hashed password; falls back to plain text if still transitioning)
        $passwordMatches = password_verify($password, $user["password"]) || ($password === $user["password"]);

        if ($passwordMatches) {
            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION["ID"] = $user["id"];
            $_SESSION["Name"] = $user["name"];
            $_SESSION["isAdmin"] = (int)$user["isAdmin"];

            // Preserve existing cart if they already added items as a guest
            if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
                $_SESSION["cart"] = [];
            }

            $_SESSION['flash_alert'] = [
                'message' => 'Welcome back, ' . htmlspecialchars($user["name"]) . '!',
                'type' => 'success'
            ];

            // Server-side redirect
            header("Location: index.php");
            exit();
        } else {
            $errorMessage = "Invalid email or password.";
        }
    } else {
        $errorMessage = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center min-vh-100">

  <?php
  // 3. Render flash alerts here so HTML and styles are already loaded
  if (!empty($_SESSION['flash_alert'])) {
      renderAlert(
          $_SESSION['flash_alert']['message'], 
          $_SESSION['flash_alert']['type']
      );
      unset($_SESSION['flash_alert']);
  }
  ?>

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-5">

        <div class="card border-0 shadow-lg rounded-4 p-4 p-sm-5 bg-white">
          <div class="text-center mb-4">
            <div
              class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3"
              style="width: 60px; height: 60px;">
              <i class="bi bi-person-fill fs-2"></i>
            </div>
            <h3 class="fw-bold">Welcome back</h3>
            <p class="text-muted small">Please enter your credentials to continue</p>
          </div>

          <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger py-2 small" role="alert">
              <?php echo htmlspecialchars($errorMessage); ?>
            </div>
          <?php endif; ?>

            <form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
              <div class="mb-3">
                <label for="email" class="form-label fw-semibold small">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-envelope"></i>
                  </span>
                  <input type="email" class="form-control bg-light border-start-0 ps-0" id="email"
                    placeholder="name@example.com" required name="login_email" 
                    value="<?php echo htmlspecialchars($_POST['login_email'] ?? ''); ?>">
                </div>
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <label for="password" class="form-label fw-semibold small mb-0">Password</label>
                  <a href="#" class="small text-decoration-none">Forgot password?</a>
                </div>
                <div class="input-group mt-1">
                  <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-lock"></i>
                  </span>
                  <input type="password" class="form-control bg-light border-start-0 ps-0" placeholder="••••••••" required
                    name="login_password">
                </div>
              </div>

              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="rememberMe">
                <label class="form-check-label small text-muted" for="rememberMe">
                  Remember this device
                </label>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold">
                Sign In
              </button>
            </form> 

          <div class="text-center mt-4">
            <span class="small text-muted">Don't have an account? </span>
            <a href="adduser.php" class="small text-decoration-none fw-semibold">Create account</a>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>