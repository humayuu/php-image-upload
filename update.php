<?php
require 'config.php';
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['isSubmitted'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: edit.php?csrfError=1");
        exit;
    }



    $id = htmlspecialchars($_POST['id']);
    $name = filter_var($_POST['productName'], FILTER_SANITIZE_SPECIAL_CHARS);
    $oldImage = htmlspecialchars($_POST['oldImage']);
    $oldMultipleImage = htmlspecialchars($_POST['oldMultiImage']);

    $image = null;
    $multipleImage = null;
    $multipleName = []; // initialize Array for Handle Multiple image
    $allowedExtension = ['jpeg', 'jpg', 'png', 'gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB
    $uploadDir = __DIR__ . "/uploads/products/";

    if (empty($name)) {
        header("Location: " . basename(__FILE__) . "?error=1");
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Upload Single Image
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
        $tmpName = $_FILES['productImage']['tmp_name'];
        $fileSize = $_FILES['productImage']['size'];

        if ($fileSize > $maxFileSize) {
            header("Location: " . basename(__FILE__) . "?sizeError=1");
            exit;
        }

        if (!in_array($ext, $allowedExtension)) {
            header("Location: " . basename(__FILE__) . "?extensionError=1");
            exit;
        }


        $newName = uniqid('pro_') . "_" . time() . "." . $ext;

        if (!move_uploaded_file($tmpName, $uploadDir . $newName)) {
            header("Location: " . basename(__FILE__) . "?uploadError=1");
            exit;
        }

        $image = "uploads/products/" . $newName;
    } else {
        $image = $oldImage;
    }

    // Upload Multiple Image
    if (isset($_FILES['multipleImages']) && is_array($_FILES['multipleImages']['error']) && $_FILES['multipleImages']['error'][0] === UPLOAD_ERR_OK) {
        foreach ($_FILES['multipleImages']['name'] as $i => $originName) {
            if ($_FILES['multipleImages']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext = strtolower(pathinfo($originName, PATHINFO_EXTENSION));
            $tmpName = $_FILES['multipleImages']['tmp_name'][$i];
            $fileSize = $_FILES['multipleImages']['size'][$i];

            if ($fileSize > $maxFileSize) {
                header("Location: " . basename(__FILE__) . "?sizeError=1");
                exit;
            }

            if (!in_array($ext, $allowedExtension)) {
                header("Location: " . basename(__FILE__) . "?extensionError=1");
                exit;
            }


            $newMultiName = uniqid('pro_') . "_" . time() . "_$i" .  "." . $ext;

            if (!move_uploaded_file($tmpName, $uploadDir . $newMultiName)) {
                header("Location: " . basename(__FILE__) . "?uploadError=1");
                exit;
            }

            $multipleName[] = "uploads/products/" . $newMultiName;
        }

        if (!empty($multipleName)) {
            $multipleImage = implode(',', $multipleName);
        }
    } else {
        $multipleImage = $oldMultipleImage;
    }
    try {
        $conn->beginTransaction();
        // Insert Data into database
        $stmt = $conn->prepare("UPDATE product_tbl 
                                       SET product_name        = :pname, 
                                           product_image       = :pimage,   
                                           product_multi_image = :mpimage
                                       WHERE id = :pid");

        $stmt->bindParam(":pid", $id);
        $stmt->bindParam(":pname", $name);
        $stmt->bindParam(":pimage", $image);
        $stmt->bindParam(":mpimage", $multipleImage);
        $result = $stmt->execute();

        if ($result) {

            if (!empty($oldImage) && $oldImage !== $image && file_exists($oldImage)) {
                unlink($oldImage);
            }

            if (!empty($oldMultipleImage) && $oldMultipleImage !== $multipleImage) {
                $multiImg = explode(',', $oldMultipleImage);
                foreach ($multiImg as $img) {
                    trim($img);
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }

            $conn->commit();

            // Redirect to Home Page 
            header("Location: index.php?update=1");
            exit;
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Product Update Failed in " . __FILE__ . "on" . __LINE__ . ": " . $e->getMessage());
    }
}


$conn = null;
