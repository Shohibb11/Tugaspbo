<?php
require_once 'db.php';
require_once 'auth.php';

check_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'] ?? 'user';
$is_admin = ($user_role === 'admin');

$target_user_id = $user_id;
if ($is_admin && isset($_GET['view_user_id'])) {
    $target_user_id = (int)$_GET['view_user_id'];
}

$target_user_name = $user_name;
if ($target_user_id !== $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute(['id' => $target_user_id]);
        $target_user = $stmt->fetch();
        if ($target_user) {
            $target_user_name = $target_user['name'];
        } else {
            $target_user_id = $user_id;
        }
    } catch (PDOException $e) {
        $target_user_id = $user_id;
    }
}

function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// ----------------------------------------------------
// PROCESS ACTIONS (CREATE, UPDATE, DELETE)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $type = $_POST['type'] ?? '';
        $category = trim($_POST['category'] ?? '');
        $amount_raw = $_POST['amount'] ?? '0';
        $date = $_POST['date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        $amount = (float)str_replace(['.', ','], ['', '.'], $amount_raw);

        if (empty($type) || empty($category) || $amount <= 0 || empty($date)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Form penambahan transaksi tidak valid!'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, description, date) VALUES (:user_id, :type, :category, :amount, :description, :date)");
                $stmt->execute([
                    'user_id' => $target_user_id,
                    'type' => $type,
                    'category' => $category,
                    'amount' => $amount,
                    'description' => $description,
                    'date' => $date
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Transaksi berhasil ditambahkan!'];
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menyimpan transaksi: ' . $e->getMessage()];
            }
        }
        $redirect_url = ($target_user_id !== $user_id) ? "transactions.php?view_user_id=" . $target_user_id : "transactions.php";
        header("Location: " . $redirect_url);
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $category = trim($_POST['category'] ?? '');
        $amount_raw = $_POST['amount'] ?? '0';
        $date = $_POST['date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        $amount = (float)str_replace(['.', ','], ['', '.'], $amount_raw);

        try {
            $checkStmt = $pdo->prepare("SELECT id FROM transactions WHERE id = :id AND user_id = :user_id");
            $checkStmt->execute(['id' => $id, 'user_id' => $target_user_id]);
            
            if (!$checkStmt->fetch()) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Transaksi tidak ditemukan atau bukan milik pengguna terpilih!'];
            } elseif (empty($type) || empty($category) || $amount <= 0 || empty($date)) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Form penyuntingan transaksi tidak valid!'];
            } else {
                $stmt = $pdo->prepare("UPDATE transactions SET type = :type, category = :category, amount = :amount, description = :description, date = :date WHERE id = :id AND user_id = :user_id");
                $stmt->execute([
                    'type' => $type,
                    'category' => $category,
                    'amount' => $amount,
                    'description' => $description,
                    'date' => $date,
                    'id' => $id,
                    'user_id' => $target_user_id
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Transaksi berhasil diperbarui!'];
            }
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal memperbarui transaksi: ' . $e->getMessage()];
        }
        $redirect_url = ($target_user_id !== $user_id) ? "transactions.php?view_user_id=" . $target_user_id : "transactions.php";
        header("Location: " . $redirect_url);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        try {
            // Verify ownership/target authorization
            $checkStmt = $pdo->prepare("SELECT id FROM transactions WHERE id = :id AND user_id = :user_id");
            $checkStmt->execute(['id' => $id, 'user_id' => $target_user_id]);

            if (!$checkStmt->fetch()) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Transaksi tidak ditemukan atau bukan milik pengguna terpilih!'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $id, 'user_id' => $target_user_id]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Transaksi berhasil dihapus!'];
            }
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menghapus transaksi: ' . $e->getMessage()];
        }
        $redirect_url = ($target_user_id !== $user_id) ? "transactions.php?view_user_id=" . $target_user_id : "transactions.php";
        header("Location: " . $redirect_url);
        exit;
    }
}

// ----------------------------------------------------
// READ / FILTER TRANSACTIONS
// ----------------------------------------------------
$filter_type = $_GET['filter_type'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

$query = "SELECT * FROM transactions WHERE user_id = :user_id";
$params = ['user_id' => $target_user_id];

if ($filter_type !== '') {
    $query .= " AND type = :filter_type";
    $params['filter_type'] = $filter_type;
}

if ($filter_category !== '') {
    $query .= " AND category = :filter_category";
    $params['filter_category'] = $filter_category;
}

if ($filter_start_date !== '') {
    $query .= " AND date >= :filter_start_date";
    $params['filter_start_date'] = $filter_start_date;
}

if ($filter_end_date !== '') {
    $query .= " AND date <= :filter_end_date";
    $params['filter_end_date'] = $filter_end_date;
}

$query .= " ORDER BY date DESC, id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Calculate sum statistics based on active filters
    $filter_total_income = 0;
    $filter_total_expense = 0;
    foreach ($transactions as $tx) {
        if ($tx['type'] === 'pemasukan') {
            $filter_total_income += $tx['amount'];
        } else {
            $filter_total_expense += $tx['amount'];
        }
    }
    $filter_net = $filter_total_income - $filter_total_expense;

} catch (PDOException $e) {
    die("Terjadi kesalahan pembacaan data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Pencatatan Keuangan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
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
            <a href="users.php" class="sidebar-link">
                <i class="fa-solid fa-users"></i>
                <span>Kelola Pengguna</span>
            </a>
            <?php endif; ?>
            <a href="transactions.php" class="sidebar-link active">
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
        <a href="users.php" class="bottom-nav-item">
            <i class="fa-solid fa-users"></i>
            <span>Pengguna</span>
        </a>
        <?php endif; ?>
        <a href="transactions.php" class="bottom-nav-item active">
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
        <?php if ($target_user_id !== $user_id): ?>
            <!-- Admin Delegation Banner -->
            <div class="alert alert-info border-0 mb-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 shadow-sm" style="border-radius: 12px; background-color: var(--primary-light); color: var(--primary-dark);">
                <div>
                    <i class="fa-solid fa-circle-info me-2"></i>
                    Menampilkan dan mengelola transaksi untuk: <strong><?= htmlspecialchars($target_user_name) ?></strong>
                </div>
                <a href="transactions.php" class="btn btn-sm btn-primary px-3 py-1.5 fw-semibold border-0 text-white" style="border-radius: 8px; background-color: var(--primary-color); font-size: 0.8rem;">
                    Kembali ke Transaksi Saya
                </a>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Transaksi</h4>
                <p class="text-secondary mb-0" style="font-size: 0.9rem;">
                    <?= $target_user_id !== $user_id ? 'Mengelola pemasukan dan pengeluaran keuangan ' . htmlspecialchars($target_user_name) . '.' : 'Kelola pemasukan dan pengeluaran keuangan Anda.' ?>
                </p>
            </div>
            <button class="btn btn-primary px-4 py-2.5 fw-semibold d-flex align-items-center gap-2 w-100 w-sm-auto justify-content-center" style="border-radius: 12px; background-color: var(--primary-color); border: none;" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fa-solid fa-plus"></i> Tambah Transaksi
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

        <!-- Statistics bar based on current filters -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="glass-card p-3 d-flex align-items-center justify-content-between bg-light border-0">
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;">Pemasukan (Filter)</small>
                        <span class="fw-bold text-success" style="font-size: 1.2rem;"><?= format_rupiah($filter_total_income) ?></span>
                    </div>
                    <i class="fa-solid fa-circle-arrow-down text-success opacity-25" style="font-size: 1.8rem;"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-3 d-flex align-items-center justify-content-between bg-light border-0">
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;">Pengeluaran (Filter)</small>
                        <span class="fw-bold text-danger" style="font-size: 1.2rem;"><?= format_rupiah($filter_total_expense) ?></span>
                    </div>
                    <i class="fa-solid fa-circle-arrow-up text-danger opacity-25" style="font-size: 1.8rem;"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-3 d-flex align-items-center justify-content-between bg-light border-0">
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;">Selisih Bersih</small>
                        <span class="fw-bold <?= $filter_net >= 0 ? 'text-primary' : 'text-danger' ?>" style="font-size: 1.2rem;"><?= format_rupiah($filter_net) ?></span>
                    </div>
                    <i class="fa-solid fa-scale-balanced text-primary opacity-25" style="font-size: 1.8rem;"></i>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="glass-card p-4 mb-4">
            <form action="transactions.php" method="GET" class="row g-3 align-items-end">
                <?php if ($target_user_id !== $user_id): ?>
                    <input type="hidden" name="view_user_id" value="<?= $target_user_id ?>">
                <?php endif; ?>
                <div class="col-sm-6 col-md-3">
                    <label for="filter_type" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Tipe Transaksi</label>
                    <select class="form-select bg-light" id="filter_type" name="filter_type" style="border-radius: 10px; font-size: 0.9rem;">
                        <option value="">Semua Tipe</option>
                        <option value="pemasukan" <?= $filter_type === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="pengeluaran" <?= $filter_type === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                </div>
                
                <div class="col-sm-6 col-md-3">
                    <label for="filter_category" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Kategori</label>
                    <select class="form-select bg-light" id="filter_category" name="filter_category" style="border-radius: 10px; font-size: 0.9rem;">
                        <option value="">Semua Kategori</option>
                        <optgroup label="Pemasukan">
                            <option value="Gaji" <?= $filter_category === 'Gaji' ? 'selected' : '' ?>>Gaji</option>
                            <option value="Investasi" <?= $filter_category === 'Investasi' ? 'selected' : '' ?>>Investasi</option>
                            <option value="Usaha" <?= $filter_category === 'Usaha' ? 'selected' : '' ?>>Usaha</option>
                            <option value="Sampingan" <?= $filter_category === 'Sampingan' ? 'selected' : '' ?>>Sampingan</option>
                            <option value="Lain-lain (Pemasukan)" <?= $filter_category === 'Lain-lain (Pemasukan)' ? 'selected' : '' ?>>Lain-lain (Pemasukan)</option>
                        </optgroup>
                        <optgroup label="Pengeluaran">
                            <option value="Makanan & Minuman" <?= $filter_category === 'Makanan & Minuman' ? 'selected' : '' ?>>Makanan & Minuman</option>
                            <option value="Transportasi" <?= $filter_category === 'Transportasi' ? 'selected' : '' ?>>Transportasi</option>
                            <option value="Listrik & Air" <?= $filter_category === 'Listrik & Air' ? 'selected' : '' ?>>Listrik & Air</option>
                            <option value="Belanja" <?= $filter_category === 'Belanja' ? 'selected' : '' ?>>Belanja</option>
                            <option value="Hiburan" <?= $filter_category === 'Hiburan' ? 'selected' : '' ?>>Hiburan</option>
                            <option value="Kesehatan" <?= $filter_category === 'Kesehatan' ? 'selected' : '' ?>>Kesehatan</option>
                            <option value="Pendidikan" <?= $filter_category === 'Pendidikan' ? 'selected' : '' ?>>Pendidikan</option>
                            <option value="Lain-lain (Pengeluaran)" <?= $filter_category === 'Lain-lain (Pengeluaran)' ? 'selected' : '' ?>>Lain-lain (Pengeluaran)</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2">
                    <label for="filter_start_date" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Mulai Tanggal</label>
                    <input type="date" class="form-control bg-light" id="filter_start_date" name="filter_start_date" value="<?= htmlspecialchars($filter_start_date) ?>" style="border-radius: 10px; font-size: 0.9rem;">
                </div>

                <div class="col-sm-6 col-md-2">
                    <label for="filter_end_date" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Sampai Tanggal</label>
                    <input type="date" class="form-control bg-light" id="filter_end_date" name="filter_end_date" value="<?= htmlspecialchars($filter_end_date) ?>" style="border-radius: 10px; font-size: 0.9rem;">
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold" style="border-radius: 10px; font-size: 0.9rem;">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="transactions.php<?= $target_user_id !== $user_id ? '?view_user_id=' . $target_user_id : '' ?>" class="btn btn-outline-secondary w-100 py-2 fw-semibold" style="border-radius: 10px; font-size: 0.9rem;">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="glass-card p-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th>Tipe</th>
                            <th>Nominal</th>
                            <th class="text-center no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted" style="font-size: 0.9rem;">
                                    <i class="fa-solid fa-receipt d-block mb-2" style="font-size: 2rem;"></i>
                                    Tidak ada data transaksi yang cocok dengan filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr style="font-size: 0.9rem;">
                                    <td class="text-secondary"><?= date('d/m/Y', strtotime($tx['date'])) ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($tx['category']) ?></td>
                                    <td class="text-secondary text-truncate" style="max-width: 250px;"><?= htmlspecialchars($tx['description'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($tx['type'] === 'pemasukan'): ?>
                                            <span class="badge-income"><i class="fa-solid fa-arrow-trend-down me-1"></i>Pemasukan</span>
                                        <?php else: ?>
                                            <span class="badge-expense"><i class="fa-solid fa-arrow-trend-up me-1"></i>Pengeluaran</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold <?= $tx['type'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                        <?= ($tx['type'] === 'pemasukan' ? '+' : '-') . ' ' . format_rupiah($tx['amount']) ?>
                                    </td>
                                    <td class="text-center no-print">
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- Edit Button with dynamic data attributes -->
                                            <button class="btn btn-sm btn-outline-primary btn-edit-transaction border-0" 
                                                    style="border-radius: 8px;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editTransactionModal"
                                                    data-id="<?= $tx['id'] ?>"
                                                    data-type="<?= $tx['type'] ?>"
                                                    data-category="<?= htmlspecialchars($tx['category']) ?>"
                                                    data-amount="<?= (int)$tx['amount'] ?>"
                                                    data-date="<?= $tx['date'] ?>"
                                                    data-description="<?= htmlspecialchars($tx['description']) ?>">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            
                                            <!-- Delete Button -->
                                            <button class="btn btn-sm btn-outline-danger btn-delete-transaction border-0" 
                                                    style="border-radius: 8px;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteTransactionModal"
                                                    data-id="<?= $tx['id'] ?>">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
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

    <!-- ----------------------------------------------------
    // MODALS SECTION
    // ---------------------------------------------------- -->

    <!-- A. ADD TRANSACTION MODAL -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addTransactionModalLabel">Tambah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="transactions.php<?= $target_user_id !== $user_id ? '?view_user_id=' . $target_user_id : '' ?>" method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        
                        <div class="mb-3">
                            <label for="add-type" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Tipe Transaksi</label>
                            <select class="form-select bg-light" id="add-type" name="type" style="border-radius: 10px; font-size: 0.9rem;" required>
                                <option value="pemasukan">Pemasukan</option>
                                <option value="pengeluaran">Pengeluaran</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="add-category" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Kategori</label>
                            <select class="form-select bg-light" id="add-category" name="category" style="border-radius: 10px; font-size: 0.9rem;" required>
                                <optgroup label="Pemasukan">
                                    <option value="Gaji">Gaji</option>
                                    <option value="Investasi">Investasi</option>
                                    <option value="Usaha">Usaha</option>
                                    <option value="Sampingan">Sampingan</option>
                                    <option value="Lain-lain (Pemasukan)">Lain-lain (Pemasukan)</option>
                                </optgroup>
                                <optgroup label="Pengeluaran">
                                    <option value="Makanan & Minuman">Makanan & Minuman</option>
                                    <option value="Transportasi">Transportasi</option>
                                    <option value="Listrik & Air">Listrik & Air</option>
                                    <option value="Belanja">Belanja</option>
                                    <option value="Hiburan">Hiburan</option>
                                    <option value="Kesehatan">Kesehatan</option>
                                    <option value="Pendidikan">Pendidikan</option>
                                    <option value="Lain-lain (Pengeluaran)" selected>Lain-lain (Pengeluaran)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="add-amount" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Nominal (Rupiah)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 fw-bold" style="border-radius: 10px 0 0 10px; font-size: 0.9rem; color: var(--text-secondary);">Rp</span>
                                <input type="text" class="form-control bg-light border-start-0 currency-input fw-bold text-dark" id="add-amount" name="amount" placeholder="0" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="add-date" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Tanggal</label>
                            <input type="date" class="form-control bg-light" id="add-date" name="date" value="<?= date('Y-m-d') ?>" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-2">
                            <label for="add-description" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Keterangan</label>
                            <textarea class="form-control bg-light" id="add-description" name="description" rows="2" placeholder="Catatan singkat..." style="border-radius: 10px; font-size: 0.9rem;"></textarea>
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

    <!-- B. EDIT TRANSACTION MODAL -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editTransactionModalLabel">Ubah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="transactions.php<?= $target_user_id !== $user_id ? '?view_user_id=' . $target_user_id : '' ?>" method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit-id" name="id">
                    <div class="modal-body">
                        
                        <div class="mb-3">
                            <label for="edit-type" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Tipe Transaksi</label>
                            <select class="form-select bg-light" id="edit-type" name="type" style="border-radius: 10px; font-size: 0.9rem;" required>
                                <option value="pemasukan">Pemasukan</option>
                                <option value="pengeluaran">Pengeluaran</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-category" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Kategori</label>
                            <select class="form-select bg-light" id="edit-category" name="category" style="border-radius: 10px; font-size: 0.9rem;" required>
                                <optgroup label="Pemasukan">
                                    <option value="Gaji">Gaji</option>
                                    <option value="Investasi">Investasi</option>
                                    <option value="Usaha">Usaha</option>
                                    <option value="Sampingan">Sampingan</option>
                                    <option value="Lain-lain (Pemasukan)">Lain-lain (Pemasukan)</option>
                                </optgroup>
                                <optgroup label="Pengeluaran">
                                    <option value="Makanan & Minuman">Makanan & Minuman</option>
                                    <option value="Transportasi">Transportasi</option>
                                    <option value="Listrik & Air">Listrik & Air</option>
                                    <option value="Belanja">Belanja</option>
                                    <option value="Hiburan">Hiburan</option>
                                    <option value="Kesehatan">Kesehatan</option>
                                    <option value="Pendidikan">Pendidikan</option>
                                    <option value="Lain-lain (Pengeluaran)">Lain-lain (Pengeluaran)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-amount" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Nominal (Rupiah)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 fw-bold" style="border-radius: 10px 0 0 10px; font-size: 0.9rem; color: var(--text-secondary);">Rp</span>
                                <input type="text" class="form-control bg-light border-start-0 currency-input fw-bold text-dark" id="edit-amount" name="amount" placeholder="0" style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit-date" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Tanggal</label>
                            <input type="date" class="form-control bg-light" id="edit-date" name="date" style="border-radius: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="mb-2">
                            <label for="edit-description" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Keterangan</label>
                            <textarea class="form-control bg-light" id="edit-description" name="description" rows="2" placeholder="Catatan singkat..." style="border-radius: 10px; font-size: 0.9rem;"></textarea>
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

    <!-- C. DELETE TRANSACTION CONFIRMATION MODAL -->
    <div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger" id="deleteTransactionModalLabel">Hapus Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="transactions.php<?= $target_user_id !== $user_id ? '?view_user_id=' . $target_user_id : '' ?>" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete-id" name="id">
                    <div class="modal-body py-3">
                        <p class="mb-0 text-secondary" style="font-size: 0.9rem;">Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.</p>
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
    <!-- Custom Application Javascript -->
    <script src="assets/js/app.js"></script>
</body>
</html>
