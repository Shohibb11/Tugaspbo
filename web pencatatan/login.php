<?php
require_once 'db.php';
require_once 'auth.php';

// Redirect to dashboard if session already exists
redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'] ?? 'user';

                // Redirect to dashboard
                header("Location: index.php");
                exit;
            } else {
                $error = 'Username atau password salah!';
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
    <title>Login - Pencatatan Keuangan</title>
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
                <i class="fa-solid fa-wallet"></i>
            </div>
            <h4 class="mt-2 mb-0 fw-bold">FinTrack</h4>
            <span class="text-muted text-center" style="font-size: 0.85rem;">Pencatatan Keuangan</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px; font-size: 0.85rem;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px; color: var(--text-muted);"><i class="fa-regular fa-user"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="username" name="username" placeholder="Masukkan username" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px; color: var(--text-muted);"><i class="fa-regular fa-key"></i></span>
                    <input type="password" class="form-control bg-light border-start-0" id="password" name="password" placeholder="Masukkan password" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold mb-3" style="border-radius: 10px; background-color: var(--primary-color); border: none; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);">
                Masuk <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
            </button>

            <div class="text-center">
                <span class="text-muted" style="font-size: 0.8rem;">Belum punya akun? </span>
                <a href="register.php" class="fw-semibold text-decoration-none" style="font-size: 0.8rem; color: var(--primary-color);">Daftar Sekarang</a>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 Bundle JS (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
