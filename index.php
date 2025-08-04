<?php
require 'config.php';

try {
  $stmt = $conn->prepare("SELECT * FROM product_tbl ORDER BY Product_name ASC");
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Connection Failed in " . __FILE__ . " on line " . __LINE__ . ": " . $e->getMessage());
  header("Location: " . basename(__FILE__) . "?dbError=1");
  exit;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Table</title>
  <link rel="stylesheet" href="assets/table-styles.css">
</head>

<body>
  <div class="table-container">
    <div class="table-header">
      <h2 class="table-title">Product List</h2>
      <a href="add.php" style="text-decoration: none;" class="btn-add-product">Add Product</a>
    </div>
    <div class="table-wrapper">
      <?php if ($products): ?>
        <table id="productTable">
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Main Image</th>
              <th>Multiple Images</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= $product['product_name'] ?></td>
                <td>
                  <div class="image-cell">
                    <img src="<?= $product['product_image'] ?>" alt="Product Image" class="product-image">
                  </div>
                </td>
                <td>
                  <div class="multiple-images">
                    <?php
                    $multipleImage = explode(",", $product['multiple_image']);
                    foreach ($multipleImage as $img):
                    ?>
                      <img src="<?= $img ?>" alt="Image 1" class="thumbnail">
                    <?php endforeach; ?>
                  </div>
                </td>
                <td>
                  <div class="action-buttons">
                    <a href="edit.php?id=<?= $product['id'] ?>" style="text-decoration: none;" class="btn-edit">Edit</a>
                    <a href="delete.php?id=<?= $product['id'] ?>" onclick="return confirm('Are You Sure?')" style="text-decoration: none;" class="btn-delete">Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>

          </tbody>
        </table>
      <?php else: ?>
        <div style="background-color: aqua; color: black; font-size:50px; text-align: center;">No Record Found!</div>
      <?php endif; ?>
    </div>
  </div>


</body>

</html>

<?php $conn = null; ?>