<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$db = "seo01_food";
$connect = mysqli_connect($servername, $username, $password, $db);

if (!$connect) {
    die("Kết nối CSDL thất bại: " . mysqli_connect_error());
}

// Lấy thông tin vai trò người dùng
$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($user = mysqli_fetch_assoc($result)) {
    $_SESSION['role'] = $user['role'] ?? 'user'; // Lưu role vào session, mặc định là 'user' nếu không có
} else {
    echo "<div class='alert alert-danger'>Không tìm thấy thông tin người dùng. Vui lòng kiểm tra cơ sở dữ liệu.</div>";
    $_SESSION['role'] = 'user';
}
mysqli_stmt_close($stmt);

// Lấy danh sách sản phẩm
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sql = "SELECT * FROM products";
if ($category_filter) {
    $sql .= " WHERE category = ?";
}
$stmt = mysqli_prepare($connect, $sql);
if ($category_filter) {
    mysqli_stmt_bind_param($stmt, "s", $category_filter);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tất Cả Sản Phẩm - BTEC Sweet Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #4B5563;
            --accent-color: #FEF3C7;
            --background-color: #FFF7ED;
            --hover-color: #3B82F6;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: rgba(255, 247, 237, 0.95);
        }
        .header {
            background: linear-gradient(90deg, #ffffff, var(--accent-color));
            padding: 12px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo img {
            height: 50px;
            margin-left: 20px;
            transition: transform 0.3s ease;
        }
        .logo img:hover {
            transform: scale(1.1);
        }
        .form-search {
            max-width: 450px;
            flex-grow: 1;
            margin: 0 20px;
        }
        .form-search input[type="text"] {
            border-radius: 20px 0 0 20px;
            padding: 10px 15px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            transition: border-color 0.3s ease;
        }
        .form-search input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        .form-search button {
            border-radius: 0 20px 20px 0;
            padding: 10px 15px;
            background-color: var(--primary-color);
            border: none;
            color: white;
            transition: background-color 0.3s ease;
        }
        .form-search button:hover {
            background-color: var(--hover-color);
        }
        .icon-cart img, .icon-user img {
            height: 30px;
            width: 30px;
            margin: 0 12px;
            transition: transform 0.3s ease;
        }
        .icon-cart img:hover, .icon-user img:hover {
            transform: scale(1.2);
        }
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }
        .navbar {
            background-color: var(--primary-color);
            padding: 8px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }
        .navbar-nav .nav-link {
            color: #fff !important;
            font-size: 15px;
            font-weight: 600;
            padding: 10px 18px;
            transition: background-color 0.3s ease, color 0.3s ease;
            border-radius: 6px;
            margin: 0 5px;
        }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
            background-color: var(--hover-color);
            color: #fff !important;
        }
        .dropdown-menu {
            z-index: 1001;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background-color: #fff;
        }
        .dropdown-menu .dropdown-item {
            font-size: 14px;
            padding: 8px 15px;
            transition: background-color 0.3s ease;
        }
        .dropdown-menu .dropdown-item:hover {
            BACKGROUND-COLOR: var(--accent-color);
            COLOR: var(--primary-color);
        }
        .content {
            flex: 1;
            padding: 25px 0;
        }
        .left {
            background: linear-gradient(180deg, var(-- biomarkers-color), #ffffff);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .cate, .brand {
            margin-bottom: 25px;
        }
        .cate .list-group-item, .brand .list-group-item {
            padding: 10px 15px;
            font-size: 14px;
            border: none;
            background-color: transparent;
            transition: color 0.3s ease, background-color 0.3s ease;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        .cate .list-group-item:hover, .brand .list-group-item:hover {
            color: var(--primary-color);
            background-color: rgba(74, 144, 226, 0.1);
        }
        .cate .list-group-item a, .brand .list-group-item a {
            text-decoration: none;
            color: var(--secondary-color);
            font-weight: 500;
            flex-grow: 1;
        }
        .cate .list-group-item i, .brand .list-group-item i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 14px;
        }
        .li1, .li2 {
            font-family: 'Poppins', sans-serif;
            font-size: 18px !important;
            font-weight: 700;
            color: var(--primary-color);
            padding: 10px 15px;
            margin-bottom: 12px;
            border-bottom: 2px solid var(--primary-color);
        }
        .right {
            padding: 20px;
        }
        .product {
            background-color: #fff;
            border: none;
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .product:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        .product-image-container {
            width: 90%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 12px;
            position: relative;
        }
        .product img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .product:hover img {
            transform: scale(1.1);
        }
        .product h5 {
            font-size: 16px;
            color: var(--secondary-color);
            margin: 10px 0 6px;
        }
        .product p {
            font-size: 14px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 12px;
        }
        .product a {
            text-decoration: none;
            color: var(--primary-color);
            font-size: 13px;
            margin: 0 6px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .product a:hover {
            color: var(--hover-color);
        }
        .product button {
            padding: 10px 20px;
            font-size: 14px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 20px;
            transition: background-color 0.3s ease;
        }
        .product button:hover {
            background-color: var(--hover-color);
        }
        .badge-promo {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #EF4444;
            color: #fff;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 12px;
        }
        .footer {
            background: linear-gradient(90deg, var(--primary-color), var(--hover-color));
            color: #fff;
            padding: 50px 0;
        }
        .footer a {
            color: #fff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: var(--accent-color);
        }
        .footer .social-icons a {
            font-size: 22px;
            margin: 0 12px;
        }
        .newsletter-form input[type="email"] {
            border-radius: 20px 0 0 20px;
            padding: 10px 15px;
            font-size: 14px;
            border: none;
        }
        .newsletter-form button {
            border-radius: 0 20px 20px 0;
            padding: 10px 15px;
            background-color: var(--hover-color);
            border: none;
            color: #fff;
            transition: background-color 0.3s ease;
        }
        .newsletter-form button:hover {
            background-color: #2563EB;
        }
        @media (max-width: 991px) {
            .sidebar-toggle {
                display: block;
            }
        }
        @media (min-width: 992px) {
            .sidebar-toggle {
                display: none;
            }
            #sidebarCollapse {
                display: block !important;
            }
        }
        .sidebar-toggle {
            font-size: 14px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            border: none;
            border-radius: 6px;
            color: #fff;
            transition: background-color 0.3s ease;
        }
        .sidebar-toggle:hover {
            background-color: var(--hover-color);
        }
        .animate__fadeIn {
            animation: fadeIn 0.6s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header class="header">
            <div class="container-fluid d-flex align-items-center justify-content-between">
                <div class="logo">
                    <a href="banbanh.php"><img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Sweet Shop"></a>
                </div>
                <form class="form-search d-flex" action="" role="search">
                    <input type="text" placeholder="Tìm kiếm bánh kẹo..." class="form-control" aria-label="Tìm kiếm sản phẩm">
                    <button type="submit" class="btn" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
                </form>
                <div class="icon-cart">
                    <a href="cart.php" aria-label="Giỏ hàng"><img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Cart"></a>
                </div>
                <div class="icon-user dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản">
                        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="User">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="account.php">Hồ Sơ</a></li>
                        <li><a class="dropdown-item" href="account.php#orders">Đơn Hàng</a></li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="admin.php">Quản Trị</a></li>
                            <!-- <li><a class="dropdown-item" href="banbanh.php">Thêm Sản Phẩm</a></li> -->
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="login_register.php">Đăng Xuất</a></li>
                    </ul>
                </div>
            </div>
        </header>
        <nav class="navbar navbar-expand-md sticky-top">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link active" href="product.php" aria-current="page">Tất Cả Sản Phẩm</a></li>
                        <li class="nav-item"><a class="nav-link" href="account.php">Tài Khoản</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Giỏ Hàng</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Liên Hệ</a></li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="banbanh.php">Thêm Sản Phẩm</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </navස