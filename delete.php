<?php
require 'config.php';

// First We Fetch images
$id = htmlspecialchars($_GET['id']);
$sql = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
$sql->bindParam(":id", $id);
$sql->execute();
$product = $sql->fetch();
$thumbnail = $product['product_image'];
$multipleImage = $product['product_multi_image'];

try {
    $conn->beginTransaction();
    // Start Delete Products 
    $stmt = $conn->prepare("DELETE FROM product_tbl WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $result = $stmt->execute();

    if ($result) {

        if (!empty($thumbnail) && file_exists($thumbnail)) {
            unlink($thumbnail);
        }
        if (!empty($multipleImage) && file_exists($thumbnail)) {
            $image = explode(', ', $multipleImage);
            foreach ($image as $img) {
                if (!empty($img) && file_exists($img)) {
                    trim($img);
                    unlink($img);
                }
            }
        }
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error in Product Delete " .  __FILE__ . "on" . __LINE__ . $e->getMessage());
}























$conn = null;
