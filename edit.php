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

  $pid = (int) $_POST['pid'];
  $productName = filter_var(trim($_POST['productName']), FILTER_SANITIZE_SPECIAL_CHARS);
  $oldImage = $_POST['oldThumbnail'];
  $oldMultiImage = $_POST['oldMultiImage'];
  $image = null;
  $multipleImage = null;
  $multipleName = [];
  $allowedExtension = ['jpeg', 'jpg', 'png', 'gif'];
  $maxFileSize = 2 * 1024 * 1024; // 2 MB
  $uploadDir = __DIR__ . "/uploads/products/";

  if (empty($productName)) {
    header("Location: " . basename(__FILE__) . "?error=1");
    exit;
  }

  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // Upload Single Image
  if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {

    $ext = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
    $tmpName = $_FILES['productImage']['tmp_name'];
    $fileSize = $_FILES['productImage']['size'];

    if ($fileSize > $maxFileSize) {
      header("Location: " . basename(__FILE__) . "?sizeError=1");
      exit;
    }

    if (!in_array($ext, $allowedExtension)) {
      header("Location: " . basename(__FILE__) . "?extensionError=1");
      exit;
    }


    $newName = uniqid('pro_') . "_" . time() . "." . $ext;

    if (!move_uploaded_file($tmpName, $uploadDir . $newName)) {
      header("Location: " . basename(__FILE__) . "?uploadError=1");
      exit;
    }

    $image = "uploads/products/" . $newName;
  } else {
    $image = $oldImage;
  }

  // Upload Multiple Image
  if (isset($_FILES['multipleImages']) && is_array($_FILES['multipleImages']['error']) && $_FILES['multipleImages']['error'][0] === UPLOAD_ERR_OK) {
    foreach ($_FILES['multipleImages']['name'] as $i => $originName) {
      if ($_FILES['multipleImages']['error'][$i] !== UPLOAD_ERR_OK) continue;

      $extension = strtolower(pathinfo($originName, PATHINFO_EXTENSION));
      $tmpName = $_FILES['multipleImages']['tmp_name'][$i];
      $fileSize = $_FILES['multipleImages']['size'][$i];

      if ($fileSize > $maxFileSize) {
        header("Location: " . basename(__FILE__) . "?sizeError=1");
        exit;
      }

      if (!in_array($extension, $allowedExtension)) {
        header("Location: " . basename(__FILE__) . "?extensionError=1");
        exit;
      }


      $newMultiName = uniqid('pro_') . "_" . time() . "_$i" .  "." . $extension;

      if (!move_uploaded_file($tmpName, $uploadDir . $newMultiName)) {
        header("Location: " . basename(__FILE__) . "?uploadError=1");
        exit;
      }

      $multipleName[] = "uploads/products/" . $newMultiName;
    }
  }

  if (!empty($multipleName)) {
    $multipleImage = implode(', ', $multipleName);
  } else {
    $multipleImage = $oldMultiImage;
  }

  // Insert Record
  try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("UPDATE product_tbl
                                     SET product_name = :pname,
                                         product_image = :pimage,
                                         product_multi_image = :pmimage,
                                     WHERE id = :pid");
    $stmt->bindParam(":pid", $pid);
    $stmt->bindParam(":pname", $productName);
    $stmt->bindParam(":pimage", $image);
    $stmt->bindParam(":pmimage", $multipleImage);
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
    <h2 class="form-title">Edit Product</h2>
    <?php
    $id = (int) $_GET['id'];
    $sql = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
    $sql->bindParam(":id", $id);
    $sql->execute();
    $product = $sql->fetch();
    ?>

    <form method="POST" action="<?= htmlspecialchars(basename(__FILE__)) ?>" enctype="multipart/form-data"
      id="productForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="pid" value="<?= htmlspecialchars($product['id']) ?>">
      <input type="hidden" name="oldThumbnail" value="<?= htmlspecialchars($product['product_image']) ?>">
      <input type="hidden" name="oldMultiImage" value="<?= htmlspecialchars($product['product_multi_image']) ?>">
      <div class="form-group">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName"
          value="<?= htmlspecialchars($product['product_name']) ?>">
      </div>

      <div class="form-group">
        <label for="productImage">Product Image</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" class="file-input">
        <img width="100" src="<?= htmlspecialchars($product['product_image']) ?>" alt="">
      </div>

      <div class="form-group">
        <label for="multipleImages">Multiple Images</label>
        <input type="file" id="multipleImages" name="multipleImages[]" accept="image/*" multiple
          class="file-input">
        <?php
        $multipleImage = explode(',', $product['product_multi_image']);
        foreach ($multipleImage as $img):
        ?>
          <img src="<?= htmlspecialchars($img) ?>" width="100" alt="">
        <?php endforeach; ?>
      </div>

      <button type="submit" name="isSubmitted" class="submit-btn">Update Product</button>
    </form>
  </div>
</body>

</html>
<?php $conn = null; ?>