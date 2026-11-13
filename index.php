<?php
// main/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anjungan Mandiri IGD - RSUD Jaya Utama</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans min-h-screen flex flex-col justify-between selection:bg-sky-500 selection:text-white">

    <!-- Header / Navbar Khusus Kiosk Pasien -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="container mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3.5">
                <div class="bg-gradient-to-tr from-sky-600 to-blue-700 text-white p-3 rounded-2xl shadow-md shadow-sky-500/20 text-xl flex items-center justify-center">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold tracking-wider uppercase text-slate-900">RSUD / RSI JAYA UTAMA</h1>
                    <p class="text-xs text-slate-500 font-semibold tracking-wide">Anjungan Kiosk Mandiri - Pendaftaran & Penapisan Pasien IGD</p>
                </div>
            </div>

            <!-- Widget Jam & Status Live -->
            <div class="flex items-center gap-3 text-xs font-semibold">
                <div class="flex items-center gap-2 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-200 shadow-inner">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <span class="text-emerald-700 font-bold">Kiosk Ready</span>
                </div>
                <div id="live-clock" class="bg-slate-50 px-4 py-2 rounded-xl border border-slate-200 text-slate-700 shadow-inner font-mono">
                    --/--/---- --:--:--
                </div>
            </div>
        </div>
    </header>

    <!-- Ticker Pengumuman untuk Pasien -->
    <div class="bg-sky-600 text-white py-2.5 px-6 text-xs font-semibold shadow-inner flex items-center gap-3">
        <span class="bg-sky-800 text-white px-2.5 py-1 rounded-lg text-[10px] uppercase tracking-widest shadow-sm">Informasi</span>
        <span class="truncate">Silakan gunakan tombol di bawah untuk memulai pendaftaran mandiri dan pengisian data penapisan awal kegawatdaruratan Anda.</span>
    </div>

    <!-- Konten Utama: Fokus Penuh ke Kiosk Pasien -->
    <main class="container mx-auto px-6 py-16 max-w-3xl flex-grow flex items-center justify-center">
        <div class="w-full">
            <div class="text-center mb-10 space-y-3">
                <span class="inline-flex items-center gap-1.5 bg-sky-50 text-sky-700 border border-sky-200 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-widest shadow-sm">
                    <i class="fa-solid fa-fingerprint text-[10px]"></i> Self-Service Check-In
                </span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">Selamat Datang di IGD</h2>
                <p class="text-slate-600 text-sm max-w-md mx-auto">Sentuh tombol di bawah untuk memulai proses pendaftaran dan pengisian keluhan medis secara mandiri.</p>
            </div>

            <!-- Kartu Tunggal Fokus Kiosk -->
            <div class="bg-white rounded-3xl p-8 md:p-10 border border-slate-200/80 shadow-xl shadow-slate-200/50 hover:shadow-sky-500/10 transition-all duration-300 group">
                <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                    <div class="w-20 h-20 bg-sky-50 text-sky-600 rounded-3xl flex items-center justify-center text-3xl shadow-inner group-hover:bg-sky-600 group-hover:text-white transition-all duration-300 flex-shrink-0">
                        <i class="fa-solid fa-laptop-medical"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-2xl font-bold text-slate-900 mb-2 group-hover:text-sky-600 transition">Mulai Pendaftaran Kiosk</h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6">Input data diri, nomor identitas, penjamin, serta asesmen keluhan awal untuk mendapatkan nomor antrean penanganan darurat.</p>
                        
                        <a href="../screening/kiosk.php" class="inline-flex items-center justify-center gap-3 w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-4 px-8 rounded-2xl shadow-lg shadow-sky-600/20 transition-all duration-200 text-base group/btn">
                            <span>Mulai Screening / Pendaftaran</span>
                            <i class="fa-solid fa-arrow-right-long group-hover/btn:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Profesional dengan Akses Tersembunyi untuk Petugas -->
    <footer class="bg-white border-t border-slate-200 text-slate-500 py-6 text-xs">
        <div class="container mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4 text-center md:text-left">
            <div>
                <p class="font-bold text-slate-700">SIMRS IGD &copy; 2026 - RSUD / RSI Jaya Utama</p>
                <p class="text-slate-400">Standar Pelayanan Kegawatdaruratan Medis Berbasis Permenkes No. 47 Tahun 2018</p>
            </div>
            
            <div class="flex items-center gap-4">
                <span class="text-slate-300">|</span>
                <!-- Tombol Akses Petugas yang Disamarkan/Tersembunyi rapi -->
                <a href="login.php" class="text-slate-400 hover:text-sky-600 transition font-medium flex items-center gap-1.5" title="Login Khusus Petugas IGD">
                    <i class="fa-solid fa-user-shield text-[11px]"></i> Login Petugas
                </a>
            </div>
        </div>
    </footer>

    <!-- Script Jam Digital Real-Time -->
    <script>
        let updateClock = () => {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
            const dateStr = now.toLocaleDateString('id-ID', options);
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('live-clock').innerHTML = `<i class="fa-regular fa-clock text-sky-600 mr-1.5"></i> ${dateStr} | ${timeStr}`;
        };
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>