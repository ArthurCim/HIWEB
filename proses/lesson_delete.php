<?php
include "../db.php";
$id = $_POST['id_lesson'];
mysqli_query($conn, "DELETE FROM lesson WHERE id_lesson='$id'");
?>
