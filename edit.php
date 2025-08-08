<?php 
require 'config.php';
session_start();

try{

  $id = trim(filter_var($_GET['id'], FILTER_VALIDATE_INT));
  $stmt = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
  $stmt->bindParam(":id", $id);
  $stmt->execute();
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  error_log("Product Fetch Failed in " . __FILE__ . "on" . __LINE__ . ": " . $e->getMessage());
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
    
    <form  id="productForm" method="post" action="update.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?=  trim(filter_var($product['id'], FILTER_VALIDATE_INT)); ?>">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="oldSingleImage" value="<?= trim(filter_var($product['product_image'], FILTER_SANITIZE_SPECIAL_CHARS)); ?>">
      <input type="hidden" name="oldMultipleImage" value="<?= trim(filter_var($product['multiple_image'], FILTER_SANITIZE_SPECIAL_CHARS)); ?>">
      <div class="form-group">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName" value="<?= filter_var($product['product_name'], FILTER_SANITIZE_SPECIAL_CHARS) ?>">
      </div>

      <div class="form-group">
        <label for="productImage">Product Image</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" class="file-input">
        <img width="100" src="<?= filter_var($product['product_image'], FILTER_SANITIZE_SPECIAL_CHARS) ?>" alt="">
      </div>

      <div class="form-group">
        <label for="multipleImages">Multiple Images</label>
        <input type="file" id="multipleImages" name="multipleImages[]" accept="image/*" multiple class="file-input">
        <?php 
        $multiImg = explode(',', $product['multiple_image']);
        foreach($multiImg as $img):
        ?>
        <img width="100" src="<?= filter_var($img, FILTER_SANITIZE_SPECIAL_CHARS) ?>" alt="">
        <?php endforeach; ?>
      </div>

      <button type="submit" name="isSubmitted" class="submit-btn">Update Product</button>
    </form>

  </div>
</body>
</html>
<?php $conn = null; ?>

