<?php
require_once 'db.php';
require_once 'auth.php';

check_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'] ?? 'user';
$is_admin = ($user_role === 'admin');

function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

$current_year = date('Y');
$current_month = date('m');

try {
    if ($is_admin) {
        
        $stmt_income = $pdo->query("SELECT SUM(amount) AS total FROM transactions WHERE type = 'pemasukan'");
        $total_income = $stmt_income->fetch()['total'] ?? 0;
        
        $stmt_expense = $pdo->query("SELECT SUM(amount) AS total FROM transactions WHERE type = 'pengeluaran'");
        $total_expense = $stmt_expense->fetch()['total'] ?? 0;
        
        $net_balance = $total_income - $total_expense;
        
        $stmt_users_count = $pdo->query("SELECT COUNT(*) AS total FROM users");
        $total_users = $stmt_users_count->fetch()['total'] ?? 0;
        
        $stmt_tx_count = $pdo->query("SELECT COUNT(*) AS total FROM transactions");
        $total_transactions_count = $stmt_tx_count->fetch()['total'] ?? 0;
        
        $stmt_recent = $pdo->query("
            SELECT t.*, u.name AS user_display_name 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id 
            ORDER BY t.date DESC, t.id DESC 
            LIMIT 5
        ");
        $recent_transactions = $stmt_recent->fetchAll();
        
        $stmt_monthly = $pdo->prepare("
            SELECT MONTH(date) AS bulan, type, SUM(amount) AS total 
            FROM transactions 
            WHERE YEAR(date) = :year 
            GROUP BY MONTH(date), type
        ");
        $stmt_monthly->execute(['year' => $current_year]);
        $monthly_data = $stmt_monthly->fetchAll();
        
        $stmt_category = $pdo->prepare("
            SELECT category, SUM(amount) AS total 
            FROM transactions 
            WHERE type = 'pengeluaran' AND MONTH(date) = :month AND YEAR(date) = :year
            GROUP BY category
        ");
        $stmt_category->execute(['month' => $current_month, 'year' => $current_year]);
        $category_expenses = $stmt_category->fetchAll();
        
    } else {
        
        $stmt_income = $pdo->prepare("SELECT SUM(amount) AS total FROM transactions WHERE user_id = :user_id AND type = 'pemasukan'");
        $stmt_income->execute(['user_id' => $user_id]);
        $total_income = $stmt_income->fetch()['total'] ?? 0;
        
        $stmt_expense = $pdo->prepare("SELECT SUM(amount) AS total FROM transactions WHERE user_id = :user_id AND type = 'pengeluaran'");
        $stmt_expense->execute(['user_id' => $user_id]);
        $total_expense = $stmt_expense->fetch()['total'] ?? 0;
        
        $net_balance = $total_income - $total_expense;
        
        $stmt_recent = $pdo->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY date DESC, id DESC LIMIT 5");
        $stmt_recent->execute(['user_id' => $user_id]);
        $recent_transactions = $stmt_recent->fetchAll();
        
        $stmt_monthly = $pdo->prepare("
            SELECT MONTH(date) AS bulan, type, SUM(amount) AS total 
            FROM transactions 
            WHERE user_id = :user_id AND YEAR(date) = :year 
            GROUP BY MONTH(date), type
        ");
        $stmt_monthly->execute(['user_id' => $user_id, 'year' => $current_year]);
        $monthly_data = $stmt_monthly->fetchAll();
        
        $stmt_category = $pdo->prepare("
            SELECT category, SUM(amount) AS total 
            FROM transactions 
            WHERE user_id = :user_id AND type = 'pengeluaran' AND MONTH(date) = :month AND YEAR(date) = :year
            GROUP BY category
        ");
        $stmt_category->execute(['user_id' => $user_id, 'month' => $current_month, 'year' => $current_year]);
        $category_expenses = $stmt_category->fetchAll();
    }
    
    $chart_months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agt", "Sep", "Okt", "Nov", "Des"];
    $income_by_month = array_fill(0, 12, 0);
    $expense_by_month = array_fill(0, 12, 0);

    foreach ($monthly_data as $row) {
        $m_index = (int)$row['bulan'] - 1; // 0-indexed
        if ($row['type'] === 'pemasukan') {
            $income_by_month[$m_index] = (float)$row['total'];
        } else {
            $expense_by_month[$m_index] = (float)$row['total'];
        }
    }

    $cat_labels = [];
    $cat_totals = [];
    foreach ($category_expenses as $row) {
        $cat_labels[] = htmlspecialchars($row['category']);
        $cat_totals[] = (float)$row['total'];
    }

} catch (PDOException $e) {
    die("Terjadi kesalahan query: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pencatatan Keuangan</title>
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
            <a href="index.php" class="sidebar-link active">
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
        <a href="index.php" class="bottom-nav-item active">
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
        <a href="reports.php" class="bottom-nav-item">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Laporan</span>
        </a>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content animated-fade">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Halo, <?= htmlspecialchars($user_name) ?>!</h4>
                <p class="text-secondary mb-0" style="font-size: 0.9rem;">
                    <?= $is_admin ? 'Berikut adalah ringkasan performa keuangan dan statistik sistem FinTrack.' : 'Berikut adalah ringkasan performa keuangan Anda.' ?>
                </p>
            </div>
            <div class="d-none d-sm-block">
                <span class="badge bg-light text-dark border p-2" style="border-radius: 10px; font-size: 0.85rem;">
                    <i class="fa-regular fa-calendar me-2"></i><?= date('d M Y') ?>
                </span>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="stat-card-wrapper">
            <?php if ($is_admin): ?>
                <!-- Admin: Total Users Card -->
                <div class="glass-card stat-card">
                    <div class="stat-info">
                        <span class="stat-label">Total Pengguna</span>
                        <h3 class="stat-val text-primary mb-0"><?= $total_users ?></h3>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <!-- Admin: Total Transactions Card -->
                <div class="glass-card stat-card">
                    <div class="stat-info">
                        <span class="stat-label">Total Transaksi</span>
                        <h3 class="stat-val text-dark mb-0"><?= $total_transactions_count ?></h3>
                    </div>
                    <div class="stat-icon bg-light text-dark">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            <?php else: ?>
                <!-- User: Balance Card -->
                <div class="glass-card stat-card">
                    <div class="stat-info">
                        <span class="stat-label">Total Saldo</span>
                        <h3 class="stat-val mb-0 <?= $net_balance >= 0 ? 'text-primary' : 'text-danger' ?>">
                            <?= format_rupiah($net_balance) ?>
                        </h3>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Income Card -->
            <div class="glass-card stat-card">
                <div class="stat-info">
                    <span class="stat-label"><?= $is_admin ? 'Pemasukan Sistem' : 'Total Pemasukan' ?></span>
                    <h3 class="stat-val text-success mb-0"><?= format_rupiah($total_income) ?></h3>
                </div>
                <div class="stat-icon success">
                    <i class="fa-solid fa-arrow-down-long"></i>
                </div>
            </div>

            <!-- Expense Card -->
            <div class="glass-card stat-card">
                <div class="stat-info">
                    <span class="stat-label"><?= $is_admin ? 'Pengeluaran Sistem' : 'Total Pengeluaran' ?></span>
                    <h3 class="stat-val text-danger mb-0"><?= format_rupiah($total_expense) ?></h3>
                </div>
                <div class="stat-icon danger">
                    <i class="fa-solid fa-arrow-up-long"></i>
                </div>
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="row mb-4">
            <!-- Monthly Chart -->
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0" style="font-size: 1rem;">Perbandingan Bulanan (<?= $current_year ?>)</h5>
                        <span class="text-muted" style="font-size: 0.8rem;">Pemasukan vs Pengeluaran</span>
                    </div>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Category Chart -->
            <div class="col-lg-4">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0" style="font-size: 1rem;">Pengeluaran Kategori</h5>
                        <span class="text-muted" style="font-size: 0.8rem;">Bulan Ini</span>
                    </div>
                    <div style="position: relative; height: 300px; width: 100%;" class="d-flex align-items-center justify-content-center">
                        <?php if (empty($cat_totals)): ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-chart-pie text-muted opacity-20 mb-3" style="font-size: 3rem;"></i>
                                <p class="text-muted mb-0" style="font-size: 0.85rem;">Tidak ada transaksi pengeluaran di bulan ini.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="categoryChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT TRANSACTIONS -->
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="font-size: 1rem;">
                    <?= $is_admin ? 'Transaksi Terbaru (Sistem)' : 'Transaksi Terbaru' ?>
                </h5>
                <a href="transactions.php" class="text-decoration-none fw-semibold" style="font-size: 0.85rem; color: var(--primary-color);">
                    Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th>Tanggal</th>
                            <?php if ($is_admin): ?>
                                <th>Pengguna</th>
                            <?php endif; ?>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th>Tipe</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="<?= $is_admin ? 6 : 5 ?>" class="text-center py-4 text-muted" style="font-size: 0.9rem;">
                                    <i class="fa-solid fa-receipt me-2"></i>Belum ada data transaksi.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $tx): ?>
                                <tr style="font-size: 0.9rem;">
                                    <td class="text-secondary"><?= date('d/m/Y', strtotime($tx['date'])) ?></td>
                                    <?php if ($is_admin): ?>
                                        <td class="fw-semibold text-secondary">
                                            <?= htmlspecialchars($tx['user_display_name'] ?? '-') ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($tx['category']) ?></td>
                                    <td class="text-secondary text-truncate" style="max-width: 200px;"><?= htmlspecialchars($tx['description'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($tx['type'] === 'pemasukan'): ?>
                                            <span class="badge-income"><i class="fa-solid fa-arrow-trend-down me-1"></i>Pemasukan</span>
                                        <?php else: ?>
                                            <span class="badge-expense"><i class="fa-solid fa-arrow-trend-up me-1"></i>Pengeluaran</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $tx['type'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                        <?= ($tx['type'] === 'pemasukan' ? '+' : '-') . ' ' . format_rupiah($tx['amount']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Data derived from PHP PDO queries
        const chartMonths = <?= json_encode($chart_months) ?>;
        const incomeByMonth = <?= json_encode($income_by_month) ?>;
        const expenseByMonth = <?= json_encode($expense_by_month) ?>;

        const ctxTrend = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: chartMonths,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: incomeByMonth,
                        backgroundColor: '#10b981', // success color
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseByMonth,
                        backgroundColor: '#f43f5e', // danger color
                        borderRadius: 6,
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Plus Jakarta Sans', size: 12, weight: '500' },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        padding: 12,
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { drawBorder: false, color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Plus Jakarta Sans', size: 11 },
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact', compactDisplay: 'short' }).format(value);
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });

        <?php if (!empty($cat_totals)): ?>
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($cat_labels) ?>,
                datasets: [{
                    data: <?= json_encode($cat_totals) ?>,
                    backgroundColor: [
                        '#4f46e5', // indigo
                        '#f59e0b', // amber
                        '#10b981', // emerald
                        '#06b6d4', // cyan
                        '#ec4899', // pink
                        '#8b5cf6', // purple
                        '#64748b'  // slate
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '500' },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15
                        }
                    },
                    tooltip: {
                        padding: 12,
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
