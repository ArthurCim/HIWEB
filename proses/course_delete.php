<?php
include "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id_courses']);
    $sql = "DELETE FROM courses WHERE id_courses=$id";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
