<?php
require 'config.php';
session_start();

try {
  $id = htmlspecialchars($_GET['id']);
  $stmt = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
  $stmt->bindParam(":id", $id);
  $stmt->execute();
  $product = $stmt->fetch();
} catch (PDOException $e) {
  error_log("Product Fetch Error in " . __FILE__ . "on" . __LINE__ . $e->getMessage());
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

    <form method="post" action="update.php" id="productForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
      <input type="hidden" name="oldImage" value="<?= htmlspecialchars($product['product_image']) ?>">
      <input type="hidden" name="oldMultiImage" value="<?= htmlspecialchars($product['product_multi_image']) ?>">

      <div class="form-group">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName"
          value="<?= htmlspecialchars($product['product_name']) ?>">
      </div>

      <div class="form-group">
        <label for="productImage">Product Image</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" class="file-input">
        <img src="<?= htmlspecialchars($product['product_image']) ?>" width="100" alt="">
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