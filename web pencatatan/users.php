<?php
require_once 'db.php';
require_once 'auth.php';

check_admin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'] ?? 'user';
$is_admin = ($user_role === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($name) || empty($username) || empty($password)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Semua kolom wajib diisi!'];
        } elseif (strlen($password) < 6) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Password minimal harus 6 karakter!'];
        } else {
            try {
                // Check if username is already taken
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
                $checkStmt->execute(['username' => $username]);
                if ($checkStmt->fetch()) {
                    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Username sudah terdaftar!'];
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (:name, :username, :password, :role)");
                    $insertStmt->execute([
                        'name' => $name,
                        'username' => $username,
                        'password' => $hashed_password,
                        'role' => $role
                    ]);
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pengguna berhasil ditambahkan!'];
                }
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
            }
        }
        header("Location: users.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($name) || empty($username)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Nama dan Username wajib diisi!'];
        } else {
            try {
                // Check username uniqueness (excluding current editing user)
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
                $checkStmt->execute(['username' => $username, 'id' => $id]);
                if ($checkStmt->fetch()) {
                    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Username sudah digunakan oleh pengguna lain!'];
                } else {
                    // Prevent suicide: cannot change own role to user
                    if ($id === $user_id && $role !== 'admin') {
                        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Anda tidak bisa mengubah peran admin Anda sendiri!'];
                    } else {
                        if (!empty($password)) {
                            if (strlen($password) < 6) {
                                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Password baru minimal harus 6 karakter!'];
                                header("Location: users.php");
                                exit;
                            }
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $pdo->prepare("UPDATE users SET name = :name, username = :username, password = :password, role = :role WHERE id = :id");
                            $updateStmt->execute([
                                'name' => $name,
                                'username' => $username,
                                'password' => $hashed_password,
                                'role' => $role,
                                'id' => $id
                            ]);
                        } else {
                            $updateStmt = $pdo->prepare("UPDATE users SET name = :name, username = :username, role = :role WHERE id = :id");
                            $updateStmt->execute([
                                'name' => $name,
                                'username' => $username,
                                'role' => $role,
                                'id' => $id
                            ]);
                        }
                        
                        if ($id === $user_id) {
                            $_SESSION['name'] = $name;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role;
                        }
                        
                        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Detail pengguna berhasil diperbarui!'];
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
            }
        }
        header("Location: users.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id === $user_id) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Anda tidak dapat menghapus akun Anda sendiri!'];
        } else {
            try {
                $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $deleteStmt->execute(['id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pengguna berhasil dihapus beserta seluruh datanya!'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menghapus pengguna: ' . $e->getMessage()];
            }
        }
        header("Location: users.php");
        exit;
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, name ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat pengguna: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - FinTrack</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .badge-admin {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        .badge-user {
            background-color: #f1f5f9;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 8px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR (Desktop View) -->
    <aside class="sidebar">
        <div class="logo-wrapper">
            <div class="logo-icon text-white">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="logo-text">FinTrack</div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <?php if ($is_admin): ?>
            <a href="users.php" class="sidebar-link active">
                <i class="fa-solid fa-users"></i>
                <span>Kelola Pengguna</span>
            </a>
            <?php endif; ?>
            <a href="transactions.php" class="sidebar-link">
                <i class="fa-solid fa-money-bill-transfer"></i>
                <span>Transaksi</span>
            </a>
            <a href="reports.php" class="sidebar-link">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Laporan</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-3 p-2 mb-3 bg-white bg-opacity-10 rounded-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 700;">
                    <?= strtoupper(substr($user_name, 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <h6 class="mb-0 text-white text-truncate" style="font-size: 0.85rem;"><?= htmlspecialchars($user_name) ?></h6>
                    <small class="text-white-50 text-truncate d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($user_role === 'admin' ? 'Administrator' : 'Pengguna') ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-light w-100 border-opacity-25" style="border-radius: 10px; font-size: 0.85rem;">
                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout
            </a>
        </div>
    </aside>

    <!-- MOBILE HEADER -->
    <header class="mobile-header">
        <div class="d-flex align-items-center gap-2">
            <div class="logo-icon text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 8px; font-size: 1rem;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <span class="fw-bold" style="font-size: 1.1rem; color: var(--primary-color);">FinTrack</span>
        </div>
        <div class="dropdown">
            <button class="btn border-0 p-0 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 32px; height: 32px; font-weight: 700; font-size: 0.85rem;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?= strtoupper(substr($user_name, 0, 1)) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px; font-size: 0.9rem;">
                <li><div class="dropdown-item-text fw-semibold"><?= htmlspecialchars($user_name) ?></div></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- BOTTOM NAV (Mobile View) -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>
        <?php if ($is_admin): ?>
        <a href="users.php" class="bottom-nav-item active">
            <i class="fa-solid fa-users"></i>
            <span>Pengguna</span>
        </a>
        <?php endif; ?>
        <a href="transactions.php" class="bottom-nav-item">
            <i class="fa-solid fa-money-bill-transfer"></i>
            <span>Transaksi</span>
        </a>
        <a href="reports.php" class="bottom-nav-item">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content animated-fade">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Pengguna</h4>
                <p class="text-secondary mb-0" style="font-size: 0.9rem;">Kelola pengguna terdaftar dan hak akses sistem.</p>
            </div>
            <button class="btn btn-primary px-4 py-2.5 fw-semibold d-flex align-items-center gap-2 w-100 w-sm-auto justify-content-center" style="border-radius: 12px; background-color: var(--primary-color); border: none;" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
            </button>
        </div>

        <!-- Session Flash Alerts -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 12px;">
                <i class="fa-solid <?= $_SESSION['flash']['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
                <?= $_SESSION['flash']['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Table Card -->
        <div class="glass-card p-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Peran</th>
                            <th>Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted" style="font-size: 0.9rem;">
                                    <i class="fa-solid fa-users d-block mb-2" style="font-size: 2rem;"></i>
                                    Tidak ada pengguna lain terdaftar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr style="font-size: 0.9rem;">
                                    <td class="text-secondary"><?= $u['id'] ?></td>
                                    <td class="fw-semibold text-dark">
                                        <?= htmlspecialchars($u['name']) ?>
                                        <?php if ($u['id'] === $user_id): ?>
                                            <span class="text-muted fw-normal ms-1">(Anda)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary"><?= htmlspecialchars($u['username']) ?></td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge-admin"><i class="fa-solid fa-user-shield me-1"></i>Admin</span>
                                        <?php else: ?>
                                            <span class="badge-user"><i class="fa-solid fa-user me-1"></i>User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- View Transactions -->
                                            <a href="transactions.php?view_user_id=<?= $u['id'] ?>" 
                                               class="btn btn-sm btn-outline-success border-0" 
                                               style="border-radius: 8px;"
                                               title="Lihat Transaksi">
                                                <i class="fa-solid fa-money-bill-transfer"></i>
                                            </a>
                                            <!-- View Reports -->
                                            <a href="reports.php?view_user_id=<?= $u['id'] ?>" 
                                               class="btn btn-sm btn-outline-info border-0" 
                                               style="border-radius: 8px;"
                                               title="Lihat Laporan">
                                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                            </a>
                                            <!-- Edit User Button -->
                                            <button class="btn btn-sm btn-outline-primary btn-edit-user border-0" 
                                                    style="border-radius: 8px;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    data-id="<?= $u['id'] ?>"
                                                    data-name="<?= htmlspecialchars($u['name']) ?>"
                                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                                    data-role="<?= $u['role'] ?>"
                                                    title="Edit Pengguna">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            
                                            <!-- Delete User Button -->
                                            <?php if ($u['id'] !== $user_id): ?>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-user border-0" 
                                                        style="border-radius: 8px;"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteUserModal"
                                                        data-id="<?= $u['id'] ?>"
                                                        title="Hapus Pengguna">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-danger border-0 opacity-25" style="border-radius: 8px;" disabled title="Tidak dapat menghapus diri sendiri">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODALS SECTION -->

    <!-- A. ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addUserModalLabel">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="users.php" method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        
                        <div class="mb-3">
                            <label for="add-name" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light" id="add-name" name="name" placeholder="Nama lengkap" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-3">
                            <label for="add-username" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Username</label>
                            <input type="text" class="form-control bg-light" id="add-username" name="username" placeholder="Buat username" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-3">
                            <label for="add-password" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Password</label>
                            <input type="password" class="form-control bg-light" id="add-password" name="password" placeholder="Password (min 6 karakter)" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-2">
                            <label for="add-role" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Peran Sistem</label>
                            <select class="form-select bg-light" id="add-role" name="role" style="border-radius: 10px; font-size: 0.9rem;" required>
                                <option value="user" selected>User (Akses Keuangan Mandiri)</option>
                                <option value="admin">Admin (Akses Pengelolaan Sistem)</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="border-radius: 10px; font-size: 0.9rem;">Batal</button>
                        <button type="submit" class="btn btn-primary px-4" style="border-radius: 10px; font-size: 0.9rem; background-color: var(--primary-color); border: none;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- B. EDIT USER MODAL -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editUserModalLabel">Ubah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="users.php" method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit-user-id" name="id">
                    <div class="modal-body">
                        
                        <div class="mb-3">
                            <label for="edit-user-name" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light" id="edit-user-name" name="name" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-user-username" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Username</label>
                            <input type="text" class="form-control bg-light" id="edit-user-username" name="username" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-user-password" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Password Baru (Opsional)</label>
                            <input type="password" class="form-control bg-light" id="edit-user-password" name="password" placeholder="Kosongkan jika tidak ingin diubah" style="border-radius: 10px; font-size: 0.9rem;">
                        </div>

                        <div class="mb-2">
                            <label for="edit-user-role" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Peran Sistem</label>
                            <select class="form-select bg-light" id="edit-user-role" name="role" style="border-radius: 10px; font-size: 0.9rem;" required>
                                <option value="user">User (Akses Keuangan Mandiri)</option>
                                <option value="admin">Admin (Akses Pengelolaan Sistem)</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="border-radius: 10px; font-size: 0.9rem;">Batal</button>
                        <button type="submit" class="btn btn-primary px-4" style="border-radius: 10px; font-size: 0.9rem; background-color: var(--primary-color); border: none;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- C. DELETE USER CONFIRMATION MODAL -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger" id="deleteUserModalLabel">Hapus Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="users.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete-user-id" name="id">
                    <div class="modal-body py-3">
                        <p class="mb-0 text-secondary" style="font-size: 0.9rem;">Apakah Anda yakin ingin menghapus pengguna ini? Semua data transaksi pengguna ini juga akan <strong>dihapus permanen</strong>.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="border-radius: 10px; font-size: 0.85rem;">Batal</button>
                        <button type="submit" class="btn btn-danger px-3" style="border-radius: 10px; font-size: 0.85rem;">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Edit User Modal Handler
        const editUserButtons = document.querySelectorAll('.btn-edit-user');
        editUserButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const username = this.getAttribute('data-username');
                const role = this.getAttribute('data-role');

                document.getElementById('edit-user-id').value = id;
                document.getElementById('edit-user-name').value = name;
                document.getElementById('edit-user-username').value = username;
                document.getElementById('edit-user-role').value = role;
            });
        });

        // Delete User Modal Handler
        const deleteUserButtons = document.querySelectorAll('.btn-delete-user');
        deleteUserButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                document.getElementById('delete-user-id').value = id;
            });
        });
    });
    </script>
</body>
</html>
