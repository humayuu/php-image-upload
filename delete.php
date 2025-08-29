<?php
require 'file.php';
$file = new File();
$id = htmlspecialchars($_GET['id']);

// Delete Record with Image unlink
$stmt = $file->pdo->prepare("DELETE FROM product_tbl WHERE id = :id");
$stmt->bindParam(":id", $id);
$result = $stmt->execute();

if ($result) {

    //Single Image
    $file->DeleteSingleImage('product_tbl', 'product_image', "id = $id");

    if (!empty($multiImage)) {
        $images = explode(',', $multiImage);
        foreach ($images as $img) {
            trim($img);
            if (!empty($img) && file_exists($img)) {
                unlink($img);
            }
        }
    }

    // Redirect to Home Page
    header("Location: index.php?success=1");
    exit;
}