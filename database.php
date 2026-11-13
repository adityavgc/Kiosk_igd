<?php
// config/database.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_igd"; // Sesuaikan nama database Anda nanti

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Biarkan kosong atau tampilkan pesan jika diperlukan
}
?>