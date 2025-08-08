<?php
require 'config.php';

try {

    $id = trim(filter_var($_GET['id'], FILTER_VALIDATE_INT));

    // first Fetch images
    $sql = $conn->prepare("SELECT * FROM product_tbl WHERE id = :id");
    $sql->bindParam(":id", $id);
    $sql->execute();
    $product = $sql->fetch(PDO::FETCH_ASSOC);
    $image = $product['product_image'];
    $multipleImage = $product['multiple_image'];

    $stmt = $conn->prepare("DELETE FROM product_tbl WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $result = $stmt->execute();

    if ($result) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }


        $multiImg = explode(',',  $multipleImage);

        foreach ($multiImg as $img) {
            if (!empty($img) && file_exists($img)) {
                unlink($img);
            }
        }

        // Redirect to Home Page
        header("Location: index.php?success=1");
        exit;
    }
} catch (PDOException $e) {
    error_log("Product Delete Failed in " . __FILE__ . "on" . __LINE__ . ": " . $e->getMessage());
}


// Close Connection
$conn = null;