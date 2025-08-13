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

// Lấy thông tin vai trò và họ tên người dùng
$user_id = $_SESSION['user_id'];
$sql = "SELECT role, full_name FROM users WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($user = mysqli_fetch_assoc($result)) {
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['full_name'] = $user['full_name'] ?? 'Người dùng';
} else {
    echo "<div class='alert alert-danger'>Không tìm thấy thông tin người dùng. Vui lòng kiểm tra cơ sở dữ liệu.</div>";
    $_SESSION['role'] = 'user';
    $_SESSION['full_name'] = 'Người dùng';
}
mysqli_stmt_close($stmt);

$contact_msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $message = mysqli_real_escape_string($connect, $_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        $contact_msg = "<div class='alert alert-danger'>Vui lòng nhập đầy đủ thông tin!</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_msg = "<div class='alert alert-danger'>Email không hợp lệ!</div>";
    } else {
        $sql = "INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
        if (mysqli_stmt_execute($stmt)) {
            $contact_msg = "<div class='alert alert-success'>Gửi liên hệ thành công! Chúng tôi sẽ phản hồi sớm.</div>";
        } else {
            $contact_msg = "<div class='alert alert-danger'>Gửi liên hệ thất bại: " . mysqli_error($connect) . "</div>";
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ - BTEC Sweet Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF5733; /* Vibrant orange-red for food delivery vibe */
            --secondary-color: #333333; /* Dark gray for text */
            --accent-color: #FFE4E1; /* Soft pink for highlights */
            --background-color: #F8F9FA; /* Light gray background */
            --hover-color: #C0392B; /* Darker shade for hover effects */
            --button-color: #28A745; /* Green for CTAs like "Add to Cart" */
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
            max-width: 500px;
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

        .contact-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .contact-section h3 {
            font-size: 24px;
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .contact-section .form-label {
            font-size: 14px;
            color: var(--secondary-color);
            font-weight: 500;
        }

        .contact-section .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 14px;
            border: 1px solid #ddd;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-section .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(255, 87, 51, 0.3);
            outline: none;
        }

        .contact-section textarea.form-control {
            resize: vertical;
        }

        .contact-section .btn {
            padding: 10px;
            font-size: 14px;
            background-color: var(--button-color);
            color: #fff;
            border: none;
            border-radius: 25px;
            transition: background-color 0.3s ease;
            width: 100%;
            max-width: 200px;
        }

        .contact-section .btn:hover {
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

            .contact-section {
                padding: 15px;
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
                    <input type="text" name="search" placeholder="Tìm kiếm bánh kẹo..." class="form-control" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" aria-label="Tìm kiếm sản phẩm">
                    <button type="submit" class="btn" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
                </form>
                <div class="icon-cart">
                    <a href="cart.php" aria-label="Giỏ hàng"><img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Cart"></a>
                </div>
                <div class="icon-user dropdown d-flex align-items-center">
                    <a href="account.php" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản" aria-haspopup="true">
                        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="User">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="account.php">Hồ Sơ</a></li>
                        <li><a class="dropdown-item" href="account.php#orders">Đơn Hàng</a></li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="admin.php">Quản Trị</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php">Đăng Xuất</a></li>
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
                        <li class="nav-item"><a class="nav-link" href="product.php">Tất Cả Sản Phẩm</a></li>
                        <li class="nav-item"><a class="nav-link" href="account.php">Tài Khoản</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Giỏ Hàng</a></li>
                        <li class="nav-item"><a class="nav-link active" href="contact.php" aria-current="page">Liên Hệ</a></li>
                        <li class="nav-item"><a class="nav-link" href="order_tracking.php">Theo dõi đơn hàng</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="content container-fluid animate__fadeIn">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-12">
                    <div class="contact-section">
                        <h3>Liên Hệ</h3>
                        <?php echo $contact_msg; ?>
                        <form action="" method="POST" class="row g-3">
                            <input type="hidden" name="submit_contact" value="1">
                            <div class="col-12">
                                <label for="name" class="form-label">Họ và Tên</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Tin Nhắn</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn">Gửi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h5>Giới Thiệu</h5>
                        <p>BTEC Sweet Shop mang đến những loại bánh kẹo ngon, chất lượng cao, lan tỏa niềm vui ngọt ngào cho mọi nhà.</p>
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
                    <p>© 2025 BTEC Sweet Shop. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>