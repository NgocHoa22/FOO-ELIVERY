<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

// Lấy thông tin vai trò và họ tên người dùng
$user_id = $_SESSION['user_id'];
$sql = "SELECT role, full_name, email, phone FROM users WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result) ?? ['role' => 'user', 'full_name' => 'Người dùng', 'email' => '', 'phone' => ''];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
mysqli_stmt_close($stmt);

$profile_msg = '';
$payment_msg = '';

// Cập nhật thông tin hồ sơ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($connect, $_POST['full_name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $phone = mysqli_real_escape_string($connect, $_POST['phone']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];

    if (empty($full_name) || empty($email)) {
        $profile_msg = "<div class='alert alert-danger'>Vui lòng nhập đầy đủ họ tên và email!</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_msg = "<div class='alert alert-danger'>Email không hợp lệ!</div>";
    } elseif (!empty($phone) && !preg_match("/^0\d{9}$/", $phone)) {
        $profile_msg = "<div class='alert alert-danger'>Số điện thoại không hợp lệ! Phải bắt đầu bằng 0 và có 10 chữ số.</div>";
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $phone, $password, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $profile_msg = "<div class='alert alert-success'>Cập nhật hồ sơ thành công!</div>";
            $_SESSION['full_name'] = $full_name; // Cập nhật session
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
        } else {
            $profile_msg = "<div class='alert alert-danger'>Cập nhật hồ sơ thất bại: " . mysqli_error($connect) . "</div>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Cập nhật thông tin thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_payment'])) {
    if ($_SESSION['role'] !== 'admin') {
        $payment_msg = "<div class='alert alert-danger'>Chỉ admin mới có quyền cập nhật thông tin thanh toán!</div>";
    } else {
        $selected_user_id = mysqli_real_escape_string($connect, $_POST['selected_user_id']);
        $card_number = mysqli_real_escape_string($connect, $_POST['card_number']);
        $expiry_date = mysqli_real_escape_string($connect, $_POST['expiry_date']);
        $cvv = mysqli_real_escape_string($connect, $_POST['cvv']);

        if (empty($card_number) || empty($expiry_date) || empty($cvv)) {
            $payment_msg = "<div class='alert alert-danger'>Vui lòng nhập đầy đủ thông tin thanh toán!</div>";
        } elseif (!preg_match("/^\d{16}$/", $card_number)) {
            $payment_msg = "<div class='alert alert-danger'>Số thẻ không hợp lệ (phải là 16 chữ số)!</div>";
        } elseif (!preg_match("/^(0[1-9]|1[0-2])\/\d{2}$/", $expiry_date)) {
            $payment_msg = "<div class='alert alert-danger'>Ngày hết hạn không hợp lệ (MM/YY)!</div>";
        } elseif (!preg_match("/^\d{3,4}$/", $cvv)) {
            $payment_msg = "<div class='alert alert-danger'>CVV không hợp lệ (3-4 chữ số)!</div>";
        } else {
            // Kiểm tra kết nối và bảng
            $sql_check = "SHOW TABLES LIKE 'payment_methods'";
            $result_check = mysqli_query($connect, $sql_check);
            if (mysqli_num_rows($result_check) == 0) {
                $payment_msg = "<div class='alert alert-danger'>Bảng payment_methods không tồn tại. Vui lòng tạo bảng!</div>";
            } else {
                // Kiểm tra xem đã có phương thức thanh toán chưa
                $sql = "SELECT id FROM payment_methods WHERE user_id = ? LIMIT 1";
                $stmt = mysqli_prepare($connect, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $selected_user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $payment_exists = mysqli_fetch_assoc($result);

                    if ($payment_exists) {
                        $sql = "UPDATE payment_methods SET card_number = ?, expiry_date = ?, cvv = ?, updated_at = NOW() WHERE user_id = ?";
                        $stmt = mysqli_prepare($connect, $sql);
                        mysqli_stmt_bind_param($stmt, "sssi", $card_number, $expiry_date, $cvv, $selected_user_id);
                    } else {
                        $sql = "INSERT INTO payment_methods (user_id, card_number, expiry_date, cvv, created_at) VALUES (?, ?, ?, ?, NOW())";
                        $stmt = mysqli_prepare($connect, $sql);
                        mysqli_stmt_bind_param($stmt, "isss", $selected_user_id, $card_number, $expiry_date, $cvv);
                    }

                    if ($stmt && mysqli_stmt_execute($stmt)) {
                        $payment_msg = "<div class='alert alert-success'>Cập nhật thông tin thanh toán thành công!</div>";
                    } else {
                        $payment_msg = "<div class='alert alert-danger'>Cập nhật thông tin thanh toán thất bại: " . mysqli_error($connect) . "</div>";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $payment_msg = "<div class='alert alert-danger'>Lỗi chuẩn bị truy vấn: " . mysqli_error($connect) . "</div>";
                }
            }
        }
    }
}

// Lấy lịch sử đơn hàng
$sql = "SELECT o.id, o.total_amount, o.status, o.shipping_address, o.created_at 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản - BTEC Sweet Shop</title>
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
            display: flex;
            justify-content: center;
        }

        .account-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
        }

        .account-section h3 {
            font-size: 20px;
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 14px;
            color: var(--secondary-color);
            font-weight: 500;
        }

        .form-control {
            border-radius: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
        }

        .form-control.invalid {
            border-color: #dc3545;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--hover-color);
        }

        .order-table {
            font-size: 14px;
        }

        .order-table th, .order-table td {
            vertical-align: middle;
            color: var(--secondary-color);
        }

        .payment-input {
            font-size: 14px;
        }

        .payment-input::-webkit-input-placeholder {
            color: #999;
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

        small {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .animate__fadeIn {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .form-search {
                max-width: 100%;
                margin: 10px 0;
            }

            .account-section {
                padding: 15px;
            }

            .order-table th, .order-table td {
                font-size: 13px;
            }

            .navbar-nav .nav-link {
                font-size: 14px;
                padding: 8px 10px;
            }

            .btn-primary {
                padding: 8px 15px;
                font-size: 14px;
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
                    <input type="text" name="search" placeholder="Tìm kiếm bánh kẹo..." class="form-control" aria-label="Tìm kiếm sản phẩm">
                    <button type="submit" class="btn" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
                </form>
                <div class="icon-cart">
                    <a href="cart.php" aria-label="Giỏ hàng"><img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Cart"></a>
                </div>
                <div class="icon-user dropdown d-flex align-items-center">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản" aria-haspopup="true">
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
                        <li class="nav-item"><a class="nav-link" href="product.php">Tất Cả Sản Phẩm</a></li>
                        <li class="nav-item"><a class="nav-link active" href="account.php" aria-current="page">Tài Khoản</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Giỏ Hàng</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Liên Hệ</a></li>
                        <li class="nav-item"><a class="nav-link" href="order_tracking.php">Theo dõi đơn hàng</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="content container-fluid animate__fadeIn">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10 col-12">
                    <div class="account-section">
                        <h3>Hồ Sơ</h3>
                        <?php echo $profile_msg; ?>
                        <form action="" method="POST" class="row g-3" id="profile-form">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Họ và Tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Số Điện Thoại</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                <small>Số điện thoại phải bắt đầu bằng 0 và có 10 chữ số (VD: 0123456789).</small>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mật Khẩu Mới (để trống nếu không đổi)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Cập Nhật Hồ Sơ</button>
                            </div>
                        </form>
                    </div>
                    <div class="account-section mt-5" id="orders">
                        <h3>Lịch Sử Đơn Hàng</h3>
                        <?php if (empty($orders)): ?>
                            <p class="text-center">Bạn chưa có đơn hàng nào.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered order-table">
                                    <thead>
                                        <tr>
                                            <th>Mã Đơn</th>
                                            <th>Tổng Tiền</th>
                                            <th>Trạng Thái</th>
                                            <th>Địa Chỉ Giao Hàng</th>
                                            <th>Ngày Đặt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                                <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                                <td><?php echo htmlspecialchars($order['status']); ?></td>
                                                <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                                                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="account-section mt-5">
                        <h3>Thông Tin Thanh Toán</h3>
                        <?php
                        // Lấy thông tin thanh toán hiện tại (nếu có)
                        $payment = ['card_number' => '', 'expiry_date' => '', 'cvv' => ''];
                        if ($_SESSION['role'] === 'admin' && isset($_POST['selected_user_id'])) {
                            $selected_user_id = mysqli_real_escape_string($connect, $_POST['selected_user_id']);
                            $sql = "SELECT card_number, expiry_date, cvv FROM payment_methods WHERE user_id = ? LIMIT 1";
                            $stmt = mysqli_prepare($connect, $sql);
                            if ($stmt) {
                                mysqli_stmt_bind_param($stmt, "i", $selected_user_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                $payment = mysqli_fetch_assoc($result) ?? ['card_number' => '', 'expiry_date' => '', 'cvv' => ''];
                                mysqli_stmt_close($stmt);
                            } else {
                                $payment_msg = "<div class='alert alert-danger'>Lỗi chuẩn bị truy vấn thanh toán: " . mysqli_error($connect) . "</div>";
                            }
                        }
                        echo $payment_msg;
                        ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <form action="" method="POST" class="row g-3">
                                <input type="hidden" name="update_payment" value="1">
                                <div class="col-md-6">
                                    <label for="selected_user_id" class="form-label">Chọn Người Dùng</label>
                                    <select class="form-control" id="selected_user_id" name="selected_user_id" required>
                                        <option value="">-- Chọn người dùng --</option>
                                        <?php
                                        $sql_users = "SELECT id, full_name FROM users";
                                        $result_users = mysqli_query($connect, $sql_users);
                                        while ($row = mysqli_fetch_assoc($result_users)) {
                                            $selected = (isset($_POST['selected_user_id']) && $_POST['selected_user_id'] == $row['id']) ? 'selected' : '';
                                            echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['full_name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="card_number" class="form-label">Số Thẻ (16 chữ số)</label>
                                    <input type="text" class="form-control payment-input" id="card_number" name="card_number" value="<?php echo htmlspecialchars($payment['card_number']); ?>" placeholder="1234567890123456" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="expiry_date" class="form-label">Ngày Hết Hạn (MM/YY)</label>
                                    <input type="text" class="form-control payment-input" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($payment['expiry_date']); ?>" placeholder="12/25" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="cvv" class="form-label">CVV (3-4 chữ số)</label>
                                    <input type="text" class="form-control payment-input" id="cvv" name="cvv" value="<?php echo htmlspecialchars($payment['cvv']); ?>" placeholder="123" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Lưu Thông Tin Thanh Toán</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="text-center">Chỉ quản trị viên mới có quyền chỉnh sửa thông tin thanh toán.</p>
                        <?php endif; ?>
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
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p>© 2025 BTEC Sweet Shop. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    <?php
    // Đóng kết nối cơ sở dữ liệu
    mysqli_close($connect);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side validation for phone number
        const phoneInput = document.getElementById('phone');
        const profileForm = document.getElementById('profile-form');

        phoneInput.addEventListener('input', function() {
            const phone = this.value;
            if (phone && !/^0\d{9}$/.test(phone)) {
                this.classList.add('invalid');
            } else {
                this.classList.remove('invalid');
            }
        });

        profileForm.addEventListener('submit', function(event) {
            const phone = phoneInput.value;
            if (phone && !/^0\d{9}$/.test(phone)) {
                event.preventDefault();
                alert('Số điện thoại không hợp lệ! Phải bắt đầu bằng 0 và có 10 chữ số.');
                phoneInput.focus();
            }
        });
    </script>
</body>
</html>