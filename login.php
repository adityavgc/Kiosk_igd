<?php
// main/login.php
session_start();
$db_path = __DIR__ . '/../igd_database.db';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi password yang terenkripsi
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_status'] = 'petugas';
            $_SESSION['username'] = $user['username'];

            header("Location: ../self/dashboard/index.php");
            exit();
        } else {
            $error = "Username atau Password salah!";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b6ea8">
    <title>Login Petugas IGD - RSUD Jaya Utama</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * { box-sizing: border-box; }

        :root {
            --primary: #0b6ea8;
            --primary-dark: #075985;
            --accent: #22c55e;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 15% 10%, rgba(14,165,233,.18), transparent 28%),
                radial-gradient(circle at 85% 90%, rgba(59,130,246,.13), transparent 30%),
                linear-gradient(135deg, #f5fbff 0%, #eef7fc 45%, #f8fafc 100%);
        }

        .background-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .42;
            background-image:
                linear-gradient(rgba(14,116,144,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14,116,144,.045) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, black, transparent 85%);
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(1px);
            animation: drift 10s ease-in-out infinite;
        }

        .orb.one {
            width: 260px;
            height: 260px;
            left: -90px;
            top: -80px;
            background: rgba(14,165,233,.12);
        }

        .orb.two {
            width: 330px;
            height: 330px;
            right: -120px;
            bottom: -130px;
            background: rgba(37,99,235,.10);
            animation-delay: -4s;
        }

        @keyframes drift {
            0%,100% { transform: translate3d(0,0,0) scale(1); }
            50% { transform: translate3d(0,-18px,0) scale(1.03); }
        }

        .shell {
            animation: enter .75s cubic-bezier(.22,1,.36,1);
        }

        @keyframes enter {
            from { opacity: 0; transform: translateY(28px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .glass {
            background: rgba(255,255,255,.78);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
        }

        .brand-panel {
            background:
                radial-gradient(circle at 75% 15%, rgba(125,211,252,.22), transparent 25%),
                radial-gradient(circle at 15% 85%, rgba(255,255,255,.09), transparent 25%),
                linear-gradient(145deg, #075985 0%, #0b6ea8 46%, #075985 100%);
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .14;
            background-image: radial-gradient(rgba(255,255,255,.7) 1px, transparent 1px);
            background-size: 24px 24px;
            mask-image: linear-gradient(135deg, black, transparent 75%);
        }

        .medical-cross {
            position: absolute;
            right: 9%;
            top: 19%;
            width: 115px;
            height: 115px;
            opacity: .08;
            transform: rotate(12deg);
        }

        .medical-cross::before,
        .medical-cross::after {
            content: "";
            position: absolute;
            background: white;
            border-radius: 12px;
        }

        .medical-cross::before {
            width: 36px;
            height: 115px;
            left: 39px;
            top: 0;
        }

        .medical-cross::after {
            width: 115px;
            height: 36px;
            left: 0;
            top: 39px;
        }

        .feature {
            transition: transform .25s ease, background .25s ease;
        }

        .feature:hover {
            transform: translateX(5px);
            background: rgba(255,255,255,.10);
        }

        .input-wrap {
            transition: transform .2s ease;
        }

        .input-wrap:focus-within {
            transform: translateY(-1px);
        }

        .input-box {
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .input-box:focus-within {
            border-color: #0ea5e9;
            background: white;
            box-shadow: 0 0 0 4px rgba(14,165,233,.10);
        }

        .input-box input {
            outline: none;
        }

        .login-btn {
            position: relative;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(3,105,161,.22);
            filter: brightness(1.03);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: -70%;
            width: 45%;
            transform: skewX(-22deg);
            background: rgba(255,255,255,.28);
            transition: left .65s ease;
        }

        .login-btn:hover::after {
            left: 130%;
        }

        .status-dot {
            box-shadow: 0 0 0 0 rgba(34,197,94,.4);
            animation: statusPulse 1.8s infinite;
        }

        @keyframes statusPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(34,197,94,.35); }
            50% { box-shadow: 0 0 0 7px rgba(34,197,94,0); }
        }

        .loader {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.38);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .error-box {
            animation: shake .4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .clock {
            font-variant-numeric: tabular-nums;
            letter-spacing: .03em;
        }

        @media (max-width: 1023px) {
            .login-card {
                max-width: 520px;
                margin: auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center px-4 py-7 sm:px-6">
    <div class="background-grid"></div>
    <div class="orb one"></div>
    <div class="orb two"></div>

    <main class="relative z-10 w-full max-w-6xl shell">
        <div class="login-card grid lg:grid-cols-[.95fr_1.05fr] overflow-hidden rounded-[2rem] border border-white/80 glass shadow-[0_30px_90px_rgba(15,23,42,.13)]">

            <!-- PANEL INFORMASI -->
            <section class="brand-panel relative hidden lg:flex min-h-[680px] p-10 xl:p-12 text-white overflow-hidden">
                <div class="medical-cross"></div>

                <div class="relative z-10 flex flex-col justify-between w-full">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-13 h-13 w-[52px] h-[52px] rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center shadow-lg">
                                <i class="fa-solid fa-hospital text-xl"></i>
                            </div>
                            <div>
                                <div class="font-extrabold tracking-wide text-[15px]">RSUD JAYA UTAMA</div>
                                <div class="text-[11px] text-sky-100 mt-0.5">Sistem Informasi IGD</div>
                            </div>
                        </div>

                        <div class="mt-12">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-300 status-dot"></span>
                                SISTEM ONLINE
                            </div>

                            <h2 class="mt-6 text-[42px] xl:text-[46px] font-extrabold leading-[1.12] tracking-tight">
                                Pelayanan IGD<br>
                                <span class="text-sky-100">lebih terintegrasi.</span>
                            </h2>

                            <p class="mt-5 max-w-md text-sm leading-7 text-sky-100/95">
                                Akses sistem informasi pelayanan Instalasi Gawat Darurat
                                secara cepat, aman, dan terpusat.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="feature flex items-center gap-3 rounded-2xl p-3">
                            <span class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <div>
                                <div class="text-sm font-bold">Akses Aman</div>
                                <div class="text-[11px] text-sky-100">Khusus petugas berwenang</div>
                            </div>
                        </div>

                        <div class="feature flex items-center gap-3 rounded-2xl p-3">
                            <span class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                                <i class="fa-solid fa-chart-line"></i>
                            </span>
                            <div>
                                <div class="text-sm font-bold">Monitoring Terpusat</div>
                                <div class="text-[11px] text-sky-100">Informasi pelayanan dalam satu dashboard</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-[10px] text-sky-200 pt-3">
                            <i class="fa-regular fa-clock"></i>
                            <span id="liveDate">Memuat waktu...</span>
                            <span class="mx-1">•</span>
                            <span class="clock" id="liveClock">--:--:--</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PANEL LOGIN -->
            <section class="p-7 sm:p-10 lg:p-12 xl:p-14 flex items-center">
                <div class="w-full max-w-md mx-auto">

                    <div class="text-center lg:text-left mb-8">
                        <div class="lg:hidden mx-auto mb-5 w-16 h-16 rounded-[20px] bg-gradient-to-br from-sky-500 to-blue-700 text-white flex items-center justify-center shadow-xl shadow-sky-200/70">
                            <i class="fa-solid fa-hospital-user text-2xl"></i>
                        </div>

                        <div class="inline-flex items-center gap-2 text-sky-700 text-[11px] font-extrabold uppercase tracking-[.14em] mb-3">
                            <span class="w-7 h-7 rounded-lg bg-sky-50 flex items-center justify-center">
                                <i class="fa-solid fa-lock text-[10px]"></i>
                            </span>
                            Area Petugas
                        </div>

                        <h1 class="text-[30px] sm:text-[34px] font-extrabold tracking-tight text-slate-900">
                            Selamat Datang
                        </h1>

                        <p class="text-sm text-slate-500 mt-2 leading-6">
                            Silakan masuk untuk mengakses <b class="text-slate-700">Dashboard IGD</b>.
                        </p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="error-box mb-5 flex items-start gap-3 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3.5 rounded-2xl">
                            <div class="w-9 h-9 shrink-0 rounded-xl bg-rose-100 flex items-center justify-center">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </div>
                            <div class="pt-0.5">
                                <p class="font-extrabold text-sm">Login tidak berhasil</p>
                                <p class="text-xs mt-1 leading-5"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="loginForm" class="space-y-5">
                        <div class="input-wrap">
                            <label for="username" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wide mb-2">
                                Username
                            </label>
                            <div class="input-box relative flex items-center rounded-2xl border border-slate-200 bg-slate-50/80">
                                <span class="w-12 shrink-0 flex items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-user"></i>
                                </span>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    autocomplete="username"
                                    required
                                    autofocus
                                    placeholder="Masukkan username"
                                    class="w-full py-3.5 pr-4 bg-transparent text-sm text-slate-800 placeholder:text-slate-400"
                                >
                            </div>
                        </div>

                        <div class="input-wrap">
                            <label for="password" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wide mb-2">
                                Password
                            </label>
                            <div class="input-box relative flex items-center rounded-2xl border border-slate-200 bg-slate-50/80">
                                <span class="w-12 shrink-0 flex items-center justify-center text-slate-400">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="Masukkan password"
                                    class="w-full py-3.5 bg-transparent text-sm text-slate-800 placeholder:text-slate-400"
                                >
                                <button
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Tampilkan password"
                                    class="w-12 h-12 shrink-0 text-slate-400 hover:text-sky-600 transition"
                                >
                                    <i id="eyeIcon" class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            id="loginButton"
                            class="login-btn w-full h-[54px] rounded-2xl bg-gradient-to-r from-sky-600 via-sky-600 to-blue-700 text-white font-extrabold text-sm shadow-lg shadow-sky-200/80 flex items-center justify-center gap-2"
                        >
                            <span id="buttonText">Masuk ke Dashboard</span>
                            <i id="buttonIcon" class="fa-solid fa-arrow-right text-xs"></i>
                            <span id="buttonLoader" class="loader hidden"></span>
                        </button>
                    </form>

                    <div class="relative flex items-center my-7">
                        <div class="h-px bg-slate-200 flex-1"></div>
                        <span class="px-3 text-[9px] font-extrabold tracking-[.18em] text-slate-400">AKSES LAIN</span>
                        <div class="h-px bg-slate-200 flex-1"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <a href="register.php" class="group h-11 rounded-xl border border-slate-200 bg-white flex items-center justify-center gap-2 text-xs font-bold text-slate-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 transition">
                            <i class="fa-solid fa-user-plus group-hover:scale-110 transition"></i>
                            Buat Akun
                        </a>

                        <a href="index.php" class="group h-11 rounded-xl border border-slate-200 bg-white flex items-center justify-center gap-2 text-xs font-bold text-slate-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 transition">
                            <i class="fa-solid fa-house group-hover:scale-110 transition"></i>
                            Beranda
                        </a>
                    </div>

                    <div class="mt-7 flex items-center justify-center gap-2 text-[10px] text-slate-400">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Area ini diperuntukkan bagi petugas yang berwenang.</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="text-center mt-5 text-[10px] text-slate-400">
            &copy; <?= date('Y'); ?> RSUD Jaya Utama <span class="mx-1">•</span> Sistem Informasi IGD
        </div>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        const buttonIcon = document.getElementById('buttonIcon');
        const buttonLoader = document.getElementById('buttonLoader');
        const liveClock = document.getElementById('liveClock');
        const liveDate = document.getElementById('liveDate');

        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';

            eyeIcon.className = isPassword
                ? 'fa-regular fa-eye-slash'
                : 'fa-regular fa-eye';

            this.setAttribute(
                'aria-label',
                isPassword ? 'Sembunyikan password' : 'Tampilkan password'
            );

            passwordInput.focus();
        });

        loginForm.addEventListener('submit', function () {
            loginButton.disabled = true;
            loginButton.classList.add('opacity-90', 'cursor-not-allowed');
            buttonText.textContent = 'Memverifikasi...';
            buttonIcon.classList.add('hidden');
            buttonLoader.classList.remove('hidden');
        });

        function updateClock() {
            const now = new Date();

            liveClock.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            liveDate.textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
