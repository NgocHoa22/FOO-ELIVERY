<?php
session_start(); // Start session at the top
$register_msg = '';
$login_msg = '';
$error_msg = '';

$servername = "localhost"; // Set your database host (e.g., "localhost")
$username = "root"; // Database username
$password = ""; // Database password
$db = "seo01_food"; // Database name
$connect = mysqli_connect($servername, $username, $password, $db);

if (!$connect) {
    $error_msg = "<p class='text-red-500 text-center'>Kết nối CSDL thất bại: " . mysqli_connect_error() . "</p>";
    // Do not use die() to allow form rendering
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['form_type']) && $_POST['form_type'] == 'register') {
            $full_name = mysqli_real_escape_string($connect, $_POST["full_name"]);
            $password = $_POST["password"];
            $email = mysqli_real_escape_string($connect, $_POST["email"]);
            $role = 'user'; // Default role as per schema

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $register_msg = "<p class='text-red-500 text-center'>Email không hợp lệ!</p>";
            } elseif (strlen($password) <= 8) {
                $register_msg = "<p class='text-red-500 text-center'>Mật khẩu phải có trên 8 ký tự!</p>";
            } elseif (!preg_match('/\d/', $password)) {
                $register_msg = "<p class='text-red-500 text-center'>Mật khẩu phải chứa ít nhất một chữ số!</p>";
            } else {
                $password = password_hash($password, PASSWORD_DEFAULT);
                $sql_check = "SELECT full_name, email FROM users WHERE full_name = ? OR email = ?";
                $stmt_check = mysqli_prepare($connect, $sql_check);
                mysqli_stmt_bind_param($stmt_check, "ss", $full_name, $email);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);

                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    $register_msg = "<p class='text-red-500 text-center'>Tên người dùng hoặc email đã tồn tại!</p>";
                } else {
                    $sql = "INSERT INTO users (full_name, password, email, role) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($connect, $sql);
                    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $password, $email, $role);
                    $result = mysqli_stmt_execute($stmt);
                    if ($result) {
                        $register_msg = "<p class='text-green-500 text-center'>Đăng ký thành công! Vui lòng đăng nhập.</p>";
                    } else {
                        $register_msg = "<p class='text-red-500 text-center'>Đăng ký thất bại: " . mysqli_error($connect) . "</p>";
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_stmt_close($stmt_check);
            }
        } elseif (isset($_POST['form_type']) && $_POST['form_type'] == 'login') {
            $email = mysqli_real_escape_string($connect, $_POST["email"]);
            $password = $_POST["password"];

            $sql = "SELECT id, full_name, password FROM users WHERE email = ?";
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    header("Location: product.php");
                    exit();
                } else {
                    $login_msg = "<p class='text-red-500 text-center'>Mật khẩu không đúng!</p>";
                }
            } else {
                $login_msg = "<p class='text-red-500 text-center'>Email không tồn tại!</p>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($connect);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Đăng Ký & Đăng Nhập</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1505576399279-565b52d4ac71?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4); /* Overlay tối để chữ nổi bật */
            z-index: 0;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.95); /* Nền mờ để nổi bật trên ảnh */
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            transition: transform 0.4s ease, opacity 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        .form-container.hidden {
            transform: translateY(50px);
            opacity: 0;
            position: absolute;
            pointer-events: none;
        }
        .form-container.active {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .form-container .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-container .logo img {
            height: 60px;
            transition: transform 0.3s ease;
        }
        .form-container .logo img:hover {
            transform: scale(1.1);
        }
        .form-container h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
        }
        .form-container label {
            color: #34495e;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-container input {
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            padding: 0.75rem;
            width: 100%;
            background-color: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-container input:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
            outline: none;
        }
        .form-container button {
            background: linear-gradient(to right, #2ecc71, #27ae60);
            color: #fff;
            font-weight: 600;
            padding: 0.85rem;
            border-radius: 8px;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            cursor: pointer;
        }
        .form-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .form-container button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-container a {
            color: #2ecc71;
            font-weight: 500;
            transition: color 0.2s ease;
            text-decoration: none;
        }
        .form-container a:hover {
            color: #27ae60;
        }
        .form-container p {
            color: #7f8c8d;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .form-container small {
            color: #7f8c8d;
            font-size: 0.8rem;
            display: block;
            margin-top: 0.5rem;
        }
        @media (max-width: 640px) {
            .form-container {
                padding: 1.5rem;
                max-width: 90%;
            }
            .form-container .logo img {
                height: 50px;
            }
            .form-container h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="relative w-full max-w-md p-6">
        <div id="registerForm" class="form-container <?php echo $register_msg ? 'active' : 'hidden'; ?>">
            <div class="logo">
                <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Sweet Shop Logo">
            </div>
            <h2>Đăng Ký</h2>
            <?php echo $error_msg; ?>
            <?php echo $register_msg; ?>
            <form action="" method="POST" class="space-y-4" id="register-form">
                <input type="hidden" name="form_type" value="register">
                <div>
                    <label for="regFullName" class="block">Họ và tên</label>
                    <input type="text" id="regFullName" name="full_name" class="mt-1" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>
                <div>
                    <label for="regEmail" class="block">Email</label>
                    <input type="email" id="regEmail" name="email" class="mt-1" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div>
                    <label for="regPassword" class="block">Mật khẩu</label>
                    <input type="password" id="regPassword" name="password" class="mt-1" required>
                    <small>Mật khẩu phải có trên 8 ký tự và chứa ít nhất một chữ số.</small>
                </div>
                <button type="submit">Đăng Ký</button>
            </form>
            <p>Đã có tài khoản? <a href="#" onclick="toggleForm()">Đăng nhập</a></p>
        </div>

        <div id="loginForm" class="form-container <?php echo $register_msg ? 'hidden' : 'active'; ?>">
            <div class="logo">
                <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Sweet Shop Logo">
            </div>
            <h2>Đăng Nhập</h2>
            <?php echo $error_msg; ?>
            <?php echo $login_msg; ?>
            <form action="" method="POST" class="space-y-4" id="login-form">
                <input type="hidden" name="form_type" value="login">
                <div>
                    <label for="loginEmail" class="block">Email</label>
                    <input type="email" id="loginEmail" name="email" class="mt-1" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div>
                    <label for="loginPassword" class="block">Mật khẩu</label>
                    <input type="password" id="loginPassword" name="password" class="mt-1" required>
                </div>
                <button type="submit">Đăng Nhập</button>
            </form>
            <p>Chưa có tài khoản? <a href="#" onclick="toggleForm()">Đăng ký</a></p>
        </div>
    </div>

    <script>
        function toggleForm() {
            const registerForm = document.getElementById('registerForm');
            const loginForm = document.getElementById('loginForm');
            registerForm.classList.toggle('active');
            registerForm.classList.toggle('hidden');
            loginForm.classList.toggle('active');
            loginForm.classList.toggle('hidden');
        }

        // Client-side validation for register form
        document.getElementById('register-form').addEventListener('submit', function(event) {
            const password = document.getElementById('regPassword').value;
            const digitCount = (password.match(/\d/g) || []).length;
            if (password.length <= 8) {
                event.preventDefault();
                alert('Mật khẩu phải có trên 8 ký tự!');
            } else if (digitCount === 0) {
                event.preventDefault();
                alert('Mật khẩu phải chứa ít nhất một chữ số!');
            }
        });
    </script>
</body>
</html>