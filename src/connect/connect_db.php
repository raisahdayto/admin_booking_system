<?php
require("config.php");
$connect_db = mysqli_connect($database_host, $database_user, $database_pass, $database_name);
// Evaluate the connection
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit(); 
} 
?>