<?php
require 'config.php';
session_start();
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isSubmitted'])) {


  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: " . basename(__FILE__)) . "?csrfTokenError=1";
    exit;
  }



  $productName = filter_var(trim($_POST['productName']), FILTER_SANITIZE_SPECIAL_CHARS);
  $image = null;
  $multiImage = null;
  $allowedType = ['jpeg', 'jpg', 'png', 'gif', 'pdf'];
  $maxFileSize = 2 * 1024 * 1024; // 2 MB
  $uploadDir =  __DIR__ .  '/uploads/products/';

  if (!is_dir($uploadDir)) {
    mkdir($uploadDir,  0755, true);
  }



  if (empty($productName)) {
    header("Location: " . basename(__FILE__)) . "?FiledError=1";
    exit;
  }

  if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {

    $ext = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
    $tmpName = $_FILES['productImage']['tmp_name'];
    $size =    $_FILES['productImage']['size'];

    if (!in_array($ext, $allowedType)) {
      header("Location: " . basename(__FILE__)) . "?typeError=1";
      exit;
    }

    if ($size > $maxFileSize) {
      header("Location: " . basename(__FILE__)) . "?szeError=1";
      exit;
    }

    $newName = uniqid('pro_') . time() . "." . $ext;

    if (!move_uploaded_file($tmpName, $newName . $uploadDir)) {
      header("Location: " . basename(__FILE__)) . "?fileUploadError=1";
      exit;
    }
    $image = 'upload/products/' . $newName;
  }

  try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("INSERT INTO product_tbl (product_name, product_image, product_multi_image) VALUES (:pname, :pimage, :pmimage)");
    $stmt->bindParam(":pname", $productName);
    $stmt->bindParam(":pimage", $newName);
    $stmt->bindParam(":pmimage", $productMultiImage);
    $result = $stmt->execute();

    if ($result) {
      $conn->commit();
      header("Location: index.php?success=1");
      exit;
    }
  } catch (PDOException $e) {
    if ($conn->inTransaction()) {
      $conn->rollBack();
    }
    error_log("Product insert error in " . __FILE__ . "on" . __LINE__ . $e->getMessage());
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


    <form method="POST" action="<?= htmlspecialchars(basename(__FILE__)) ?>" id="productForm">
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
        3
      </div>

      <button type="submit" name="isSubmitted" class="submit-btn">Add Product</button>
    </form>
  </div>
</body>

</html>
<?php $conn = null; ?>