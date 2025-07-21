<?php
session_start();

define('UPLOAD_DIR', 'uploads');

$q = $_GET['q'] ?? '';

// --- BẢO MẬT: Ngăn chặn tấn công directory traversal ---
// Chỉ cho phép ký tự an toàn và định dạng folder/file.ext
if (!preg_match('/^[a-f0-9]{16}\/[a-f0-9]{40}\.[a-z0-9]+$/', $q)) {
    http_response_code(400);
    die('Liên kết không hợp lệ.');
}

$file_path = UPLOAD_DIR . '/' . $q;
$dir_path = dirname($file_path);
$filename = basename($file_path);
$password_file = $dir_path . '/password.txt';

// Kiểm tra xem file và thư mục có tồn tại không
if (!file_exists($file_path) || !is_dir($dir_path)) {
    http_response_code(404);
    die('Tệp không tồn tại hoặc đã bị xóa.');
}

$needs_password = file_exists($password_file);
$password_verified = false;
$error_message = '';

// Kiểm tra xem mật khẩu đã được xác thực trong session chưa
if (isset($_SESSION['password_verified'][$dir_path]) && $_SESSION['password_verified'][$dir_path] === true) {
    $password_verified = true;
}

// Xử lý khi người dùng nhập mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $submitted_password = $_POST['password'];
    $hash = file_get_contents($password_file);
    if (password_verify($submitted_password, $hash)) {
        $_SESSION['password_verified'][$dir_path] = true;
        $password_verified = true;
    } else {
        $error_message = 'Mật khẩu không chính xác.';
    }
}

// Xử lý yêu cầu tải xuống
if (isset($_GET['download']) && $_GET['download'] == '1') {
    if (!$needs_password || $password_verified) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        flush(); // Dọn dẹp bộ đệm
        readfile($file_path);
        exit;
    } else {
        // Nếu cố tình tải mà chưa nhập pass, chuyển về trang nhập pass
        header('Location: view.php?q=' . urlencode($q));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Tải xuống tệp - Chia Sẻ Tệp Tức Thì</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        html, body, h1, h2, h3, h4, h5, h6 { font-family: "Roboto", sans-serif; }
        body { background-color: #f1f1f1; }
        .download-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            padding: 32px;
            box-shadow: 0 4px 10px 0 rgba(0,0,0,0.2), 0 4px 20px 0 rgba(0,0,0,0.19);
            text-align: center;
            border-radius: 8px;
        }
        .download-btn {
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            cursor: pointer;
            width: 100%;
            border-radius: 5px;
            font-size: 20px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: background-color .3s;
        }
        .download-btn:hover { background-color: #45a049; }
        .file-info {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
        }
        .file-info p { margin: 5px 0; }
        .password-form input[type=password] {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .error { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>

<div class="download-container">
    <h1 class="w3-xxlarge">Tải Xuống Tệp</h1>

    <?php if ($needs_password && !$password_verified): ?>
        
        <h2><i class="fas fa-lock"></i> Yêu Cầu Mật Khẩu</h2>
        <p>Tệp này được bảo vệ bằng mật khẩu. Vui lòng nhập mật khẩu để tiếp tục.</p>
        
        <form method="post" class="password-form">
            <input type="password" name="password" placeholder="Nhập mật khẩu" required autofocus>
            <button type="submit" class="download-btn">Xác Nhận</button>
        </form>

        <?php if ($error_message): ?>
            <p class="error w3-margin-top"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <!-- ADS HERE -->
        <div class="w3-center w3-padding-16">
            <small>Đây là khu vực đặt quảng cáo</small>
        </div>

    <?php else: ?>
        
        <h2><i class="fas fa-file-download"></i> Tệp của bạn đã sẵn sàng</h2>
        <p>Nhấp vào nút bên dưới để bắt đầu tải xuống.</p>

        <div class="file-info">
            <p><strong><i class="fas fa-file"></i> Tên tệp:</strong> <?= htmlspecialchars(substr($filename, 41)) // Ẩn phần hash, chỉ hiển thị phần đuôi file nếu muốn. Hoặc bạn có thể lưu tên gốc vào một file .info và đọc ra ?>.</p>
            <p><strong><i class="fas fa-weight-hanging"></i> Kích thước:</strong> <?= round(filesize($file_path) / 1024 / 1024, 2) ?> MB</p>
        </div>

        <!-- ADS HERE -->
        <div class="w3-center w3-padding-16">
            <small>Đây là khu vực đặt quảng cáo</small>
        </div>
        
        <a href="?q=<?= htmlspecialchars($q) ?>&download=1" class="download-btn">
            <i class="fas fa-download"></i> Tải Xuống Ngay
        </a>
        <p class="w3-small w3-margin-top">Liên kết này sẽ tự động hết hạn.</p>

    <?php endif; ?>

</div>

</body>
</html>