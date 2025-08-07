<?php
// Connection to Database
require 'config.php';
require './vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// create new manager instance with desired driver
$manager = new ImageManager(new Driver());

// Start Session
session_start();

// Generate CSRF Token if its Empty
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['isSubmitted'])) {

  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: " . basename(__FILE__) . "?csrfError=1");
    exit;
  }

  try {
    $name = trim(filter_var($_POST['productName'], FILTER_SANITIZE_SPECIAL_CHARS));
    $image = null;
    $multipleImage = null;
    $multipleNames = [];

    $uploadDir = __DIR__ . "/uploads/products/";
    $allowedExtension = ['jpeg', 'jpg', 'png', 'gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB

    if (empty($name)) {
      header("Location: " . basename(__FILE__) . "?inputError=1");
      exit;
    }

    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }


    // Upload Single Image
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
      $extension = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
      $size = $_FILES['productImage']['size'];
      $tempName = $_FILES['productImage']['tmp_name'];

      if ($size > $maxFileSize) {
        header("Location: " . basename(__FILE__) . "?fileSize=1");
        exit;
      }

      if (!in_array($extension, $allowedExtension)) {
        header("Location: " . basename(__FILE__) . "?typeError=1");
        exit;
      }


      $newName = uniqid('pro_') . "_" . time() . "."  . $extension;
      $targetPath = $uploadDir . $newName;

      $manager->read($tempName)
        ->resize(800, 700)
        ->save($targetPath);



      $image = "uploads/products/" . $newName;
    }

    // Upload Multiple Image
    if (isset($_FILES['multipleImages']) && is_array($_FILES['multipleImages']['error']) && $_FILES['multipleImages']['error'][0] === UPLOAD_ERR_OK) {
      foreach ($_FILES['multipleImages']['name'] as $i => $origin) {

        if ($_FILES['multipleImages']['error'][$i] !== UPLOAD_ERR_OK) {
          continue;
        }
        $extension = strtolower(pathinfo($_FILES['multipleImages']['name'][$i], PATHINFO_EXTENSION));
        $size = $_FILES['multipleImages']['size'][$i];
        $tempName = $_FILES['multipleImages']['tmp_name'][$i];

        if ($size > $maxFileSize) {
          header("Location: " . basename(__FILE__) . "?fileSize=1");
          exit;
        }

        if (!in_array($extension, $allowedExtension)) {
          header("Location: " . basename(__FILE__) . "?typeError=1");
          exit;
        }


        $newMulti = uniqid('pro_') . '_' . time() . "_$i." . $extension;
        $targetPath = $uploadDir . $newMulti;

        $manager->read($tempName)
          ->cover(600, 600)
          ->save($targetPath);

        $multipleNames[] = "uploads/products/" . $newMulti;

        if (!empty($multipleNames)) {
          $multipleImage = implode(',', $multipleNames);
        }
      }
    }

    // Insert Data into Database
    $stmt = $conn->prepare("INSERT INTO product_tbl (product_name, product_image, multiple_image) VALUES (:pname, :pimage, :mpimage)");
    $stmt->bindParam(":pname", $name);
    $stmt->bindParam(":pimage", $image);
    $stmt->bindParam(":mpimage", $multipleImage);
    $result = $stmt->execute();

    if ($result) {
      header("Location: index.php?success");
      exit;
    }
  } catch (PDOException $e) {
    error_log("Add Product Failed in " . __FILE__ . "on" . __LINE__ . ": " . $e->getMessage());
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
  <style>
    .error {
      color: #842029;
      background: #f8d7da;
      border: 1px solid #f5c2c7;
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      border-radius: 0.25rem;
      font-size: 0.9rem;
      line-height: 1.4;
    }
  </style>
</head>

<body>
  <div class="form-container">
    <h2 class="form-title">Add Product</h2>
    <?php
    if (isset($_GET['csrfError']) && $_GET['csrfError'] == 1) {
      echo  '<div class="error"> ❗ Invalid request. Please refresh the page and try again. </div>';
    } elseif (isset($_GET['inputError']) && $_GET['inputError'] == 1) {
      echo  '<div class="error"> ❗ All Felids are Required. </div>';
    } elseif (isset($_GET['fileSize']) && $_GET['fileSize'] == 1) {
      echo  '<div class="error"> ❗ Max File Size is 2MB. </div>';
    } elseif (isset($_GET['typeError']) && $_GET['typeError'] == 1) {
      echo  '<div class="error"> ❗ File Type Not Allowed. </div>';
    } elseif (isset($_GET['fileUploadError']) && $_GET['fileUploadError'] == 1) {
      echo  '<div class="error"> ❗ File Upload Error. </div>';
    }
    ?>
    <form method="post" action="<?= basename(__FILE__) ?>" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
        <input type="file" id="multipleImages" name="multipleImages[]" accept="image/*" multiple class="file-input">
      </div>
      <button type="submit" name="isSubmitted" class="submit-btn">Add Product</button>
    </form>

  </div>
</body>

</html>