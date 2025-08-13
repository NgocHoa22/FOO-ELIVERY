<?php
session_start();
include 'config.php';

// Lấy thông tin vai trò và họ tên người dùng nếu đã đăng nhập
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_logged_in = !empty($user_id);
if ($is_logged_in) {
    $sql = "SELECT role, full_name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($user = mysqli_fetch_assoc($result)) {
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['full_name'] = $user['full_name'] ?? 'Người dùng';
    } else {
        $_SESSION['role'] = 'user';
        $_SESSION['full_name'] = 'Người dùng';
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['role'] = 'guest';
    $_SESSION['full_name'] = 'Khách';
}

// Lấy danh sách sản phẩm với bộ lọc tìm kiếm và danh mục
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';
$loai = isset($_GET['loai']) ? mysqli_real_escape_string($connect, $_GET['loai']) : '';
$sql = "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?)";
$params = ["%$search%", "%$search%"];
$types = "ss";

if ($loai && in_array($loai, ['đồ ăn', 'nước uống'])) {
    $sql .= " AND loai_banh_keo = ?";
    $params[] = $loai;
    $types .= "s";
}

$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Lấy 3 sản phẩm nổi bật (mới nhất)
$sql = "SELECT id, name, price, image, loai_banh_keo FROM products ORDER BY id DESC LIMIT 3";
$result = mysqli_query($connect, $sql);
$featured_products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Xử lý thêm sản phẩm vào giỏ hàng
$cart_msg = '';
$cart_id = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    if (!$is_logged_in) {
        header("Location: index.php?action=login_required");
        exit();
    }
    $product_id = intval($_POST['product_id']);
    $sql = "SELECT id FROM carts WHERE user_id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $cart = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($cart) {
                $cart_id = $cart['id'];
            } else {
                $sql = "INSERT INTO carts (user_id) VALUES (?)";
                $stmt = mysqli_prepare($connect, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $cart_id = mysqli_insert_id($connect);
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    if ($cart_id) {
        $sql = "SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $sql = "UPDATE cart_items SET quantity = quantity + 1 WHERE cart_id = ? AND product_id = ?";
                $stmt = mysqli_prepare($connect, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);
            } else {
                $sql = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)";
                $stmt = mysqli_prepare($connect, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);
            }
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $cart_msg = "<div class='alert alert-success'>Thêm vào giỏ hàng thành công!</div>";
            } else {
                $cart_msg = "<div class='alert alert-danger'>Lỗi thêm sản phẩm: " . mysqli_error($connect) . "</div>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Hiển thị thông báo nếu được chuyển hướng từ index.php
if (isset($_GET['action']) && $_GET['action'] === 'login_required') {
    $cart_msg = "<div class='alert alert-warning'>Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!</div>";
}

if (isset($connect)) mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm - BTEC Sweet Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF5733;
            --secondary-color: #333333;
            --accent-color: #FFE4E1;
            --background-color: #F8F9FA;
            --hover-color: #C0392B;
            --button-color: #28A745;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            min-height: 100vh;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #fff;
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo img {
            height: 40px;
            margin-left: 15px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .form-search {
            max-width: 500x;
            flex-grow: 1;
            margin: 0 15px;
        }

        .form-search input[type="text"] {
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-search input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(255, 87, 51, 0.3);
            outline: none;
        }

        .form-search button {
            border-radius: 25px;
            padding: 12px 20px;
            background-color: var(--primary-color);
            border: none;
            color: white;
            transition: background-color 0.3s ease;
        }

        .form-search button:hover {
            background-color: var(--hover-color);
        }

        .icon-cart img, .icon-user img {
            height: 28px;
            width: 28px;
            margin: 0 10px;
            transition: transform 0.3s ease;
        }

        .icon-cart img:hover, .icon-user img:hover {
            transform: scale(1.1);
        }

        .user-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--secondary-color);
            margin-left: 8px;
        }

        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
            font-size: 15px;
            font-weight: 500;
            padding: 8px 15px;
            transition: background-color 0.3s ease;
            border-radius: 5px;
            margin: 0 5px;
        }

        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
            background-color: var(--hover-color);
        }

        .dropdown-menu {
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
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .content {
            flex: 1;
            padding: 20px 0;
        }

        /* Sidebar Styles */
        .sidebar {
            background-color: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 80px;
            min-height: calc(100vh - 80px);
        }

        .sidebar .carousel-inner {
            border-radius: 8px;
            overflow: hidden;
        }

        .sidebar .carousel-item img {
            width: 100%;
            height: 150px;
            object-fit: contain;
            transition: transform 0.3s ease;
            background-color: #fff;
        }

        .sidebar .carousel-item img:hover {
            transform: scale(1.05);
        }

        .sidebar .carousel-control-prev,
        .sidebar .carousel-control-next {
            width: 15%;
            background: rgba(0, 0, 0, 0.3);
        }

        .sidebar .carousel-control-prev-icon,
        .sidebar .carousel-control-next-icon {
            background-color: var(--primary-color);
            border-radius: 50%;
            padding: 10px;
        }

        .sidebar .carousel-indicators {
            bottom: -35px;
        }

        .sidebar .carousel-indicators button {
            background-color: var(--secondary-color);
        }

        .sidebar .carousel-indicators .active {
            background-color: var(--primary-color);
        }

        .sidebar .featured-products {
            margin-top: 50px;
        }

        .sidebar .featured-products h5 {
            font-size: 16px;
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .sidebar .featured-product {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .sidebar .featured-product:hover {
            background-color: var(--accent-color);
        }

        .sidebar .featured-product img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 8px;
            margin-right: 10px;
        }

        .sidebar .featured-product .product-info h6 {
            font-size: 14px;
            color: var(--secondary-color);
            margin: 0 0 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar .featured-product .product-info p {
            font-size: 13px;
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        .sidebar .featured-product .product-info a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 12px;
        }

        .sidebar .featured-product .product-info a:hover {
            color: var(--hover-color);
            text-decoration: underline;
        }

        /* Category Filter Styles */
        .category-filter {
            margin-bottom: 20px;
        }

        .category-filter select {
            max-width: 200px;
            border-radius: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        /* Product Grid Styles */
        .product-grid {
            flex-grow: 1;
        }

        .product-card {
            background-color: #fff;
            border: none;
            border-radius: 10px;
            text-align: center;
            padding: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .product-card img {
            height: 180px;
            width: 100%;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 10px;
            aspect-ratio: 4/3;
        }

        .product-card h5 {
            font-size: 16px;
            color: var(--secondary-color);
            margin: 8px 0 5px;
            font-weight: 500;
        }

        .product-card p {
            font-size: 14px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .product-card .detail-link {
            display: block;
            margin-bottom: 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .product-card .detail-link:hover {
            color: var(--hover-color);
            text-decoration: underline;
        }

        .product-card button {
            padding: 10px;
            font-size: 14px;
            background-color: var(--button-color);
            color: #fff;
            border: none;
            border-radius: 25px;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .product-card button:hover {
            background-color: #218838;
        }

        .footer {
            background: linear-gradient(90deg, var(--primary-color), var(--hover-color));
            color: #fff;
            padding: 40px 0;
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
            font-size: 20px;
            margin: 0 10px;
        }

        .newsletter-form input[type="email"] {
            border-radius: 25px 0 0 25px;
            padding: 10px 15px;
            font-size: 14px;
            border: none;
        }

        .newsletter-form button {
            border-radius: 0 25px 25px 0;
            padding: 10px 15px;
            background-color: var(--button-color);
            border: none;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .newsletter-form button:hover {
            background-color: #218838;
        }

        .animate__fadeIn {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .form-search {
                max-width: 100%;
                margin: 10px 0;
            }

            .sidebar {
                position: static;
                min-height: auto;
                margin-bottom: 20px;
            }

            .product-card img {
                height: 150px;
            }

            .navbar-nav .nav-link {
                font-size: 14px;
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header class="header">
            <div class="container-fluid d-flex align-items-center justify-content-between">
                <div class="logo">
                    <a href="product.php"><img src="https://static.topcv.vn/company_logos/DWHfT2l4XAuq6CUImYechRFCaL83TqQi_1684808932____5d8c4c0fa1867bd1c4d3e2dbaac52f71.jpg" alt="BTEC Sweet Shop"></a>
                </div>
                <form class="form-search d-flex" action="product.php" method="GET" role="search">
                    <input type="text" name="search" placeholder="Tìm kiếm bánh kẹo..." class="form-control" value="<?php echo htmlspecialchars($search); ?>" aria-label="Tìm kiếm sản phẩm">
                    <button type="submit" class="btn" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
                </form>
                <div class="icon-cart">
                    <a href="<?php echo $is_logged_in ? 'cart.php' : 'index.php?action=login_required'; ?>" aria-label="Giỏ hàng"><img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Cart"></a>
                </div>
                <div class="icon-user dropdown d-flex align-items-center">
                    <a href="<?php echo $is_logged_in ? 'account.php' : 'index.php'; ?>" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản" aria-haspopup="true">
                        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="User">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($is_logged_in): ?>
                            <li><a class="dropdown-item" href="account.php">Hồ Sơ</a></li>
                            <li><a class="dropdown-item" href="account.php#orders">Đơn Hàng</a></li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="admin.php">Quản Trị</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php">Đăng Xuất</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="login.php">Đăng Nhập</a></li>
                            <li><a class="dropdown-item" href="login_regist.php">Đăng Ký</a></li>
                        <?php endif; ?>
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
                        <li class="nav-item"><a class="nav-link" href="<?php echo $is_logged_in ? 'account.php' : 'index.php?action=login_required'; ?>">Tài Khoản</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $is_logged_in ? 'cart.php' : 'index.php?action=login_required'; ?>">Giỏ Hàng</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Liên Hệ</a></li>
                        <li class="nav-item"><a class="nav-link" href="order_tracking.php">Theo dõi đơn hàng</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="content container-fluid animate__fadeIn">
            <div class="row">
                <div class="col-lg-3 col-md-12">
                    <div class="sidebar">
                        <div id="adCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#adCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#adCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#adCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            </div>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <a href="product.php?search=Chocolate">
                                        <img src="https://vuakhuyenmai.vn/wp-content/uploads/shopeefood-khuyen-mai-88K-21-1-2022.jpg" class="d-block w-100" alt="Quảng cáo Chocolate">
                                    </a>
                                </div>
                                <div class="carousel-item">
                                    <a href="product.php?search=Đồ ăn">
                                        <img src="https://ss-images.saostar.vn/wpr700/pc/1643101578162/saostar-k9qbqy5h0ep60pwz.jpeg" class="d-block w-100" alt="Quảng cáo Đồ ăn">
                                    </a>
                                </div>
                                <div class="carousel-item">
                                    <a href="product.php">
                                        <img src="https://tse1.mm.bing.net/th/id/OIP.B1XV3HJDp0FJI4jMw_cJVQAAAA?rs=1&pid=ImgDetMain&o=7&rm=3" class="d-block w-100" alt="Ưu đãi Giỏ hàng">
                                    </a>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#adCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#adCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                        <div class="featured-products">
                            <h5>Sản Phẩm Nổi Bật</h5>
                            <?php foreach ($featured_products as $product): ?>
                                <div class="featured-product">
                                    <img src="<?php echo htmlspecialchars($product['image'] ?? 'images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="product-info">
                                        <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</p>
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>">Xem chi tiết</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 col-md-12 product-grid">
                    <div class="mb-4">
                        <h3>Sản Phẩm</h3>
                        <form class="category-filter" method="GET" action="product.php">
                            <div class="input-group">
                                <select name="loai" class="form-select" onchange="this.form.submit()">
                                    <option value="" <?php echo $loai === '' ? 'selected' : ''; ?>>Tất cả</option>
                                    <option value="đồ ăn" <?php echo $loai === 'đồ ăn' ? 'selected' : ''; ?>>Đồ ăn</option>
                                    <option value="nước uống" <?php echo $loai === 'nước uống' ? 'selected' : ''; ?>>Nước uống</option>
                                </select>
                                <?php if ($search): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <?php endif; ?>
                            </div>
                        </form>
                        <?php if (!empty($cart_msg)) echo $cart_msg; ?>
                        <?php if (empty($products)): ?>
                            <div class="alert alert-info">Không tìm thấy sản phẩm nào.</div>
                        <?php endif; ?>
                    </div>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
                                <div class="card product-card">
                                    <img src="<?php echo htmlspecialchars($product['image'] ?? 'images/placeholder.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</p>
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="detail-link">Xem chi tiết</a>
                                        <form action="" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Thêm vào giỏ</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h5>Giới Thiệu</h5>
                        <p>BTEC Food Shop mang đến những loại đồ ăn ngon, chất lượng cao, lan tỏa niềm vui cho mọi nhà.</p>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h5>Liên Hệ</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-map-marker-alt me-2"></i>406 Xuân Phương</li>
                            <li><i class="fas fa-phone me-2"></i>0899133869</li>
                            <li><i class="fas fa-envelope me-2"></i>hoa2282005hhh@gmail.com</li>
                        </ul>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h5>Đăng Ký Bản Tin</h5>
                        <form class="newsletter-form d-flex">
                            <input type="email" placeholder="Nhập email của bạn..." class="form-control" aria-label="Email đăng ký bản tin" required>
                            <button type="submit" class="btn">Đăng Ký</button>
                        </form>
                        <h5 class="mt-4">Theo Dõi Chúng Tôi</h5>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/hoa082005" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://www.instagram.com/phuongbbne/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="https://www.youtube.com/@MixiGaming3con" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p>© 2025 Food Shop. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>