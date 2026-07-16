<?php
require_once 'db.php';
require_once 'auth.php';

redirect_if_logged_in();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            
            if ($stmt->fetch()) {
                $error = 'Username sudah terdaftar!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare("INSERT INTO users (name, username, password) VALUES (:name, :username, :password)");
                $insertStmt->execute([
                    'name' => $name,
                    'username' => $username,
                    'password' => $hashed_password
                ]);

                $success = 'Pendaftaran berhasil! Silakan login.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Pencatatan Keuangan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-wrapper">

    <div class="glass-card auth-card animated-fade">
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h4 class="mt-2 mb-0 fw-bold">Daftar Akun</h4>
            <span class="text-muted text-center" style="font-size: 0.85rem;">Buat akun baru</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px; font-size: 0.85rem;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; font-size: 0.85rem;">
                <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Nama Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px; color: var(--text-muted);"><i class="fa-regular fa-id-card"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="name" name="name" placeholder="Masukkan nama lengkap" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px; color: var(--text-muted);"><i class="fa-regular fa-user"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="username" name="username" placeholder="Buat username" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px; color: var(--text-muted);"><i class="fa-regular fa-key"></i></span>
                    <input type="password" class="form-control bg-light border-start-0" id="password" name="password" placeholder="Masukkan password" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Konfirmasi Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px; color: var(--text-muted);"><i class="fa-regular fa-circle-check"></i></span>
                    <input type="password" class="form-control bg-light border-start-0" id="confirm_password" name="confirm_password" placeholder="Ulangi password" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold mb-3" style="border-radius: 10px; background-color: var(--primary-color); border: none; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);">
                Daftar Akun <i class="fa-solid fa-user-plus ms-2"></i>
            </button>

            <div class="text-center">
                <span class="text-muted" style="font-size: 0.8rem;">Sudah punya akun? </span>
                <a href="login.php" class="fw-semibold text-decoration-none" style="font-size: 0.8rem; color: var(--primary-color);">Login disini</a>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
