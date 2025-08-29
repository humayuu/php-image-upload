<?php
// Class Start Here
class File
{
    private $dsn = "mysql:host=localhost;dbname=store_db;charset=utf8mb4;";
    private $user = "root";
    private $password = "";
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    public $pdo = null;
    public $result = [];
    public $error = [];

    // Function for Database connection
    public function __construct()
    {
        try {
            $this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);
        } catch (PDOException $e) {
            throw new Exception("Database Connection error: " . $e->getMessage());
        }
    }

    // Function for Check if the table Exists in this Database or not
    private function tableExists($table)
    {
        try {
            $tableInDB = $this->pdo->query("SHOW TABLES LIKE " . $this->pdo->quote($table));
            if ($tableInDB->rowCount() > 0) {
                return true;
            } else {
                $this->error[] = "Table " . $table . " does not exist in this database.";
                return false;
            }
        } catch (PDOException $e) {
            $this->error[] = "Error in finding Table: " . $e->getMessage();
            return false;
        }
    }

    // Function for Single Image Upload
    public function SingleImage($table, $file, $redirect = null)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        $allowedExt = ['jpeg', 'jpg', 'png', 'gif', 'pdf'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        $uploadPath = __DIR__ . '/uploads/products/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $image = null;

        try {
            if (isset($_FILES[$file]) && $_FILES[$file]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
                $size = $_FILES[$file]['size'];
                $tmpName = $_FILES[$file]['tmp_name'];

                // Validate file size
                if ($size > $maxFileSize) {
                    $this->error[] = "File size exceeds maximum limit of 2MB";
                    if ($redirect !== null) {
                        header("Location: " . $redirect . "?sizeError=1");
                        exit;
                    }
                    return false;
                }

                // Validate file extension
                if (!in_array($ext, $allowedExt)) {
                    $this->error[] = "Invalid file extension";
                    if ($redirect !== null) {
                        header("Location: " . $redirect . "?extError=1");
                        exit;
                    }
                    return false;
                }

                $newName = uniqid('pro_') . '_' . time() . '.' . $ext;
                $fullPath = $uploadPath . $newName;

                if (!move_uploaded_file($tmpName, $fullPath)) {
                    $this->error[] = "Failed to upload file";
                    if ($redirect !== null) {
                        header("Location: " . $redirect . "?uploadError=1");
                        exit;
                    }
                    return false;
                }

                $image = 'uploads/products/' . $newName;
                return $image;
            } else {
                $this->error[] = "No file uploaded or upload error occurred";
                return false;
            }
        } catch (Exception $e) {
            $this->error[] = "Error in upload image: " . $e->getMessage();
            return false;
        }
    }

    // Function for Multiple Image Upload
    public function MultipleImage($table, $file, $redirect = null)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        $allowedExt = ['jpeg', 'jpg', 'png', 'gif', 'pdf'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        $uploadPath = __DIR__ . '/uploads/products/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $multipleName = [];

        try {
            if (isset($_FILES[$file]) && is_array($_FILES[$file]['name'])) {
                $fileCount = count($_FILES[$file]['name']);

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES[$file]['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $originalName = $_FILES[$file]['name'][$i];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $size = $_FILES[$file]['size'][$i];
                    $tmpName = $_FILES[$file]['tmp_name'][$i];

                    // Validate file size
                    if ($size > $maxFileSize) {
                        $this->error[] = "File {$originalName} exceeds maximum size limit";
                        if ($redirect !== null) {
                            header("Location: " . $redirect . "?sizeError=1");
                            exit;
                        }
                        continue;
                    }

                    // Validate file extension
                    if (!in_array($ext, $allowedExt)) {
                        $this->error[] = "Invalid file extension for {$originalName}";
                        if ($redirect !== null) {
                            header("Location: " . $redirect . "?extError=1");
                            exit;
                        }
                        continue;
                    }

                    $newMultiName = uniqid('pro_') . '_' . time() . '_' . $i . '.' . $ext;
                    $fullPath = $uploadPath . $newMultiName;

                    if (!move_uploaded_file($tmpName, $fullPath)) {
                        $this->error[] = "Failed to upload file {$originalName}";
                        if ($redirect !== null) {
                            header("Location: " . $redirect . "?uploadError=1");
                            exit;
                        }
                        continue;
                    }

                    $multipleName[] = "uploads/products/" . $newMultiName;
                }

                if (!empty($multipleName)) {
                    $multipleImage = implode(',', $multipleName);
                    return $multipleImage;
                }

                return false;
            }

            return false;
        } catch (Exception $e) {
            $this->error[] = "Error in upload multiple images: " . $e->getMessage();
            return false;
        }
    }

    // Function for Delete Image from Folder
    public function DeleteImage($table, $column, $where = null)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        try {
            // Build query
            $query = "SELECT `$column` FROM `$table`";
            if ($where !== null) {
                $query .= " WHERE $where";
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result && isset($result[$column])) {
                $imagePath = $result[$column];

                // Handle single or multiple images
                if (strpos($imagePath, ',') !== false) {
                    // Multiple images
                    $images = explode(',', $imagePath);
                    foreach ($images as $img) {
                        $fullPath = __DIR__ . '/' . $img;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                } else {
                    // Single image
                    $fullPath = __DIR__ . '/' . $imagePath;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
                return true;
            }

            return false;
        } catch (PDOException $e) {
            $this->error[] = "Error in Delete image: " . $e->getMessage();
            return false;
        }
    }

    // Function to get errors
    public function getErrors()
    {
        return $this->error;
    }

    // Function to get last error
    public function getLastError()
    {
        return end($this->error);
    }

    // Function to clear errors
    public function clearErrors()
    {
        $this->error = [];
    }

    // Function for Close Connection
    public function __destruct()
    {
        $this->pdo = null;
    }
} // Class Ends Here