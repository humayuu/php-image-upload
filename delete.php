<?php
require 'config.php';

try {

    $id = htmlspecialchars($_GET['id']);

    $stmt = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $singleImage = $product['product_image'];
    $multipleImage = $product['multiple_image'];

    $sql = $conn->prepare("DELETE FROM product_tbl WHERE id = :id");
    $sql->bindParam(":id", $id);
    $result = $sql->execute();

    if ($result) {

        if (!empty($singleImage) && file_exists($singleImage)) {
            unlink($singleImage);
        }

        if (!empty($multipleImage)) {
            $multi = explode(',', $multipleImage);

            foreach ($multi as $img) {
                trim($img);
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

















$conn = null;
