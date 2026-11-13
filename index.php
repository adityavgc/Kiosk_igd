<?php
// index.php (Tampilan Pembuka / Landing Page)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Sistem Pelayanan & Screening IGD</title>
    <!-- Memuat Tailwind CSS agar tampilan langsung rapi dan modern -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 font-sans min-h-screen flex flex-col justify-between">

    <!-- Header Sederhana -->
    <header class="bg-blue-700 text-white p-6 shadow-md text-center">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-wide">SISTEM INFORMASI PELAYANAN & SCREENING IGD</h1>
        <p class="text-blue-200 text-sm mt-1">Berdasarkan Permenkes No. 47 Tahun 2018</p>
    </header>

    <!-- Konten Utama Tengah -->
    <main class="container mx-auto px-4 py-16 max-w-xl flex-grow flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center space-y-6 w-full border border-slate-200">
            <div class="inline-block bg-blue-100 text-blue-800 p-4 rounded-full text-5xl mb-2">
                🏥
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Selamat Datang</h2>
            <p class="text-slate-600 text-sm leading-relaxed">
                Silakan pilih salah satu menu di bawah ini untuk melanjutkan ke modul sistem pelayanan atau anjungan mandiri (kiosk).
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <a href="main/index.php" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-bold py-3.5 px-4 rounded-xl text-center text-sm transition shadow">
                    Menu Utama
                </a>
                <a href="screening/kiosk.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl text-center text-sm transition shadow">
                    Buka Mode Kiosk / Screening
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-4 text-slate-500 text-xs bg-white border-t">
        SIMRS IGD Engine &copy; 2026 - All Rights Reserved
    </footer>

</body>
</html>