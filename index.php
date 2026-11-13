<?php
// dashboard/index.php
session_start();

// Proteksi keamanan
if (!isset($_SESSION['user_status']) || $_SESSION['user_status'] !== 'petugas') {
    header("Location: ../main/login.php?access=denied");
    exit();
}

// Filter tanggal (default: hari ini)
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$selected_date = $_GET['tanggal'] ?? $today;
$date_obj = DateTime::createFromFormat('!Y-m-d', $selected_date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $selected_date) {
    $selected_date = $today;
}

// Database SQLite
$db_path = __DIR__ . '/../igd_database.db';
$patients = [];
$error_message = null;

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT no_registrasi, nik, nama, keluhan, penjamin,
               status_penanganan, status_pembayaran, waktu_datang, red_flag
        FROM patients
        WHERE date(waktu_datang) = :tanggal
        ORDER BY id DESC
    ");
    $stmt->execute([':tanggal' => $selected_date]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Koneksi database gagal: " . $e->getMessage();
}

$total_patients = count($patients);
$red_count = 0;
$handled_count = 0;
$unpaid_count = 0;

foreach ($patients as $p) {
    if ((int)$p['red_flag'] === 1) $red_count++;
    $status = strtolower((string)($p['status_penanganan'] ?? ''));
    if (str_contains($status, 'selesai') || str_contains($status, 'ditangani')) $handled_count++;
    $payment = strtolower((string)($p['status_pembayaran'] ?? ''));
    if ($payment && (str_contains($payment, 'belum') || str_contains($payment, 'pending'))) $unpaid_count++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas IGD - RSUD Jaya Utama</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(circle at 10% 0%, rgba(14,165,233,.12), transparent 28%),
                radial-gradient(circle at 90% 15%, rgba(99,102,241,.10), transparent 25%),
                #f4f7fb;
        }
        .glass {
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .soft-shadow { box-shadow: 0 14px 40px rgba(15,23,42,.08); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 20px 45px rgba(15,23,42,.12); }
        .row-hover { transition: background .2s ease; }
        .row-hover:hover { background: #f8fbff; }
        .pulse-ring { animation: pulseRing 2s infinite; }
        @keyframes pulseRing {
            0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.28); }
            50% { box-shadow: 0 0 0 10px rgba(16,185,129,0); }
        }
        .fade-in { animation: fadeIn .5s ease both; }
        @keyframes fadeIn {
            from { opacity:0; transform:translateY(8px); }
            to { opacity:1; transform:translateY(0); }
        }
        .modal-bg { background: rgba(15,23,42,.5); backdrop-filter: blur(6px); }
    </style>
</head>

<body class="min-h-screen text-slate-800 text-base">
<div class="min-h-screen flex flex-col">

    <!-- TOP BAR -->
    <header class="sticky top-0 z-50 glass border-b border-white/70 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="relative w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-700 text-white flex items-center justify-center shadow-lg shadow-sky-500/25 text-xl">
                        <i class="fa-solid fa-heart-pulse"></i>
                        <span class="absolute -right-1 -top-1 w-3.5 h-3.5 rounded-full bg-emerald-400 border-2 border-white"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">Dashboard Petugas IGD</h1>
                            <span class="hidden sm:inline-flex px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-100 text-[11px] font-extrabold uppercase">Live</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">Monitoring antrean, triage & screening pasien</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-ring"></span>
                        <span class="text-xs font-bold text-slate-600">Sistem Online</span>
                    </div>
                    <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs sm:text-sm text-slate-700 font-semibold">
                        <i class="fa-solid fa-user-shield text-sky-600 mr-1.5"></i>
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?>
                    </div>
                    <a href="../main/index.php" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold transition shadow-sm">
                        <i class="fa-solid fa-house mr-1.5"></i> Menu
                    </a>
                    <a href="logout.php" class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs sm:text-sm font-bold transition">
                        <i class="fa-solid fa-right-from-bracket mr-1.5"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-8 flex-1">

        <!-- HERO SECTION -->
        <section class="mb-8 fade-in">
            <div class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-7 sm:p-10 text-white soft-shadow">
                <div class="absolute -right-20 -top-24 w-80 h-80 rounded-full bg-sky-400/15 blur-3xl"></div>
                <div class="absolute right-20 -bottom-36 w-96 h-96 rounded-full bg-indigo-400/10 blur-3xl"></div>
                <div class="relative flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/10 text-xs font-bold uppercase tracking-wider text-sky-200 mb-3">
                            <i class="fa-solid fa-shield-heart"></i> IGD Command Center
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Selamat datang, <?= htmlspecialchars($_SESSION['username'] ?? 'Petugas'); ?> 👋</h2>
                        <p class="mt-2.5 text-sm sm:text-base text-slate-300 max-w-2xl">Pantau antrean dan screening pasien berdasarkan tanggal kunjungan yang dipilih.</p>
                    </div>
                    <div class="text-left md:text-right">
                        <div id="clock" class="text-2xl sm:text-3xl font-extrabold tracking-tight">--:--:--</div>
                        <div id="date" class="text-xs sm:text-sm text-slate-400 mt-1">Memuat waktu...</div>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($error_message): ?>
            <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl text-sm font-semibold fade-in">
                <i class="fa-solid fa-circle-exclamation mr-2 text-base"></i><?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- STATS CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <div class="bg-white rounded-[24px] p-6 border border-slate-200 soft-shadow card-hover fade-in">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Kunjungan</p>
                        <p class="text-4xl font-black text-slate-900 mt-2.5"><?= $total_patients; ?></p>
                        <p class="text-xs text-slate-500 mt-1.5">Pasien terdaftar pada tanggal terpilih</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[24px] p-6 border border-slate-200 soft-shadow card-hover fade-in">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Red Flag</p>
                        <p class="text-4xl font-black text-rose-600 mt-2.5"><?= $red_count; ?></p>
                        <p class="text-xs text-slate-500 mt-1.5">Perlu perhatian khusus</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[24px] p-6 border border-slate-200 soft-shadow card-hover fade-in">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Ditangani</p>
                        <p class="text-4xl font-black text-emerald-600 mt-2.5"><?= $handled_count; ?></p>
                        <p class="text-xs text-slate-500 mt-1.5">Status selesai</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[24px] p-6 border border-slate-200 soft-shadow card-hover fade-in">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Pembayaran</p>
                        <p class="text-4xl font-black text-amber-500 mt-2.5"><?= $unpaid_count; ?></p>
                        <p class="text-xs text-slate-500 mt-1.5">Belum / pending</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- PATIENT TABLE SECTION -->
        <section class="bg-white rounded-[30px] border border-slate-200 soft-shadow overflow-hidden fade-in">
            <div class="p-6 sm:p-7 border-b border-slate-100">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-extrabold text-slate-900">Antrean & Screening Pasien</h2>
                            <span id="resultCount" class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-extrabold"><?= $total_patients; ?> DATA</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Data kunjungan tanggal <?= htmlspecialchars(date('d-m-Y', strtotime($selected_date)), ENT_QUOTES); ?> ditampilkan di bagian atas.</p>
                    </div>

                    <!-- Filter Section yang Sudah Dirapikan & Responsif -->
                    <form method="GET" class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50">
                            <label for="tanggalFilter" class="text-xs font-bold text-slate-600 whitespace-nowrap"><i class="fa-solid fa-calendar-days mr-1"></i> Tanggal</label>
                            <input id="tanggalFilter" name="tanggal" type="date" value="<?= htmlspecialchars($selected_date, ENT_QUOTES); ?>"
                                   class="min-w-0 px-2 py-1 bg-transparent focus:outline-none text-sm font-bold text-slate-700">
                            <button type="submit" class="px-3 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold transition">Tampilkan</button>
                        </div>
                        <a href="?tanggal=<?= htmlspecialchars($today, ENT_QUOTES); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-sm font-bold text-center transition flex items-center justify-center">
                            <i class="fa-solid fa-calendar-day mr-1.5"></i> Hari Ini
                        </a>
                        <div class="relative flex-1 sm:flex-none">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input id="searchInput" type="text" placeholder="Cari nama, registrasi, keluhan..."
                                   class="w-full sm:w-64 pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-100 focus:border-sky-400 text-sm font-medium transition">
                        </div>
                        <select id="statusFilter" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-100 text-sm font-bold text-slate-700 transition">
                            <option value="">Semua Status</option>
                            <option value="red">🚨 Red Flag</option>
                            <option value="normal">🟢 Normal</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="table-wrap overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1020px]" id="patientTable">
                    <thead>
                        <tr class="bg-slate-50/90 text-slate-500 uppercase text-xs font-extrabold tracking-wider border-b border-slate-100">
                            <th class="px-6 py-4.5">Registrasi</th>
                            <th class="px-6 py-4.5">Pasien</th>
                            <th class="px-6 py-4.5">Keluhan</th>
                            <th class="px-6 py-4.5">Penjamin</th>
                            <th class="px-6 py-4.5">Status</th>
                            <th class="px-6 py-4.5">Waktu Datang</th>
                            <th class="px-6 py-4.5 text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                    <?php if (empty($patients)): ?>
                        <tr id="emptyRow">
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-4">
                                    <i class="fa-solid fa-inbox"></i>
                                </div>
                                <p class="text-base font-bold text-slate-600">Belum ada data pasien</p>
                                <p class="text-sm text-slate-400 mt-1">Data dari kiosk akan muncul di sini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $index => $p): ?>
                            <?php $isRed = ((int)$p['red_flag'] === 1); ?>
                            <tr class="patient-row row-hover <?= $isRed ? 'bg-rose-50/30' : ''; ?>"
                                data-red="<?= $isRed ? 'red' : 'normal'; ?>"
                                data-search="<?= htmlspecialchars(strtolower(($p['no_registrasi'] ?? '') . ' ' . ($p['nama'] ?? '') . ' ' . ($p['keluhan'] ?? '') . ' ' . ($p['penjamin'] ?? ''))); ?>">
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-slate-800 text-base"><?= htmlspecialchars($p['no_registrasi']); ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-11 h-11 rounded-xl <?= $isRed ? 'bg-rose-100 text-rose-600' : 'bg-sky-100 text-sky-600'; ?> flex items-center justify-center font-extrabold text-base">
                                            <?= htmlspecialchars(strtoupper(substr(trim($p['nama'] ?? 'P'), 0, 1))); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-base"><?= htmlspecialchars($p['nama']); ?></p>
                                            <p class="text-xs text-slate-500 font-normal"><?= htmlspecialchars($p['nik'] ?? '-'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-slate-700 max-w-[280px]">
                                    <span class="line-clamp-2"><?= htmlspecialchars($p['keluhan']); ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex px-3 py-1.5 rounded-lg bg-sky-50 text-sky-700 border border-sky-100 font-bold text-xs">
                                        <?= htmlspecialchars($p['penjamin']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <?php if ($isRed): ?>
                                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-rose-100 text-rose-700 border border-rose-200 font-extrabold text-xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            <?= htmlspecialchars($p['status_penanganan']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 font-bold text-xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <?= htmlspecialchars($p['status_penanganan']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-5 text-slate-600 font-mono text-xs">
                                    <?= htmlspecialchars($p['waktu_datang']); ?>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <button type="button"
                                            class="detail-btn w-10 h-10 rounded-xl bg-slate-100 hover:bg-sky-100 text-slate-600 hover:text-sky-700 transition text-base"
                                            data-name="<?= htmlspecialchars($p['nama'], ENT_QUOTES); ?>"
                                            data-reg="<?= htmlspecialchars($p['no_registrasi'], ENT_QUOTES); ?>"
                                            data-nik="<?= htmlspecialchars($p['nik'] ?? '-', ENT_QUOTES); ?>"
                                            data-keluhan="<?= htmlspecialchars($p['keluhan'], ENT_QUOTES); ?>"
                                            data-penjamin="<?= htmlspecialchars($p['penjamin'], ENT_QUOTES); ?>"
                                            data-status="<?= htmlspecialchars($p['status_penanganan'], ENT_QUOTES); ?>"
                                            data-payment="<?= htmlspecialchars($p['status_pembayaran'] ?? '-', ENT_QUOTES); ?>"
                                            data-waktu="<?= htmlspecialchars($p['waktu_datang'], ENT_QUOTES); ?>"
                                            data-red="<?= $isRed ? 'YA' : 'Tidak'; ?>">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="noMatchRow" class="hidden">
                            <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3"></i>
                                <p class="text-base font-bold text-slate-600">Data tidak ditemukan</p>
                                <p class="text-sm mt-1">Coba ubah kata kunci atau filter.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4.5 border-t border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row justify-between gap-2 text-xs text-slate-500 font-medium">
                <span><i class="fa-solid fa-circle-info mr-1.5"></i> Klik ikon mata untuk melihat detail pasien.</span>
                <span>Terakhir dimuat: <strong id="lastLoaded" class="text-slate-700">baru saja</strong></span>
            </div>
        </section>
    </main>

    <footer class="py-6 text-center text-xs text-slate-400">
        <span class="font-bold text-slate-600">SIMRS Dashboard IGD</span> · RSUD / RSI Jaya Utama · &copy; 2026
    </footer>
</div>

<!-- DETAIL MODAL -->
<div id="detailModal" class="hidden fixed inset-0 z-[100] modal-bg items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-[30px] shadow-2xl overflow-hidden fade-in">
        <div class="p-6 sm:p-7 bg-gradient-to-br from-slate-900 to-blue-950 text-white">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span id="modalBadge" class="inline-flex px-3.5 py-1 rounded-full bg-white/10 border border-white/10 text-xs font-bold uppercase">Detail Pasien</span>
                    <h3 id="modalName" class="text-2xl font-extrabold mt-2.5">-</h3>
                    <p id="modalReg" class="text-sm text-slate-300 mt-1">-</p>
                </div>
                <button onclick="closeModal()" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 transition text-base flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="p-6 sm:p-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">NIK</p>
                <p id="modalNik" class="text-sm font-bold text-slate-800 mt-1.5">-</p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">Penjamin</p>
                <p id="modalPenjamin" class="text-sm font-bold text-slate-800 mt-1.5">-</p>
            </div>
            <div class="sm:col-span-2 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">Keluhan Utama</p>
                <p id="modalKeluhan" class="text-sm font-semibold text-slate-800 mt-1.5">-</p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">Status Penanganan</p>
                <p id="modalStatus" class="text-sm font-bold text-slate-800 mt-1.5">-</p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">Pembayaran</p>
                <p id="modalPayment" class="text-sm font-bold text-slate-800 mt-1.5">-</p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">Waktu Datang</p>
                <p id="modalWaktu" class="text-sm font-bold text-slate-800 mt-1.5">-</p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-extrabold uppercase text-slate-400">Red Flag</p>
                <p id="modalRed" class="text-sm font-extrabold mt-1.5">-</p>
            </div>
        </div>
        <div class="px-6 sm:px-7 pb-6 sm:pb-7">
            <button onclick="closeModal()" class="w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition shadow-md">Tutup</button>
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const resultCount = document.getElementById('resultCount');
    const noMatchRow = document.getElementById('noMatchRow');

    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent =
            now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('date').textContent =
            now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }
    updateClock();
    setInterval(updateClock, 1000);

    function filterPatients() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const filter = statusFilter?.value || '';
        const rows = [...document.querySelectorAll('.patient-row')];
        let visible = 0;

        rows.forEach(row => {
            const matchesSearch = !q || row.dataset.search.includes(q);
            const matchesStatus = !filter || row.dataset.red === filter;
            const show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (resultCount) resultCount.textContent = visible + ' DATA';
        if (noMatchRow) noMatchRow.classList.toggle('hidden', visible !== 0 || rows.length === 0);
    }

    searchInput?.addEventListener('input', filterPatients);
    statusFilter?.addEventListener('change', filterPatients);

    const modal = document.getElementById('detailModal');
    function openModal(data) {
        document.getElementById('modalName').textContent = data.name || '-';
        document.getElementById('modalReg').textContent = 'No. Registrasi: ' + (data.reg || '-');
        document.getElementById('modalNik').textContent = data.nik || '-';
        document.getElementById('modalPenjamin').textContent = data.penjamin || '-';
        document.getElementById('modalKeluhan').textContent = data.keluhan || '-';
        document.getElementById('modalStatus').textContent = data.status || '-';
        document.getElementById('modalPayment').textContent = data.payment || '-';
        document.getElementById('modalWaktu').textContent = data.waktu || '-';
        const red = document.getElementById('modalRed');
        red.textContent = data.red || 'Tidak';
        red.className = 'text-sm font-extrabold mt-1.5 ' + (data.red === 'YA' ? 'text-rose-600' : 'text-emerald-600');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.detail-btn').forEach(btn => {
        btn.addEventListener('click', () => openModal({
            name: btn.dataset.name,
            reg: btn.dataset.reg,
            nik: btn.dataset.nik,
            keluhan: btn.dataset.keluhan,
            penjamin: btn.dataset.penjamin,
            status: btn.dataset.status,
            payment: btn.dataset.payment,
            waktu: btn.dataset.waktu,
            red: btn.dataset.red
        }));
    });

    modal?.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });

    document.getElementById('lastLoaded').textContent =
        new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
</script>
</body>
</html>