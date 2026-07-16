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
            // User does not exist, revert to self
            $target_user_id = $user_id;
        }
    } catch (PDOException $e) {
        $target_user_id = $user_id;
    }
}

function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

$indonesian_months = [
    1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April",
    5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus",
    9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"
];

$view = $_GET['view'] ?? 'daily';
if ($view !== 'daily' && $view !== 'monthly') {
    $view = 'daily';
}

$selected_year = (int)($_GET['year'] ?? date('Y'));
$selected_month = (int)($_GET['month'] ?? date('n'));

$start_dropdown_year = date('Y') - 5;
$end_dropdown_year = date('Y') + 1;

try {
    if ($view === 'daily') {
        $stmt = $pdo->prepare("
            SELECT 
                date, 
                SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END) AS total_income, 
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) AS total_expense 
            FROM transactions 
            WHERE user_id = :user_id AND MONTH(date) = :month AND YEAR(date) = :year 
            GROUP BY date 
            ORDER BY date ASC
        ");
        $stmt->execute([
            'user_id' => $target_user_id,
            'month' => $selected_month,
            'year' => $selected_year
        ]);
        $report_data = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(date) AS bulan, 
                SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END) AS total_income, 
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) AS total_expense 
            FROM transactions 
            WHERE user_id = :user_id AND YEAR(date) = :year 
            GROUP BY MONTH(date) 
            ORDER BY MONTH(date) ASC
        ");
        $stmt->execute([
            'user_id' => $target_user_id,
            'year' => $selected_year
        ]);
        $report_data = $stmt->fetchAll();
    }

    $grand_total_income = 0;
    $grand_total_expense = 0;
    foreach ($report_data as $row) {
        $grand_total_income += $row['total_income'];
        $grand_total_expense += $row['total_expense'];
    }
    $grand_net_balance = $grand_total_income - $grand_total_expense;

} catch (PDOException $e) {
    die("Terjadi kesalahan pembacaan laporan: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - FinTrack</title>
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
            <a href="transactions.php" class="sidebar-link">
                <i class="fa-solid fa-money-bill-transfer"></i>
                <span>Transaksi</span>
            </a>
            <a href="reports.php" class="sidebar-link active">
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
        <a href="transactions.php" class="bottom-nav-item">
            <i class="fa-solid fa-money-bill-transfer"></i>
            <span>Transaksi</span>
        </a>
        <a href="reports.php" class="bottom-nav-item active">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content animated-fade">
        <?php if ($target_user_id !== $user_id): ?>
            <!-- Admin Delegation Banner -->
            <div class="alert alert-info border-0 mb-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 shadow-sm no-print" style="border-radius: 12px; background-color: var(--primary-light); color: var(--primary-dark);">
                <div>
                    <i class="fa-solid fa-circle-info me-2"></i>
                    Menampilkan laporan keuangan untuk: <strong><?= htmlspecialchars($target_user_name) ?></strong>
                </div>
                <a href="reports.php" class="btn btn-sm btn-primary px-3 py-1.5 fw-semibold border-0 text-white" style="border-radius: 8px; background-color: var(--primary-color); font-size: 0.8rem;">
                    Kembali ke Laporan Saya
                </a>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Laporan Keuangan</h4>
                <p class="text-secondary mb-0" style="font-size: 0.9rem;">
                    <?= $target_user_id !== $user_id ? 'Analisis ringkasan pemasukan dan pengeluaran periodik untuk ' . htmlspecialchars($target_user_name) . '.' : 'Analisis ringkasan pemasukan dan pengeluaran periodik.' ?>
                </p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-dark px-4 py-2.5 fw-semibold d-flex align-items-center gap-2 w-100 w-sm-auto justify-content-center no-print" style="border-radius: 12px;">
                <i class="fa-solid fa-print"></i> Cetak / Ekspor PDF
            </button>
        </div>

        <!-- View Navigation Tabs -->
        <div class="card border-0 mb-4 bg-transparent no-print">
            <div class="card-body p-0">
                <div class="btn-group w-100" role="group">
                    <a href="reports.php?view=daily&month=<?= $selected_month ?>&year=<?= $selected_year ?><?= $target_user_id !== $user_id ? '&view_user_id=' . $target_user_id : '' ?>" class="btn py-2.5 fw-semibold <?= $view === 'daily' ? 'btn-primary' : 'btn-light border text-dark' ?>" style="border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                        <i class="fa-solid fa-calendar-day me-2"></i>Laporan Harian (Bulanan)
                    </a>
                    <a href="reports.php?view=monthly&year=<?= $selected_year ?><?= $target_user_id !== $user_id ? '&view_user_id=' . $target_user_id : '' ?>" class="btn py-2.5 fw-semibold <?= $view === 'monthly' ? 'btn-primary' : 'btn-light border text-dark' ?>" style="border-top-right-radius: 12px; border-bottom-right-radius: 12px;">
                        <i class="fa-solid fa-calendar-days me-2"></i>Laporan Bulanan (Tahunan)
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="glass-card p-4 mb-4 no-print">
            <form action="reports.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                <?php if ($target_user_id !== $user_id): ?>
                    <input type="hidden" name="view_user_id" value="<?= $target_user_id ?>">
                <?php endif; ?>
                
                <?php if ($view === 'daily'): ?>
                    <div class="col-sm-6 col-md-5">
                        <label for="month" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Bulan</label>
                        <select class="form-select bg-light" id="month" name="month" style="border-radius: 10px; font-size: 0.9rem;">
                            <?php foreach ($indonesian_months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $selected_month === $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-sm-6 <?= $view === 'daily' ? 'col-md-5' : 'col-md-10' ?>">
                    <label for="year" class="form-label fw-semibold" style="font-size: 0.85rem; color: var(--text-secondary);">Tahun</label>
                    <select class="form-select bg-light" id="year" name="year" style="border-radius: 10px; font-size: 0.9rem;">
                        <?php for ($y = $start_dropdown_year; $y <= $end_dropdown_year; $y++): ?>
                            <option value="<?= $y ?>" <?= $selected_year === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold" style="border-radius: 10px; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Sheet -->
        <div class="glass-card p-4">
            <!-- Printable Report Header -->
            <div class="text-center mb-4 py-2 border-bottom">
                <h3 class="fw-bold mb-1">LAPORAN KEUANGAN FINTRACK</h3>
                <?php if ($target_user_id !== $user_id): ?>
                    <h5 class="text-dark fw-bold mb-1" style="font-size: 1.1rem; letter-spacing: 0.5px;">
                        PENGGUNA: <?= strtoupper(htmlspecialchars($target_user_name)) ?>
                    </h5>
                <?php endif; ?>
                <h5 class="text-secondary fw-semibold mb-2" style="font-size: 1.05rem;">
                    <?php if ($view === 'daily'): ?>
                        Periode: <?= $indonesian_months[$selected_month] ?> <?= $selected_year ?>
                    <?php else: ?>
                        Periode Tahunan: <?= $selected_year ?>
                    <?php endif; ?>
                </h5>
                <small class="text-muted d-block pb-2">Dicetak oleh: <?= htmlspecialchars($user_name) ?> | Waktu: <?= date('d/m/Y H:i') ?></small>
            </div>

            <!-- Report Table -->
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-center" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th>#</th>
                            <th><?= $view === 'daily' ? 'Tanggal' : 'Bulan' ?></th>
                            <th>Total Pemasukan</th>
                            <th>Total Pengeluaran</th>
                            <th>Selisih Bersih (Net)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted" style="font-size: 0.9rem;">
                                    <i class="fa-solid fa-file-excel d-block mb-2" style="font-size: 2rem;"></i>
                                    Tidak ada data transaksi ditemukan pada periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($report_data as $row): 
                                $net_row = $row['total_income'] - $row['total_expense'];
                            ?>
                                <tr style="font-size: 0.9rem;">
                                    <td class="text-center text-muted"><?= $no++ ?></td>
                                    <td class="fw-semibold">
                                        <?php if ($view === 'daily'): ?>
                                            <?= date('d/m/Y', strtotime($row['date'])) ?>
                                        <?php else: ?>
                                            <?= $indonesian_months[(int)$row['bulan']] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-success fw-semibold"><?= format_rupiah($row['total_income']) ?></td>
                                    <td class="text-end text-danger fw-semibold"><?= format_rupiah($row['total_expense']) ?></td>
                                    <td class="text-end fw-bold <?= $net_row >= 0 ? 'text-primary' : 'text-danger' ?>">
                                        <?= format_rupiah($net_row) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr class="fw-bold" style="font-size: 0.95rem;">
                            <td colspan="2" class="text-center">GRAND TOTAL</td>
                            <td class="text-end text-success"><?= format_rupiah($grand_total_income) ?></td>
                            <td class="text-end text-danger"><?= format_rupiah($grand_total_expense) ?></td>
                            <td class="text-end <?= $grand_net_balance >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= format_rupiah($grand_net_balance) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
