<?php
require 'config.php';


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
    $id = htmlspecialchars($_GET['id']);
    $sql = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
    $sql->bindParam(":id", $id);
    $sql->execute();
    $product = $sql->fetch();
    ?>

    <form id="productForm">
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
        $image = explode(', ', $product['product_multi_image']);
        foreach ($image as $img):

        ?>
          <img width="100" src="<?= htmlspecialchars($img['product_multi_image']) ?>s" alt="">
        <?php endforeach; ?>

      </div>

      <button type="submit" name="isSubmitted" class="submit-btn">Add Product</button>
    </form>
  </div>
</body>

</html>
<?php $conn = null; ?>