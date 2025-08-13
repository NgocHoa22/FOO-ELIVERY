<?php
session_start();
include 'config.php';

$register_msg = '';
$login_msg = '';
$error_msg = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_lockout'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "<div class='alert alert-danger text-center'>CSRF token không hợp lệ!</div>";
    } elseif ($_SESSION['login_attempts'] >= 3 && time() < $_SESSION['login_lockout'] + 300) {
        $login_msg = "<div class='alert alert-danger text-center'>Tài khoản bị khóa tạm thời. Thử lại sau 5 phút.</div>";
    } else {
        if (isset($_POST['form_type']) && $_POST['form_type'] == 'register') {
            $full_name = mysqli_real_escape_string($connect, $_POST['full_name']);
            $email = mysqli_real_escape_string($connect, $_POST['email']);
            $phone = mysqli_real_escape_string($connect, $_POST['phone']);
            $password = $_POST['password'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $register_msg = "<div class='alert alert-danger text-center'>Email không hợp lệ!</div>";
            } elseif (strlen($password) <= 8) {
                $register_msg = "<div class='alert alert-danger text-center'>Mật khẩu phải có trên 8 ký tự!</div>";
            } elseif (!preg_match('/^\D*\d\D*\d\D*$/', $password)) {
                $register_msg = "<div class='alert alert-danger text-center'>Mật khẩu phải chứa đúng 2 chữ số!</div>";
            } else {
                $password = password_hash($password, PASSWORD_DEFAULT);
                $sql_check = "SELECT email FROM users WHERE email = ?";
                $stmt_check = mysqli_prepare($connect, $sql_check);
                mysqli_stmt_bind_param($stmt_check, "s", $email);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);

                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    $register_msg = "<div class='alert alert-danger text-center'>Email đã tồn tại!</div>";
                } else {
                    $sql = "INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')";
                    $stmt = mysqli_prepare($connect, $sql);
                    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $password);
                    if (mysqli_stmt_execute($stmt)) {
                        $register_msg = "<div class='alert alert-success text-center'>Đăng ký thành công! Vui lòng đăng nhập.</div>";
                    } else {
                        $register_msg = "<div class='alert alert-danger text-center'>Đăng ký thất bại: " . mysqli_error($connect) . "</div>";
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_stmt_close($stmt_check);
            }
        } elseif (isset($_POST['form_type']) && $_POST['form_type'] == 'login') {
            $email = mysqli_real_escape_string($connect, $_POST['email']);
            $password = $_POST['password'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 3) {
                    $_SESSION['login_lockout'] = time();
                }
                $login_msg = "<div class='alert alert-danger text-center'>Email không hợp lệ!</div>";
            } elseif (strlen($password) <= 8) {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 3) {
                    $_SESSION['login_lockout'] = time();
                }
                $login_msg = "<div class='alert alert-danger text-center'>Mật khẩu phải có trên 8 ký tự!</div>";
            } else {
                $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
                $stmt = mysqli_prepare($connect, $sql);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $user = mysqli_fetch_assoc($result);
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['full_name'] = $user['full_name'] ?? 'Người dùng';
                        $_SESSION['role'] = $user['role'] ?? 'user';
                        $_SESSION['login_attempts'] = 0; // Reset attempts on success
                        mysqli_stmt_close($stmt);
                        mysqli_close($connect);
                        header("Location: product.php");
                        exit();
                    } else {
                        $_SESSION['login_attempts']++;
                        if ($_SESSION['login_attempts'] >= 3) {
                            $_SESSION['login_lockout'] = time();
                        }
                        $login_msg = "<div class='alert alert-danger text-center'>Mật khẩu không đúng!</div>";
                    }
                } else {
                    $_SESSION['login_attempts']++;
                    if ($_SESSION['login_attempts'] >= 3) {
                        $_SESSION['login_lockout'] = time();
                    }
                    $login_msg = "<div class='alert alert-danger text-center'>Email không tồn tại!</div>";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Refresh CSRF token
}

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập / Đăng Ký - BTEC Sweet Shop</title>
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
                        <!-- Login Form -->
                        <div id="loginForm" class="form-container <?php echo $register_msg ? 'd-none' : 'd-block'; ?>">
                            <h3 class="text-center mb-4">Đăng Nhập</h3>
                            <?php echo $error_msg; ?>
                            <?php echo $login_msg; ?>
                            <form action="" method="POST" id="login-form">
                                <input type="hidden" name="form_type" value="login">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="loginEmail" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Mật Khẩu</label>
                                    <input type="password" class="form-control" id="loginPassword" name="password" required>
                                    <small class="form-text text-muted">Mật khẩu phải có trên 8 ký tự.</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Đăng Nhập</button>
                                <p class="text-center mt-3">
                                    Chưa có tài khoản? <a href="#" onclick="toggleForm()">Đăng Ký</a>
                                </p>
                            </form>
                        </div>
                        <!-- Register Form -->
                        <div id="registerForm" class="form-container <?php echo $register_msg ? 'd-block' : 'd-none'; ?>">
                            <h3 class="text-center mb-4">Đăng Ký</h3>
                            <?php echo $error_msg; ?>
                            <?php echo $register_msg; ?>
                            <form action="" method="POST" id="register-form">
                                <input type="hidden" name="form_type" value="register">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="mb-3">
                                    <label for="regFullName" class="form-label">Họ và Tên</label>
                                    <input type="text" class="form-control" id="regFullName" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="regEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="regEmail" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="regPhone" class="form-label">Số Điện Thoại</label>
                                    <input type="tel" class="form-control" id="regPhone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="regPassword" class="form-label">Mật Khẩu</label>
                                    <input type="password" class="form-control" id="regPassword" name="password" required>
                                    <small class="form-text text-muted">Mật khẩu phải có trên 8 ký tự và đúng 2 chữ số.</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Đăng Ký</button>
                                <p class="text-center mt-3">
                                    Đã có tài khoản? <a href="#" onclick="toggleForm()">Đăng Nhập</a>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            loginForm.classList.toggle('d-none');
            loginForm.classList.toggle('d-block');
            registerForm.classList.toggle('d-none');
            registerForm.classList.toggle('d-block');
        }

        // Client-side validation for login form
        document.getElementById('login-form').addEventListener('submit', function(event) {
            const password = document.getElementById('loginPassword').value;
            if (password.length <= 8) {
                event.preventDefault();
                alert('Mật khẩu phải có trên 8 ký tự!');
            }
        });

        // Client-side validation for register form
        document.getElementById('register-form').addEventListener('submit', function(event) {
            const password = document.getElementById('regPassword').value;
            const digitCount = (password.match(/\d/g) || []).length;
            if (password.length <= 8) {
                event.preventDefault();
                alert('Mật khẩu phải có trên 8 ký tự!');
            } else if (digitCount !== 2) {
                event.preventDefault();
                alert('Mật khẩu phải chứa đúng 2 chữ số!');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>