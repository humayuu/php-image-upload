<?php
require 'config.php';
$id = htmlspecialchars($_GET['id']);

$sql = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
$sql->bindParam(":id", $id);
$sql->execute();
$product = $sql->fetch();
$singleImage = $product['product_image'];
$multipleImage = $product['product_multi_image'];

// Delete Product 

try {

    $stmt = $conn->prepare("DELETE FROM product_tbl WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $result = $stmt->execute();

    if ($result) {

        if (file_exists($singleImage)) {
            unlink($singleImage);
        }
        if (file_exists($multipleImage)) {
            unlink($multipleImage);
        }
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error in Product Delete " .  __FILE__ . "on" . __LINE__ . $e->getMessage());
}























$conn = null;
