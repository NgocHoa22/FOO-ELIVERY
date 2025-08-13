<?php
$host = 'localhost';
$username = 'root';
$password = ''; // Để trống nếu không có mật khẩu
$database = 'seo01_food';
$connect = mysqli_connect($host, $username, $password, $database);
if (!$connect) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
?>