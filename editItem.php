<?php
include("database.php");
if (!empty($_SESSION['flash_alert'])) {
    renderAlert(
        $_SESSION['flash_alert']['message'], 
        $_SESSION['flash_alert']['type']
    );
    unset($_SESSION['flash_alert']);
}

function updateItems($conn)
{
    if (isset($_POST["id"])) {
        try {
            $id = (int)$_POST["id"];
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $type = $_POST['type'] ?? '';
            $price = (float)($_POST['price'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);

            // 1. Update basic item data (trailing comma removed)
            $query = "UPDATE `items` SET `NAME` = ?, `DESCRIPTION` = ?, `TYPE` = ?, `PRICE` = ?, `RATINGS` = ? WHERE `id` = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssidi", $name, $description, $type, $price, $rating, $id);
            $stmt->execute();
            $stmt->close();

            // 2. Process image only if a new one was uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $filename = $_FILES["image"]["name"];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $allowedExts = ["jpg", "png", "jpeg", "gif"];

                if (!in_array($ext, $allowedExts)) {
                    die("Invalid file extension.");
                }

                $tempname = $_FILES["image"]["tmp_name"];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tempname);
                finfo_close($finfo);

                $allowedMimes = ["image/jpeg", "image/png", "image/gif"];
                if (!in_array($mime, $allowedMimes)) {
                    die("Invalid file format.");
                }

                $uniqueFilename = uniqid('img_', true) . '.' . $ext;
                $destination = "uploads/" . $uniqueFilename;

                if (!move_uploaded_file($tempname, $destination)) {
                    die("Failed to move uploaded file.");
                }

                // Update the matching image row using item_id in WHERE clause
                $image_query = "UPDATE `images` SET `image` = ?, `path` = ? WHERE `item_id` = ?";
                $image_stmt = $conn->prepare($image_query);
                $image_stmt->bind_param("ssi", $uniqueFilename, $destination, $id);
                $image_stmt->execute();
                $image_stmt->close();
            }

            header("Location: admin.php");
            exit();

        } catch (Exception $e) {
            echo "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Call the function on form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    updateItems($conn);
}

// Fetch existing data for form population
$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
$name = '';
$description = '';
$price = '';
$type = '';
$ratings = 1;

if ($id > 0) {
    $query = "SELECT items.*, images.path 
              FROM items 
              LEFT JOIN images ON items.id = images.item_id 
              WHERE items.id = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row["NAME"];
            $description = $row["DESCRIPTION"];
            $price = $row["PRICE"];
            $type = $row["TYPE"];
            $ratings = (int)$row["RATINGS"];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Update Product</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/mdb.min.css" />
  <style>
    .range-container { width: 100%; font-family: Arial, sans-serif; }
    .range-container label { display: block; margin-bottom: 10px; font-weight: 600; }
    .range-row { display: flex; align-items: center; gap: 15px; }
    .range-row input[type="range"] { width: 100%; cursor: pointer; }
    #quantityValue { min-width: 25px; font-size: 18px; font-weight: bold; }
  </style>
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; padding: 2rem 0;">
      <div class="text-center">
        <form style="width: 26rem;" enctype="multipart/form-data" method="post" action="">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

          <div data-mdb-input-init class="form-outline mb-4">
            <input type="text" id="name" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>"/>
            <label class="form-label" for="name">Name</label>
          </div>

          <div data-mdb-input-init class="form-outline mb-4">
            <textarea class="form-control" id="description" rows="4" name="description"><?php echo htmlspecialchars($description); ?></textarea>
            <label class="form-label" for="description">Description</label>
          </div>

          <div data-mdb-input-init class="form-outline mb-4">
            <input type="number" step="0.01" id="price" class="form-control" name="price" value="<?php echo htmlspecialchars($price); ?>" />
            <label class="form-label" for="price">Price in Dollars</label>
          </div>

          <div class="mb-4 text-start">
            <label class="form-label" for="type">Category</label>
            <select name="type" id="type" class="form-select">
              <option value="fruit" <?php echo ($type === "fruit") ? "selected" : ""; ?>>Fruits</option>
              <option value="vegetable" <?php echo ($type === "vegetable") ? "selected" : ""; ?>>Vegetables</option>
            </select>
          </div>

          <div class="range-container mb-4 text-start">
            <label for="quantity">Rating: <span id="quantityValue"><?php echo $ratings; ?></span></label>
            <div class="range-row">
              <input type="range" id="quantity" min="1" max="5" value="<?php echo $ratings; ?>" name="rating">
            </div>
          </div>

          <div class="mb-4 text-start">
            <label class="form-label" for="image">Replace Image</label>
            <input type="file" id="image" class="form-control" name="image" />
          </div>

          <button data-mdb-ripple-init type="submit" name="submit" class="btn btn-primary btn-block mb-4">Update</button>
        </form>
      </div>
    </div>
  </div>

  <script type="text/javascript" src="js/mdb.umd.min.js"></script>
  <script>
    const slider = document.getElementById("quantity");
    const value = document.getElementById("quantityValue");
    slider.addEventListener("input", function () {
        value.textContent = slider.value;
    });
  </script>
</body>
</html>