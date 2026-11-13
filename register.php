<?php
// main/register.php
$db_path = __DIR__ . '/../igd_database.db';
$message = "";

// Buat tabel users jika belum ada
try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'petugas'
    )");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Enkripsi password aman

        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        $message = "Akun petugas berhasil dibuat! Silakan login.";
    }
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Petugas - RSUD Jaya Utama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white p-8 rounded-3xl shadow-xl border border-slate-200 w-full max-w-md">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Pendaftaran Akun Petugas</h1>
        <p class="text-xs text-slate-500 mb-6">Daftarkan akun baru untuk akses dashboard IGD.</p>

        <?php if (!empty($message)): ?>
            <div class="mb-4 bg-sky-50 border border-sky-200 text-sky-700 px-4 py-3 rounded-xl text-xs font-semibold">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-sky-500 focus:outline-none text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-sky-500 focus:outline-none text-sm">
            </div>
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg text-sm">
                Daftarkan Akun
            </button>
        </form>
        <div class="text-center mt-6">
            <a href="login.php" class="text-xs text-sky-600 hover:underline font-semibold">&larr; Sudah punya akun? Login di sini</a>
        </div>
    </div>
</body>
</html>