<?php
session_start();
require 'file.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$file = new File();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isSubmitted'])) {
  // Verify CSRF token
  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: " . basename(__FILE__) . "?csrfTokenError=1");  // FIXED: Added '?' before parameter
    exit;
  }

  try {
    // Sanitize and validate input
    $name = filter_var(trim($_POST['productName']), FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($name)) {
      $errors[] = "Product name is required";
    }

    $table = 'product_tbl';
    $redirect = 'add.php';

    // Initialize image variables
    $image = null;
    $multiImage = null;

    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] !== UPLOAD_ERR_NO_FILE) {
      $image = $file->SingleImage($table, 'productImage', $redirect);
      if (!$image && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $errors[] = $file->getLastError() ?: "Failed to upload product image";
      }
    }

    if (isset($_FILES['multipleImages']) && $_FILES['multipleImages']['error'][0] !== UPLOAD_ERR_NO_FILE) {
      $multiImage = $file->MultipleImage($table, 'multipleImages', $redirect);
      if (!$multiImage && !empty($_FILES['multipleImages']['name'][0])) {
        $errors[] = $file->getLastError() ?: "Failed to upload multiple images";
      }
    }

    // If no errors, proceed with database insertion
    if (empty($errors) && !empty($name)) {
      $file->pdo->beginTransaction();

      $stmt = $file->pdo->prepare("INSERT INTO `$table` (product_name, product_image, product_multi_image) VALUES (:pname, :pimage, :pmimage)");
      $stmt->bindParam(":pname", $name);
      $stmt->bindParam(":pimage", $image);
      $stmt->bindParam(":pmimage", $multiImage);
      $result = $stmt->execute();

      if ($result) {
        $file->pdo->commit();
        $success = true;
        // Redirect to index page after successful submission
        header("Location: index.php?success=1");
        exit;
      } else {
        $file->pdo->rollBack();
        $errors[] = "Failed to save product to database";
      }
    }
  } catch (PDOException $e) {
    if ($file->pdo->inTransaction()) {
      $file->pdo->rollBack();
    }
    $errors[] = "Database error occurred";
    error_log("Error in insert " . __FILE__ . " on line " . __LINE__ . ": " . $e->getMessage());  // FIXED: Added spaces
  } catch (Exception $e) {
    $errors[] = "An error occurred: " . $e->getMessage();
    error_log("Error in " . __FILE__ . " on line " . __LINE__ . ": " . $e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Form</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<style>
  .error {
    color: #842029;
    background-color: #f8d7da;
    border: 1px solid #f5c2c7;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 0.25rem;
    font-size: 0.9rem;
    line-height: 1.4;
  }
</style>

<body>
  <div class="form-container">
    <h2 class="form-title">Add Product</h2>

    <form method="POST" action="<?= htmlspecialchars(basename(__FILE__)) ?>" enctype="multipart/form-data"
      id="productForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="form-group">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName">
      </div>

      <div class="form-group">
        <label for="productImage">Product Image</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" class="file-input">
      </div>

      <div class="form-group">
        <label for="multipleImages">Multiple Images</label>
        <input type="file" id="multipleImages" name="multipleImages[]" accept="image/*" multiple
          class="file-input">
      </div>

      <button type="submit" name="isSubmitted" class="submit-btn">Add Product</button>
    </form>
  </div>
</body>

</html>