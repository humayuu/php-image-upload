<?php
require 'config.php';


try {
  $stmt = $conn->prepare("SELECT * FROM product_tbl ORDER BY product_name");
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Product Fetch Failed in " . __FILE__ . "on" . __LINE__ . ": " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Table</title>
  <link rel="stylesheet" href="assets/table-styles.css">
  <style>
    .error {
      color: #181515ff;
      background: #e9e8f0ff;
      border: 1px solid #11deecff;
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      border-radius: 0.25rem;
      font-size: 0.9rem;
      line-height: 1.4;
    }
  </style>
</head>

<body>
  <div class="table-container">
    <div class="table-header">
      <h2 class="table-title">Product List</h2>
      <?php
      if (isset($_GET['success']) && $_GET['success'] == 1) {
        echo  '<div class="error">Product Deleted Successfully. </div>';
      } elseif (isset($_GET['update']) && $_GET['update'] == 1) {
        echo  '<div class="error">Product Updated Successfully. </div>';
      }
      ?>
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
            <tr>
              <?php foreach ($products as $product): ?>
                <td><?= $product['product_name'] ?></td>
                <td>
                  <div class="image-cell">
                    <img src="<?= $product['product_image'] ?>" alt="Product Image" class="product-image">
                  </div>
                </td>
                <td>
                  <div class="multiple-images">
                    <?php
                    $multipleImage = explode(',', $product['multiple_image']);
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