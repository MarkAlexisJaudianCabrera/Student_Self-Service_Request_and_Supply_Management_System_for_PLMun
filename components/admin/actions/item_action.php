<?php
    $conn = new mysqli("localhost", "root", "1234", "plmun_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    /* ADD ITEM */
    if (isset($_POST['add'])) {

        $id   = $_POST['itemtbID'];
        $name = $_POST['name'];
        $desc = $_POST['description'] ?? '';
        $price = $_POST['price'];
        $stock = $_POST['stock_quantity'];
        $cat   = $_POST['category'];

        $stmt = $conn->prepare("
            INSERT INTO itemtb(itemtbID,name,description,price,stock_quantity,category)
            VALUES(?,?,?,?,?,?)
        ");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssdis", $id,$name,$desc,$price,$stock,$cat);

        if (!$stmt->execute()) {

            // ❗ Duplicate primary key error (MySQL = 1062)
            if ($conn->errno == 1062) {
                echo "<script>
                    alert('Error: Item ID already exists!');
                    window.history.back();
                </script>";
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
        }
    }

    /* EDIT ITEM */
    if (isset($_POST['edit'])) {

        $id   = $_POST['itemtbID'];
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock_quantity'];
        $cat   = $_POST['category'];

        $stmt = $conn->prepare("
            UPDATE itemtb
            SET name=?, description=?, price=?, stock_quantity=?, category=?
            WHERE itemtbID=?
        ");
        $stmt->bind_param("ssdiss", $name,$desc,$price,$stock,$cat,$id);
        $stmt->execute();
    }

    /* DELETE ITEM */
    if (isset($_GET['delete'])) {

        $id = $_GET['delete'];

        $stmt = $conn->prepare("DELETE FROM itemtb WHERE itemtbID=?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
    }

    header("Location: ../items.php");
    exit;