<?php
session_start();
include 'config.php';

$login_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $login_msg = "<div class='alert alert-danger'>Vui lòng nhập đầy đủ email và mật khẩu!</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_msg = "<div class='alert alert-danger'>Email không hợp lệ!</div>";
    } elseif (strlen($password) < 8) {
        $login_msg = "<div class='alert alert-danger'>Mật khẩu phải có ít nhất 8 ký tự!</div>";
    } else {
        $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'] ?? 'Người dùng';
                $_SESSION['role'] = $user['role'] ?? 'user';
                mysqli_stmt_close($stmt);
                mysqli_close($connect);
                header("Location: product.php");
                exit();
            } else {
                $login_msg = "<div class='alert alert-danger'>Mật khẩu không đúng!</div>";
            }
        } else {
            $login_msg = "<div class='alert alert-danger'>Email không tồn tại!</div>";
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
    <title>Đăng Nhập - BTEC Sweet Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid d-flex justify-content-center align-items-center min-vh-100">
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Đăng Nhập</h3>
                        <?php echo $login_msg; ?>
                        <form action="login.php" method="POST" id="login-form">
                            <div class="mb-3">
                                <label for="login-email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="login-email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="login-password" class="form-label">Mật Khẩu</label>
                                <input type="password" class="form-control" id="login-password" name="password" required>
                                <small class="form-text text-muted">Mật khẩu phải có ít nhất 8 ký tự.</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Đăng Nhập</button>
                            <p class="text-center mt-3">
                                Chưa có tài khoản? <a href="login_register.html">Đăng Ký</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', function(event) {
            const password = document.getElementById('login-password').value;
            if (password.length < 8) {
                event.preventDefault();
                alert('Mật khẩu phải có ít nhất 8 ký tự!');
            }
        });
    </script>
</body>
</html>