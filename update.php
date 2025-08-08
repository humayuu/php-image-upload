<?php
// Connection to Database
require 'config.php';
require './vendor/autoload.php';


use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// create new manager instance with desired driver
$manager = new ImageManager(new Driver());

// Start Session
session_start();

// Generate CSRF Token if its Empty
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['isSubmitted'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: " . basename(__FILE__) . "?csrfError=1");
        exit;
    }

    try {

        $id = trim(filter_var($_POST['id'], FILTER_VALIDATE_INT));
        $name = trim(filter_var($_POST['productName'], FILTER_SANITIZE_SPECIAL_CHARS));
        $oldSingleImg = trim(filter_var($_POST['oldSingleImage'], FILTER_SANITIZE_SPECIAL_CHARS));
        $oldMultiImg = trim(filter_var($_POST['oldMultipleImage'], FILTER_SANITIZE_SPECIAL_CHARS));



        $image = null;
        $multipleImage = null;
        $multipleNames = []; // Initialize Array For handle Multiple Image name

        $uploadDir = __DIR__ . "/uploads/products/";
        $allowedExtension = ['jpeg', 'jpg', 'png', 'gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2 MB

        if (empty($name)) {
            header("Location: " . basename(__FILE__) . "?inputError=1");
            exit;
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }


        // Upload Single Image
        if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
            $size = $_FILES['productImage']['size'];
            $tempName = $_FILES['productImage']['tmp_name'];

            if ($size > $maxFileSize) {
                header("Location: " . basename(__FILE__) . "?fileSize=1");
                exit;
            }

            if (!in_array($extension, $allowedExtension)) {
                header("Location: " . basename(__FILE__) . "?typeError=1");
                exit;
            }


            $newName = uniqid('pro_') . "_" . time() . "."  . $extension;
            $targetPath = $uploadDir . $newName;

            $manager->read($tempName)
                ->resize(800, 700)
                ->save($targetPath);



            $image = "uploads/products/" . $newName;
        } else {
            $image = $oldSingleImg;
        }

        // Upload Multiple Image
        if (isset($_FILES['multipleImages']) && is_array($_FILES['multipleImages']['error']) && $_FILES['multipleImages']['error'][0] === UPLOAD_ERR_OK) {
            foreach ($_FILES['multipleImages']['name'] as $i => $origin) {

                if ($_FILES['multipleImages']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $extension = strtolower(pathinfo($_FILES['multipleImages']['name'][$i], PATHINFO_EXTENSION));
                $size = $_FILES['multipleImages']['size'][$i];
                $tempName = $_FILES['multipleImages']['tmp_name'][$i];

                if ($size > $maxFileSize) {
                    header("Location: " . basename(__FILE__) . "?fileSize=1");
                    exit;
                }

                if (!in_array($extension, $allowedExtension)) {
                    header("Location: " . basename(__FILE__) . "?typeError=1");
                    exit;
                }


                $newMulti = uniqid('pro_') . '_' . time() . "_$i." . $extension;
                $targetPath = $uploadDir . $newMulti;

                $manager->read($tempName)
                    ->cover(600, 600)
                    ->save($targetPath);

                $multipleNames[] = "uploads/products/" . $newMulti;

                if (!empty($multipleNames)) {
                    $multipleImage = implode(',', $multipleNames);
                }
            }
        } else {
            $multipleImage = $oldMultiImg;
        }

        // Update Data into Database
        $stmt = $conn->prepare("UPDATE product_tbl 
                                         SET product_name = :pname,
                                             product_image = :pimage, 
                                            multiple_image = :mpimage
                                        WHERE id = :id");

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":pname", $name);
        $stmt->bindParam(":pimage", $image);
        $stmt->bindParam(":mpimage", $multipleImage);
        $result = $stmt->execute();

        if ($result) {

            if (!empty($oldSingleImg) && $image !== $oldSingleImg && file_exists($oldSingleImg)) {
                unlink($oldSingleImg);
            }

            if (!empty($oldMultiImg) && $multipleImage !== $oldMultiImg) {
                $multiImg = explode(',',  $oldMultiImg);

                foreach ($multiImg as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }

            // Redirect to Home Page
            header("Location: index.php?update=1");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Add Product Failed in " . __FILE__ . "on" . __LINE__ . ": " . $e->getMessage());
    }
}
$conn = null;
