<?php
require 'config.php';
session_start();

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submit'])) {
  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: " . basename(__FILE__) . "?csrfError=1");
    exit;
  }

  try {

    $pid = htmlspecialchars($_POST['pid']);
    $name = filter_var($_POST['productName'], FILTER_SANITIZE_SPECIAL_CHARS);
    $oldSingleImage = $_POST['oldSingleImage'];
    $oldMultipleImage = $_POST['oldMultipleImage'];

    $uploadDir = __DIR__ . "/uploads/products/";
    $allowedExt = ['jpeg', 'jpg', 'png', 'gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB

    // Create Uploads Directory
    if (!is_dir($uploadDir)) {
      if (!mkdir($uploadDir, 0755, true)) {
        header("Location: " . basename(__FILE__) . "?dirError=1");
        exit;
      }
    }

    if (empty($name)) {
      header("Location: " . basename(__FILE__) . "?error=1");
      exit;
    }

    // Single Image
    $image =  null;
    $multipleImage = null;;
    $multiNames = []; // Initialize the array

    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
      $ext      = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
      $fileSize = $_FILES['productImage']['size'];
      $tmpFile  = $_FILES['productImage']['tmp_name'];

      if ($fileSize > $maxFileSize) {
        header("Location: " . basename(__FILE__) . "?fileSize=1");
        exit;
      }

      if (!in_array($ext, $allowedExt)) {
        header("Location: " . basename(__FILE__) . "?extension=1");
        exit;
      }

      $newName = uniqid('pro_') . '_' . time() . '.' . $ext;

      if (!move_uploaded_file($tmpFile, $uploadDir . $newName)) {
        header("Location: " . basename(__FILE__) . "?fileUpload=1");
        exit;
      }

      $image = 'uploads/products/' . $newName; // Fixed: removed leading slash for consistency
    } else {
      $image = $oldSingleImage;
    }

    // Multiple Images handling
    if (isset($_FILES['multipleImages']) && is_array($_FILES['multipleImages']['error']) && $_FILES['multipleImages']['error'][0] === UPLOAD_ERR_OK) {

      foreach ($_FILES['multipleImages']['name'] as $i => $origName) {
        // Skip empty files
        if ($_FILES['multipleImages']['error'][$i] !== UPLOAD_ERR_OK) {
          continue;
        }

        $ext  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $size = $_FILES['multipleImages']['size'][$i];
        $tmp  = $_FILES['multipleImages']['tmp_name'][$i];

        if ($size > $maxFileSize) {
          header("Location: " . basename(__FILE__) . "?sizeError=1");
          exit;
        }
        if (!in_array($ext, $allowedExt)) {
          header("Location: " . basename(__FILE__) . "?typeError=1");
          exit;
        }

        $newMulti = uniqid('pro_') . '_' . time() . "_$i." . $ext;
        if (!move_uploaded_file($tmp, $uploadDir . $newMulti)) {
          header("Location: " . basename(__FILE__) . "?uploadError=1");
          exit;
        }
        $multiNames[] = 'uploads/products/' . $newMulti;
      }
    }

    // Fixed: Proper handling of multiple images
    if (!empty($multiNames)) {
      $multipleImage = implode(',', $multiNames);
    } else {
      $multipleImage = $oldMultipleImage;
    }

    $stmt = $conn->prepare("UPDATE product_tbl 
                                   SET product_name = :pname,  
                                       product_image = :pimage, 
                                       multiple_image = :pmimage
                                  WHERE id = :id");

    $stmt->bindParam(":id", $pid);
    $stmt->bindParam(":pname", $name);
    $stmt->bindParam(":pimage", $image);
    $stmt->bindParam(":pmimage", $multipleImage);
    $result = $stmt->execute();

    if ($result) {
      if (!empty($oldSingleImage) && $image !== $oldSingleImage && file_exists($oldSingleImage)) {
        unlink($oldSingleImage);
      }

      if (!empty($oldMultipleImage) && $multipleImage !== $oldMultipleImage) {
        $multi = explode(',', $oldMultipleImage);
        foreach ($multi as $img) {
          $img = trim($img);
          if (!empty($img) && file_exists($img)) {
            unlink($img);
          }
        }
      }

      header("Location: index.php?success=1");
      exit;
    }
  } catch (PDOException $e) {
    error_log("Connection Failed in " . __FILE__ . " on line " . __LINE__ . ": " . $e->getMessage());
    header("Location: " . basename(__FILE__) . "?dbError=1");
    exit;
  }
}

$id = htmlspecialchars($_GET['id']);
$sql = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
$sql->bindParam(":id", $id);
$sql->execute();
$product = $sql->fetch(PDO::FETCH_ASSOC);

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
      background-color: #f8d7da;
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
    <h2 class="form-title">Edit Product</h2>
    <?php
    // Display error messages
    if (isset($_GET['csrfError'])) {
      echo '<div class="error">CSRF token mismatch. Please try again.</div>';
    }
    if (isset($_GET['error'])) {
      echo '<div class="error">Product name is required.</div>';
    }
    if (isset($_GET['fileSize'])) {
      echo '<div class="error">File size too large. Maximum 2MB allowed.</div>';
    }
    if (isset($_GET['extension'])) {
      echo '<div class="error">Invalid file extension. Only jpeg, jpg, png, gif allowed.</div>';
    }
    if (isset($_GET['fileUpload'])) {
      echo '<div class="error">Failed to upload file.</div>';
    }
    if (isset($_GET['sizeError'])) {
      echo '<div class="error">One or more files are too large.</div>';
    }
    if (isset($_GET['typeError'])) {
      echo '<div class="error">One or more files have invalid extensions.</div>';
    }
    if (isset($_GET['uploadError'])) {
      echo '<div class="error">Failed to upload one or more files.</div>';
    }
    if (isset($_GET['dbError'])) {
      echo '<div class="error">Database error occurred.</div>';
    }
    if (isset($_GET['dirError'])) {
      echo '<div class="error">Failed to create upload directory.</div>';
    }
    ?>
    <form id="productForm" method="post" action="<?= basename(__FILE__) ?>" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="pid" value="<?= htmlspecialchars($product['id']) ?>">
      <input type="hidden" name="oldSingleImage" value="<?= $product['product_image'] ?>">
      <input type="hidden" name="oldMultipleImage" value="<?= $product['multiple_image'] ?>">

      <div class="form-group">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName" value="<?= $product['product_name'] ?>">
      </div>

      <div class="form-group">
        <label for="productImage">Product Image</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" class="file-input">
        <img width="100" src="<?= $product['product_image'] ?>" alt="">
      </div>

      <div class="form-group">
        <label for="multipleImages">Multiple Images</label>
        <input type="file" id="multipleImages" name="multipleImages[]" accept="image/*" multiple class="file-input">
        <?php
        $multipleImage = explode(',', $product['multiple_image']);
        foreach ($multipleImage as $img):
        ?>
          <img width="100" src="<?= $img ?>" alt="">
        <?php endforeach; ?>
      </div>

      <button type="submit" name="submit" class="submit-btn">Update Product</button>
    </form>
  </div>
</body>

</html>
<?php $conn = null; ?>